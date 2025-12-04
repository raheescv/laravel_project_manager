<?php

namespace App\Actions\Sale;

use App\Models\Sale;

class InventoryAction
{
    public function getInvoiceBySaleId($sale_id)
    {
        if (!$sale_id) {
            return [
                'success' => false,
                'items' => [],
                'message' => 'No sale id provided'
            ];
        }

        $sale = Sale::where('id', $sale_id)
            ->select('id', 'invoice_no', 'reference_no')
            ->first();

        if (!$sale) {
            return [
                'success' => false,
                'items' => [],
                'message' => 'Sale not found'
            ];
        }

        return [
            'success' => true,
            'items' => [
                [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'reference_no' => $sale->reference_no,
                ]
            ]
        ];
    }
}
