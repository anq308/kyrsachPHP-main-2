<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'comment' => 'nullable|string|max:1000',
            'payment_method' => ['required', Rule::in(Order::PAYMENT_METHODS)],
            'pickup_point_id' => 'required|exists:pickup_points,id',
        ];
    }
}
