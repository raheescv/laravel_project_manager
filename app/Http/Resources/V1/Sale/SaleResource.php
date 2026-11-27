<?php

namespace App\Http\Resources\V1\Sale;

use App\Actions\Student\GetBalanceAction;
use App\Helpers\SaleHelper;
use App\Models\Configuration;
use App\Models\Sale;
use App\Models\StudentDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Sale
 */
class SaleResource extends JsonResource
{
    /**
     * What every endpoint returning this resource eager-loads — the receipt
     * block needs the variant columns, units, categories and payment aliases.
     */
    public const RELATIONS = [
        'items.product:id,name,code,name_arabic,type,description,size,color,variant_value,main_category_id',
        'items.product.mainCategory:id,name',
        'items.unit:id,name',
        'items.options',
        'items.employee:id,name',
        'payments.paymentMethod:id,name,alias_name',
        'account:id,name,mobile,email,tax_no',
        'createdUser:id,name',
        'branch',
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'invoice_no' => $this->invoice_no,
            // The biller's own `{code}/{n}` number printed on the "office_bill" receipt.
            'bill_no' => $this->bill_no,
            // Its `n`, so the till can count on from it while it is offline.
            'staff_bill_sequence' => $this->staff_bill_sequence,
            // Doubles as the provisional reference printed on the receipt when the
            // sale was rung up offline; `client_uuid` below is what tells the app
            // which of the two this is.
            'reference_no' => $this->reference_no,
            // Echoed back so the app can retire the matching row from its
            // offline outbox — including when this response is a replay of a
            // sale an earlier attempt already committed.
            'client_uuid' => $this->client_uuid,
            'date' => $this->date,
            'status' => $this->status,
            'notes' => $this->notes,
            'branch' => $this->branch?->name,
            'customer' => [
                'name' => $this->customer_name ?: $this->account?->name,
                'mobile' => $this->customer_mobile ?: $this->account?->mobile,
            ],
            'items' => $this->items->map(fn ($item) => [
                // Edit round-trip ids — the app sends these back so an update
                // patches the existing rows instead of creating duplicates.
                'id' => (int) $item->id,
                'product_id' => (int) $item->product_id,
                'employee_id' => $item->employee_id ? (int) $item->employee_id : null,
                'code' => $item->product?->code,
                // "mulak pack - Soda / Mango": the variant's layers after the family name.
                'name' => $item->product?->displayName(),
                'name_arabic' => $item->product?->name_arabic,
                // As the web invoice prints it (print_item_label: product, or "code) category").
                'print_name' => $this->printItemLabel() === 'product'
                    ? $item->product?->displayName()
                    : $item->product?->code.') '.$item->product?->mainCategory?->name,
                'print_name_arabic' => $this->printItemLabel() === 'product' ? $item->product?->name_arabic : $item->product?->mainCategory?->name_arabic,
                'description' => $item->product?->description ? Str::limit($item->product->description, 30) : null,
                'unit' => $item->unit?->name,
                'type' => $item->product?->type,
                'employee' => $item->employee?->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'tax' => (float) $item->tax,
                'total' => round((float) $item->unit_price * (float) $item->quantity - (float) $item->discount, 2),
                // The stored line total (after tax) and its share of the bill discount — the
                // two amounts the web invoice prints with discounts shown or hidden.
                'line_total' => (float) $item->total,
                'effective_total' => (float) ($item->effective_total ?? $item->total),
                // Picked options (base, flavour, add-ons…), printed under the line.
                'options' => $item->options->map->toLine()->values(),
            ])->values(),
            'payments' => $this->payments->map(fn ($payment) => [
                'id' => (int) $payment->id,
                'payment_method_id' => $payment->payment_method_id ? (int) $payment->payment_method_id : null,
                'method' => $payment->paymentMethod?->name,
                'alias' => $payment->paymentMethod?->alias_name,
                'amount' => (float) $payment->amount,
            ])->values(),
            'summary' => [
                'gross_amount' => (float) $this->gross_amount,
                'item_discount' => (float) $this->item_discount,
                'other_discount' => (float) $this->other_discount,
                'tax_amount' => (float) $this->tax_amount,
                'tip' => (float) $this->tip,
                'round_off' => (float) $this->round_off,
                'grand_total' => (float) $this->grand_total,
                'paid' => (float) $this->paid,
                'balance' => (float) $this->balance,
            ],
            'created_by' => $this->createdUser?->name,
            'created_at' => $this->created_at?->toIso8601String(),
            // Present for a student's sale: the card balance after it, for the receipt.
            'student' => $this->studentCard(),
            'receipt' => $this->receipt(),
        ];
    }

    /**
     * Everything else the web invoice (resources/views/sale/print.blade.php)
     * prints, so the app's on-device receipt is the same document.
     *
     * @return array<string, mixed>
     */
    private function receipt(): array
    {
        $grandTotal = str_pad(intval($this->grand_total).'00', 6, '0', STR_PAD_LEFT);
        $showMobile = (Configuration::where('key', 'enable_customer_mobile_in_print')->value('value') ?? 'yes') === 'yes';

        return [
            'branch_location' => $this->branch?->location,
            'branch_mobile' => $this->branch?->mobile,
            'branch_email' => $this->branch?->email,
            'gst_no' => Configuration::where('key', 'gst_no')->value('value'),
            'customer_mobile' => $showMobile ? $this->account?->mobile : null,
            'customer_email' => $this->account?->email,
            'customer_tax_no' => $this->account?->tax_no,
            'barcode' => SaleHelper::barcodeString($grandTotal),
            'qr' => url('/').'/invoice/'.$this->id,
            'served_by' => $this->employeeNames(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'currency_code' => (string) tenant_cache('currency_code', ''),
            'currency_symbol' => (string) tenant_cache('currency_symbol', ''),
        ];
    }

    private function printItemLabel(): string
    {
        return $this->printItemLabel ??= Configuration::where('key', 'print_item_label')->value('value') ?? 'product';
    }

    private ?string $printItemLabel = null;

    private function studentCard(): ?array
    {
        $detail = $this->account_id ? StudentDetail::where('account_id', $this->account_id)->first(['account_id', 'admission_no', 'grade', 'section']) : null;
        if (! $detail) {
            return null;
        }

        return [
            'account_id' => (int) $detail->account_id,
            'admission_no' => $detail->admission_no,
            'class' => $detail->classLabel(),
            'card_balance' => (new GetBalanceAction())->execute((int) $detail->account_id),
        ];
    }
}
