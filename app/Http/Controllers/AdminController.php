<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use App\Models\Chat;
use App\Models\Brand;
use App\Models\HeatmapEvent;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use App\Services\DashboardService;

class AdminController extends Controller
{
    public function dashboard(DashboardService $dashboardService)
    {
        $events = HeatmapEvent::where('brand_id', 8)
            ->where('url', 'http://localhost/photo-studio/public/login')
            ->where('type', 'click')
            ->get();

        $user = auth()->user();
        $brandIds = $user->isRole('admin')
            ? Brand::pluck('id')->toArray()
            : $user->auth_brands->pluck('id')->toArray();

        $stats = $dashboardService->getWeeklyStats();
        $chartData = $dashboardService->getWeeklyChartData();
        $bestAgent    = $dashboardService->getBestAgent($brandIds);
        $quickStats   = $dashboardService->getQuickStats($brandIds);
        $responseTime = $dashboardService->getAvgResponseTime($brandIds);
        $brandId      = 8;

        emit_pusher_notification('agent-status', 'agent-online', [
            'agent_id'  => auth()->id(),
            'brand_ids' => auth()->user()->auth_brands->pluck('id')
        ]);

        return view('admin.dashboard', [
            'events'         => $events,

            // Visitors
            'visitorsData'   => $chartData,
            'visitorPercent' => $stats['percent'],
            'percentChange'  => $stats['change'],

            // Activity
            'activityData'   => $chartData,
            'activityPercent'=> $stats['percent'],
            'activityChange' => $stats['change'],
            //Agent
            'bestAgent' => $bestAgent,
            'quickStats' => $quickStats,
            'responseTime' => $responseTime,
            'brandId'         => $brandId,
        ]);
    }

    public function visitorAnalytics(DashboardService $dashboardService)
    {
        $user     = auth()->user();
        $brandIds = $user->isRole('admin')
            ? Brand::pluck('id')->toArray()
            : $user->auth_brands->pluck('id')->toArray();

        return response()->json($dashboardService->getVisitorAnalytics($brandIds));
    }

    private function getHeatmapEvents()
    {
        return HeatmapEvent::where('brand_id', 8)
            ->where('url', 'http://localhost/photo-studio/public/login')
            ->where('type', 'click')
            ->get();
    }

    public function chat($chatId = null)
    {
        $userBrandIds = auth()->user()->auth_brands->pluck('id')->toArray();
        $user = User::with(['get_chat' => function($query) use ($userBrandIds) {
                $query->whereHas('visitor', function($q) use ($userBrandIds) {
                    $q->whereIn('brand_id', $userBrandIds);
                });
            }])
            ->where('role', 3)
            ->get();

        if(auth()->user()->role == 1) {
            $chats = Chat::with(['visitor', 'messages', 'main_visitor'])
                ->where('status', 'open')
                ->latest()
                ->get();
        }else{
            $chats = Chat::with(['visitor', 'main_visitor'])
                ->where('status', 'open')
                ->whereHas('visitor', function($query) use ($userBrandIds) {
                    $query->whereIn('brand_id', $userBrandIds);
                })
                ->latest()
                ->get();
        }

        $messages = collect();
        $chatId = null;

        return view('admin.chat', compact('user', 'messages', 'chatId', 'chats'));
    }
    public function notification()
    {
        return view('admin.notification');
    }

    public function getNotification()
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest();

        return DataTables::of($notifications)

            ->addColumn('title', function ($row) {
                return $row->data['title'] ?? '-';
            })

            ->addColumn('message', function ($row) {
                return $row->data['message'] ?? '-';
            })

            ->addColumn('status', function ($row) {
                if (is_null($row->read_at)) {
                    return '<span class="badge bg-label-danger">Unread</span>';
                }
                return '<span class="badge bg-label-success">Read</span>';
            })

            ->addColumn('date', function ($row) {
                return $row->created_at->diffForHumans();
            })

            ->addColumn('action', function ($row) {
                return '
                    <div class="dropdown">
                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                            data-bs-toggle="dropdown">
                            <i class="icon-base bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item mark-read" data-id="'.$row->id.'" href="javascript:void(0);">
                                <i class="icon-base bx bx-check me-1"></i> Mark as Read
                            </a>
                            <a class="dropdown-item text-danger delete-noti" data-id="'.$row->id.'" href="javascript:void(0);">
                                <i class="icon-base bx bx-trash me-1"></i> Delete
                            </a>
                        </div>
                    </div>
                ';
            })

            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function markAsRead(Request $request)
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $request->id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    public function show($chatId)
    {
        $chat = Chat::with('visitor')->findOrFail($chatId);
        $visitorId = Chat::where('id', $chatId)->value('visitor_id');
        $user = User::with('get_chat')->where('visitor_id', $visitorId)->first();

        return view('admin.chat.messages', compact('chatId', 'user', 'chat'));
    }

