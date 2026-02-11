<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class IssueItem extends Model implements AuditableContracts
{
    use Auditable;

    protected $fillable = [
        'issue_id',
        'product_id',
        'quantity_in',
        'quantity_out',
        'date',
    ];

    protected $casts = [
        'quantity_in' => 'decimal:2',
        'quantity_out' => 'decimal:2',
        'date' => 'date',
    ];

    public static function rules(int $id = 0, array $merge = []): array
    {
        return array_merge([
            'issue_id' => ['required', 'exists:issues,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity_in' => [
                'nullable',
                'numeric',
                'min:0',
                function (string $attribute, $value, $fail, $validator = null) {
                    $data = $validator && method_exists($validator, 'getData') ? $validator->getData() : [];
                    $otherKey = str_replace('quantity_in', 'quantity_out', $attribute);
                    $otherValue = \Illuminate\Support\Arr::get($data, $otherKey);
                    $hasIn = (float) ($value ?? 0) > 0;
                    $hasOut = (float) ($otherValue ?? 0) > 0;
                    if ($hasIn && $hasOut) {
                        $fail(__('Only one of quantity in or quantity out can be filled per item.'));
                    }
                    if (! $hasIn && ! $hasOut) {
                        $fail(__('Either quantity in or quantity out must be filled per item.'));
                    }
                },
            ],
            'quantity_out' => ['nullable', 'numeric', 'min:0'],
            'date' => ['date'],
        ], $merge);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
