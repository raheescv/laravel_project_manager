<?php

namespace App\Actions\V1\Parent;

use App\Actions\Parent\FindStudentAction;
use App\Actions\QPay\StartPaymentAction;
use App\Actions\Student\PreOrder\ChargeAction;
use App\Exceptions\ParentPortalException;
use App\Http\Requests\V1\Parent\StartTopupRequest;
use App\Http\Resources\V1\Parent\TopupResource;
use App\Models\Account;
use App\Models\Guardian;
use App\Models\QpayTransaction;
use App\Models\StudentPreOrder;
use App\Models\StudentPreOrderCollection;
use App\Services\Payment\MpgsClient;
use App\Services\Payment\QPayApiLog;
use App\Services\Payment\QPayClient;
use App\Services\TenantService;
use App\Support\Payment\MpgsSettings;
use App\Support\Payment\QPaySettings;
use App\Support\Student\StudentSettings;

/**
 * Open a top-up and hand the portal what takes the parent's browser to the
 * payment page of the card type they chose:
 *
 * - debit (`payment.type = qpay`): the signed form the portal posts to QPay as-is;
 * - credit (`payment.type = mpgs`): a Hosted Checkout session the portal opens with
 *   the gateway's checkout.min.js (`payment.script`, `payment.session_id`).
 *
 * Nothing is credited until the gateway itself says the money was taken.
 */
class StartTopupAction
{
    public function execute(StartTopupRequest $request, Guardian $guardian, int|string $accountId): array
    {
        $student = (new FindStudentAction())->execute($guardian, $accountId);

        // Without a portal address the gateway would bring the parent back to nowhere.
        if (! StudentSettings::current()->portalLink()) {
            throw new ParentPortalException('Online top-up is not available right now. Please contact the school office.');
        }

        $lang = $request->validated('lang') === 'Ar' ? 'Ar' : 'En';
        // An older portal sends no method: the first card type the school offers.
        $gateway = QpayTransaction::METHODS[$request->validated('method') ?: (GetStudentAction::methods()[0]['key'] ?? 'debit')];
        $preOrder = $request->validated('pre_order_date') ? $this->unpaidPreOrder($student, $request->validated('pre_order_date')) : null;
        $amount = $preOrder ? ChargeAction::amountFor($preOrder) : (float) $request->validated('amount');
        $response = (new StartPaymentAction())->execute($guardian, $student, $amount, $lang, $gateway, $request->boolean('confirm_pending'), $preOrder?->id);
        if (! $response['success']) {
            // A block on an unfinished top-up carries the moment it lifts, so the portal can count
            // down to it, and that top-up's reference, so the portal can link back to its result page.
            // `confirmable`: a credit card top-up the portal may resend with confirm_pending.
            throw new ParentPortalException($response['message'], array_filter([
                'retry_at' => $response['retry_at'] ?? null,
                'pun' => $response['pun'] ?? null,
                'confirmable' => $response['confirmable'] ?? null,
            ]));
        }

        $transaction = $response['data'];

        return [
            'topup' => new TopupResource($transaction->setRelation('account', $student)),
            'payment' => $transaction->isCreditCard()
                ? $this->creditCardPage($request, $transaction, $student)
                : $this->qpayForm($request, $transaction),
        ];
    }

    /**
     * The day order the parent is paying for: still open, not paid yet. Its exact
     * total is the payment, and the payment pays it when it lands (SettleAction).
     */
    private function unpaidPreOrder(Account $student, string $date): StudentPreOrder
    {
        $order = StudentPreOrder::query()
            ->where('account_id', $student->id)
            ->where('type', StudentPreOrder::TYPE_DAY)
            ->where('status', StudentPreOrder::STATUS_ACTIVE)
            ->whereDate('date', $date)
            ->latest('id')
            ->first();

        if (! $order) {
            throw new ParentPortalException('That pre-order was not found. Please order the day again.');
        }
        if (StudentPreOrderCollection::where('student_pre_order_id', $order->id)->whereDate('date', $date)->exists()) {
            throw new ParentPortalException('This pre-order is already paid.');
        }
        if (StudentSettings::current()->preOrderLocked($order->date)) {
            throw new ParentPortalException('Orders for that day have closed.');
        }

        return $order;
    }

    private function qpayForm(StartTopupRequest $request, QpayTransaction $transaction): array
    {
        $settings = QPaySettings::current();
        $fields = (new QPayClient($settings))->paymentFields(
            pun: $transaction->pun,
            amount: (float) $transaction->amount,
            returnUrl: $this->apiUrl($request, 'api.v1.parent.qpay.return'),
            description: 'CardTopup'.$transaction->account_id,
            lang: $transaction->lang ?: 'En',
            requestDate: $transaction->request_date,
        );

        // The browser posts it, so the row stays pending until QPay's result comes back (HandleReturnAction).
        QPayApiLog::start(QPayApiLog::PAYMENT, $settings->gatewayUrl(), $fields, $settings->merchantId);

        return [
            'type' => 'qpay',
            'url' => $settings->gatewayUrl(),
            'method' => 'POST',
            'fields' => $fields,
        ];
    }

    /**
     * INITIATE_CHECKOUT, then the session the portal opens the gateway's page with.
     * A session the gateway would not open leaves no payment behind: the row is
     * closed as failed at once, since no card ever reached the gateway.
     */
    private function creditCardPage(StartTopupRequest $request, QpayTransaction $transaction, Account $student): array
    {
        $settings = MpgsSettings::current();

        try {
            $session = (new MpgsClient($settings))->initiateCheckout(
                orderId: $transaction->pun,
                amount: (float) $transaction->amount,
                description: 'Card top-up for '.$student->name,
                returnUrl: $this->apiUrl($request, 'api.v1.parent.mpgs.return', ['pun' => $transaction->pun]),
                cancelUrl: $this->apiUrl($request, 'api.v1.parent.mpgs.cancel', ['pun' => $transaction->pun]),
                locale: $transaction->lang === 'Ar' ? 'ar' : 'en',
                notificationUrl: $settings->receivesNotifications() ? $this->apiUrl($request, 'api.v1.parent.mpgs.notification') : null,
            );
        } catch (\Throwable $th) {
            report($th);
            $transaction->forceFill([
                'status' => QpayTransaction::STATUS_FAILED,
                'failure_reason' => 'The card payment page could not be opened: '.$th->getMessage(),
                'completed_at' => now(),
            ])->save();

            throw new ParentPortalException('The card payment page could not be opened. Please try again, or pay by debit card.');
        }

        $transaction->forceFill(['payload' => ['session_id' => $session['session_id']]])->save();

        return [
            'type' => 'mpgs',
            'script' => $settings->checkoutScriptUrl(),
            'session_id' => $session['session_id'],
        ];
    }

    /**
     * The gateway sends the parent back through the API, which forwards to the
     * portal. On a bare IP / localhost the tenant cannot be read from the host, so
     * the URL carries the tenant hint the portal itself sends.
     */
    private function apiUrl(StartTopupRequest $request, string $route, array $parameters = []): string
    {
        $host = $request->getHost();
        $hint = $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)
            ? app(TenantService::class)->getCurrentTenant()?->subdomain
            : null;

        return route($route, array_filter($parameters + ['tenant' => $hint]));
    }
}
