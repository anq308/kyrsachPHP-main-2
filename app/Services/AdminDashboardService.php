<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\AuditLog;
use App\Models\InventoryReceipt;
use App\Models\Motorcycle;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SalesRequest;
use App\Models\ServiceSlot;
use App\Models\ServiceRequest;
use App\Models\StockMovement;
use App\Models\StatusHistory;
use App\Models\StaffNote;
use App\Models\User;
use App\Models\WarehouseTask;

class AdminDashboardService
{
    public function payload(): array
    {
        $motorcycles = Motorcycle::latest()->get();
        $orders = Order::with(['items', 'user', 'pickupPoint', 'reservations.motorcycle', 'payments', 'warehouseTasks.motorcycle'])->latest()->get();
        $salesRequests = SalesRequest::with(['user', 'motorcycle'])->latest()->get();
        $serviceRequests = ServiceRequest::with(['user', 'serviceSlot'])->latest()->get();
        $payments = Payment::with(['order.user', 'user'])->latest()->get();
        $serviceSlots = ServiceSlot::latest('service_date')->get();
        $stockMovements = StockMovement::with(['motorcycle', 'user'])->latest()->take(50)->get();
        $warehouseTasks = WarehouseTask::with(['order.user', 'motorcycle', 'assignedUser'])->latest()->get();
        $inventoryReceipts = InventoryReceipt::with(['motorcycle', 'user'])->latest()->get();
        $staffNotes = StaffNote::with('user')->latest()->take(50)->get();
        $auditLogs = AuditLog::with('user')->latest()->take(50)->get();
        $messages = ContactMessage::latest()->get();
        $users = User::with(['orders', 'salesRequests', 'serviceRequests'])->latest()->get();
        $statusHistories = StatusHistory::with('user')->latest()->take(20)->get();

        return [
            'motorcycles' => $motorcycles,
            'orders' => $orders,
            'sales_requests' => $salesRequests,
            'service_requests' => $serviceRequests,
            'payments' => $payments,
            'service_slots' => $serviceSlots,
            'stock_movements' => $stockMovements,
            'warehouse_tasks' => $warehouseTasks,
            'inventory_receipts' => $inventoryReceipts,
            'staff_notes' => $staffNotes,
            'audit_logs' => $auditLogs,
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
                'paymentsCount' => $payments->count(),
                'pendingPaymentsCount' => $payments->where('status', 'pending')->count(),
                'paidPaymentsTotal' => $payments->where('status', 'paid')->sum('amount'),
                'newSalesRequestsCount' => $salesRequests->where('status', 'new')->count(),
                'newServiceRequestsCount' => $serviceRequests->where('status', 'new')->count(),
                'totalRevenue' => $orders->where('status', '!=', 'cancelled')->sum('total'),
                'unavailableCount' => $motorcycles->where('is_available', false)->count(),
                'stockUnits' => $motorcycles->sum('stock_quantity'),
                'reservedUnits' => $motorcycles->sum('reserved_quantity'),
                'availableUnits' => $motorcycles->sum(fn ($motorcycle) => $motorcycle->availableStock()),
                'lowStockCount' => $motorcycles->filter(fn ($motorcycle) => $motorcycle->availableStock() <= 1)->count(),
                'warehouseTasksCount' => $warehouseTasks->count(),
                'newWarehouseTasksCount' => $warehouseTasks->where('status', 'new')->count(),
                'inventoryReceiptsCount' => $inventoryReceipts->count(),
                'plannedInventoryReceiptsCount' => $inventoryReceipts->where('status', 'planned')->count(),
            ],
        ];
    }
}
