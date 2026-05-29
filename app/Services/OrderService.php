<?php

namespace App\Services;

use App\Models\Motorcycle;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private CartService $cartService,
        private ClientNotificationService $notificationService,
        private PaymentService $paymentService
    ) {}

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
                'payment_method' => $customerData['payment_method'],
                'payment_status' => $customerData['payment_method'] === 'online_mock' ? 'paid' : 'pending',
                'pickup_point_id' => $customerData['pickup_point_id'],
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'motorcycle_id' => $item['motorcycle']->id,
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ]);

                Reservation::create([
                    'user_id' => $userId,
                    'order_id' => $order->id,
                    'motorcycle_id' => $item['motorcycle']->id,
                    'status' => 'active',
                    'expires_at' => now()->addDays(2),
                ]);

                $item['motorcycle']->update(['is_available' => false]);
            }

            $this->paymentService->createForOrder($order, $customerData['payment_method']);

            $this->cartService->clear();
            $this->notificationService->create(
                $userId ? User::find($userId) : null,
                'Заказ оформлен',
                "Заказ #{$order->id} создан. Техника предварительно забронирована, менеджер проверит детали заказа.",
                'order'
            );

            return $order->load(['items', 'pickupPoint', 'reservations.motorcycle', 'payments']);
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
            if (! $motorcycle || ! $motorcycle->is_available || $this->hasActiveReservation($motorcycle)) {
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

    private function hasActiveReservation(Motorcycle $motorcycle): bool
    {
        return $motorcycle->reservations()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
