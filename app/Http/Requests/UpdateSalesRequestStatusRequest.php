<?php

namespace App\Http\Requests;

use App\Models\SalesRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(SalesRequest::STATUSES)],
            'status_comment' => 'nullable|string|max:1000',
        ];
    }
}
