<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;

class PaymentService
{
    public function createForOrder(Order $order, string $method): Payment
    {
        $isMockOnlinePayment = $method === 'online_mock';

        return Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'amount' => (int) $order->total,
            'method' => $method,
            'status' => $isMockOnlinePayment ? 'paid' : 'pending',
            'transaction_id' => $isMockOnlinePayment ? 'MOCK-'.now()->format('YmdHis').'-'.$order->id : null,
            'paid_at' => $isMockOnlinePayment ? now() : null,
            'meta' => [
                'source' => 'checkout',
                'note' => $isMockOnlinePayment ? 'Демо-оплата для дипломного проекта' : 'Оплата ожидается при выдаче или после согласования',
            ],
        ]);
    }
}
