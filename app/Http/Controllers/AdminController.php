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
        $historyView = request()->query('view') === 'history';
        $chats = $this->chatListQuery($historyView)->get();

        $messages = collect();
        $chatId = null;

        return view('admin.chat', compact('messages', 'chatId', 'chats', 'historyView'));
    }

    public function chatContacts(Request $request)
    {
        $historyView = $request->query('view') === 'history';

        return view('admin.chat._contacts', [
            'chats' => $this->chatListQuery($historyView)->get(),
            'historyView' => $historyView,
        ]);
    }

    private function chatListQuery(bool $historyView)
    {
        $onlineSince = now()->subMinutes(3);
        $userBrandIds = auth()->user()->auth_brands->pluck('id')->toArray();

        $query = Chat::with(['visitor', 'main_visitor'])
            ->withMax('messages', 'created_at')
            ->when(auth()->user()->role != 1, function ($query) use ($userBrandIds) {
                $query->whereHas('visitor', function ($visitorQuery) use ($userBrandIds) {
                    $visitorQuery->whereIn('brand_id', $userBrandIds);
                });
            })
            ->whereHas('main_visitor', function ($query) use ($historyView, $onlineSince) {
                if ($historyView) {
                    $query->whereNull('last_seen_at')
                        ->orWhere('last_seen_at', '<', $onlineSince);
                } else {
                    $query->where('last_seen_at', '>=', $onlineSince);
                }
            });

        if (!$historyView) {
            $query->where('status', 'open');
        }

        return $query->latest();
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
        $chat = Chat::with(['visitor', 'main_visitor'])->findOrFail($chatId);
        $readOnly = request()->boolean('past');
        $visitorId = Chat::where('id', $chatId)->value('visitor_id');
        $user = User::with('get_chat')->where('visitor_id', $visitorId)->first();

        return view('admin.chat.messages', compact('chatId', 'user', 'chat', 'readOnly'));
    }

    public function chatProfile($chatId)
    {
        $chat = Chat::with('main_visitor')->findOrFail($chatId);
        $this->authorizeChatProfile($chat);

        $visitor = $chat->main_visitor;
        abort_unless($visitor, 404);
        $user = User::where('visitor_id', $chat->visitor_id)->firstOrFail();
        $pastChatsBefore = now()->subWeek();
        $currentChat = Chat::where('visitor_id', $chat->visitor_id)
            ->where('brand_id', $chat->brand_id)
            ->where('status', 'open')
            ->withMax('messages', 'created_at')
            ->get()
            ->sortByDesc(fn (Chat $currentChat) => $this->chatActivityAt($currentChat))
            ->first();

        $chatIds = Chat::where('visitor_id', $chat->visitor_id)
            ->where('brand_id', $chat->brand_id)
            ->select('id');
        $pastMessageCount = Message::whereIn('chat_id', $chatIds)
            ->where('created_at', '<=', $pastChatsBefore)
            ->where(function ($query) {
                $query->whereNotNull('sender')->orWhere('is_ai', true);
            })
            ->count();

        $pastChatQuery = Chat::where('visitor_id', $chat->visitor_id)
            ->where('brand_id', $chat->brand_id)
            ->where(function ($query) use ($pastChatsBefore) {
                $query->whereHas('messages', fn ($messages) => $messages->where('created_at', '<=', $pastChatsBefore))
                    ->orWhere(function ($noMessages) use ($pastChatsBefore) {
                        $noMessages->whereDoesntHave('messages')
                            ->where(function ($activity) use ($pastChatsBefore) {
                                $this->wherePastChatActivity($activity, $pastChatsBefore);
                            });
                    });
            });
        $pastChats = $pastChatQuery
            ->withMax([
                'messages as past_messages_max_created_at' => fn ($messages) => $messages->where('created_at', '<=', $pastChatsBefore),
            ], 'created_at')
            ->get()
            ->map(function ($pastChat) {
                return [
                    'id' => $pastChat->id,
                    'status' => $pastChat->status === 'open' ? 'closed' : $pastChat->status,
                    'date' => optional($pastChat->past_messages_max_created_at
                        ? Carbon::parse($pastChat->past_messages_max_created_at)
                        : $this->chatActivityAt($pastChat))->toIso8601String(),
                    'preview' => $pastChat->messages()
                        ->whereNotNull('sender')
                        ->where('created_at', '<=', now()->subWeek())
                        ->latest()
                        ->value('message') ?? 'No messages',
                ];
            })
            ->sortByDesc('date')
            ->values();

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
            'past_message_count' => $pastMessageCount,
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

    private function wherePastChatActivity($query, Carbon $cutoff): void
    {
        $query->where(function ($activity) use ($cutoff) {
            $activity->whereNull('last_visitor_activity_at')
                ->orWhere('last_visitor_activity_at', '<=', $cutoff);
        })->where(function ($activity) use ($cutoff) {
            $activity->whereNull('last_agent_activity_at')
                ->orWhere('last_agent_activity_at', '<=', $cutoff);
        })->whereDoesntHave('messages', fn ($messages) => $messages->where('created_at', '>', $cutoff))
            ->where(function ($fallback) use ($cutoff) {
                $fallback->whereNotNull('last_visitor_activity_at')
                    ->orWhereNotNull('last_agent_activity_at')
                    ->orWhereHas('messages')
                    ->orWhereRaw('COALESCE(closed_at, updated_at) <= ?', [$cutoff]);
            });
    }

    private function chatActivityAt(Chat $chat): ?Carbon
    {
        $timestamps = collect([
            $chat->last_visitor_activity_at,
            $chat->last_agent_activity_at,
            $chat->messages_max_created_at ?? null,
        ])->filter()->map(fn ($timestamp) => Carbon::parse($timestamp));

        return $timestamps->isNotEmpty()
            ? $timestamps->max()
            : ($chat->closed_at ?? $chat->updated_at);
    }

    public function messages(Request $request, $chatId)
    {
        $limit = max(1, min(100, (int) $request->get('limit', 20)));
        $offset = max(0, (int) $request->get('offset', 0));
        $prepend = $request->boolean('prepend');
        $cutoff = now()->subWeek();

        $chat = Chat::with(['visitor', 'main_visitor'])->find($chatId);

        $messageQuery = Message::with('user')
            ->where('chat_id', $chatId);

        if ($request->boolean('past')) {
            $messageQuery->where('created_at', '<=', $cutoff);
        } else {
            $messageQuery->where('created_at', '>', $cutoff);
        }

        $total = (clone $messageQuery)->count();
        $messages = $messageQuery
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

        return response()->json([
            'data' => $messages,
            'has_more' => ($offset + $messages->count()) < $total,
            'count' => $messages->count(),
            'url' => $chat->visitor->last_url ?? null
        ]);
    }
}