<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get the order instance from the route
        $order = $this->route('order');

        // Only authorize if the order is still 'pending'
        return $order && $order->status === 'pending';
    }

    public function rules(): array
    {
        return [
            'customer_id'        => 'required|exists:customers,id',
            'note'               => 'nullable|string|max:1000',
            'discount'           => 'nullable|numeric|min:0',
            
            // Validate the products being updated
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'The order must contain at least one product.',
            'items.*.quantity.min' => 'Each product must have a quantity of at least 1.',
        ];
    }
}