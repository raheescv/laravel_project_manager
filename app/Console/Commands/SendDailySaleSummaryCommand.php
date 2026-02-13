<?php

namespace App\Console\Commands;

use App\Helpers\SaleHelper;
use App\Helpers\TelegramHelper;
use App\Models\SaleDaySession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\User;

class SendDailySaleSummaryCommand extends Command
{
    protected $signature = 'send:daily-sale-summary
                            {id? : Sale day session ID (e.g. 18). If omitted, uses the latest closed session.}';

    protected $description = 'Generate day session report as PDF and send it to Telegram';

    public function handle(TelegramHelper $telegram, SaleHelper $saleHelper): int
    {
        $sessionId = $this->argument('id');

        $session = $sessionId
            ? SaleDaySession::withoutGlobalScopes()->with(['branch', 'opener', 'closer'])->find($sessionId)
            : SaleDaySession::withoutGlobalScopes()->closed()->with(['branch', 'opener', 'closer'])->orderByDesc('closed_at')->first();

        if (! $session) {
            $this->error($sessionId ? "Sale day session with ID {$sessionId} not found." : 'No closed sale day session found.');
            return self::FAILURE;
        }

        $this->info("Generating PDF for day session #{$session->id} (branch: {$session->branch->name})...");

        $view = $saleHelper->daySessionReportView($session);
        $html = $view->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait'); // 80mm width in points (80*2.83465), height auto
        $pdf->setOption('margin-top', 0);
        $pdf->setOption('margin-right', 0);
        $pdf->setOption('margin-bottom', 0);
        $pdf->setOption('margin-left', 0);

        $filename = 'sale-day-session-report-'.$session->id.'-'.now()->format('Y-m-d').'.pdf';
        $path = storage_path('app/temp/'.$filename);
        File::ensureDirectoryExists(dirname($path));
        $pdf->save($path);

        try {
            $caption = sprintf(
                'Daily sale summary — Session #%d — %s — %s',
                $session->id,
                $session->branch->name,
                $session->closed_at?->format('d M Y H:i') ?? 'N/A'
            );

            User::where('is_telegram_enabled', true)->each(function ($user) use ($telegram, $path, $caption) {
                $telegram->sendDocumentToChatId($user->telegram_chat_id, $path, $caption);
            });

            File::delete($path);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (File::exists($path)) {
                File::delete($path);
            }
            $this->error('Error: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
