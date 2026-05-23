<?php

namespace App\Trading\Ai;

use App\Models\TradingAiAnalysis;
use App\Models\TradingStrategyRun;
use App\Trading\Brokers\BrokerManager;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * AI post-trade analyst.
 *
 *   - dailyPostmortem(): summarizes today's command runs + trades, persists
 *     the critique as a TradingAiAnalysis row the dashboard can render.
 *   - explainDecision(): explains a single TradingStrategyRun (for chat UX).
 *
 * Uses prompt caching on the long system prompt so daily runs stay cheap.
 */
class TradeAnalyst
{
    public function __construct(private BrokerManager $brokers) {}

    public function dailyPostmortem(?\DateTimeInterface $date = null): ?TradingAiAnalysis
    {
        $date = $date ?? now()->startOfDay();
        $runs = TradingStrategyRun::query()
            ->whereDate('ran_at', $date)
            ->orderBy('ran_at')
            ->limit(500)
            ->get();

        if ($runs->isEmpty()) {
            return null;
        }

        $positions = collect();
        try {
            $positions = collect($this->brokers->broker()->positions())->map->toArray();
        } catch (\Throwable) {
            // ignore — analysis can still run
        }

        $context = [
            'date' => $date->format('Y-m-d'),
            'runs' => $runs->map(fn ($r) => [
                'time' => optional($r->ran_at)->toIso8601String(),
                'strategy' => $r->strategy_code,
                'symbol' => $r->symbol,
                'action' => $r->action,
                'outcome' => $r->outcome,
                'reason' => $r->reason,
            ])->all(),
            'positions' => $positions->all(),
        ];

        $prompt = $this->buildPostmortemPrompt($context);

        try {
            $response = OpenAI::chat()->create([
                'model' => config('trading.ai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
            ]);
            $text = $response->choices[0]->message->content ?? '';
            $tokens = $response->usage->totalTokens ?? 0;
        } catch (\Throwable $e) {
            Log::warning('AI postmortem failed', ['err' => $e->getMessage()]);
            $text = '⚠️ AI analysis unavailable: '.$e->getMessage();
            $tokens = 0;
        }

        return TradingAiAnalysis::create([
            'kind' => 'daily_postmortem',
            'for_date' => $date,
            'prompt' => $prompt,
            'response' => $text,
            'model' => config('trading.ai.model', 'gpt-4o-mini'),
            'tokens_used' => $tokens,
            'context' => $context,
        ]);
    }

    public function explainDecision(TradingStrategyRun $run): TradingAiAnalysis
    {
        $prompt = 'Explain this trading decision in 4-6 bullets. Cite the strategy code, '
            ."symbol, action, and reason given. Suggest one improvement.\n\n"
            .json_encode($run->only(['strategy_code', 'symbol', 'action', 'outcome', 'reason', 'snapshot']), JSON_PRETTY_PRINT);

        try {
            $response = OpenAI::chat()->create([
                'model' => config('trading.ai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
            ]);
            $text = $response->choices[0]->message->content ?? '';
            $tokens = $response->usage->totalTokens ?? 0;
        } catch (\Throwable $e) {
            $text = '⚠️ '.$e->getMessage();
            $tokens = 0;
        }

        return TradingAiAnalysis::create([
            'kind' => 'trade_critique',
            'symbol' => $run->symbol,
            'prompt' => $prompt,
            'response' => $text,
            'model' => config('trading.ai.model', 'gpt-4o-mini'),
            'tokens_used' => $tokens,
            'context' => ['run_id' => $run->id],
        ]);
    }

    public function chat(string $question, array $context = []): TradingAiAnalysis
    {
        $body = "User question:\n{$question}\n\nContext:\n".json_encode($context, JSON_PRETTY_PRINT);

        try {
            $response = OpenAI::chat()->create([
                'model' => config('trading.ai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $body],
                ],
                'temperature' => 0.4,
            ]);
            $text = $response->choices[0]->message->content ?? '';
            $tokens = $response->usage->totalTokens ?? 0;
        } catch (\Throwable $e) {
            $text = '⚠️ '.$e->getMessage();
            $tokens = 0;
        }

        return TradingAiAnalysis::create([
            'kind' => 'chat',
            'prompt' => $body,
            'response' => $text,
            'model' => config('trading.ai.model', 'gpt-4o-mini'),
            'tokens_used' => $tokens,
            'context' => $context,
        ]);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a disciplined Indian-markets (NSE/BSE) trading analyst.

When you review activity:
  * Tell the operator WHAT happened, WHY it likely happened, and WHAT to change.
  * Cite specific symbols, times (IST), and rule codes you see in the data.
  * Distinguish between strategy decisions and risk-gate rejections.
  * Flag any pattern of churn (rapid buy-then-sell on the same symbol).
  * Never invent numbers — if data is missing, say so.
  * Output Markdown with short sections and bullet points.

Style: concise, opinionated, kind but blunt.
PROMPT;
    }

    private function buildPostmortemPrompt(array $context): string
    {
        return "Produce a daily post-mortem for {$context['date']}. ".
            "Inputs (JSON):\n".json_encode($context, JSON_PRETTY_PRINT).
            "\n\nDeliver:\n".
            "## Day Summary\n".
            "## Best decisions\n".
            "## Worst decisions\n".
            "## Patterns to watch\n".
            "## Suggested parameter tweaks\n";
    }
}
