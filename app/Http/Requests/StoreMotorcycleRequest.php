<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreMotorcycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'engine_capacity' => 'required|integer',
            'power' => 'required|integer',
            'price' => 'required|integer',
            'description' => 'required|string',
            'image_url' => [
                'required',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (filter_var($value, FILTER_VALIDATE_URL) || str_starts_with($value, '/')) {
                        return;
                    }

                    $fail('Поле изображения должно быть абсолютным URL или начинаться с "/".');
                },
            ],
            'is_available' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'reserved_quantity' => 'nullable|integer|min:0',
            'transmission' => 'nullable|string|max:255',
            'cooling' => 'nullable|string|max:255',
            'fuel_system' => 'nullable|string|max:255',
            'weight' => 'nullable|integer',
            'tank_capacity' => 'nullable|numeric',
        ];
    }
}
