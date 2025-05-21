<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Visitor extends Model
{
    protected $fillable = [
        'branch_id',
        'user_id',
        'user_name',
        'ip_address',
        'user_agent',
        'url',
        'visited_at',
        'device_type',
        'browser',
        'os',
    ];

    public $timestamps = false;

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getVisitorStats($startDate, $endDate)
    {
        $totalVisitors = self::whereBetween('visited_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count('user_id');

        $activeUsers = self::where('visited_at', '>=', now()->subMinutes(5))
            ->distinct('user_id')
            ->count('user_id');

        $pageViews = self::whereBetween('visited_at', [$startDate, $endDate])->count();

        return [
            'total_visitors' => $totalVisitors,
            'active_users' => $activeUsers,
            'page_views' => $pageViews,
        ];
    }

    public static function getTrafficData($startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $visitors = self::select(DB::raw('DATE(visited_at) as date'), DB::raw('COUNT(DISTINCT user_id) as count'))
            ->whereBetween('visited_at', [$start, $end])
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $result = [];
        while ($start <= $end) {
            $dateStr = $start->format('Y-m-d');
            $result[$dateStr] = $visitors[$dateStr] ?? 0;
            $start->addDay();
        }

        return $result;
    }

    public static function getPopularPages()
    {
        $pages = self::select('url', DB::raw('COUNT(*) as views'))
            ->where('visited_at', '>=', now()->subDays(30))
            ->groupBy('url')
            ->orderByDesc('views')
            ->limit(5)
            ->get();

        return $pages->map(function ($page) {
            return [
                'url' => $page->url,
                'views' => $page->views,
            ];
        })->toArray();
    }

    public static function getDeviceStats()
    {
        $stats = self::select('device_type', DB::raw('COUNT(DISTINCT user_id) as users'), DB::raw('COUNT(*) as sessions'))
            ->where('visited_at', '>=', now()->subDays(30))
            ->groupBy('device_type')
            ->get();

        return $stats->map(function ($stat) {
            return [
                'device' => $stat->device_type,
                'users' => $stat->users,
                'sessions' => $stat->sessions,
            ];
        })->toArray();
    }

    public static function getOnlineActiveUsers($user_id = null, $branch_id = null)
    {
        $query = self::with('user')
            ->select('user_id', 'user_name')
            ->selectRaw('COUNT(*) as sessions_count')
            ->selectRaw('MAX(visited_at) as last_active_at')
            ->whereNotNull('user_id');

        if ($user_id) {
            $query->where('user_id', $user_id);
        }

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        return $query->groupBy('user_id', 'user_name')
            ->orderByDesc('last_active_at')
            ->get()
            ->map(function ($visitor) {
                return [
                    'user_id' => $visitor->user_id,
                    'name' => $visitor->user_name,
                    'last_active_at' => $visitor->last_active_at,
                    'sessions_count' => $visitor->sessions_count,
                    'is_online' => $visitor->visited_at >= now()->subMinutes(5),
                ];
            });
    }

    public static function getUserActivity($userId, $limit = 20)
    {
        return self::where('user_id', $userId)
            ->select('url', 'visited_at', 'device_type', 'browser', 'ip_address', 'os')
            ->orderByDesc('visited_at')
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'url' => $activity->url,
                    'os' => $activity->os,
                    'visited_at' => $activity->visited_at,
                    'ip_address' => $activity->ip_address,
                    'device_type' => $activity->device_type,
                    'browser' => $activity->browser,
                    'time_ago' => $activity->visited_at->diffForHumans(),
                ];
            });
    }

    public static function getTopUserActivities($startDate = null, $endDate = null, $limit = 5)
    {
        $query = self::with('user')
            ->select('user_id', 'user_name')
            ->selectRaw('COUNT(*) as total_visits')
            ->selectRaw('MAX(visited_at) as last_visit')
            ->whereNotNull('user_id');

        if ($startDate && $endDate) {
            $query->whereBetween('visited_at', [$startDate, $endDate]);
        }

        return $query->groupBy('user_id', 'user_name')
            ->orderByDesc('total_visits')
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'user_id' => $activity->user_id,
                    'name' => $activity->user_name,
                    'total_visits' => $activity->total_visits,
                    'last_visit' => $activity->last_visit,
                    'last_visit_ago' => Carbon::parse($activity->last_visit)->diffForHumans(),
                ];
            });
    }
}
