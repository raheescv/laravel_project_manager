<?php

namespace App\Http\Requests\V1\Sale;

/**
 * The Items tab of the mobile Sales list: the same filters as the invoice list,
 * applied to the sales the lines belong to, with sorts that make sense per line.
 */
class ItemIndexRequest extends IndexRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'sort_by' => ['nullable', 'string', 'in:date,total,quantity,id'],
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'sort_by.in' => 'The sort by field must be one of: date, total, quantity, id.',
        ]);
    }
}
