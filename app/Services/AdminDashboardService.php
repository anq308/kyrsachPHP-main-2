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
