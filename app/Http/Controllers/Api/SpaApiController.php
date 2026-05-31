<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\ContactRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\StoreMotorcycleRequest;
use App\Http\Requests\StoreSalesRequestRequest;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Requests\UpdateSalesRequestStatusRequest;
use App\Http\Requests\UpdateServiceRequestStatusRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\ContactMessage;
use App\Models\Favorite;
use App\Models\InventoryReceipt;
use App\Models\Motorcycle;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PickupPoint;
use App\Models\SalesRequest;
use App\Models\ServiceSlot;
use App\Models\ServiceRequest;
use App\Models\StaffNote;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseTask;
use App\Services\AdminDashboardService;
use App\Services\AuditLogService;
use App\Services\CartService;
use App\Services\ClientNotificationService;
use App\Services\OrderLifecycleService;
use App\Services\OrderService;
use App\Services\StatusHistoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SpaApiController extends Controller
{
    public function __construct(
        private AdminDashboardService $adminDashboardService,
        private CartService $cartService,
        private OrderService $orderService,
        private OrderLifecycleService $orderLifecycleService,
        private ClientNotificationService $notificationService,
        private StatusHistoryService $statusHistoryService,
        private AuditLogService $auditLogService
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        $user = $request->user();
        $cart = session()->get('cart', []);
        $compare = session()->get('compare', []);

        return response()->json([
            'user' => $this->userPayload($user),
            'cart_count' => count($cart),
            'compare_count' => count($compare),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function csrfToken(): JsonResponse
    {
        return response()->json([
            'csrf_token' => csrf_token(),
        ]);
    }

    public function home(): JsonResponse
    {
        $popularMotorcycles = Motorcycle::where('is_available', true)
            ->orderByDesc('views_count')
            ->take(3)
            ->get();

        return response()->json([
            'popularMotorcycles' => $popularMotorcycles,
            'news' => $this->newsItems(),
        ]);
    }

    public function catalog(Request $request): JsonResponse
    {
        $query = Motorcycle::query();

        $availability = $request->input('availability', 'available');
        if ($availability === 'available') {
            $query->where('is_available', true);
        } elseif ($availability === 'unavailable') {
            $query->where('is_available', false);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->string('brand')->toString());
        }

        if ($request->filled('year')) {
            $query->where('year', (int) $request->input('year'));
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', (int) $request->input('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', (int) $request->input('price_max'));
        }

        if ($request->filled('engine_min')) {
            $query->where('engine_capacity', '>=', (int) $request->input('engine_min'));
        }

        if ($request->filled('engine_max')) {
            $query->where('engine_capacity', '<=', (int) $request->input('engine_max'));
        }

        if ($request->filled('power_min')) {
            $query->where('power', '>=', (int) $request->input('power_min'));
        }

        if ($request->filled('power_max')) {
            $query->where('power', '<=', (int) $request->input('power_max'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = max(1, min(30, (int) $request->input('per_page', 9)));
        $motorcycles = $query->paginate($perPage)->withQueryString();
        $payload = $motorcycles->toArray();
        $payload['filters'] = $this->catalogFilters();

        return response()->json($payload);
    }

    public function motorcycle(Request $request, string $id): JsonResponse
    {
        $motorcycle = Motorcycle::findOrFail($id);
        $motorcycle->increment('views_count');
        $motorcycle->refresh();

        $compare = session()->get('compare', []);
        $user = $request->user();

        return response()->json([
            'motorcycle' => $motorcycle,
            'is_in_compare' => in_array($motorcycle->id, $compare, true),
            'is_favorite' => $user ? $user->hasFavorite($motorcycle->id) : false,
        ]);
    }

    public function pickupPoints(): JsonResponse
    {
        return response()->json([
            'pickup_points' => PickupPoint::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function serviceSlots(): JsonResponse
    {
        return response()->json([
            'service_slots' => ServiceSlot::where('status', 'available')
                ->whereDate('service_date', '>=', now()->toDateString())
                ->orderBy('service_date')
                ->orderBy('starts_at')
                ->get()
                ->filter(fn (ServiceSlot $slot) => $slot->hasFreeCapacity())
                ->values(),
        ]);
    }

    public function compareIndex(): JsonResponse
    {
        $compareIds = session()->get('compare', []);
        $motorcycles = Motorcycle::whereIn('id', $compareIds)->get();

        return response()->json([
            'motorcycles' => $motorcycles,
            'ids' => $compareIds,
        ]);
    }

    public function toggleCompare(string $id): JsonResponse
    {
        Motorcycle::findOrFail($id);
        $compare = session()->get('compare', []);
        $idInt = (int) $id;

        if (in_array($idInt, $compare, true)) {
            $compare = array_values(array_diff($compare, [$idInt]));
            session()->put('compare', $compare);

            return response()->json([
                'message' => 'Товар удалён из сравнения.',
                'ids' => $compare,
            ]);
        }

        if (count($compare) >= 4) {
            return response()->json([
                'message' => 'Можно сравнивать не более 4 товаров.',
            ], 422);
        }

        $compare[] = $idInt;
        session()->put('compare', $compare);

        return response()->json([
            'message' => 'Товар добавлен к сравнению!',
            'ids' => $compare,
        ]);
    }

    public function cartIndex(): JsonResponse
    {
        return response()->json($this->cartService->normalize());
    }

    public function cartAdd(Request $request, string $id): JsonResponse
    {
        $motorcycle = Motorcycle::findOrFail($id);
        $cart = $this->cartService->add($motorcycle);

        return response()->json([
            'message' => 'Товар добавлен в корзину!',
            'buy_now' => $request->boolean('buy_now'),
            'cart' => $cart,
        ]);
    }

    public function cartRemove(string $id): JsonResponse
    {
        $cart = $this->cartService->remove($id);

        return response()->json([
            'message' => 'Товар удален из корзины!',
            'cart' => $cart,
        ]);
    }

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $order = $this->orderService->createFromCart($validated, Auth::id());

        return response()->json([
            'message' => 'Заказ #'.$order->id.' успешно оформлен! Мы свяжемся с вами в ближайшее время.',
            'order_id' => $order->id,
        ], 201);
    }

    public function contact(ContactRequest $request): JsonResponse
    {
        $validated = $request->validated();

        ContactMessage::create($validated);

        return response()->json([
            'message' => 'Сообщение отправлено! Мы свяжемся с вами в ближайшее время.',
        ], 201);
    }

    public function storeSalesRequest(StoreSalesRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['type'] = $validated['type'] ?? 'consultation';
        $validated['status'] = 'new';
        $validated['user_id'] = Auth::id();

        $salesRequest = SalesRequest::create($validated)->load('motorcycle');

        return response()->json([
            'message' => 'Заявка отправлена. Менеджер AVANTIS свяжется с вами в ближайшее время.',
            'sales_request' => $salesRequest,
        ], 201);
    }

    public function salesRequestsIndex(Request $request): JsonResponse
    {
        $salesRequests = $request->user()
            ->salesRequests()
            ->with('motorcycle')
            ->latest()
            ->get();

        return response()->json([
            'sales_requests' => $salesRequests,
        ]);
    }

    public function adminSalesRequestsIndex(Request $request): JsonResponse
    {
        $this->authorizeSalesWork($request);

        $query = SalesRequest::with(['user', 'motorcycle'])->latest();

        if ($request->filled('status') && in_array($request->input('status'), SalesRequest::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type') && in_array($request->input('type'), SalesRequest::TYPES, true)) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%")
                    ->orWhereHas('motorcycle', function ($motorcycleQuery) use ($search) {
                        $motorcycleQuery->where('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%");
                    });
            });
        }

        return response()->json([
            'sales_requests' => $this->adminListPayload($request, $query),
        ]);
    }

    public function storeServiceRequest(StoreServiceRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $slot = null;

        if (! empty($validated['service_slot_id'])) {
            $slot = ServiceSlot::findOrFail($validated['service_slot_id']);

            if (! $slot->hasFreeCapacity()) {
                throw ValidationException::withMessages([
                    'service_slot_id' => 'Выбранное время сервиса уже занято.',
                ]);
            }

            $validated['preferred_date'] = $validated['preferred_date'] ?? $slot->service_date;
        }

        $validated['status'] = 'new';
        $validated['user_id'] = Auth::id();

        $serviceRequest = ServiceRequest::create($validated);
        $slot?->increment('booked_count');

        return response()->json([
            'message' => 'Заявка на сервисное обслуживание отправлена. Менеджер свяжется с вами для подтверждения записи.',
            'service_request' => $serviceRequest->load('serviceSlot'),
        ], 201);
    }

    public function serviceRequestsIndex(Request $request): JsonResponse
    {
        $serviceRequests = $request->user()
            ->serviceRequests()
            ->with('serviceSlot')
            ->latest()
            ->get();

        return response()->json([
            'service_requests' => $serviceRequests,
        ]);
    }

    public function adminServiceRequestsIndex(Request $request): JsonResponse
    {
        $this->authorizeServiceWork($request);

        $query = ServiceRequest::with(['user', 'serviceSlot'])->latest();

        if ($request->filled('status') && in_array($request->input('status'), ServiceRequest::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->string('service_type')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('preferred_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('preferred_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('motorcycle_model', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'service_requests' => $this->adminListPayload($request, $query),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Неверные учетные данные.',
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Вход выполнен.',
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Регистрация прошла успешно!',
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Вы вышли из системы.',
        ]);
    }

    public function favoritesIndex(Request $request): JsonResponse
    {
        $favorites = $request->user()
            ->favorites()
            ->with('motorcycle')
            ->latest()
            ->get()
            ->pluck('motorcycle')
            ->filter()
            ->values();

        return response()->json([
            'favorites' => $favorites,
        ]);
    }

    public function toggleFavorite(Request $request, string $id): JsonResponse
    {
        $motorcycle = Motorcycle::findOrFail($id);
        $user = $request->user();

        $existing = Favorite::where('user_id', $user->id)
            ->where('motorcycle_id', $motorcycle->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'message' => 'Товар удалён из избранного.',
                'is_favorite' => false,
            ]);
        }

        Favorite::create([
            'user_id' => $user->id,
            'motorcycle_id' => $motorcycle->id,
        ]);

        return response()->json([
            'message' => 'Товар добавлен в избранное!',
            'is_favorite' => true,
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $orders = $user->orders()
            ->with(['items', 'pickupPoint', 'reservations.motorcycle', 'payments'])
            ->latest()
            ->get();
        $salesRequests = $user->salesRequests()->with('motorcycle')->latest()->get();
        $serviceRequests = $user->serviceRequests()->with('serviceSlot')->latest()->get();
        $notifications = $user->clientNotifications()->latest()->take(20)->get();

        return response()->json([
            'user' => $this->userPayload($user),
            'orders' => $orders,
            'sales_requests' => $salesRequests,
            'service_requests' => $serviceRequests,
            'notifications' => $notifications,
            'unread_notifications_count' => $user->clientNotifications()->where('is_read', false)->count(),
            'favorites_count' => $user->favorites()->count(),
        ]);
    }

    public function markNotificationsRead(Request $request): JsonResponse
    {
        $request->user()
            ->clientNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'Уведомления отмечены как прочитанные.',
        ]);
    }

    public function adminDashboard(): JsonResponse
    {
        return response()->json($this->adminDashboardService->payload());
    }

    public function adminOrdersIndex(Request $request): JsonResponse
    {
        $this->authorizeSalesWork($request);

        $query = Order::with(['items', 'user', 'pickupPoint', 'reservations.motorcycle', 'payments', 'warehouseTasks.motorcycle'])->latest();

        if ($request->filled('status') && in_array($request->input('status'), Order::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        return response()->json([
            'orders' => $this->adminListPayload($request, $query),
        ]);
    }

    public function adminAuditLogsIndex(Request $request): JsonResponse
    {
        $this->authorizeAdminWork($request);

        $query = \App\Models\AuditLog::with('user')->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return response()->json([
            'audit_logs' => $this->adminListPayload($request, $query),
        ]);
    }

    public function adminCustomerShow(string $id): JsonResponse
    {
        $this->authorizeSalesWork(request());

        $customer = User::with([
            'orders.items',
            'orders.payments',
            'orders.pickupPoint',
            'salesRequests.motorcycle',
            'serviceRequests.serviceSlot',
            'payments',
            'clientNotifications',
        ])->findOrFail($id);

        return response()->json([
            'customer' => $this->userPayload($customer),
            'orders' => $customer->orders->sortByDesc('created_at')->values(),
            'sales_requests' => $customer->salesRequests->sortByDesc('created_at')->values(),
            'service_requests' => $customer->serviceRequests->sortByDesc('created_at')->values(),
            'payments' => $customer->payments->sortByDesc('created_at')->values(),
            'notifications' => $customer->clientNotifications->sortByDesc('created_at')->values(),
        ]);
    }

    public function adminStockMovementsIndex(Request $request): JsonResponse
    {
        $this->authorizeWarehouseWork($request);

        $query = StockMovement::with(['motorcycle', 'user'])->latest();

        if ($request->filled('type') && in_array($request->input('type'), StockMovement::TYPES, true)) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('motorcycle_id')) {
            $query->where('motorcycle_id', (int) $request->input('motorcycle_id'));
        }

        return response()->json([
            'stock_movements' => $this->adminListPayload($request, $query),
        ]);
    }

    public function adminInventoryReceiptsIndex(Request $request): JsonResponse
    {
        $this->authorizeWarehouseWork($request);

        $query = InventoryReceipt::with(['motorcycle', 'user'])->latest();

        if ($request->filled('status') && in_array($request->input('status'), InventoryReceipt::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('motorcycle_id')) {
            $query->where('motorcycle_id', (int) $request->input('motorcycle_id'));
        }

        return response()->json([
            'inventory_receipts' => $this->adminListPayload($request, $query),
        ]);
    }

    public function adminStoreInventoryReceipt(Request $request): JsonResponse
    {
        $this->authorizeWarehouseWork($request);

        $validated = $this->validateInventoryReceipt($request);
        $validated['user_id'] = $request->user()?->id;
        $validated['received_at'] = $validated['status'] === 'received' ? now() : null;

        $receipt = InventoryReceipt::create($validated)->load(['motorcycle', 'user']);

        if ($receipt->status === 'received') {
            $this->applyInventoryReceipt($receipt, $request->user());
        }

        $this->auditLogService->record('inventory_receipt_created', $receipt, $request->user(), 'Создано поступление техники на склад.', null, $receipt->fresh(['motorcycle', 'user'])->toArray());

        return response()->json([
            'message' => $receipt->status === 'received' ? 'Поступление принято, остаток товара увеличен.' : 'Поступление запланировано.',
            'inventory_receipt' => $receipt->fresh(['motorcycle', 'user']),
        ], 201);
    }

    public function adminUpdateInventoryReceiptStatus(Request $request, string $id): JsonResponse
    {
        $this->authorizeWarehouseWork($request);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', InventoryReceipt::STATUSES)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $receipt = InventoryReceipt::with(['motorcycle', 'user'])->findOrFail($id);
        $before = $receipt->only(['status', 'received_at', 'comment']);
        $oldStatus = $receipt->status;

        $receipt->forceFill([
            'status' => $validated['status'],
            'comment' => $validated['comment'] ?? $receipt->comment,
            'received_at' => $validated['status'] === 'received' ? ($receipt->received_at ?? now()) : null,
        ])->save();

        if ($oldStatus !== 'received' && $receipt->status === 'received') {
            $this->applyInventoryReceipt($receipt, $request->user());
        }

        $receipt->refresh();
        $this->auditLogService->record('inventory_receipt_status_updated', $receipt, $request->user(), 'Изменён статус поступления техники.', $before, $receipt->only(['status', 'received_at', 'comment']));

        return response()->json([
            'message' => $receipt->status === 'received' ? 'Поставка принята на склад.' : 'Статус поставки обновлён.',
            'inventory_receipt' => $receipt->fresh(['motorcycle', 'user']),
        ]);
    }

    public function adminStaffNotesIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(StaffNote::ENTITY_TYPES))],
            'entity_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->authorizeStaffNoteAccess($request, $validated['entity_type'] ?? null);

        $query = StaffNote::with('user')->latest();

        if (! empty($validated['entity_type'])) {
            $entityClass = StaffNote::resolveEntityClass($validated['entity_type']);

            if ($entityClass) {
                $query->where('noteable_type', $entityClass);
            }
        }

        if (! empty($validated['entity_id'])) {
            $query->where('noteable_id', (int) $validated['entity_id']);
        }

        return response()->json([
            'staff_notes' => $this->adminListPayload($request, $query),
        ]);
    }

    public function adminStoreStaffNote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string', 'in:'.implode(',', array_keys(StaffNote::ENTITY_TYPES))],
            'entity_id' => ['required', 'integer', 'min:1'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $this->authorizeStaffNoteAccess($request, $validated['entity_type']);

        $entityClass = StaffNote::resolveEntityClass($validated['entity_type']);
        $entity = $entityClass::findOrFail($validated['entity_id']);

        $note = StaffNote::create([
            'user_id' => $request->user()?->id,
            'noteable_type' => $entity::class,
            'noteable_id' => $entity->id,
            'body' => $validated['body'],
        ]);

        $this->auditLogService->record('staff_note_created', $note, $request->user(), 'Добавлен внутренний комментарий сотрудника.', null, $note->toArray());

        return response()->json([
            'message' => 'Внутренний комментарий сохранён.',
            'staff_note' => $note->fresh('user'),
        ], 201);
    }

    public function adminPaymentsIndex(Request $request): JsonResponse
    {
        $this->authorizeSalesWork($request);

        $query = Payment::with(['order.user', 'user'])->latest();

        if ($request->filled('status') && in_array($request->input('status'), Payment::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('method') && in_array($request->input('method'), Order::PAYMENT_METHODS, true)) {
            $query->where('method', $request->input('method'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhere('order_id', $search)
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('order', function ($orderQuery) use ($search) {
                        $orderQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return response()->json([
            'payments' => $this->adminListPayload($request, $query),
        ]);
    }

    public function adminStoreServiceSlot(Request $request): JsonResponse
    {
        $this->authorizeServiceWork($request);

        $validated = $this->validateServiceSlot($request);
        $slot = ServiceSlot::create($validated);
        $this->auditLogService->record('service_slot_created', $slot, $request->user(), 'Создан слот записи на сервис.', null, $slot->toArray());

        return response()->json([
            'message' => 'Слот сервиса создан.',
            'service_slot' => $slot,
        ], 201);
    }

    public function adminUpdateServiceSlot(Request $request, string $id): JsonResponse
    {
        $this->authorizeServiceWork($request);

        $slot = ServiceSlot::findOrFail($id);
        $before = $slot->only(['service_date', 'starts_at', 'ends_at', 'service_type', 'capacity', 'booked_count', 'status', 'comment']);
        $validated = $this->validateServiceSlot($request);

        if (($validated['booked_count'] ?? 0) > ($validated['capacity'] ?? 1)) {
            throw ValidationException::withMessages([
                'booked_count' => 'Количество записей не может быть больше вместимости слота.',
            ]);
        }

        $slot->update($validated);
        $this->auditLogService->record('service_slot_updated', $slot, $request->user(), 'Обновлён слот записи на сервис.', $before, $slot->fresh()->toArray());

        return response()->json([
            'message' => 'Слот сервиса обновлён.',
            'service_slot' => $slot->fresh(),
        ]);
    }

    public function adminDeleteServiceSlot(Request $request, string $id): JsonResponse
    {
        $this->authorizeServiceWork($request);

        $slot = ServiceSlot::findOrFail($id);

        if ($slot->serviceRequests()->exists()) {
            return response()->json([
                'message' => 'Нельзя удалить слот, к которому уже привязаны сервисные заявки.',
            ], 422);
        }

        $before = $slot->toArray();
        $slot->delete();
        $this->auditLogService->record('service_slot_deleted', null, $request->user(), 'Удалён слот записи на сервис.', $before);

        return response()->json([
            'message' => 'Слот сервиса удалён.',
        ]);
    }

    public function adminUsersIndex(Request $request): JsonResponse
    {
        $this->authorizeAdminWork($request);

        $query = User::with(['orders', 'salesRequests', 'serviceRequests'])->latest();

        if ($request->filled('role') && in_array($request->input('role'), User::ROLES, true)) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'users' => $this->adminListPayload($request, $query),
        ]);
    }

    public function adminStoreMotorcycle(StoreMotorcycleRequest $request): JsonResponse
    {
        $this->authorizeWarehouseWork($request);

        $validated = $request->validated();
        $validated['stock_quantity'] = (int) ($validated['stock_quantity'] ?? 1);
        $validated['reserved_quantity'] = min((int) ($validated['reserved_quantity'] ?? 0), $validated['stock_quantity']);
        $validated['is_available'] = $request->boolean('is_available');

        $motorcycle = Motorcycle::create($validated);
        $motorcycle->refreshAvailability();

        return response()->json([
            'message' => 'Товар успешно добавлен.',
            'motorcycle' => $motorcycle,
        ], 201);
    }

    public function adminUpdateMotorcycle(StoreMotorcycleRequest $request, string $id): JsonResponse
    {
        $this->authorizeWarehouseWork($request);

        $validated = $request->validated();
        $validated['stock_quantity'] = (int) ($validated['stock_quantity'] ?? 1);
        $validated['reserved_quantity'] = min((int) ($validated['reserved_quantity'] ?? 0), $validated['stock_quantity']);
        $validated['is_available'] = $request->boolean('is_available');

        $motorcycle = Motorcycle::findOrFail($id);
        $motorcycle->update($validated);
        $motorcycle->refreshAvailability();

        return response()->json([
            'message' => 'Товар успешно обновлён.',
            'motorcycle' => $motorcycle,
        ]);
    }

    public function adminUpdateMotorcycleStock(Request $request, string $id): JsonResponse
    {
        $this->authorizeWarehouseWork($request);

        $validated = $request->validate([
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'reserved_quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['reserved_quantity'] > $validated['stock_quantity']) {
            throw ValidationException::withMessages([
                'reserved_quantity' => 'Резерв не может быть больше общего остатка.',
            ]);
        }

        $motorcycle = Motorcycle::findOrFail($id);
        $before = $motorcycle->only(['stock_quantity', 'reserved_quantity', 'is_available']);
        $motorcycle->update([
            'stock_quantity' => $validated['stock_quantity'],
            'reserved_quantity' => $validated['reserved_quantity'],
        ]);
        $motorcycle->refreshAvailability();
        $motorcycle->refresh();
        $quantityDelta = (int) $motorcycle->stock_quantity - (int) $before['stock_quantity'];

        StockMovement::create([
            'motorcycle_id' => $motorcycle->id,
            'user_id' => $request->user()?->id,
            'type' => 'correction',
            'quantity' => $quantityDelta,
            'stock_before' => (int) $before['stock_quantity'],
            'stock_after' => (int) $motorcycle->stock_quantity,
            'reserved_before' => (int) $before['reserved_quantity'],
            'reserved_after' => (int) $motorcycle->reserved_quantity,
            'reason' => $validated['reason'] ?? 'Ручная корректировка склада',
        ]);
        $this->auditLogService->record('stock_updated', $motorcycle, $request->user(), 'Изменён складской остаток товара.', $before, $motorcycle->only(['stock_quantity', 'reserved_quantity', 'is_available']));

        return response()->json([
            'message' => 'Складской остаток обновлён.',
            'motorcycle' => $motorcycle,
        ]);
    }

    public function adminUpdateWarehouseTaskStatus(Request $request, string $id): JsonResponse
    {
        $this->authorizeWarehouseWork($request);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', WarehouseTask::STATUSES)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $task = WarehouseTask::with(['order.warehouseTasks', 'motorcycle'])->findOrFail($id);
        $before = $task->only(['status', 'assigned_user_id', 'comment', 'completed_at']);

        $task->forceFill([
            'status' => $validated['status'],
            'assigned_user_id' => $request->user()?->id,
            'comment' => $validated['comment'] ?? $task->comment,
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
        ])->save();

        if ($task->motorcycle && $validated['status'] === 'completed') {
            StockMovement::create([
                'motorcycle_id' => $task->motorcycle_id,
                'user_id' => $request->user()?->id,
                'type' => 'reservation',
                'quantity' => 0,
                'stock_before' => $task->motorcycle->stock_quantity,
                'stock_after' => $task->motorcycle->stock_quantity,
                'reserved_before' => $task->motorcycle->reserved_quantity,
                'reserved_after' => $task->motorcycle->reserved_quantity,
                'reason' => "Товар подготовлен к выдаче по заказу #{$task->order_id}",
            ]);
        }

        $task->refresh();
        $this->auditLogService->record('warehouse_task_updated', $task, $request->user(), 'Кладовщик обновил задачу по заказу.', $before, $task->only(['status', 'assigned_user_id', 'comment', 'completed_at']));

        $order = $task->order?->fresh(['warehouseTasks', 'items', 'pickupPoint', 'reservations.motorcycle', 'payments']);
        if ($order && $order->warehouseTasks->isNotEmpty() && $order->warehouseTasks->every(fn (WarehouseTask $warehouseTask) => $warehouseTask->status === 'completed') && ! in_array($order->status, ['ready_for_pickup', 'completed', 'cancelled'], true)) {
            $this->orderLifecycleService->updateStatus($order, 'ready_for_pickup', $request->user(), null, 'Склад подготовил товар к выдаче.');
        }

        return response()->json([
            'message' => 'Складская задача обновлена.',
            'warehouse_task' => $task->fresh(['order.user', 'motorcycle', 'assignedUser']),
        ]);
    }

    public function adminDeleteMotorcycle(Request $request, string $id): JsonResponse
    {
        $this->authorizeWarehouseWork($request);

        $motorcycle = Motorcycle::findOrFail($id);
        $motorcycle->delete();

        return response()->json([
            'message' => 'Товар удалён.',
        ]);
    }

    public function adminUpdateOrderStatus(UpdateOrderStatusRequest $request, string $id): JsonResponse
    {
        $this->authorizeSalesWork($request);

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $order = $this->orderLifecycleService->updateStatus(
            $order,
            $request->input('status'),
            $request->user(),
            $request->input('pickup_ready_at'),
            $request->input('status_comment')
        );
        $this->auditLogService->record('order_status_updated', $order, $request->user(), 'Изменён статус заказа.', ['status' => $oldStatus], ['status' => $order->status]);

        if ($order->status === 'approved') {
            $this->createWarehouseTasksForOrder($order, $request->user());
        }

        return response()->json([
            'message' => 'Статус заказа #'.$order->id.' обновлён.',
            'order' => $order,
        ]);
    }

    public function adminUpdatePaymentStatus(Request $request, string $id): JsonResponse
    {
        $this->authorizeSalesWork($request);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', Payment::STATUSES)],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = Payment::with(['order', 'user'])->findOrFail($id);
        $before = $payment->only(['status', 'transaction_id', 'paid_at']);
        $oldStatus = $payment->status;
        $status = $validated['status'];

        $payment->forceFill([
            'status' => $status,
            'transaction_id' => $validated['transaction_id']
                ?? $payment->transaction_id
                ?? ($status === 'paid' ? 'MANUAL-'.now()->format('YmdHis').'-'.$payment->id : null),
            'paid_at' => $status === 'paid' ? ($payment->paid_at ?? now()) : null,
        ])->save();

        $payment->order?->forceFill([
            'payment_status' => $status,
        ])->save();

        if ($oldStatus !== $status && $payment->order) {
            $this->notifyPaymentCustomer($payment, $oldStatus);
        }
        $payment->refresh();
        $this->auditLogService->record('payment_status_updated', $payment, $request->user(), 'Изменён статус оплаты.', $before, $payment->only(['status', 'transaction_id', 'paid_at']));

        return response()->json([
            'message' => 'Статус оплаты #'.$payment->id.' обновлён.',
            'payment' => $payment->fresh(['order.user', 'user']),
        ]);
    }

    public function adminUpdateSalesRequestStatus(UpdateSalesRequestStatusRequest $request, string $id): JsonResponse
    {
        $this->authorizeSalesWork($request);

        $salesRequest = SalesRequest::findOrFail($id);
        $oldStatus = $salesRequest->status;
        $salesRequest->update(['status' => $request->input('status')]);
        $this->statusHistoryService->record($salesRequest, $oldStatus, $salesRequest->status, $request->user(), $request->input('status_comment'));
        $this->auditLogService->record('sales_request_status_updated', $salesRequest, $request->user(), 'Изменён статус заявки на покупку.', ['status' => $oldStatus], ['status' => $salesRequest->status]);
        $salesRequest->load(['user', 'motorcycle']);
        $this->notifySalesRequestCustomer($salesRequest, $oldStatus);

        return response()->json([
            'message' => 'Статус заявки #'.$salesRequest->id.' обновлён.',
            'sales_request' => $salesRequest,
        ]);
    }

    public function adminDeleteSalesRequest(Request $request, string $id): JsonResponse
    {
        $this->authorizeSalesWork($request);

        $salesRequest = SalesRequest::findOrFail($id);
        $salesRequest->delete();

        return response()->json([
            'message' => 'Заявка #'.$salesRequest->id.' удалена.',
        ]);
    }

    public function adminUpdateServiceRequestStatus(UpdateServiceRequestStatusRequest $request, string $id): JsonResponse
    {
        $this->authorizeServiceWork($request);

        $serviceRequest = ServiceRequest::findOrFail($id);
        $oldStatus = $serviceRequest->status;
        $serviceRequest->update(['status' => $request->input('status')]);
        $this->statusHistoryService->record($serviceRequest, $oldStatus, $serviceRequest->status, $request->user(), $request->input('status_comment'));
        $this->auditLogService->record('service_request_status_updated', $serviceRequest, $request->user(), 'Изменён статус сервисной заявки.', ['status' => $oldStatus], ['status' => $serviceRequest->status]);
        $serviceRequest->load(['user', 'serviceSlot']);
        $this->notifyServiceRequestCustomer($serviceRequest, $oldStatus);

        return response()->json([
            'message' => 'Статус сервисной заявки #'.$serviceRequest->id.' обновлён.',
            'service_request' => $serviceRequest,
        ]);
    }

    public function adminDeleteServiceRequest(Request $request, string $id): JsonResponse
    {
        $this->authorizeServiceWork($request);

        $serviceRequest = ServiceRequest::findOrFail($id);
        $serviceRequest->delete();

        return response()->json([
            'message' => 'Сервисная заявка #'.$serviceRequest->id.' удалена.',
        ]);
    }

    public function adminUpdateUserRole(UpdateUserRoleRequest $request, string $id): JsonResponse
    {
        $this->authorizeAdminWork($request);

        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id && $request->input('role') !== User::ROLE_ADMIN) {
            return response()->json([
                'message' => 'Нельзя снять роль администратора у своей учетной записи.',
            ], 422);
        }

        $before = $user->only(['role', 'is_admin']);
        $user->update([
            'role' => $request->input('role'),
        ]);
        $this->auditLogService->record('user_role_updated', $user, $request->user(), 'Изменена роль пользователя.', $before, $user->fresh()->only(['role', 'is_admin']));

        return response()->json([
            'message' => 'Роль пользователя обновлена.',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    private function userPayload(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'role' => $user->role,
            'is_manager' => $user->isManager(),
            'can_manage' => $user->canManagePanel(),
            'can_manage_sales' => $user->canManageSales(),
            'can_manage_service' => $user->canManageService(),
            'can_manage_warehouse' => $user->canManageWarehouse(),
            'created_at' => optional($user->created_at)?->toISOString(),
        ];
    }

    private function authorizeSalesWork(Request $request): void
    {
        abort_unless($request->user()?->canManageSales(), 403, 'Эта задача доступна менеджеру продаж или администратору.');
    }

    private function authorizeServiceWork(Request $request): void
    {
        abort_unless($request->user()?->canManageService(), 403, 'Эта задача доступна менеджеру сервиса или администратору.');
    }

    private function authorizeWarehouseWork(Request $request): void
    {
        abort_unless($request->user()?->canManageWarehouse(), 403, 'Эта задача доступна кладовщику или администратору.');
    }

    private function authorizeAdminWork(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Эта задача доступна только администратору.');
    }

    private function authorizeStaffNoteAccess(Request $request, ?string $entityType = null): void
    {
        $user = $request->user();

        if ($entityType === 'order' || $entityType === 'sales_request' || $entityType === 'customer') {
            $this->authorizeSalesWork($request);

            return;
        }

        if ($entityType === 'service_request') {
            $this->authorizeServiceWork($request);

            return;
        }

        if ($entityType === 'warehouse_task' || $entityType === 'motorcycle') {
            $this->authorizeWarehouseWork($request);

            return;
        }

        abort_unless($user?->canManagePanel(), 403, 'Комментарии доступны только сотрудникам мотосалона.');
    }

    private function createWarehouseTasksForOrder(Order $order, ?User $manager = null): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (! $item->motorcycle_id) {
                continue;
            }

            $task = WarehouseTask::firstOrCreate(
                [
                    'order_id' => $order->id,
                    'motorcycle_id' => $item->motorcycle_id,
                ],
                [
                    'quantity' => $item->quantity,
                    'status' => 'new',
                    'comment' => 'Подготовить товар к выдаче после подтверждения заказа менеджером.',
                ],
            );

            if ($task->wasRecentlyCreated) {
                $this->auditLogService->record('warehouse_task_created', $task, $manager, 'Создана задача кладовщику после подтверждения заказа.', null, $task->toArray());
            }
        }
    }

    private function validateInventoryReceipt(Request $request): array
    {
        return $request->validate([
            'motorcycle_id' => ['required', 'exists:motorcycles,id'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:'.implode(',', InventoryReceipt::STATUSES)],
            'expected_at' => ['nullable', 'date'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function applyInventoryReceipt(InventoryReceipt $receipt, ?User $user = null): void
    {
        $motorcycle = $receipt->motorcycle()->lockForUpdate()->firstOrFail();
        $before = $motorcycle->only(['stock_quantity', 'reserved_quantity', 'is_available']);

        $motorcycle->increment('stock_quantity', $receipt->quantity);
        $motorcycle->refreshAvailability();
        $motorcycle->refresh();

        StockMovement::create([
            'motorcycle_id' => $motorcycle->id,
            'user_id' => $user?->id,
            'type' => 'receipt',
            'quantity' => $receipt->quantity,
            'stock_before' => (int) $before['stock_quantity'],
            'stock_after' => (int) $motorcycle->stock_quantity,
            'reserved_before' => (int) $before['reserved_quantity'],
            'reserved_after' => (int) $motorcycle->reserved_quantity,
            'reason' => "Поставка от {$receipt->supplier_name} по поступлению #{$receipt->id}",
        ]);
    }

    private function validateServiceSlot(Request $request): array
    {
        return $request->validate([
            'service_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'booked_count' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:'.implode(',', ServiceSlot::STATUSES)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function notifySalesRequestCustomer(SalesRequest $salesRequest, ?string $oldStatus): void
    {
        if ($oldStatus === $salesRequest->status) {
            return;
        }

        $messages = [
            'in_progress' => ['Заявка принята в работу', "Менеджер начал обработку заявки #{$salesRequest->id}."],
            'approved' => ['Заявка согласована', "Заявка #{$salesRequest->id} согласована. Менеджер уточнит детали покупки."],
            'completed' => ['Заявка завершена', "Заявка #{$salesRequest->id} завершена."],
            'cancelled' => ['Заявка отменена', "Заявка #{$salesRequest->id} отменена."],
        ];

        if (isset($messages[$salesRequest->status])) {
            [$title, $message] = $messages[$salesRequest->status];
            $this->notificationService->create($salesRequest->user, $title, $message, 'sales_request');
        }
    }

    private function notifyServiceRequestCustomer(ServiceRequest $serviceRequest, ?string $oldStatus): void
    {
        if ($oldStatus === $serviceRequest->status) {
            return;
        }

        $messages = [
            'confirmed' => ['Запись на сервис подтверждена', "Сервисная заявка #{$serviceRequest->id} подтверждена."],
            'in_service' => ['Техника в сервисе', "По заявке #{$serviceRequest->id} начались сервисные работы."],
            'done' => ['Сервис завершён', "Работы по сервисной заявке #{$serviceRequest->id} завершены."],
            'cancelled' => ['Сервисная запись отменена', "Сервисная заявка #{$serviceRequest->id} отменена."],
        ];

        if (isset($messages[$serviceRequest->status])) {
            [$title, $message] = $messages[$serviceRequest->status];
            $this->notificationService->create($serviceRequest->user, $title, $message, 'service_request');
        }
    }

    private function notifyPaymentCustomer(Payment $payment, ?string $oldStatus): void
    {
        if ($oldStatus === $payment->status || ! $payment->order) {
            return;
        }

        $messages = [
            'paid' => ['Оплата подтверждена', "Оплата по заказу #{$payment->order_id} подтверждена."],
            'pending' => ['Оплата ожидается', "По заказу #{$payment->order_id} ожидается оплата."],
            'failed' => ['Оплата не прошла', "По заказу #{$payment->order_id} требуется повторная оплата или связь с менеджером."],
            'refunded' => ['Оплата возвращена', "По заказу #{$payment->order_id} оформлен возврат оплаты."],
        ];

        if (isset($messages[$payment->status])) {
            [$title, $message] = $messages[$payment->status];
            $this->notificationService->create($payment->order->user, $title, $message, 'payment');
        }
    }

    private function catalogFilters(): array
    {
        $baseQuery = Motorcycle::query();

        return [
            'brands' => (clone $baseQuery)->select('brand')->distinct()->orderBy('brand')->pluck('brand')->values(),
            'years' => (clone $baseQuery)->select('year')->distinct()->orderByDesc('year')->pluck('year')->values(),
            'price' => [
                'min' => (int) (clone $baseQuery)->min('price'),
                'max' => (int) (clone $baseQuery)->max('price'),
            ],
            'engine' => [
                'min' => (int) (clone $baseQuery)->min('engine_capacity'),
                'max' => (int) (clone $baseQuery)->max('engine_capacity'),
            ],
            'power' => [
                'min' => (int) (clone $baseQuery)->min('power'),
                'max' => (int) (clone $baseQuery)->max('power'),
            ],
        ];
    }

    private function adminListPayload(Request $request, Builder $query): mixed
    {
        $perPage = (int) $request->input('per_page', 0);

        if ($perPage > 0) {
            return $query->paginate(max(1, min(50, $perPage)))->withQueryString();
        }

        return $query->get();
    }

    private function newsItems(): array
    {
        return [
            [
                'title' => 'Открытие нового сезона эндуро 2026',
                'date' => '12 Мая 2026',
                'image' => asset('images/news_season.png'),
                'excerpt' => 'Готовим технику и экипировку. Расписание гонок и тренировок на ближайший месяц.',
            ],
            [
                'title' => 'Поступление новой коллекции Avantis',
                'date' => '28 Апреля 2026',
                'image' => asset('images/news_collection.png'),
                'excerpt' => 'Обновленные двигатели, улучшенная подвеска и агрессивный дизайн. Успей забронировать.',
            ],
            [
                'title' => 'Как подготовить байк к сезону?',
                'date' => '15 Апреля 2026',
                'image' => asset('images/news_maintenance.png'),
                'excerpt' => 'Советы от наших механиков: проверка жидкостей, цепи и резины перед первым выездом.',
            ],
        ];
    }
}
