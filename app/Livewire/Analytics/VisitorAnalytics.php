<?php

namespace App\Livewire\Analytics;

use App\Models\Branch;
use App\Models\User;
use App\Models\Visitor;
use Carbon\Carbon;
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

    public $moduleStats;

    public $userStats;

    public $totalVisits = 0;

    public $sparklineData = [];

    public $stats = [
        'total_visitors' => 0,
        'active_users' => 0,
        'page_views' => 0,
        'weekly_change' => 0,
        'daily_change' => 0,
        'engagement_rate' => 0,
    ];

    public $trafficData = [];

    public $trafficSources = [];

    public $popularPages = [];

    public $deviceStats = [];

    protected $listeners = [
        'refreshData' => 'refreshData',
        'echo:private-visitor-analytics,.visitor.created' => 'refreshData',
    ];

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->users = User::all(['id', 'name']);
        $this->branches = Branch::all(['id', 'name']);
        $this->setDateRange($this->dateRange);
        $this->loadData();
    }

    public function setDateRange($range)
    {
        $this->dateRange = $range;

        switch ($range) {
            case '7d':
                $this->startDate = Carbon::now()->subDays(7)->startOfDay();
                $this->endDate = Carbon::now()->endOfDay();
                break;
            case '30d':
                $this->startDate = Carbon::now()->subDays(30)->startOfDay();
                $this->endDate = Carbon::now()->endOfDay();
                break;
            case 'this_month':
                $this->startDate = Carbon::now()->startOfMonth();
                $this->endDate = Carbon::now()->endOfMonth();
                break;
            default:
                $this->startDate = Carbon::now()->subDays(7)->startOfDay();
                $this->endDate = Carbon::now()->endOfDay();
        }

        $this->loadData();
    }

    public function loadData()
    {
        // Get current period stats
        $currentStats = Visitor::getVisitorStats(
            $this->startDate->toDateTimeString(),
            $this->endDate->toDateTimeString()
        );

        // Get previous period stats for comparison
        $previousStart = (clone $this->startDate)->subDays($this->endDate->diffInDays($this->startDate));
        $previousEnd = (clone $this->startDate)->subDay();
        $previousStats = Visitor::getVisitorStats(
            $previousStart->toDateTimeString(),
            $previousEnd->toDateTimeString()
        );

        // Calculate changes
        $weeklyChange = $previousStats['total_visitors'] > 0
            ? (($currentStats['total_visitors'] - $previousStats['total_visitors']) / $previousStats['total_visitors']) * 100
            : 0;

        // Get yesterday's stats for daily change
        $yesterdayStats = Visitor::getVisitorStats(
            Carbon::yesterday()->startOfDay()->toDateTimeString(),
            Carbon::yesterday()->endOfDay()->toDateTimeString()
        );

        $dailyChange = $yesterdayStats['total_visitors'] > 0
            ? (($currentStats['total_visitors'] - $yesterdayStats['total_visitors']) / $yesterdayStats['total_visitors']) * 100
            : 0;

        // Update stats
        $this->stats = array_merge($currentStats, [
            'weekly_change' => round($weeklyChange, 1),
            'daily_change' => round($dailyChange, 1),
        ]);

        // Get traffic data
        $this->trafficData = Visitor::getTrafficData(
            $this->startDate->toDateTimeString(),
            $this->endDate->toDateTimeString()
        );

        // Get popular pages
        $this->popularPages = Visitor::getPopularPages();

        // Get device stats
        $this->deviceStats = Visitor::getDeviceStats();

        // Generate sparkline data (last 20 points)
        $this->sparklineData = $this->generateSparklineData();

    }

    protected function generateSparklineData()
    {
        $data = [];
        $end = Carbon::now();
        $start = (clone $end)->subMinutes(20);

        while ($start <= $end) {
            $activeUsers = Visitor::where('visited_at', '>=', $start)
                ->where('visited_at', '<', $start->copy()->addMinute())
                ->distinct('ip_address')
                ->count('ip_address');
            $data[] = $activeUsers;
            $start->addMinute();
        }

        return $data;
    }

    public function updated($field)
    {
        if (in_array($field, ['user_id', 'branch_id'])) {
            $this->loadData();
        }
    }

    public function refreshData()
    {
        $this->loadData();
        $this->dispatch('visitorDataUpdated', [
            'trafficData' => $this->trafficData,
            'trafficSources' => $this->trafficSources,
            'sparklineData' => $this->sparklineData,
        ]);
    }

    public function render()
    {
        return view('livewire.analytics.visitor-analytics');
    }
}
