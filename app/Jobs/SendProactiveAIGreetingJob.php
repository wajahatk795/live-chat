<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Brand;
use App\Models\ChatSetting;
use App\Services\Ai\AiServiceFactory;
use App\Services\AgentPresenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendProactiveAIGreetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $chat;

    public function __construct(Chat $chat)
    {
        $this->chat = $chat;
    }

    public function handle()
    {
        // Reload fresh data
        $this->chat = Chat::find($this->chat->id);
        if (!$this->chat || $this->chat->status !== 'open') {
            Log::info("SendProactiveAIGreetingJob: Chat #{$this->chat?->id} not found or closed. Skipping.");
            return;
        }

        // Skip if already greeted
        if ($this->chat->ai_greeted) {
            Log::info("SendProactiveAIGreetingJob: Chat #{$this->chat->id} already greeted. Skipping.");
            return;
        }

        // Skip if any messages already exist in the chat (visitor or agent typed something)
        $hasMessages = Message::where('chat_id', $this->chat->id)
            ->where(function ($q) {
                $q->whereNotNull('sender')->orWhere('is_ai', true);
            })
            ->exists();

        if ($hasMessages) {
            Log::info("SendProactiveAIGreetingJob: Chat #{$this->chat->id} already has messages. Skipping proactive greeting.");
            return;
        }

        $brand = $this->chat->get_brand ?: ($this->chat->brand ?: Brand::find($this->chat->brand_id));
        $settings = $brand ? ($brand->chatSetting ?: ChatSetting::where('brand_id', $brand->id)->first()) : null;

        if (!$settings || !$settings->ai_enabled) {
            Log::info("SendProactiveAIGreetingJob: AI not enabled for Chat #{$this->chat->id}. Skipping.");
            return;
        }

        if ($brand && app(AgentPresenceService::class)->hasOnlineAgent($brand)) {
            Log::info("SendProactiveAIGreetingJob: An agent is online for Chat #{$this->chat->id}. Skipping.");
            return;
        }

        // Verify that the configured delay has actually passed since chat creation
        $delaySeconds = $settings->ai_handoff_delay ?? 60;
        $createdAt = \Carbon\Carbon::parse($this->chat->created_at);
        if ($createdAt->copy()->addSeconds($delaySeconds)->isFuture()) {
            Log::info("SendProactiveAIGreetingJob: Full delay ({$delaySeconds}s) has not elapsed yet for Chat #{$this->chat->id}. Skipping premature greeting.");
            return;
        }

        try {
            // Mark chat as greeted and handled by AI
            $this->chat->update([
                'ai_greeted' => true,
                'is_handled_by_ai' => true,
                'ai_processing' => true,
            ]);

            $providerName = $settings->ai_provider ?: 'ollama';
            $model = $settings->ai_model ?: 'llama3';

            $brandName = $brand->name ?: ($brand->domain ?: 'our company');
            $brandUrl = $brand->url ?: ($brand->domain ?: '');
            $customPrompt = $settings->ai_prompt ?: 'You are a professional, helpful, and concise live chat support assistant.';

            $systemPrompt = "{$customPrompt}\n\nYou represent the brand: \"{$brandName}\" ({$brandUrl}).\n\n"
                . "TASK: Generate a short, friendly proactive greeting message to a website visitor who just arrived. "
                . "Keep it under 20 words. Be welcoming and ask how you can help. "
                . "Do NOT use generic greetings like 'Hello!' alone — mention the brand or offer help.";

            if (!empty($settings->website_knowledge)) {
                $systemPrompt .= "\n\n=== OFFICIAL WEBSITE KNOWLEDGE ===\n"
                    . $settings->website_knowledge . "\n"
                    . "=================================";
            }

            $aiService = AiServiceFactory::make($providerName);

            // Broadcast typing event
            emit_pusher_notification(
                'chat.' . $this->chat->id,
                'typing',
                ['role' => 4]
            );

            $reply = $aiService->generateReply($this->chat, $systemPrompt, $model);

            // Save the greeting message
            $msg = Message::create([
                'chat_id' => $this->chat->id,
                'sender' => null,
                'message' => $reply,
                'is_ai' => true,
                'is_read' => false
            ]);

            // Broadcast with auto_open flag so the widget auto-opens
            emit_pusher_notification(
                'chat.' . $this->chat->id,
                'new-message',
                [
                    'chat_id' => $this->chat->id,
                    'user_id' => 'ai',
                    'message' => $reply,
                    'sender' => 'ai',
                    'sender_type' => 'ai',
                    'role' => 4,
                    'created_at' => $msg->formatted_created_at,
                    'formatted_created_at' => $msg->formatted_created_at,
                    'id' => $msg->id,
                    'is_read' => false,
                    'is_ai' => true,
                    'auto_open' => true, // Signal widget to auto-open with ringtone
                ]
            );

            $this->chat->update(['ai_processing' => false]);

            Log::info("SendProactiveAIGreetingJob: Proactive greeting sent for Chat #{$this->chat->id}");

        } catch (\Exception $e) {
            $this->chat->update(['ai_processing' => false]);
            Log::error('SendProactiveAIGreetingJob error: ' . $e->getMessage());
        }
    }
}
