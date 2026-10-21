<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visitor;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Website;
use App\Models\User;
use App\Models\Brand;
use App\Models\OfflineMessage;
use App\Models\FormSubmission;
use App\Mail\OfflineMessageMail;
use App\Notifications\NewVisitorNotification;
use App\Notifications\BrandApprovalRequest;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Http;

class VisitorController extends Controller
{
    private const CHAT_IDLE_TIMEOUT_HOURS = 24;

    public function getChatBySession(Request $request)
    {
        $request->validate([
            'session_id' => 'required',
            'limit' => 'nullable|integer',
            'before_id' => 'nullable|integer',
        ]);

        $limit = $request->limit ?? 20;

        $visitor = Visitor::where('session_id', $request->session_id)->first();
        if (!$visitor) {
            return response()->json([
                'chat_id' => null,
                'messages' => [],
                'unread_count' => 0
            ]);
        }

        $chat = Chat::with('agent', 'visitor')
            ->where('visitor_id', $visitor->id)
            ->where('status', 'open')
            ->first();

        if (!$chat) {
            return response()->json([
                'chat_id' => null,
                'messages' => [],
                'unread_count' => 0
            ]);
        }

        $query = Message::with('user')
            ->where('chat_id', $chat->id)
            ->where(function ($q) {
                $q->whereNotNull('sender')->orWhere('is_ai', true);
            })
            ->orderBy('id', 'desc'); // newest first

        if ($request->before_id) {
            $query->where('id', '<', $request->before_id);
        }

        $messages = $query->take($limit)->get()->sortBy('id')->values();

        $visitorUser = User::where('visitor_id', $visitor->id)->first();
        $visitorUserId = $visitorUser ? $visitorUser->id : null;

        $unreadCount = Message::where('chat_id', $chat->id)
            ->where('sender', '!=', $visitorUserId)
            ->where(function ($q) {
                $q->whereNotNull('sender')->orWhere('is_ai', true);
            })
            ->where('is_read', false)
            ->count();

        $data = $messages->map(function ($msg) {
            return [
                'id' => $msg->id,
                'message' => $msg->message,
                'role' => $msg->is_ai ? 4 : ($msg->user->role ?? $msg->sender),
                'sender' => $msg->is_ai ? 'ai' : ($msg->sender ?? null),
                'is_ai' => (bool)$msg->is_ai,
                'created_at' => $msg->created_at,
                'formatted_created_at' => $msg->formatted_created_at,
                'is_read' => $msg->is_read,
            ];
        });

        return response()->json([
            'chat_id' => $chat->id,
            'messages' => $data,
            'unread_count' => $unreadCount
        ]);
    }

