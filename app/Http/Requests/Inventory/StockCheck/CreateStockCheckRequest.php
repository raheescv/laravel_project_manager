<?php

namespace App\Http\Requests\Inventory\StockCheck;

use Illuminate\Foundation\Http\FormRequest;

class CreateStockCheckRequest extends FormRequest
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
        return [
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
