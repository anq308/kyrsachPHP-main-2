<?php

namespace App\Http\Requests;

use App\Models\SalesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motorcycle_id' => 'nullable|exists:motorcycles,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'type' => ['nullable', Rule::in(SalesRequest::TYPES)],
            'comment' => 'nullable|string|max:2000',
        ];
    }
}
