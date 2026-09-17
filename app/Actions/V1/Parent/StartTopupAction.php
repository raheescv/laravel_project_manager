<?php

namespace App\Actions\V1\Parent;

use App\Actions\Parent\FindStudentAction;
use App\Actions\QPay\StartPaymentAction;
use App\Exceptions\ParentPortalException;
use App\Http\Requests\V1\Parent\StartTopupRequest;
use App\Http\Resources\V1\Parent\TopupResource;
use App\Models\Guardian;
use App\Services\Payment\QPayClient;
use App\Services\TenantService;
use App\Support\Payment\QPaySettings;
use App\Support\Student\StudentSettings;

/**
 * Open a QPay top-up and hand the portal the signed form that takes the parent's
 * browser to QPay's payment page. The portal posts it as-is; nothing is credited
 * until QPay's signed result (or an inquiry) says the money was taken.
 */
class StartTopupAction
{
    public function execute(StartTopupRequest $request, Guardian $guardian, int|string $accountId): array
    {
        $student = (new FindStudentAction())->execute($guardian, $accountId);

        // Without a portal address QPay would bring the parent back to nowhere.
        if (! StudentSettings::current()->portalLink()) {
            throw new ParentPortalException('Online top-up is not available right now. Please contact the school office.');
        }

        $lang = $request->validated('lang') === 'Ar' ? 'Ar' : 'En';
        $response = (new StartPaymentAction())->execute($guardian, $student, (float) $request->validated('amount'), $lang);
        if (! $response['success']) {
            throw new ParentPortalException($response['message']);
        }

        $transaction = $response['data'];
        $settings = QPaySettings::current();

        return [
            'topup' => new TopupResource($transaction->setRelation('account', $student)),
            'payment' => [
                'url' => $settings->gatewayUrl(),
                'method' => 'POST',
                'fields' => (new QPayClient($settings))->paymentFields(
                    pun: $transaction->pun,
                    amount: (float) $transaction->amount,
                    returnUrl: $this->returnUrl($request),
                    description: 'CardTopup'.$transaction->account_id,
                    lang: $transaction->lang ?: 'En',
                    requestDate: $transaction->request_date,
                ),
            ],
        ];
    }

    /**
     * QPay posts the result to the API, which sends the browser on to the portal.
     * On a bare IP / localhost the tenant cannot be read from the host, so the
     * return carries the tenant hint the portal itself sends.
     */
    private function returnUrl(StartTopupRequest $request): string
    {
        $host = $request->getHost();
        $hint = $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)
            ? app(TenantService::class)->getCurrentTenant()?->subdomain
            : null;

        return route('api.v1.parent.qpay.return', array_filter(['tenant' => $hint]));
    }
}
