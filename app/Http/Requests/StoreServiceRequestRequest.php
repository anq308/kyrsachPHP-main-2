<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'motorcycle_model' => 'required|string|max:255',
            'service_type' => 'required|string|max:255',
            'preferred_date' => 'nullable|date',
            'comment' => 'nullable|string|max:2000',
        ];
    }
}
