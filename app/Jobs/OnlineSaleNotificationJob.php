<?php

namespace App\Jobs;

use App\Events\NotificationCreatedEvent;
use App\Models\Sale;
use App\Models\StorefrontCheckout;
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
 * a live chiming alert for those who switched browser notifications on.
 *
 * Only an order placed on the showcase / landing website (source "storefront") is
 * announced — a sale rung up in the shop never is, whoever dispatches the job.
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

        if (! $sale || $sale->source !== 'storefront') {
            return;
        }

        $details = $this->alertDetails($sale, StorefrontCheckout::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->where('sale_id', $sale->id)
            ->first());

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
                    kind: NotificationCreatedEvent::KIND_ONLINE_ORDER,
                    meta: $details,
                ));
            }
        }
    }

    /**
     * What the live alert shows at a glance, so staff know what to pack without opening it.
     *
     * @return array{sale_id: int, invoice_no: string, customer: string, mobile: string|null, amount: string, fulfilment: string|null, items: int}
     */
    private function alertDetails(Sale $sale, ?StorefrontCheckout $checkout): array
    {
        $currency = $checkout->currency ?? '';

        return [
            'sale_id' => (int) $sale->id,
            'invoice_no' => (string) $sale->invoice_no,
            'customer' => $sale->customer_name ?: 'A customer',
            'mobile' => $sale->customer_mobile ?: null,
            'amount' => trim($currency.' '.number_format((float) $sale->grand_total, 2)),
            'fulfilment' => $checkout ? ($checkout->isDelivery() ? 'Delivery' : 'Pickup') : null,
            'items' => (int) array_sum(array_column($checkout->items ?? [], 'quantity')),
        ];
    }
}
