<?php

namespace App\Actions\Student\PreOrder;

use App\Mail\AppointmentMail;
use App\Models\EmailLog;
use App\Support\Student\StudentSettings;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Email the list of [$date]'s pre-orders (SummaryAction) to the addresses in
 * Settings → Student Cards → Canteen. Each address gets its own message and its
 * own Email Log row, so one bad address does not stop the rest.
 */
class SendSummaryAction
{
    public const MODULE = 'student';

    public const TYPE = 'pre_order_summary';

    public function execute(Carbon $date): array
    {
        try {
            $settings = StudentSettings::current();
            if ($settings->preOrderSummaryEmails === []) {
                throw new Exception('Add who receives the pre-order summary in Settings → Student Cards → Canteen first.', 1);
            }

            $summary = (new SummaryAction())->execute($date);
            $subject = 'Pre-orders for '.$date->format('D j M').' · '.$summary['students'].' '.Str::plural('student', $summary['students']);
            $body = view('mail.student.pre-order-summary', [
                'summary' => $summary,
                'date' => $date,
                'cutoff' => $settings->preOrderCutoffLabel(),
                'currency' => base_currency()['code'] ?? 'QAR',
                'decimals' => currency_decimals(),
            ])->render();

            $sent = [];
            $failed = [];
            foreach ($settings->preOrderSummaryEmails as $email) {
                $log = EmailLog::create([
                    'module' => self::MODULE,
                    'type' => self::TYPE,
                    'to_email' => $email,
                    'subject' => $subject,
                    'body' => $body,
                    'status' => 'queued',
                ]);
                try {
                    // LogSentEmail flips the row to "sent" once the message is out.
                    Mail::to($email)->send(new AppointmentMail(
                        subjectLine: $subject,
                        bodyHtml: $body,
                        companyName: tenant_cache('company_name', '') ?? '',
                        logId: $log->id,
                    ));
                    $sent[] = $email;
                } catch (\Throwable $th) {
                    $log->update(['status' => 'failed', 'error' => $th->getMessage()]);
                    Log::warning('Pre-order summary email failed', ['to' => $email, 'error' => $th->getMessage()]);
                    $failed[] = $email;
                }
            }

            if ($sent === []) {
                throw new Exception('The pre-order summary could not be sent to '.implode(', ', $failed).'.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Pre-order summary sent to '.implode(', ', $sent).($failed ? ' (failed: '.implode(', ', $failed).')' : '');
            $return['data'] = $summary;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
