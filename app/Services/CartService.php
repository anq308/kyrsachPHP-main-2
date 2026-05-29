<?php

namespace App\Services;

use App\Models\Motorcycle;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function raw(): array
    {
        return session()->get('cart', []);
    }

    public function add(Motorcycle $motorcycle): array
    {
        $cart = $this->raw();
        $id = (string) $motorcycle->id;
        $desiredQuantity = (int) ($cart[$id]['quantity'] ?? 0) + 1;

        if (! $motorcycle->canReserve($desiredQuantity)) {
            throw ValidationException::withMessages([
                'motorcycle' => 'Товар сейчас недоступен для заказа.',
            ]);
        }

        if (isset($cart[$id])) {
            $cart[$id]['quantity']++;
        } else {
            $cart[$id] = [
                'name' => $motorcycle->brand.' '.$motorcycle->model,
                'quantity' => 1,
                'price' => $motorcycle->price,
                'image' => $motorcycle->image_url,
            ];
        }

        session()->put('cart', $cart);

        return $this->normalize($cart);
    }

    public function remove(string $id): array
    {
        $cart = $this->raw();

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        return $this->normalize($cart);
    }

    public function clear(): void
    {
        session()->forget('cart');
    }

    public function normalize(?array $cart = null): array
    {
        $items = [];
        $total = 0;

        foreach ($cart ?? $this->raw() as $id => $item) {
            $quantity = (int) $item['quantity'];
            $price = (int) $item['price'];
            $lineTotal = $quantity * $price;
            $total += $lineTotal;

            $items[] = [
                'id' => is_numeric($id) ? (int) $id : $id,
                'name' => $item['name'],
                'quantity' => $quantity,
                'price' => $price,
                'image' => $item['image'],
                'line_total' => $lineTotal,
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'count' => count($items),
        ];
    }
}
