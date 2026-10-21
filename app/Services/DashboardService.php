<?php

namespace App\Services;

use App\Models\User;
use App\Models\Message;
use App\Models\Visitor;
use App\Models\Chat;
use App\Models\HeatmapEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getWeeklyChartData(string $column = 'created_at'): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek   = Carbon::now()->endOfWeek();

        $countsByDay = User::whereBetween($column, [$startOfWeek, $endOfWeek])
            ->selectRaw("DATE($column) as date_val, COUNT(*) as count")
            ->groupBy('date_val')
            ->pluck('count', 'date_val')
            ->toArray();

        return collect(range(0, 6))->map(function ($i) use ($startOfWeek, $countsByDay) {
            $dayDate = $startOfWeek->copy()->addDays($i)->format('Y-m-d');
            return (int) ($countsByDay[$dayDate] ?? 0);
        })->toArray();
    }

    public function getWeeklyStats(string $column = 'created_at'): array
    {
        $thisWeek = $this->getWeekCount($column, Carbon::now());
        $lastWeek = $this->getWeekCount($column, Carbon::now()->subWeek());
        $totalUsers = User::count();

        return [
            'percent' => $totalUsers > 0
                ? round(($thisWeek / $totalUsers) * 100)
                : 0,

            'change' => $lastWeek > 0
                ? round((($thisWeek - $lastWeek) / $lastWeek) * 100, 1)
                : 0,
        ];
    }

    private function getWeekCount(string $column, Carbon $date): int
    {
        return User::whereBetween($column, [
            $date->copy()->startOfWeek(),
            $date->copy()->endOfWeek()
        ])->count();
    }

    public function getBestAgent(array $brandIds): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();

        // Sirf assigned brands ke chats wale agents
        $bestAgent = User::whereHas('brandss', fn($q) => $q->whereIn('brand_id', $brandIds))
            ->withCount(['messages' => fn($q) => $q->whereBetween('created_at', [$startOfMonth, $endOfMonth])])
            ->orderBy('messages_count', 'desc')
            ->first();

        if (!$bestAgent) {
            return ['name' => 'No Agent', 'messages_count' => 0, 'target_percent' => 0];
        }

        $lastMonthCount = Message::where('sender', $bestAgent->id)
            ->whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ])->count();

        return [
            'name'           => $bestAgent->name,
            'messages_count' => $bestAgent->messages_count,
            'target_percent' => $lastMonthCount > 0
                ? round(($bestAgent->messages_count / $lastMonthCount) * 100)
                : 100,
        ];
    }

    public function getQuickStats(array $brandIds): array
    {
        return [
            'visitors_today'  => Visitor::whereIn('brand_id', $brandIds)
                                    ->whereDate('created_at', today())->count(),

            'active_visitors' => Visitor::whereIn('brand_id', $brandIds)
                                    ->whereHas('user', function ($q) {
                                        $q->where('last_seen', '>=', now()->subMinutes(5));
                                    })
                                    ->count(),

            'chats_today'     => Chat::whereIn('brand_id', $brandIds)
                                    ->whereDate('created_at', today())->count(),

            'open_chats'      => Chat::whereIn('brand_id', $brandIds)
                                    ->where('status', 'open')->count(),
        ];
    }

    public function getAvgResponseTime(array $brandIds): array
    {
        $chats = Chat::with(['messages' => fn($q) => $q->orderBy('created_at')])
            ->whereIn('brand_id', $brandIds)
            ->whereDate('created_at', today())
            ->get();

        $responseTimes = [];

        foreach ($chats as $chat) {
            $customerMsg = $chat->messages->where('sender_type', 'customer')->first();
            $agentMsg    = $chat->messages->where('sender_type', 'agent')->first();

            if ($customerMsg && $agentMsg) {
                $responseTimes[] = $agentMsg->created_at->diffInSeconds($customerMsg->created_at);
            }
        }

        $avgSeconds = count($responseTimes) > 0
            ? round(array_sum($responseTimes) / count($responseTimes))
            : 0;

        return [
            'avg_seconds'     => $avgSeconds,
            'avg_formatted'   => $this->formatResponseTime($avgSeconds),
            'total_responded' => count($responseTimes),
        ];
    }

    private function formatResponseTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $mins = floor($seconds / 60);
            $secs = $seconds % 60;
            return $mins . 'm ' . $secs . 's';
        } else {
            $hours = floor($seconds / 3600);
            $mins  = floor(($seconds % 3600) / 60);
            return $hours . 'h ' . $mins . 'm';
        }
    }

    public function getVisitorAnalytics(array $brandIds): array
    {
        $totalVisitors = Visitor::whereIn('brand_id', $brandIds)->count();

        $uniqueVisitors = Visitor::whereIn('brand_id', $brandIds)
            ->distinct('session_id')
            ->count('session_id');

        $returningVisitors = Visitor::whereIn('brand_id', $brandIds)
            ->select('session_id')
            ->groupBy('session_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $topCountries = Visitor::whereIn('brand_id', $brandIds)
            ->select('country', DB::raw('count(*) as total'))
            ->groupBy('country')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $devices = Visitor::whereIn('brand_id', $brandIds)
            ->select('device', DB::raw('count(*) as total'))
            ->groupBy('device')
            ->get();

        $topPages = HeatmapEvent::whereIn('brand_id', $brandIds)
            ->select('url', DB::raw('count(*) as total'))
            ->groupBy('url')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return compact(
            'totalVisitors', 'uniqueVisitors', 'returningVisitors',
            'topCountries', 'devices', 'topPages'
        );
    }
}
