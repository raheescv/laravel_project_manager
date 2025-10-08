<?php

namespace App\Livewire\Dashboard;

use App\Services\FlatTradeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class MarketInfo extends Component
{
    public $indices = [];
    public $topGainers = [];
    public $topLosers = [];
    public $topVolume = [];
    public $loading = true;
    public $error = null;
    public $exchange = 'NSE';
    public $refreshInterval = 30000; // 30 seconds

    protected $listeners = ['refreshMarketData'];

    public function mount()
    {
        $this->loadMarketData();
    }

    public function loadMarketData()
    {
        try {
            $this->loading = true;
            $this->error = null;

            // Check if user has permission
            if (!auth()->user()->can('flat_trade.view')) {
                $this->error = 'You do not have permission to view market data.';
                $this->loading = false;
                return;
            }

            $flatTradeService = new FlatTradeService();

            // Load indices data with caching
            $this->indices = Cache::remember("market_indices_{$this->exchange}", 60, function() use ($flatTradeService) {
                return $this->getIndicesData($flatTradeService);
            });

            // Load top gainers with caching
            $this->topGainers = Cache::remember("market_gainers_{$this->exchange}", 60, function() use ($flatTradeService) {
                return $this->getTopListData($flatTradeService, 'T', 'NSEALL', 'CHANGE');
            });

            // Load top losers with caching
            $this->topLosers = Cache::remember("market_losers_{$this->exchange}", 60, function() use ($flatTradeService) {
                return $this->getTopListData($flatTradeService, 'T', 'NSEALL', 'CHANGE', false);
            });

            // Load top volume with caching
            $this->topVolume = Cache::remember("market_volume_{$this->exchange}", 60, function() use ($flatTradeService) {
                return $this->getTopListData($flatTradeService, 'T', 'NSEALL', 'VOLUME');
            });

            $this->loading = false;

        } catch (\Exception $e) {
            Log::error('Market data loading failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'exchange' => $this->exchange
            ]);
            
            $this->error = 'Failed to load market data. Please try again.';
            $this->loading = false;
        }
    }

    private function getIndicesData(FlatTradeService $service)
    {
        try {
            $response = $service->getIndexList($this->exchange);
            
            // Handle different response formats
            if (isset($response['data']) && is_array($response['data'])) {
                $indices = $response['data'];
            } elseif (is_array($response)) {
                $indices = $response;
            } else {
                return [];
            }
            
            // Sort by change percentage and take top 5
            usort($indices, function($a, $b) {
                $changeA = abs($a['change_percent'] ?? 0);
                $changeB = abs($b['change_percent'] ?? 0);
                return $changeB <=> $changeA;
            });
            
            return array_slice($indices, 0, 5);
        } catch (\Exception $e) {
            Log::error('Failed to fetch indices', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getTopListData(FlatTradeService $service, $type, $basket, $criteria, $ascending = true)
    {
        try {
            $response = $service->getTopList($this->exchange, $type, $basket, $criteria);
            
            // Handle different response formats
            if (isset($response['data']) && is_array($response['data'])) {
                $data = $response['data'];
            } elseif (is_array($response)) {
                $data = $response;
            } else {
                return [];
            }
            
            if (!$ascending && $criteria === 'CHANGE') {
                // For losers, we want negative changes, so reverse the order
                usort($data, function($a, $b) {
                    return ($a['change'] ?? 0) <=> ($b['change'] ?? 0);
                });
            } elseif ($ascending && $criteria === 'CHANGE') {
                // For gainers, we want positive changes
                usort($data, function($a, $b) {
                    return ($b['change'] ?? 0) <=> ($a['change'] ?? 0);
                });
            }
            
            return array_slice($data, 0, 5);
        } catch (\Exception $e) {
            Log::error('Failed to fetch top list', [
                'error' => $e->getMessage(),
                'criteria' => $criteria
            ]);
            return [];
        }
    }

    public function refreshMarketData()
    {
        // Clear cache before refreshing
        Cache::forget("market_indices_{$this->exchange}");
        Cache::forget("market_gainers_{$this->exchange}");
        Cache::forget("market_losers_{$this->exchange}");
        Cache::forget("market_volume_{$this->exchange}");
        
        $this->loadMarketData();
    }

    public function changeExchange($exchange)
    {
        $this->exchange = $exchange;
        $this->loadMarketData();
    }

    public function render()
    {
        return view('livewire.dashboard.market-info');
    }
}
