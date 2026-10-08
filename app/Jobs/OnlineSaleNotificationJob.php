<?php

namespace App\Jobs;

use App\Events\NotificationCreatedEvent;
use App\Models\Sale;
use App\Models\User;
use App\Notifications\OnlineSaleNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the shop a storefront order was paid through Tap: a bell notification for
 * every active admin and every user who can see Online Payments in the tenant, and
 * a live toast for those who switched browser notifications on.
 *
 * The tenant is passed explicitly — a queue worker has no request to resolve it from.
 */
class OnlineSaleNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $tenantId,
        protected int $saleId,
        protected string $link,
    ) {}

    public function handle(): void
    {
        $sale = Sale::withoutGlobalScopes()->where('tenant_id', $this->tenantId)->find($this->saleId);

        if (! $sale) {
            return;
        }

        $title = 'New Online Order';
        $message = sprintf(
            '%s paid %s for invoice #%s.',
            $sale->customer_name ?: 'A customer',
            number_format((float) $sale->grand_total, 2),
            $sale->invoice_no,
        );

        $recipients = User::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->active()
            ->get()
            ->filter(fn (User $user): bool => $user->is_admin || $user->can('sale.online payments') || $user->can('sale.view'));

        foreach ($recipients as $user) {
            $user->notify(new OnlineSaleNotification($title, $message, $this->link, $sale->id));

            if ($user->is_browser_notification_enabled) {
                event(new NotificationCreatedEvent(
                    userId: $user->id,
                    title: $title,
                    content: $message,
                    link: $this->link,
                ));
            }
        }
    }
}
