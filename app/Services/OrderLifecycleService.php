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

    public function updateStatus(Order $order, string $status, ?User $manager = null, ?string $pickupReadyAt = null, ?string $comment = null): Order
    {
        $oldStatus = $order->status;

        $payload = ['status' => $status];
        if ($status === 'ready_for_pickup') {
            $payload['pickup_ready_at'] = $pickupReadyAt ? Carbon::parse($pickupReadyAt) : ($order->pickup_ready_at ?? now()->addDay());
        }

        $order->update($payload);
        $this->statusHistoryService->record($order, $oldStatus, $order->status, $manager, $comment);
        $this->syncReservations($order);
        $this->notifyCustomer($order, $oldStatus);

        return $order->fresh(['items', 'pickupPoint', 'reservations.motorcycle', 'payments']);
    }

    private function syncReservations(Order $order): void
    {
        if ($order->status === 'cancelled') {
            foreach ($order->reservations()->with('motorcycle')->where('status', 'active')->get() as $reservation) {
                $reservation->update([
                    'status' => 'released',
                    'released_at' => now(),
                ]);

                if ($reservation->motorcycle) {
                    $reservation->motorcycle->decrement('reserved_quantity', min($reservation->quantity, $reservation->motorcycle->reserved_quantity));
                    $reservation->motorcycle->refreshAvailability();
                }
            }
        }

        if ($order->status === 'completed') {
            foreach ($order->reservations()->with('motorcycle')->where('status', 'active')->get() as $reservation) {
                $reservation->update([
                    'status' => 'completed',
                ]);

                if ($reservation->motorcycle) {
                    $reservation->motorcycle->decrement('reserved_quantity', min($reservation->quantity, $reservation->motorcycle->reserved_quantity));
                    $reservation->motorcycle->decrement('stock_quantity', min($reservation->quantity, $reservation->motorcycle->stock_quantity));
                    $reservation->motorcycle->refreshAvailability();
                }
            }
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