    public function chatProfile($chatId)
    {
        $chat = Chat::with('main_visitor')->findOrFail($chatId);
        $this->authorizeChatProfile($chat);

        $visitor = $chat->main_visitor;
        abort_unless($visitor, 404);
        $user = User::where('visitor_id', $chat->visitor_id)->firstOrFail();
        $currentChat = Chat::where('visitor_id', $chat->visitor_id)
            ->where('brand_id', $chat->brand_id)
            ->where('status', 'open')
            ->latest('updated_at')
            ->first(['id', 'status']);

        $pastChats = Chat::where('visitor_id', $chat->visitor_id)
            ->where('brand_id', $chat->brand_id)
            ->where('status', 'closed')
            ->orderByRaw('COALESCE(last_visitor_activity_at, closed_at, updated_at) DESC')
            ->get(['id', 'status', 'created_at', 'updated_at', 'last_visitor_activity_at', 'closed_at'])
            ->map(function ($pastChat) {
                return [
                    'id' => $pastChat->id,
                    'status' => $pastChat->status,
                    'date' => optional($pastChat->last_visitor_activity_at ?? $pastChat->closed_at ?? $pastChat->updated_at)->toIso8601String(),
                    'preview' => $pastChat->messages()->whereNotNull('sender')->latest()->value('message') ?? 'No messages',
                ];
            });

        $lastActivity = $visitor->last_seen_at
            ?? $chat->last_visitor_activity_at
            ?? $visitor->created_at;

        return response()->json([
            'visitor' => [
                'id' => $visitor->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'notes' => $visitor->notes ?? $user->about,
                'tags' => $visitor->tags ?? [],
                'source' => $visitor->source,
                'keyword' => $visitor->keyword,
                'avatar' => $user->image ? asset($user->image) : asset('assets/images/default.png'),
                'ip_address' => $visitor->ip_address,
                'country' => $visitor->country,
                'country_code' => $visitor->country_code,
                'city' => $visitor->city,
                'device' => $visitor->device,
                'browser' => $visitor->browser,
                'os' => $visitor->os,
                'last_url' => $visitor->last_url,
                'session_started_at' => optional($visitor->created_at)->toIso8601String(),
                'last_seen_at' => optional($lastActivity)->toIso8601String(),
                'session_duration_seconds' => $visitor->created_at
                    ? max(0, $visitor->created_at->diffInSeconds($lastActivity))
                    : 0,
            ],
            'current_chat' => [
                'id' => $currentChat?->id,
                'status' => $currentChat?->status,
            ],
            'past_chats' => $pastChats,
        ]);
    }

    public function updateChatProfile(Request $request, $chatId)
    {
        abort_unless((int) auth()->user()->role === 2, 403, 'Administrators can view visitor profiles but cannot update them.');

        $chat = Chat::with('main_visitor')->findOrFail($chatId);
        $this->authorizeChatProfile($chat);
        abort_unless($chat->main_visitor, 404);
        $user = User::where('visitor_id', $chat->visitor_id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'string|max:40',
        ]);

        $userUpdates = ['name' => $validated['name']];
        foreach (['email', 'phone', 'address'] as $field) {
            if (array_key_exists($field, $validated)) {
                $userUpdates[$field] = $validated[$field];
            }
        }
        $user->update($userUpdates);

        $visitorUpdates = [];
        if (array_key_exists('notes', $validated)) {
            $visitorUpdates['notes'] = $validated['notes'];
        }
        if (array_key_exists('tags', $validated)) {
            $visitorUpdates['tags'] = array_values(array_unique(array_filter(array_map('trim', $validated['tags'] ?? []))));
        }
        if ($visitorUpdates) {
            $chat->main_visitor->update($visitorUpdates);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Visitor profile saved.',
        ]);
    }

    private function authorizeChatProfile(Chat $chat): void
    {
        $user = auth()->user();
        abort_unless(
            $user->role == 1 || $user->auth_brands()->where('brand.id', $chat->brand_id)->exists(),
            403
        );
    }

    public function messages(Request $request, $chatId)
    {
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        $prepend = $request->get('prepend', 0); // 1 if load more

        $chat = Chat::with(['visitor', 'main_visitor'])->find($chatId);

        // Fetch messages
        $messages = Message::with('user')
            ->where('chat_id', $chatId)
            ->orderBy('id', 'desc')  // newest first
            ->skip($offset)
            ->take($limit)
            ->get();

        $messages->each(function (Message $message) use ($chat) {
            if ($message->user && (int) $message->user->role === 3) {
                $message->user->setAttribute('country_code', $chat?->main_visitor?->country_code);
            }
        });

        // Conditionally reverse
        if (!$prepend) {
            // Initial load: reverse so front shows oldest → newest
            $messages = $messages->reverse()->values();
        }
        // If prepend=true, keep desc → top shows newest first in that batch

        $total = Message::where('chat_id', $chatId)->count();

        return response()->json([
            'data' => $messages,
            'has_more' => ($offset + $messages->count()) < $total,
            'count' => $messages->count(),
            'url' => $chat->visitor->last_url ?? null
        ]);
    }
}
