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
}
