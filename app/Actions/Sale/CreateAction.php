<?php

namespace App\Actions\Sale;

use App\Actions\Student\Card\GuardCardPaymentAction;
use App\Models\Sale;
use App\Models\User;
use App\Support\Migration\BulkImport;
use Exception;

class CreateAction
{
    public $model;

    public $userId;

    public function execute($data, $userId)
    {
        $this->userId = $userId;
        try {
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id');
            $data['created_by'] = $this->userId;
            $data['invoice_no'] = $data['invoice_no'] ?? getNextSaleInvoiceNo($data['branch_id']);
            $data['source'] = $this->resolveSource($data['source'] ?? null);

            validationHelper(Sale::rules(), $data);
            $this->model = Sale::create($data);

            $this->items($data['items']);
            $this->payments($data['payments']);
            $this->comboOffers($data['comboOffers'] ?? []);

            $this->model->refresh();

            $updateData = [
                'gross_amount' => $this->model->items->sum('gross_amount'),
                'item_discount' => $this->model->items->sum('discount'),
                'tax_amount' => $this->model->items->sum('tax_amount'),
                'paid' => $this->model->payments->sum('amount'),
            ];
            if ($updateData['gross_amount'] == $this->model->gross_amount) {
                unset($updateData['gross_amount']);
            }
            if ($updateData['item_discount'] == $this->model->item_discount) {
                unset($updateData['item_discount']);
            }
            if ($updateData['tax_amount'] == $this->model->tax_amount) {
                unset($updateData['tax_amount']);
            }
            if ($updateData['paid'] == $this->model->paid) {
                unset($updateData['paid']);
            }
            if ($updateData) {
                $this->model->update($updateData);
            }
            $this->reconcileRoundOff(array_diff_key($updateData, ['paid' => true]));

            $this->model->refresh();
            $totalDiscount = ($this->model->item_discount ?? 0) + ($this->model->other_discount ?? 0);
            // Validate max_discount_per_sale
            $user = User::find($this->userId);
            if ($totalDiscount) {
                User::validateMaxDiscount($user->max_discount_per_sale, $this->model->gross_amount, $totalDiscount);
            }

            if ($this->model['status'] == 'completed') {
                $response = (new StockUpdateAction())->execute($this->model, $this->userId);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                $this->model->refresh();
                $response = (new GuardCardPaymentAction())->execute($this->model);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                $response = (new JournalEntryAction())->execute($this->model, $this->userId);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Sale';
            $return['data'] = $this->model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * Where this sale came from.
     *
     * Every caller passes its own channel explicitly (see saleSources()). Anything
     * else — an unrecognised value, or a `source` that rode in on a request body
     * because a caller hands the whole payload straight through — is discarded and
     * inferred from the request instead, so the column can never be spoofed by a
     * client and never ends up holding a value the UI cannot render.
     */
    private function resolveSource($source): string
    {
        if (is_string($source) && array_key_exists($source, saleSources())) {
            return $source;
        }

        return request()->is('api/*') ? 'api' : 'web';
    }

    private function items($data)
    {
        foreach ($data as $value) {
            $value['sale_id'] = $this->model->id;
            $response = (new Item\CreateAction())->execute($value, $this->userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }

    private function payments($data)
    {
        foreach ($data as $value) {
            $value['sale_id'] = $this->model->id;
            $value['date'] = $this->model->date;
            $response = (new Payment\CreateAction())->execute($value, $this->userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }

    private function comboOffers($data)
    {
        foreach ($data as $value) {
            $value['sale_id'] = $this->model->id;
            $response = (new ComboOffer\CreateAction())->execute($value);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }

    /**
     * The caller worked out round_off against its own totals. When the amounts
     * re-summed from the saved items differ from those (a client rounding
     * 38.745 down to 38.74 while PHP rounds it up), the stored grand_total would
     * no longer be whole — 1,300.01 against a 1,300.00 the cashier collected —
     * so the round off is recalculated from the saved totals.
     *
     * @param  array<string, mixed>  $resyncedAmounts
     */
    private function reconcileRoundOff(array $resyncedAmounts): void
    {
        if (! $resyncedAmounts || BulkImport::replayingHistory() || ! saleRoundOffEnabled()) {
            return;
        }
        $this->model->refresh();
        $base = (float) $this->model->total - (float) $this->model->other_discount + (float) $this->model->freight;
        $roundOff = saleRoundOffFor($base);
        if (round((float) $this->model->round_off, 2) != $roundOff) {
            $this->model->update(['round_off' => $roundOff]);
        }
    }
}
