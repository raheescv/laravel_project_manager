<?php

namespace App\Providers;

use App\Services\FlatTradeService;
use App\Trading\Alerts\AlertDispatcher;
use App\Trading\Alerts\Channels\DatabaseChannel;
use App\Trading\Alerts\Channels\LogChannel;
use App\Trading\Alerts\Channels\TelegramChannel;
use App\Trading\Brokers\BrokerManager;
use App\Trading\Brokers\FlatTradeBrokerAdapter;
use App\Trading\Brokers\KiteBrokerAdapter;
use App\Trading\Brokers\PaperBroker;
use App\Trading\Risk\RiskGate;
use App\Trading\Strategies\MeanReversionStrategy;
use App\Trading\Strategies\MomentumScoreStrategy;
use App\Trading\Strategies\StrategyRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Wires up the Trading domain: brokers, strategies, alert channels,
 * the RiskGate, and the event subscriber. Registered in bootstrap/providers.php.
 */
class TradingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Config defaults.
        $this->mergeConfigFrom(__DIR__.'/../../config/trading.php', 'trading');

        // Singletons for the domain.
        $this->app->singleton(RiskGate::class, fn () => new RiskGate());

        $this->app->singleton(BrokerManager::class, function ($app) {
            $manager = new BrokerManager();

            $manager->register(new PaperBroker(startingFunds: 1_000_000.0));

            try {
                if ($app->bound(FlatTradeService::class) || class_exists(FlatTradeService::class)) {
                    $manager->register(new FlatTradeBrokerAdapter($app->make(FlatTradeService::class)), default: true);
                }
            } catch (\Throwable) {
                // ignore — FlatTrade may not be configured
            }

            $manager->register(new KiteBrokerAdapter());

            return $manager;
        });

        $this->app->singleton(StrategyRegistry::class, function () {
            $registry = new StrategyRegistry();
            $registry->register(new MomentumScoreStrategy());
            $registry->register(new MeanReversionStrategy());

            return $registry;
        });

        $this->app->singleton(AlertDispatcher::class, function () {
            $dispatcher = new AlertDispatcher();
            $dispatcher->registerChannel(new DatabaseChannel());
            $dispatcher->registerChannel(new LogChannel());
            // Telegram only registers if the SDK is bound.
            if (class_exists(\Telegram\Bot\Laravel\Facades\Telegram::class)) {
                $dispatcher->registerChannel(new TelegramChannel());
            }

            return $dispatcher;
        });
    }

    public function boot(): void
    {
        // Wire the domain-event subscriber.
        Event::subscribe(\App\Listeners\Trading\BroadcastTradingEvent::class);
    }
}
