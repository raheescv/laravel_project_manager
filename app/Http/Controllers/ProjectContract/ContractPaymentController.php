<?php

namespace App\Http\Controllers\ProjectContract;

use App\Actions\ProjectContract\Payment\CreateAction as PaymentCreateAction;
use App\Actions\ProjectContract\Payment\ReverseAction;
use App\Actions\ProjectContract\PaymentTerm\CreateAction as TermCreateAction;
use App\Actions\ProjectContract\PaymentTerm\DeleteAction as TermDeleteAction;
use App\Actions\ProjectContract\PaymentTerm\UpdateAction as TermUpdateAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProjectContract\Concerns\RespondsWithContract;
use App\Http\Requests\ProjectContract\PaymentRequest;
use App\Http\Requests\ProjectContract\TermRequest;
use App\Models\ProjectContract;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Payment terms and the payments made against them. */
class ContractPaymentController extends Controller
{
    use ApiResponseTrait;
    use RespondsWithContract;

    public function storeTerm(int $id, TermRequest $request, TermCreateAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);

        return $this->result($action->execute(['project_contract_id' => $contract->id] + $request->validated(), Auth::id()), $contract->id);
    }

    public function updateTerm(int $id, int $termId, TermRequest $request, TermUpdateAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);
        $term = $contract->terms()->findOrFail($termId);

        return $this->result($action->execute($request->validated(), $term->id, Auth::id()), $contract->id);
    }

    public function destroyTerm(int $id, int $termId, TermDeleteAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);
        $term = $contract->terms()->findOrFail($termId);

        return $this->result($action->execute($term->id, Auth::id()), $contract->id);
    }

    public function storePayment(int $id, PaymentRequest $request, PaymentCreateAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);

        return $this->result($action->execute(['project_contract_id' => $contract->id] + $request->validated(), Auth::id()), $contract->id);
    }

    public function reversePayment(int $id, int $paymentId, Request $request, ReverseAction $action): JsonResponse
    {
        $contract = ProjectContract::findOrFail($id);
        $payment = $contract->payments()->findOrFail($paymentId);
        $request->validate(['reason' => ['required', 'string', 'max:255']], ['reason.required' => 'Give a reason for reversing the payment.']);

        return $this->result($action->execute($payment->id, $request->input('reason'), Auth::id()), $contract->id);
    }
}
