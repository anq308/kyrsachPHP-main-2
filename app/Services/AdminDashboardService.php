<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\Motorcycle;
use App\Models\Order;
use App\Models\SalesRequest;
use App\Models\ServiceRequest;
use App\Models\StatusHistory;
use App\Models\User;

class AdminDashboardService
{
    public function payload(): array
    {
        $motorcycles = Motorcycle::latest()->get();
        $orders = Order::with(['items', 'user', 'pickupPoint', 'reservations.motorcycle', 'payments'])->latest()->get();
        $salesRequests = SalesRequest::with(['user', 'motorcycle'])->latest()->get();
        $serviceRequests = ServiceRequest::with('user')->latest()->get();
        $messages = ContactMessage::latest()->get();
        $users = User::with(['orders', 'salesRequests', 'serviceRequests'])->latest()->get();
        $statusHistories = StatusHistory::with('user')->latest()->take(20)->get();

        return [
            'motorcycles' => $motorcycles,
            'orders' => $orders,
            'sales_requests' => $salesRequests,
            'service_requests' => $serviceRequests,
            'messages' => $messages,
            'users' => $users,
            'status_histories' => $statusHistories,
            'analytics' => [
                'monthlyRevenue' => $orders
                    ->where('status', '!=', 'cancelled')
                    ->filter(fn ($order) => $order->created_at?->isCurrentMonth())
                    ->sum('total'),
                'ordersByStatus' => $orders
                    ->groupBy('status')
                    ->map(fn ($items) => $items->count()),
                'popularMotorcycles' => $motorcycles
                    ->sortByDesc('views_count')
                    ->take(5)
                    ->values()
                    ->map(fn ($motorcycle) => [
                        'id' => $motorcycle->id,
                        'name' => $motorcycle->brand.' '.$motorcycle->model,
                        'views_count' => (int) $motorcycle->views_count,
                        'price' => (int) $motorcycle->price,
                    ]),
                'serviceTypes' => $serviceRequests
                    ->groupBy('service_type')
                    ->map(fn ($items, $type) => [
                        'type' => $type,
                        'count' => $items->count(),
                    ])
                    ->values()
                    ->sortByDesc('count')
                    ->values(),
                'salesConversion' => $salesRequests->count() > 0
                    ? round(($salesRequests->where('status', 'completed')->count() / $salesRequests->count()) * 100, 1)
                    : 0,
            ],
            'stats' => [
                'usersCount' => $users->count(),
                'ordersCount' => $orders->count(),
                'salesRequestsCount' => $salesRequests->count(),
                'serviceRequestsCount' => $serviceRequests->count(),
                'contactMessagesCount' => $messages->count(),
                'newSalesRequestsCount' => $salesRequests->where('status', 'new')->count(),
                'newServiceRequestsCount' => $serviceRequests->where('status', 'new')->count(),
                'totalRevenue' => $orders->where('status', '!=', 'cancelled')->sum('total'),
                'unavailableCount' => $motorcycles->where('is_available', false)->count(),
            ],
        ];
    }
}
