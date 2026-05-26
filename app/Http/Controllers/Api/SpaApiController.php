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
use App\Models\ContactMessage;
use App\Models\Favorite;
use App\Models\Motorcycle;
use App\Models\Order;
use App\Models\PickupPoint;
use App\Models\SalesRequest;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\AdminDashboardService;
use App\Services\CartService;
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
        private StatusHistoryService $statusHistoryService
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

        $validated['status'] = 'new';
        $validated['user_id'] = Auth::id();

        $serviceRequest = ServiceRequest::create($validated);

        return response()->json([
            'message' => 'Заявка на сервисное обслуживание отправлена. Менеджер свяжется с вами для подтверждения записи.',
            'service_request' => $serviceRequest,
        ], 201);
    }

    public function serviceRequestsIndex(Request $request): JsonResponse
    {
        $serviceRequests = $request->user()
            ->serviceRequests()
            ->latest()
            ->get();

        return response()->json([
            'service_requests' => $serviceRequests,
        ]);
    }

    public function adminServiceRequestsIndex(Request $request): JsonResponse
    {
        $query = ServiceRequest::with('user')->latest();

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
            ->with(['items', 'pickupPoint', 'reservations.motorcycle'])
            ->latest()
            ->get();
        $salesRequests = $user->salesRequests()->with('motorcycle')->latest()->get();
        $serviceRequests = $user->serviceRequests()->latest()->get();
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

    public function adminStoreMotorcycle(StoreMotorcycleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['is_available'] = $request->boolean('is_available');

        $motorcycle = Motorcycle::create($validated);

        return response()->json([
            'message' => 'Товар успешно добавлен.',
            'motorcycle' => $motorcycle,
        ], 201);
    }

    public function adminUpdateMotorcycle(StoreMotorcycleRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();
        $validated['is_available'] = $request->boolean('is_available');

        $motorcycle = Motorcycle::findOrFail($id);
        $motorcycle->update($validated);

        return response()->json([
            'message' => 'Товар успешно обновлён.',
            'motorcycle' => $motorcycle,
        ]);
    }

    public function adminDeleteMotorcycle(string $id): JsonResponse
    {
        $motorcycle = Motorcycle::findOrFail($id);
        $motorcycle->delete();

        return response()->json([
            'message' => 'Товар удалён.',
        ]);
    }

    public function adminUpdateOrderStatus(UpdateOrderStatusRequest $request, string $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $order = $this->orderLifecycleService->updateStatus(
            $order,
            $request->input('status'),
            $request->user(),
            $request->input('pickup_ready_at')
        );

        return response()->json([
            'message' => 'Статус заказа #'.$order->id.' обновлён.',
            'order' => $order,
        ]);
    }

    public function adminUpdateSalesRequestStatus(UpdateSalesRequestStatusRequest $request, string $id): JsonResponse
    {
        $salesRequest = SalesRequest::findOrFail($id);
        $oldStatus = $salesRequest->status;
        $salesRequest->update(['status' => $request->input('status')]);
        $this->statusHistoryService->record($salesRequest, $oldStatus, $salesRequest->status, $request->user());
        $salesRequest->load(['user', 'motorcycle']);

        return response()->json([
            'message' => 'Статус заявки #'.$salesRequest->id.' обновлён.',
            'sales_request' => $salesRequest,
        ]);
    }

    public function adminDeleteSalesRequest(string $id): JsonResponse
    {
        $salesRequest = SalesRequest::findOrFail($id);
        $salesRequest->delete();

        return response()->json([
            'message' => 'Заявка #'.$salesRequest->id.' удалена.',
        ]);
    }

    public function adminUpdateServiceRequestStatus(UpdateServiceRequestStatusRequest $request, string $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::findOrFail($id);
        $oldStatus = $serviceRequest->status;
        $serviceRequest->update(['status' => $request->input('status')]);
        $this->statusHistoryService->record($serviceRequest, $oldStatus, $serviceRequest->status, $request->user());
        $serviceRequest->load('user');

        return response()->json([
            'message' => 'Статус сервисной заявки #'.$serviceRequest->id.' обновлён.',
            'service_request' => $serviceRequest,
        ]);
    }

    public function adminDeleteServiceRequest(string $id): JsonResponse
    {
        $serviceRequest = ServiceRequest::findOrFail($id);
        $serviceRequest->delete();

        return response()->json([
            'message' => 'Сервисная заявка #'.$serviceRequest->id.' удалена.',
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
            'created_at' => optional($user->created_at)?->toISOString(),
        ];
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
