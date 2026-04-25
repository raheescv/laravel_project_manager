<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $branchId = (int) $this->session()->get('branch_id');

        return [
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => [
                'required',
                Rule::exists('inventories', 'id')->where(function (Builder $query) use ($branchId): void {
                    $query->where('branch_id', $branchId)
                        ->whereNull('employee_id');
                }),
            ],
            'items.*.quantity' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
        ];
    }
}