    public function postVisitorMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'session_id' => 'required'
        ]);

        // 1️⃣ Get visitor via session
        $visitor = Visitor::where('session_id', $request->session_id)
            ->with('user')
            ->first();

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'Visitor not found'
            ], 404);
        }

        $visitor->last_seen_at = now();
        $visitor->save();

        $chat = $this->getOrCreateCurrentChat($visitor);
        $chat->load('agent', 'visitor', 'get_brand');
        $chat->update(['last_visitor_activity_at' => now()]);


        $visitorUser = $visitor->user;
        $roleId = optional($visitorUser)->role;
        $userId = optional($visitorUser)->id;

        $msg = Message::create([
            'chat_id' => $chat->id,
            'sender' => $userId,
            'message' => $request->message
        ]);

        $brand = $chat->get_brand ?: ($chat->brand ?: Brand::find($chat->brand_id));
        $brandUserIds = $brand ? $brand->users->pluck('id')->all() : [];

        emit_pusher_notification(
            'chat.' . $chat->id, // channel
            'new-message',                // event
            [
                'chat_id' => $chat->id,
                'user_id' => $userId,
                'message' => $request->message,
                'sender' => $userId,
                'sender_type' => 'visitor',
                'role' => 3,
                'country_code' => $visitor->country_code,
                'user' => [
                    'id' => $userId,
                    'name' => $visitorUser->name ?? 'Visitor',
                    'image' => $visitorUser->image ?? null,
                    'role' => 3,
                    'country_code' => $visitor->country_code,
                ],
                'created_at' => $msg->formatted_created_at,
                'formatted_created_at' => $msg->formatted_created_at,
                'id' => $msg->id,
                'is_read' => false
            ]
        );

        emit_pusher_notification(
            'admin-notifications',
            'visitor-message',
            [
                'chat' => [
                    'id' => $chat->id,
                ],
                'visitor' => [
                    'id' => $visitor->id,
                    'country_code' => $visitor->country_code,
                ],
                'message' => [
                    'id' => $msg->id,
                    'message' => $request->message,
                    'formatted_created_at' => $msg->formatted_created_at,
                ],
                'user_ids' => $brandUserIds,
            ]
        );

        // Check if AI is enabled for this brand and set up delayed AI handoff
        $settings = $brand ? ($brand->chatSetting ?: \App\Models\ChatSetting::where('brand_id', $brand->id)->first()) : null;

        Log::info("VisitorController: Visitor message received for Chat #{$chat->id}, Brand #{$brand?->id}, ai_enabled: " . ($settings?->ai_enabled ? 'true' : 'false'));

        if ($settings && $settings->ai_enabled) {
            // Set ai_pending flag — the scheduled command chat:check-ai-handoff will trigger after delay if agent doesn't reply
            $pendingSince = now()->toDateTimeString();
            $chat->update([
                'ai_pending' => true,
                'ai_pending_since' => $pendingSince,
            ]);

            $delaySeconds = $settings->ai_handoff_delay ?? 60;
            Log::info("VisitorController: AI handoff pending for Chat #{$chat->id}, will trigger after {$delaySeconds}s delay if agent doesn't reply");
        }

        return response()->json([
            'status' => true,
            'chat_id' => $chat->id,
            'message' => 'Message sent successfully',
        ], 200);
    }

    public function loadMore(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|integer',
            'before_id' => 'required|integer',
        ]);

        $chat = Chat::find($request->chat_id);
        if (!$chat)
            return response()->json(['messages' => []]);

        $messages = Message::where('chat_id', $chat->id)
            ->where('id', '<', $request->before_id)
            ->where(function ($q) {
                $q->whereNotNull('sender')->orWhere('is_ai', true);
            })
            ->orderBy('id', 'desc')
            ->limit(10) // fetch 10 older messages
            ->get();

        // return in ascending order
        $messages = $messages->sortBy('id')->values();

        $data = $messages->map(function ($msg) {
            return [
                'id' => $msg->id,
                'message' => $msg->message,
                'role' => $msg->is_ai ? 4 : ($msg->user->role ?? $msg->sender),
                'sender_role' => $msg->is_ai ? 4 : ($msg->user->role ?? $msg->sender),
                'sender' => $msg->is_ai ? 'ai' : ($msg->sender ?? null),
                'is_ai' => (bool)$msg->is_ai,
                'created_at' => $msg->created_at,
                'formatted_created_at' => $msg->formatted_created_at,
                'is_read' => $msg->is_read,
            ];
        });

        return response()->json(['messages' => $data]);
    }

    public function postVisitorInit(Request $request)
    {
        $request->validate([
            'domain' => 'required',
            'session_id' => 'required',
            'referrer' => 'nullable|url|max:2048',
            'landing_url' => 'nullable|url|max:2048',
        ]);

        $ip = $request->ip();

        // 🔹 Device detection
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        $device = $agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablet' : 'Desktop');
        $browser = $agent->browser();
        $os = $agent->platform();

        // 🔹 Location using IP API
        $location = Http::get("http://ip-api.com/json/{$ip}")->json();
        if (
            app()->environment('local')
            && in_array($ip, ['127.0.0.1', '::1'], true)
            && (($location['status'] ?? null) !== 'success' || empty($location['countryCode']))
        ) {
            $location = Http::get('http://ip-api.com/json/')->json();
        }

        $country = $location['country'] ?? null;
        $countryCode = strtoupper((string) ($location['countryCode'] ?? ''));
        $countryCode = preg_match('/^[A-Z]{2}$/', $countryCode) ? $countryCode : null;
        $city = $location['city'] ?? null;

        // 1️⃣ Website
        $website = Website::firstOrCreate(
            ['domain' => $request->domain],
            ['name' => $request->domain]
        );
        $brand = Brand::with('users', 'chatSetting')->where('domain', $request->domain)->first();
        if (!$brand) {
            return response()->json([
                'error' => true,
                'message' => 'Brand Not Found'
            ]);
        }

        $ip = $request->ip();

        // 🔒 IP Restriction
        if ($brand->allowed_ips) {

            $allowedIps = array_map('trim', explode(',', $brand->allowed_ips));

            if (!in_array($ip, $allowedIps)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Access denied from this IP'
                ], 403);
            }
        }

        if ($brand->status == 0) {
            // Notify admin first time only
            if (!$brand->is_verified) {
                $brand->is_verified = true;
                $brand->save();

                $admin = User::where('role', 1)->first();

                // send notification to admin
                $admin->notify(new BrandApprovalRequest($brand));
            }

            return response()->json([
                'status' => 'pending',
                'message' => 'Waiting for admin approval',
            ]);
        }

        if ($brand->status == 2) {
            return response()->json([
                'status' => 'error',
                'message' => 'Brand rejected',
            ]);
        }

        $userIds = $brand->users->pluck('id')->toArray();

        $sessionId = $request->session_id;

        // 2️⃣ Visitor
        $visitor = Visitor::firstOrCreate(
            ['session_id' => $request->session_id],
            ['brand_id' => $brand->id] //website is like brand_id
        );

        if ($visitor->wasRecentlyCreated || (!$visitor->source && !$visitor->keyword)) {
            [$source, $keyword] = $this->resolveVisitorAttribution(
                $request->input('referrer'),
                $request->input('landing_url')
            );
            $visitor->source = $source;
            $visitor->keyword = $keyword;
        }

        $visitor->update([
            'ip_address' => $ip,
            'country' => $country,
            'country_code' => $countryCode,
            'city' => $city,
            'device' => $device,
            'browser' => $browser,
            'os' => $os,
            'last_seen_at' => now(),
        ]);

        $visitorUser = User::firstOrCreate(
            ['visitor_id' => $visitor->id], // optional: link user to visitor
            [
                'name' => 'Visitor-' . $visitor->id,
                'email' => 'visitor' . $visitor->id . '@example.com',
                'password' => bcrypt(Str::random(8)), // random password
                'role' => 3
            ]
        );

        $chat = $this->getOrCreateCurrentChat($visitor);
        $chat->update(['last_visitor_activity_at' => now()]);

        $this->broadcastVisitorOnline($visitor, $chat, $brand, $userIds);

        $settings = $brand->chatSetting;

        $agentOnline = User::whereIn('id', $userIds)
            ->where('is_online', 1)
            ->exists();

        // ✅ ONLY FIRST TIME VISITOR
        if ($visitor->wasRecentlyCreated) {

            // Send DB notification
            $admins = User::where('role', 1)->get();
            $brandUsers = $brand->users;
            $allNotifiableUsers = $admins->merge($brandUsers)->unique('id');
            foreach ($allNotifiableUsers as $user) {
                $user->notify(new NewVisitorNotification($brand, $visitor));
            }

            // Send Pusher notification
            emit_pusher_notification(
                'admin-notifications',
                'visitor-joined',
                [
                    'brand' => [
                        'id' => $brand->id,
                        'domain' => $brand->domain,
                    ],
                    'visitor' => [
                        'id' => $visitor->id,
                        'session_id' => $visitor->session_id,
                        'country_code' => $visitor->country_code,
                    ],
                    'chat' => [
                        'id' => $chat->id,
                        'status' => $chat->status
                    ],
                    'user_ids' => $userIds
                ]
            );
        }

        return response()->json([
            'success' => true,
            'visitor_id' => $visitor->id,
            'chat_id' => $chat->id,
            'brand_id' => $brand->id,
            'agent_online' => $agentOnline,
            'user_ids' => $userIds,
            'message' => 'Visitor initialized successfully',
            'settings' => [
                'chat_enabled' => $settings->chat_enabled ?? true,
                'ai_enabled' => $settings->ai_enabled ?? false,
                'ai_handoff_delay' => $settings->ai_handoff_delay ?? 60,
                'primary_color' => $settings->primary_color ?? '#696cff',
                'welcome_message' => $settings->welcome_message ?? 'Hello! How can we help you today?',
                'offline_message' => $settings->offline_message ?? 'We are currently offline.',
                'popup_delay' => $settings->popup_delay ?? 5,
                'sound_enabled' => $settings->sound_enabled ?? true,
                'chat_position' => $settings->chat_position ?? 'right',
            ]
        ]);
    }

    private function resolveVisitorAttribution(?string $referrer, ?string $landingUrl): array
    {
        $referrerHost = $referrer ? parse_url($referrer, PHP_URL_HOST) : null;
        $referrerHost = is_string($referrerHost) ? strtolower(preg_replace('/^www\./', '', $referrerHost)) : null;

        $landingQuery = $landingUrl ? parse_url($landingUrl, PHP_URL_QUERY) : null;
        $referrerQuery = $referrer ? parse_url($referrer, PHP_URL_QUERY) : null;
        parse_str(is_string($landingQuery) ? $landingQuery : '', $landingParams);
        parse_str(is_string($referrerQuery) ? $referrerQuery : '', $referrerParams);

        $source = $landingParams['utm_source'] ?? null;
        if (!$source && $referrerHost) {
            $source = $referrerHost;
        }
        if (!is_string($source) || trim($source) === '') {
            $source = null;
        }

        $keyword = $landingParams['utm_term'] ?? null;
        if (!is_string($keyword) || trim($keyword) === '') {
            $keyword = null;
            foreach (['q', 'query', 'p', 'text', 'search_query'] as $key) {
                if (!empty($referrerParams[$key]) && is_string($referrerParams[$key])) {
                    $keyword = $referrerParams[$key];
                    break;
                }
            }
        }

        return [
            $source ? mb_substr($source, 0, 255) : ($referrerHost ?: 'Direct'),
            $keyword ? mb_substr($keyword, 0, 255) : null,
        ];
    }

    private function getOrCreateCurrentChat(Visitor $visitor): Chat
    {
        $openChats = Chat::where('visitor_id', $visitor->id)
            ->where('brand_id', $visitor->brand_id)
            ->where('status', 'open');
        $inactiveSince = now()->subHours(self::CHAT_IDLE_TIMEOUT_HOURS);

        (clone $openChats)
            ->where(function ($query) use ($inactiveSince) {
                $query->where('last_visitor_activity_at', '<=', $inactiveSince)
                    ->orWhere(function ($query) use ($inactiveSince) {
                        $query->whereNull('last_visitor_activity_at')
                            ->where('created_at', '<=', $inactiveSince);
                    });
            })
            ->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_reason' => 'inactive',
            ]);

        $chat = $openChats->latest('id')->first();

        if (!$chat) {
            $agentId = Chat::where('visitor_id', $visitor->id)
                ->where('brand_id', $visitor->brand_id)
                ->latest('id')
                ->value('agent_id');

            $chat = Chat::create([
                'visitor_id' => $visitor->id,
                'brand_id' => $visitor->brand_id,
                'agent_id' => $agentId ?? 1,
                'status' => 'open',
                'last_visitor_activity_at' => now(),
            ]);
        }

        return $chat;
    }

    private function broadcastVisitorOnline(Visitor $visitor, Chat $chat, Brand $brand, array $userIds): void
    {
        emit_pusher_notification(
            'admin-notifications',
            'visitor-online',
            [
                'brand' => [
                    'id' => $brand->id,
                    'domain' => $brand->domain,
                ],
                'visitor' => [
                    'id' => $visitor->id,
                    'session_id' => $visitor->session_id,
                ],
                'chat' => [
                    'id' => $chat->id,
                    'status' => $chat->status,
                ],
                'user_ids' => $userIds,
            ]
        );
    }

    public function typing(Request $request)
    {
        $visitor = Visitor::where('session_id', $request->session_id)
            ->with('user')
            ->first();

        $chat = Chat::with('agent', 'visitor')->where('visitor_id', $visitor->id)
            ->where('status', 'open')
            ->first();

        if ($chat) {
            emit_pusher_notification(
                'chat.' . $chat->id,
                'typing',
                ['role' => 3]
            );
        }


        return response()->json(['status' => true]);
    }

    public function markRead(Request $request)
    {
        $visitor = Visitor::where('session_id', $request->session_id)->first();
        if (!$visitor)
            return response()->json();

        $chat = Chat::where('visitor_id', $visitor->id)
            ->where('status', 'open')
            ->first();
        if (!$chat)
            return response()->json(['status' => false]);

        $visitorUser = User::where('visitor_id', $visitor->id)->first();
        if (!$visitorUser)
            return response()->json(['status' => false]);

        Message::where('chat_id', $chat->id)
            ->where('sender', '!=', $visitorUser->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        emit_pusher_notification(
            'chat.' . $chat->id,
            'messages-read',
            [
                'chat_id' => $chat->id,
                'sender_type' => 'agent'
            ]
        );

        return response()->json(['status' => true]);
    }

    public function chatActivity(Request $request)
    {
        $visitor = Visitor::where('session_id', $request->session_id)->first();
        if (!$visitor)
            return response()->json();

        $chat = Chat::where('visitor_id', $visitor->id)
            ->where('status', 'open')
            ->first();
        if (!$chat)
            return response()->json(['status' => false]);

        $msg = Message::create([
            'chat_id' => $chat->id,
            'sender' => null,
            'message' => $request->message,
            'is_read' => true
        ]);

        emit_pusher_notification(
            'chat.' . $chat->id,
            'activity',
            [
                'message' => $request->message,
                'created_at' => $msg->created_at,
                'formatted_created_at' => $msg->formatted_created_at,
            ]
        );

        return response()->json(['status' => true]);
    }

    public function visitorActivity(Request $request)
    {
        $request->validate([
            'session_id' => 'required',
            'url' => 'required|string'
        ]);

        $visitor = Visitor::where('session_id', $request->session_id)->first();
        if (!$visitor)
            return response()->json(['status' => false]);

        $visitor->last_url = $request->url;
        $visitor->last_seen_at = now();
        $visitor->save();

        $chat = Chat::where('visitor_id', $visitor->id)->where('status', 'open')->first();
        if ($chat) {
            $msg = Message::create([
                'chat_id' => $chat->id,
                'sender' => null,
                'message' => "Visitor navigated to: " . $request->url,
                'is_read' => true
            ]);

            emit_pusher_notification(
                'chat.' . $chat->id,
                'activity',
                [
                    'message' => "Visitor navigated to: " . $request->url,
                    'created_at' => $msg->created_at,
                    'formatted_created_at' => $msg->formatted_created_at,
                ]
            );
        }

        return response()->json(['status' => true]);
    }

    public function visitorHeartbeat(Request $request)
    {
        $visitor = Visitor::where('session_id', $request->session_id)->first();
        if (!$visitor)
            return response()->json();

        $wasOffline = !$visitor->last_seen_at
            || $visitor->last_seen_at->lt(now()->subMinutes(3));
        $visitor->last_seen_at = now();
        $visitor->save();

        $chat = Chat::where('visitor_id', $visitor->id)
            ->where('status', 'open')
            ->first();

        if ($chat) {
            $chat->last_visitor_activity_at = now();
            $chat->save();

            if ($wasOffline) {
                $brand = Brand::find($chat->brand_id);
                if ($brand) {
                    $this->broadcastVisitorOnline($visitor, $chat, $brand, $brand->users->pluck('id')->all());
                }
            }
        }

        return response()->json(['status' => true]);
    }

    public function verifyBrand(Request $request)
    {
        $brand = Brand::where('id', $request->brand_id)
            ->where('verify_token', $request->token)
            ->first();

        if (!$brand) {
            return response()->json(['status' => false]);
        }

        $brand->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verify_token' => null
        ]);

        return response()->json(['status' => true]);
    }

    public function offlineMessage(Request $request)
    {
        $request->validate([
            'message' => 'required'
        ]);

        $brand = Brand::find($request->brand_id);

        $offline = OfflineMessage::create([
            'brand_id' => $request->brand_id,
            'name' => $request->name,
            'email' => $request->email,
            'message' => $request->message
        ]);

        FormSubmission::create([
            'brand_id' => $request->brand_id,
            'page_url' => $request->page_url ?? null,
            'form_action' => route('api.offline-message'),
            'form_method' => 'POST',
            'form_data' => [
                'name' => $request->name,
                'email' => $request->email,
                'message' => $request->message,
            ],
            'meta' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session_id,
                'chat_id' => $request->chat_id,
            ]
        ]);

        // ⭐ send email to admin + agents
        $admins = User::where('role', 1)->get();
        $agents = $brand?->users ?? collect();
        $users = $admins->merge($agents)->unique('id');

        foreach ($users as $user) {
            Mail::to('mikehuckabee42@gmail.com')->send(new OfflineMessageMail($offline));
        }

        return response()->json([
            'success' => true,
            'message' => 'Offline message stored'
        ]);
    }

    public function triggerPendingAiResponse(Request $request)
    {
        $request->validate([
            'session_id' => 'required',
        ]);

        $visitor = Visitor::where('session_id', $request->session_id)->first();
        if (!$visitor) {
            return response()->json(['status' => false, 'message' => 'Visitor not found']);
        }

        $chat = Chat::where('visitor_id', $visitor->id)
            ->where('status', 'open')
            ->first();

        if (!$chat) {
            return response()->json(['status' => false, 'message' => 'Open chat not found']);
        }

        // Verify if AI is pending
        if (!$chat->ai_pending || !$chat->ai_pending_since) {
            return response()->json(['status' => false, 'message' => 'AI handoff not pending']);
        }

        // Verify that delay has elapsed
        $brand = $chat->get_brand ?: ($chat->brand ?: Brand::find($chat->brand_id));
        $settings = $brand ? ($brand->chatSetting ?: \App\Models\ChatSetting::where('brand_id', $brand->id)->first()) : null;

        if (!$settings || !$settings->ai_enabled) {
            return response()->json(['status' => false, 'message' => 'AI not enabled']);
        }

        // Check if agent replied after ai_pending_since
        $agentReplied = Message::where('chat_id', $chat->id)
            ->where('created_at', '>', $chat->ai_pending_since)
            ->where(function ($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->whereIn('role', [1, 2]);
                });
            })
            ->exists();

        if ($agentReplied) {
            $chat->update(['ai_pending' => false, 'ai_pending_since' => null]);
            return response()->json(['status' => false, 'message' => 'Agent already replied']);
        }

        // Dispatch AI response job synchronously
        \App\Jobs\ProcessAIResponseJob::dispatchSync($chat);

        return response()->json([
            'status' => true,
            'message' => 'AI response triggered successfully'
        ]);
    }

    public function triggerProactiveGreeting(Request $request)
    {
        $request->validate([
            'session_id' => 'required',
        ]);

        $visitor = Visitor::where('session_id', $request->session_id)->first();
        if (!$visitor) {
            return response()->json(['status' => false, 'message' => 'Visitor not found']);
        }

        $chat = Chat::where('visitor_id', $visitor->id)
            ->where('status', 'open')
            ->first();

        if (!$chat || $chat->ai_greeted) {
            return response()->json(['status' => false, 'message' => 'Chat not found or already greeted']);
        }

        // Check if messages already exist
        $hasMessages = Message::where('chat_id', $chat->id)
            ->where(function ($q) {
                $q->whereNotNull('sender')->orWhere('is_ai', true);
            })
            ->exists();

        if ($hasMessages) {
            $chat->update(['ai_greeted' => true]);
            return response()->json(['status' => false, 'message' => 'Chat already has messages']);
        }

        \App\Jobs\SendProactiveAIGreetingJob::dispatchSync($chat);

        return response()->json([
            'status' => true,
            'message' => 'Proactive greeting triggered successfully'
        ]);
    }
}
