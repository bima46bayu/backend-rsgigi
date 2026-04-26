<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'exists:suppliers,id'
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000'
            ],

            'items' => [
                'required',
                'array',
                'min:1'
            ],

            'items.*.item_id' => [
                'required',
                'exists:items,id'
            ],

            'items.*.qty' => [
                'required',
                'integer',
                'min:1'
            ],

            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0'
            ],
        ];
    }
}