<?php

namespace App\Trading\Services;

use App\Models\TradingStrategyRun;
use App\Trading\Brokers\BrokerManager;
use App\Trading\Brokers\PaperBroker;
use App\Trading\Contracts\BrokerContract;
use App\Trading\DataObjects\OrderRequest;
use App\Trading\DataObjects\OrderResult;
use App\Trading\Events\OrderPlaced;
use App\Trading\Events\OrderRejected;
use App\Trading\Risk\RiskGate;
use Illuminate\Support\Facades\DB;

/**
 * The one and only entrypoint for placing orders.
 *
 *   Strategy → Signal → TradeExecutor::execute() → RiskGate → Broker
 *
 * Refactored commands MUST go through here so risk + alerting are uniform.
 */
class TradeExecutor
{
    public function __construct(
        private RiskGate $riskGate,
        private BrokerManager $brokers,
    ) {}

    public function execute(OrderRequest $request, array $context = [], bool $paper = false): OrderResult
    {
        $request = $request->idempotencyKey ? $request : $request->withIdempotencyKey();

        return DB::transaction(function () use ($request, $context, $paper) {
            $decision = $this->riskGate->evaluate($request, $context);

            if (! $decision->approved) {
                TradingStrategyRun::create([
                    'strategy_code' => $request->strategyCode ?? 'manual',
                    'command' => $context['command'] ?? null,
                    'symbol' => $request->symbol,
                    'action' => $request->side,
                    'outcome' => 'rejected',
                    'reason' => $decision->reason,
                    'snapshot' => ['decision' => $decision, 'order' => $request->toArray()],
                    'ran_at' => now(),
                ]);

                OrderRejected::dispatch($request, $decision);

                return OrderResult::failure($decision->reason);
            }

            $broker = $paper
                ? app(PaperBroker::class)
                : $this->brokers->broker($context['broker'] ?? null);

            $result = $broker->placeOrder($request);

            TradingStrategyRun::create([
                'strategy_code' => $request->strategyCode ?? 'manual',
                'command' => $context['command'] ?? null,
                'symbol' => $request->symbol,
                'action' => $request->side,
                'outcome' => $result->success ? ($paper ? 'paper' : 'placed') : 'broker_error',
                'reason' => $result->error,
                'snapshot' => ['order' => $request->toArray(), 'result' => $result->toArray()],
                'ran_at' => now(),
            ]);

            if ($result->success) {
                OrderPlaced::dispatch($request, $result);
            }

            return $result;
        });
    }

    public function brokerFor(string $code): BrokerContract
    {
        return $this->brokers->broker($code);
    }
}
