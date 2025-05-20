<?php

namespace App\Livewire\Analytics;

use App\Models\Branch;
use App\Models\User;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class VisitorAnalytics extends Component
{
    use WithPagination;

    public $dateRange = '7d';

    public $startDate;

    public $endDate;

    public $user_id;

    public $branch_id;

    public $users;

    public $branches;

    public $stats = [
        'total_visitors' => 0,
        'active_users' => 0,
        'page_views' => 0,
        'weekly_change' => 0,
        'daily_change' => 0,
        'engagement_rate' => 0,
    ];

    public $trafficData = [];

    public $popularPages = [];

    public $deviceStats = [];

    protected $queryString = ['dateRange', 'user_id', 'branch_id'];

    protected $listeners = [
        'refreshData' => 'loadData',
        'echo:private-visitor-analytics,.visitor.created' => 'loadData',
    ];

    public function mount()
    {
        // Cache users and branches for 1 hour since they don't change often
        $this->users = Cache::remember('analytics_users', 3600, function () {
            return User::select(['id', 'name'])->get();
        });

        $this->branches = Cache::remember('analytics_branches', 3600, function () {
            return Branch::select(['id', 'name'])->get();
        });

        $this->setDateRange($this->dateRange);
    }

    public function updated($key, $value)
    {
        if ($key == 'user_id' || $key == 'branch_id') {
            $this->loadData();
        }
    }

    public function setDateRange($range)
    {
        if (! in_array($range, ['7d', '30d', 'this_month'])) {
            $range = '7d';
        }

        $this->dateRange = $range;

        switch ($range) {
            case '30d':
                $this->startDate = Carbon::now()->subDays(30)->startOfDay();
                $this->endDate = Carbon::now()->endOfDay();
                break;
            case 'this_month':
                $this->startDate = Carbon::now()->startOfMonth();
                $this->endDate = Carbon::now()->endOfMonth();
                break;
            default: // 7d
                $this->startDate = Carbon::now()->subDays(7)->startOfDay();
                $this->endDate = Carbon::now()->endOfDay();
        }

        $this->loadData();
    }

    public function loadData()
    {
        $cacheKey = "analytics_{$this->dateRange}_{$this->user_id}_{$this->branch_id}";

        $data = Cache::remember($cacheKey, 1, function () {
            $query = Visitor::query()
                ->when($this->user_id, fn ($q) => $q->where('user_id', $this->user_id))
                ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id));

            // Get current period stats
            $currentStats = $this->getVisitorStats($query->clone(), $this->startDate, $this->endDate);
            // Get previous period stats
            $previousStart = (clone $this->startDate)->subDays($this->endDate->diffInDays($this->startDate));
            $previousEnd = (clone $this->startDate)->subDay();
            $previousStats = $this->getVisitorStats($query->clone(), $previousStart, $previousEnd);

            // Get yesterday's stats
            $yesterdayStats = $this->getVisitorStats(
                $query->clone(),
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay()
            );

            // Calculate changes
            $weeklyChange = $previousStats['total_visitors'] > 0
                ? (($currentStats['total_visitors'] - $previousStats['total_visitors']) / $previousStats['total_visitors']) * 100
                : 0;

            $dailyChange = $yesterdayStats['total_visitors'] > 0
                ? (($currentStats['total_visitors'] - $yesterdayStats['total_visitors']) / $yesterdayStats['total_visitors']) * 100
                : 0;

            // Get traffic data (visitors per day)
            $trafficData = [];
            $period = Carbon::parse($this->startDate)->toPeriod($this->endDate);
            foreach ($period as $date) {
                $trafficData[$date->format('Y-m-d')] = 0;
            }
            $dailyTraffic = $query->clone()
                ->selectRaw('DATE(visited_at) as date, COUNT(DISTINCT user_id) as visitors')
                ->whereBetween('visited_at', [$this->startDate, $this->endDate])
                ->groupBy('date')
                ->pluck('visitors', 'date')
                ->toArray();

            // Merge all dates with actual traffic data
            $trafficData = array_merge($trafficData, $dailyTraffic);

            $trafficChartData = [
                'labels' => array_keys($trafficData),
                'datasets' => [
                    ['data' => array_values($trafficData), 'label' => 'Visitors', 'fill' => true, 'tension' => 0.4],
                ],
            ];

            return [
                'stats' => array_merge($currentStats, [
                    'weekly_change' => round($weeklyChange, 1),
                    'daily_change' => round($dailyChange, 1),
                ]),
                'trafficData' => $trafficChartData,
                'popularPages' => $this->getPopularPages($query),
                'deviceStats' => $this->getDeviceStats($query),
            ];
        });

        // Update component properties with cached data
        $this->stats = $data['stats'];
        $this->trafficData = $data['trafficData'];
        $this->popularPages = $data['popularPages'];
        $this->deviceStats = $data['deviceStats'];
        $this->dispatch('visitorDataUpdated', $this->trafficData);
    }

    protected function getVisitorStats($query, $start, $end)
    {
        $result = $query->clone()
            ->whereBetween('visited_at', [$start, $end])
            ->selectRaw('
                COUNT(DISTINCT user_id) as total_visitors,
                COUNT(DISTINCT user_id) as active_users,
                COUNT(*) as page_views
            ')
            ->first();

        $engagementRate = $result->total_visitors > 0
            ? ($result->page_views / $result->total_visitors) * 100
            : 0;

        return [
            'total_visitors' => $result->total_visitors,
            'active_users' => $result->active_users,
            'page_views' => $result->page_views,
            'engagement_rate' => round($engagementRate, 1),
        ];
    }

    protected function getPopularPages($query)
    {
        return $query->clone()
            ->whereBetween('visited_at', [$this->startDate, $this->endDate])
            ->selectRaw('url, COUNT(*) as views')
            ->groupBy('url')
            ->orderByDesc('views')
            ->limit(5)
            ->get()
            ->toArray();
    }

    protected function getDeviceStats($query)
    {
        return $query->clone()
            ->whereBetween('visited_at', [$this->startDate, $this->endDate])
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();
    }

    public function render()
    {
        return view('livewire.analytics.visitor-analytics');
    }
}
