<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Models\AgentPresence;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Pusher\Pusher;

class HandleInactiveChats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:handle-inactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Log::info('time now: ' . now());
        // Log::info('threshold: ' . now()->subMinutes(2));
        AgentPresence::where('last_seen_at', '<', now()->subSeconds(User::ONLINE_TIMEOUT_SECONDS))
            ->delete();

        User::where('role', 2)
            ->with('agentPresences')
            ->get()
            ->each(function (User $user) {
                $isOnline = $user->agentPresences
                    ->contains(fn (AgentPresence $presence) => $presence->last_seen_at->gte(now()->subSeconds(User::ONLINE_TIMEOUT_SECONDS)));
                $user->update(['is_online' => $isOnline]);
            });

        return Command::SUCCESS;
        // Log::info('chat:handle-inactive command started');
        // $now = now();

        // $chats = Chat::where('status', 'open')->get();

        // foreach ($chats as $chat) {

        //     // Log::info("Checking chat ID: {$chat->id}");

        //     $inactiveMinutes = $chat->last_visitor_activity_at
        //         ? Carbon::parse($chat->last_visitor_activity_at)->diffInMinutes($now)
        //         : 999;

        //     // Log::info("Chat {$chat->id} inactive minutes: {$inactiveMinutes}");

        //     // 🟡 2–5 min → agent warning
        //     if ($inactiveMinutes >= 2 && $inactiveMinutes < 5 && !$chat->agent_warned) {

        //         Message::create([
        //             'chat_id' => $chat->id,
        //             'sender' => null,
        //             'message' => '⚠ Visitor inactive (2+ minutes)',
        //             'is_read' => true
        //         ]);

        //         $chat->update(['agent_warned' => true]);
        //     }

        //     // 🟠 10–15 min → system message
        //     if ($inactiveMinutes >= 10 && $inactiveMinutes < 15 && !$chat->system_notified) {

        //         Message::create([
        //             'chat_id' => $chat->id,
        //             'sender' => null,
        //             'message' => '⏳ Visitor inactive for 10 minutes',
        //             'is_read' => true
        //         ]);

        //         $chat->update(['system_notified' => true]);
        //     }

        //     // 🔴 20+ min → auto close
        //     if ($inactiveMinutes >= 20 && $chat->status === 'open') {

        //         Message::create([
        //             'chat_id' => $chat->id,
        //             'sender' => null,
        //             'message' => '❌ Chat closed due to inactivity',
        //             'is_read' => true
        //         ]);

        //         $chat->update([
        //             'status' => 'closed',
        //             'closed_at' => now()
        //         ]);

        //         emit_pusher_notification(
        //             'chat.' . $chat->id,
        //             'activity',
        //             [
        //                 'message' => "❌ Chat closed due to inactivity",
        //                 'chat_status' => 'closed',
        //                 'chat_id' => $chat->id,
        //             ]
        //         );
        //     }
        // }
    }
}
