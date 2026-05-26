<?php

namespace App\Services;

use App\Models\Motorcycle;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private CartService $cartService) {}

    public function createFromCart(array $customerData, ?int $userId): Order
    {
        $cart = $this->cartService->raw();

        if (empty($cart)) {
            throw ValidationException::withMessages([
                'cart' => 'Корзина пуста!',
            ]);
        }

        return DB::transaction(function () use ($customerData, $userId, $cart) {
            $items = $this->validatedOrderItems($cart);
            $total = array_sum(array_column($items, 'line_total'));

            $order = Order::create([
                'user_id' => $userId,
                'name' => $customerData['name'],
                'phone' => $customerData['phone'],
                'email' => $customerData['email'] ?? null,
                'address' => $customerData['address'] ?? null,
                'comment' => $customerData['comment'] ?? null,
                'total' => $total,
                'status' => 'new',
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'motorcycle_id' => $item['motorcycle']->id,
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ]);
            }

            $this->cartService->clear();

            return $order->load('items');
        });
    }

    private function validatedOrderItems(array $cart): array
    {
        $items = [];

        foreach ($cart as $id => $item) {
            if (! is_numeric($id)) {
                throw ValidationException::withMessages([
                    'cart' => 'В корзине есть некорректная позиция.',
                ]);
            }

            $motorcycle = Motorcycle::find((int) $id);
            if (! $motorcycle || ! $motorcycle->is_available) {
                throw ValidationException::withMessages([
                    'cart' => 'Один из товаров больше недоступен для заказа.',
                ]);
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $price = (int) $motorcycle->price;

            $items[] = [
                'motorcycle' => $motorcycle,
                'name' => $motorcycle->brand.' '.$motorcycle->model,
                'price' => $price,
                'quantity' => $quantity,
                'line_total' => $price * $quantity,
            ];
        }

        return $items;
    }
}
