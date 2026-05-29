<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Order::STATUSES)],
            'pickup_ready_at' => 'nullable|date',
            'status_comment' => 'nullable|string|max:1000',
        ];
    }
}
