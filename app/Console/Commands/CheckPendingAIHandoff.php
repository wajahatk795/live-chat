<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Chat;
use App\Models\Message;
use App\Models\ChatSetting;
use App\Jobs\ProcessAIResponseJob;
use App\Jobs\SendProactiveAIGreetingJob;
use App\Services\Ai\OfflineAgentAiHandoff;
use Illuminate\Support\Facades\Log;

class CheckPendingAIHandoff extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:check-ai-handoff';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check open chats for AI handoff timeout and send proactive greeting to idle visitors';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->processPendingHandoffs();
        $this->processProactiveGreetings();

        return Command::SUCCESS;
    }

    /**
     * Scenario 1: Visitor sent message, agent did not reply within brand delay -> AI takes over
     */
    protected function processPendingHandoffs()
    {
        $pendingChats = Chat::where('status', 'open')
            ->where('ai_pending', true)
            ->whereNotNull('ai_pending_since')
            ->where('ai_processing', false)
            ->get();

        foreach ($pendingChats as $chat) {
            $brand = $chat->get_brand ?: ($chat->brand ?: \App\Models\Brand::find($chat->brand_id));
            $settings = $brand ? ($brand->chatSetting ?: ChatSetting::where('brand_id', $brand->id)->first()) : null;

            if (!$settings || !$settings->ai_enabled) {
                continue;
            }

            if (app(OfflineAgentAiHandoff::class)->hasOnlineAgent($chat)) {
                continue;
            }

            $delaySeconds = $settings->ai_handoff_delay ?? 60;
            $pendingSince = $chat->ai_pending_since ? \Carbon\Carbon::parse($chat->ai_pending_since) : null;

            if ($pendingSince && $pendingSince->copy()->addSeconds($delaySeconds)->isPast()) {
                // Check if agent replied after pending_since
                $agentReplied = Message::where('chat_id', $chat->id)
                    ->where('created_at', '>', $pendingSince)
                    ->where(function ($q) {
                        $q->whereHas('user', function ($uq) {
                            $uq->whereIn('role', [1, 2]);
                        });
                    })
                    ->exists();

                if ($agentReplied) {
                    $chat->update(['ai_pending' => false, 'ai_pending_since' => null]);
                    continue;
                }

                Log::info("CheckPendingAIHandoff: Triggering AI handoff for Chat #{$chat->id} after {$delaySeconds}s delay");
                ProcessAIResponseJob::dispatch($chat);
            }
        }
    }

    /**
     * Scenario 2: Visitor arrived but sent no message -> Proactive AI greeting after delay with auto-open
     */
    protected function processProactiveGreetings()
    {
        $idleChats = Chat::where('status', 'open')
            ->where('ai_greeted', false)
            ->where('ai_processing', false)
            ->get();

        foreach ($idleChats as $chat) {
            $brand = $chat->get_brand ?: ($chat->brand ?: \App\Models\Brand::find($chat->brand_id));
            $settings = $brand ? ($brand->chatSetting ?: ChatSetting::where('brand_id', $brand->id)->first()) : null;

            if (!$settings || !$settings->ai_enabled) {
                continue;
            }

            if (app(OfflineAgentAiHandoff::class)->hasOnlineAgent($chat)) {
                continue;
            }

            $delaySeconds = $settings->ai_handoff_delay ?? 60;
            $createdAt = $chat->created_at ? \Carbon\Carbon::parse($chat->created_at) : null;

            if ($createdAt && $createdAt->copy()->addSeconds($delaySeconds)->isPast()) {
                // Check if any message exists in chat
                $hasMessages = Message::where('chat_id', $chat->id)
                    ->where(function ($q) {
                        $q->whereNotNull('sender')->orWhere('is_ai', true);
                    })
                    ->exists();

                if ($hasMessages) {
                    $chat->update(['ai_greeted' => true]);
                    continue;
                }

                Log::info("CheckPendingAIHandoff: Triggering proactive AI greeting for Chat #{$chat->id} after {$delaySeconds}s delay");
                SendProactiveAIGreetingJob::dispatch($chat);
            }
        }
    }
}
