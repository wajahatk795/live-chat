<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Chat;
use App\Events\NewMessage;

class MessageController extends Controller
{
    public function send(Request $request)
    {
        abort_unless((int) auth()->user()->role === 2, 403, 'Administrators can view chats but cannot send replies.');

        $request->validate([
            'chat_id' => 'required|integer|exists:chats,id',
            'message' => 'required|string|max:5000',
        ]);

        $chat = Chat::with('visitor', 'agent')->find($request->chat_id);
        if(!$chat){
            return response()->json(['error' => 'Chat not found'], 404);
        }

        if ($chat->isPastConversation()) {
            return response()->json([
                'error' => true,
                'message' => 'Past conversations are read-only.',
            ], 403);
        }

        // 🚫 Block agent from sending while AI is processing a response
        if ($chat->ai_processing) {
            return response()->json([
                'error' => true,
                'message' => 'Please wait — AI is generating a response. You can send your message once it completes.',
                'ai_processing' => true
            ], 423); // 423 Locked
        }

        // ✅ If chat was handled by AI, switch to agent and add system message
        if ($chat->is_handled_by_ai) {
            $agentName = auth()->user()->name ?? 'Agent';

            // Add system message: "Switched to Agent [name]"
            $systemMsg = Message::create([
                'chat_id' => $chat->id,
                'sender' => null,
                'message' => "Switched to {$agentName}",
                'is_read' => true
            ]);

            emit_pusher_notification(
                'chat.' . $chat->id,
                'agent-switched',
                [
                    'chat_id' => $chat->id,
                    'message' => "Switched to {$agentName}",
                    'agent_name' => $agentName,
                    'formatted_created_at' => $systemMsg->formatted_created_at,
                ]
            );

            // Clear AI flags
            $chat->update([
                'is_handled_by_ai' => false,
                'ai_pending' => false,
                'ai_pending_since' => null,
            ]);
        }

        // Also clear ai_pending if agent replies before AI delay fires
        if ($chat->ai_pending) {
            $chat->update([
                'ai_pending' => false,
                'ai_pending_since' => null,
            ]);
        }

        $msg = Message::create([
            'chat_id' => $chat->id,
            'sender'  => auth()->id(),
            'message' => $request->message
        ]);

        $messageData = [
            'chat_id' => $chat->id,
            'message' => $msg->message,
            'role' => auth()->user()->role ?? 2,
            'formatted_created_at' => $msg->formatted_created_at,
            'created_at' => $msg->created_at,
            'id' => $msg->id,
            'is_read' => (bool) $msg->is_read,
            'user' => [
                'id' => auth()->id(),
                'image' => auth()->user()->image ?? null,
                'name' => auth()->user()->name ?? '',
            ],
        ];

        emit_pusher_notification(
            'chat.' . $chat->id,
            'new-message',
            $messageData
        );

        return response()->json([
            'status' => 'sent',
            'message' => $messageData,
        ]);
    }

    public function chartStart(Request $request)
    {
        $request->validate([
            'visitor_id' => 'required',
            'brand_id' => 'required'
        ]);

        $chat = Chat::where('visitor_id', $request->visitor_id)
                    ->where('brand_id', $request->brand_id)
                    ->where('status', 'open')
                    ->first();
        if(!$chat){
            return response()->json([
                'error' => true,
                'message' => 'Chat not found'
            ]);
        }
        $chat->agent_id = auth()->user()->id;
        $chat->save();

        // Create chat
        // $chat = Chat::create([
        //     'visitor_id' => $request->visitor_id,
        //     'brand_id' => $request->brand_id,
        //     'agent_id' => auth()->user()->id,
        //     'status' => 'open'
        // ]);

        return response()->json([
            'chat_id' => $chat->id
        ]);
    }

    public function typing(Request $request)
    {
        emit_pusher_notification(
            'chat.' . $request->chat_id,
            'typing',
            [
                'role' => 2,
                'chat_id' => $request->chat_id
            ]
        );

        return response()->json(['status' => true]);
    }

    public function markRead(Request $request)
    {
        $chat = Chat::with('visitor')->where('id', $request->chat_id)->first();
        if(!$chat){
            return response()->json(['error' => true]);
        }

        Message::where('chat_id', $chat->id)
            ->where('sender', $chat->visitor->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        emit_pusher_notification(
            'chat.' . $chat->id,
            'messages-read',
            [
                'chat_id' => $chat->id,
                'sender_type' => 'visitor'
            ]
        );

        return response()->json(['status' => true]);
    }

     public function closeChat(Request $request)
     {
         $chat = Chat::find($request->chat_id);
         if (!$chat) {
             return response()->json(['error' => 'Chat not found'], 404);
         }

         $chat->update([
             'status' => 'closed',
             'closed_at' => now()
         ]);

         Message::create([
             'chat_id' => $chat->id,
             'sender' => null,
             'message' => 'Chat closed by agent',
             'is_read' => true
         ]);

         emit_pusher_notification(
             'chat.' . $chat->id,
             'activity',
             [
                 'message' => "Chat closed by agent",
                 'chat_status' => 'closed',
                 'chat_id' => $chat->id,
             ]
         );

         return response()->json(['status' => true]);
     }
}
