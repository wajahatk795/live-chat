<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Models\Brand;
use App\Services\Ai\AiServiceFactory;
use App\Services\AgentPresenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessAIResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $chat;
    public $pendingSince; // The timestamp when ai_pending was set (to avoid stale jobs)
    public bool $triggerImmediately;

    public function __construct(Chat $chat, ?string $pendingSince = null, bool $triggerImmediately = false)
    {
        $this->chat = $chat;
        $this->pendingSince = $pendingSince;
        $this->triggerImmediately = $triggerImmediately;
    }

    public function handle()
    {
        // Reload fresh chat data
        $this->chat = Chat::find($this->chat->id);
        if (!$this->chat || $this->chat->status !== 'open') {
            Log::info("ProcessAIResponseJob: Chat #{$this->chat?->id} not found or closed. Skipping.");
            return;
        }

        $brand = $this->chat->get_brand ?: ($this->chat->brand ?: Brand::find($this->chat->brand_id));
        $settings = $brand ? ($brand->chatSetting ?: \App\Models\ChatSetting::where('brand_id', $brand->id)->first()) : null;

        if (!$settings || !$settings->ai_enabled) {
            Log::warning("ProcessAIResponseJob: AI not enabled or settings missing for Chat #{$this->chat->id}, Brand #{$brand?->id}");
            return;
        }

        if ($brand && app(AgentPresenceService::class)->hasOnlineAgent($brand)) {
            Log::info("ProcessAIResponseJob: Online agent is available for Chat #{$this->chat->id}; skipping offline handoff.");
            return;
        }

        // If this job was dispatched with a delay (has pendingSince), verify it's still valid
        if ($this->pendingSince) {
            // Check if ai_pending is still true
            if (!$this->chat->ai_pending) {
                Log::info("ProcessAIResponseJob: Chat #{$this->chat->id} ai_pending is false (agent replied in time). Skipping.");
                return;
            }

            // Check if the pending timestamp matches (visitor may have sent another message which dispatched a newer job)
            $chatPendingSince = $this->chat->ai_pending_since ? Carbon::parse($this->chat->ai_pending_since)->toDateTimeString() : null;
            if ($chatPendingSince !== $this->pendingSince) {
                Log::info("ProcessAIResponseJob: Chat #{$this->chat->id} pending_since mismatch (newer job exists). Skipping. Expected: {$this->pendingSince}, Got: {$chatPendingSince}");
                return;
            }

            // Verify that the full delay duration has actually passed
            $delaySeconds = $settings->ai_handoff_delay ?? 60;
            $pendingSinceCarbon = Carbon::parse($this->pendingSince);
            if (!$this->triggerImmediately && $pendingSinceCarbon->copy()->addSeconds($delaySeconds)->isFuture()) {
                Log::info("ProcessAIResponseJob: Full delay ({$delaySeconds}s) has not elapsed yet for Chat #{$this->chat->id}. Skipping premature response.");
                return;
            }

            // Check if agent replied after pending_since (double check)
            $agentReplied = Message::where('chat_id', $this->chat->id)
                ->where('created_at', '>', $this->pendingSince)
                ->where(function ($q) {
                    $q->whereHas('user', function ($uq) {
                        $uq->whereIn('role', [1, 2]); // Admin or Agent
                    });
                })
                ->exists();

            if ($agentReplied) {
                Log::info("ProcessAIResponseJob: Agent replied after pending_since for Chat #{$this->chat->id}. Skipping.");
                $this->chat->update(['ai_pending' => false, 'ai_pending_since' => null]);
                return;
            }
        }

        try {
            // Mark as processing so agent can't send during AI response
            $this->chat->update([
                'is_handled_by_ai' => true,
                'ai_processing' => true,
                'ai_pending' => false,
                'ai_pending_since' => null,
            ]);

            $providerName = $settings->ai_provider ?: 'ollama';
            $model = $settings->ai_model ?: 'llama3';

            $brandName = $brand->name ?: ($brand->domain ?: 'our company');
            $brandUrl = $brand->url ?: ($brand->domain ?: '');
            $customPrompt = $settings->ai_prompt ?: 'You are a professional, helpful, and concise live chat support assistant.';

            $systemPrompt = "{$customPrompt}\n\nYou represent the brand: \"{$brandName}\" ({$brandUrl}).\n\n"
                . "STRICT LIVE CHAT CONSTRAINTS:\n"
                . "1. MAX LENGTH: Your response MUST be 1 to 2 short sentences (under 35 words). Be direct and concise.\n"
                . "2. ACKNOWLEDGMENTS / THANKS: If the user says 'thank you', 'thanks', 'okay', 'ok', or similar, respond ONLY with a short closing like: \"You're welcome! Let me know if you need anything else.\" Never add unsolicited book lists or marketing pitches.\n"
                . "3. NO UNSOLICITED LISTS: Never dump book lists or recommendations unless the user specifically asks for them.\n"
                . "4. DETAIL ON DEMAND: Only give a long response if the visitor explicitly asks (e.g. 'explain in detail', 'give me a full list').\n"
                . "5. ACCURACY: Base your facts only on the website knowledge below.\n"
                . "6. HUMAN / SPECIALIST REQUESTS: If the visitor asks to connect with a specialist, live agent, human, or representative, politely reply: \"I've notified our support specialist — they will join this chat shortly! In the meantime, is there anything I can help you with?\" Never say you cannot connect them.";

            if (!empty($settings->website_knowledge)) {
                $systemPrompt .= "\n\n=== OFFICIAL WEBSITE KNOWLEDGE ===\n"
                    . $settings->website_knowledge . "\n"
                    . "=================================";
            }

            $aiService = AiServiceFactory::make($providerName);

            // Broadcast typing event so visitor knows AI is thinking
            emit_pusher_notification(
                'chat.' . $this->chat->id,
                'typing',
                ['role' => 4]
            );

            // Also broadcast ai-processing event so admin panel blocks agent send
            emit_pusher_notification(
                'chat.' . $this->chat->id,
                'ai-processing',
                ['chat_id' => $this->chat->id, 'processing' => true]
            );

            $reply = $aiService->generateReply($this->chat, $systemPrompt, $model);

            // Save reply
            $msg = Message::create([
                'chat_id' => $this->chat->id,
                'sender' => null,
                'message' => $reply,
                'is_ai' => true,
                'is_read' => false
            ]);

            // Mark processing complete
            $this->chat->update(['ai_processing' => false]);

            // Broadcast via Pusher helper
            emit_pusher_notification(
                'chat.' . $this->chat->id,
                'new-message',
                [
                    'chat_id' => $this->chat->id,
                    'user_id' => 'ai',
                    'message' => $reply,
                    'sender' => 'ai',
                    'sender_type' => 'ai',
                    'role' => 4, // using role 4 for AI
                    'created_at' => $msg->formatted_created_at,
                    'formatted_created_at' => $msg->formatted_created_at,
                    'id' => $msg->id,
                    'is_read' => false,
                    'is_ai' => true
                ]
            );

            // Broadcast ai-processing complete so admin panel re-enables agent send
            emit_pusher_notification(
                'chat.' . $this->chat->id,
                'ai-processing',
                ['chat_id' => $this->chat->id, 'processing' => false]
            );

        } catch (\Exception $e) {
            // Make sure to clear processing flag even on error
            $this->chat->update(['ai_processing' => false]);
            emit_pusher_notification(
                'chat.' . $this->chat->id,
                'ai-processing',
                ['chat_id' => $this->chat->id, 'processing' => false]
            );
            Log::error('AI processing error: ' . $e->getMessage());
        }
    }
}
