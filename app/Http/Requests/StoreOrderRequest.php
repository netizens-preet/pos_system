<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'        => 'required|exists:customers,id',
            'note'               => 'nullable|string|max:1000',
            'discount'           => 'nullable|numeric|min:0',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'You must add at least one product to the order.',
            'items.*.product_id.exists' => 'One of the selected products is invalid.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}