<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],

            'items.*.purchase_order_item_id' => [
                'required',
                'exists:purchase_order_items,id'
            ],

            'items.*.qty_received' => [
                'required',
                'integer',
                'min:1'
            ],

            'items.*.qty_rejected' => [
                'nullable',
                'integer',
                'min:0'
            ],

            'items.*.expiry_date' => [
                'nullable',
                'date'
            ],
        ];
    }
}