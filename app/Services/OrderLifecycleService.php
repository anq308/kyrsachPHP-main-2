<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;

class OrderLifecycleService
{
    public function __construct(
        private ClientNotificationService $notificationService,
        private StatusHistoryService $statusHistoryService
    ) {}

    public function updateStatus(Order $order, string $status, ?User $manager = null, ?string $pickupReadyAt = null): Order
    {
        $oldStatus = $order->status;

        $payload = ['status' => $status];
        if ($status === 'ready_for_pickup') {
            $payload['pickup_ready_at'] = $pickupReadyAt ? Carbon::parse($pickupReadyAt) : ($order->pickup_ready_at ?? now()->addDay());
        }

        $order->update($payload);
        $this->statusHistoryService->record($order, $oldStatus, $order->status, $manager);
        $this->syncReservations($order);
        $this->notifyCustomer($order, $oldStatus);

        return $order->fresh(['items', 'pickupPoint', 'reservations.motorcycle']);
    }

    private function syncReservations(Order $order): void
    {
        if ($order->status === 'cancelled') {
            $order->reservations()->where('status', 'active')->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

            foreach ($order->items as $item) {
                $item->motorcycle?->update(['is_available' => true]);
            }
        }

        if ($order->status === 'completed') {
            $order->reservations()->where('status', 'active')->update([
                'status' => 'completed',
            ]);
        }
    }

    private function notifyCustomer(Order $order, ?string $oldStatus): void
    {
        if ($oldStatus === $order->status) {
            return;
        }

        $messages = [
            'processing' => [
                'Заказ принят менеджером',
                "Заказ #{$order->id} принят в обработку. Менеджер проверяет наличие и условия выдачи.",
            ],
            'approved' => [
                'Заказ подтверждён',
                "Заказ #{$order->id} согласован. Техника забронирована за вами до указанного срока.",
            ],
            'ready_for_pickup' => [
                'Заказ готов к выдаче',
                "Заказ #{$order->id} можно забрать в выбранном пункте выдачи.",
            ],
            'completed' => [
                'Заказ завершён',
                "Заказ #{$order->id} завершён. Спасибо, что выбрали AVANTIS.",
            ],
            'cancelled' => [
                'Заказ отменён',
                "Заказ #{$order->id} отменён. Бронь техники снята.",
            ],
        ];

        if (! isset($messages[$order->status])) {
            return;
        }

        [$title, $message] = $messages[$order->status];
        $this->notificationService->create($order->user, $title, $message, 'order');
    }
}
