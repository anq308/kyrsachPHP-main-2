<?php

namespace Tests\Feature;

use App\Models\Motorcycle;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PickupPoint;
use App\Models\Reservation;
use App\Models\SalesRequest;
use App\Models\ServiceSlot;
use App\Models\ServiceRequest;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_sales_request(): void
    {
        $motorcycle = Motorcycle::factory()->create(['is_available' => true]);

        $this->postJson('/api/applications', [
            'motorcycle_id' => $motorcycle->id,
            'name' => 'Иван',
            'phone' => '+79990000000',
            'email' => 'ivan@example.com',
            'type' => 'purchase',
            'comment' => 'Нужна консультация по комплектации.',
        ])->assertCreated()
            ->assertJsonPath('sales_request.status', 'new');

        $this->assertDatabaseHas('sales_requests', [
            'motorcycle_id' => $motorcycle->id,
            'phone' => '+79990000000',
            'type' => 'purchase',
            'status' => 'new',
        ]);
    }

    public function test_customer_can_create_service_request(): void
    {
        $slot = ServiceSlot::create([
            'service_date' => now()->addDay()->toDateString(),
            'starts_at' => '10:00',
            'ends_at' => '11:00',
            'service_type' => 'Диагностика',
            'capacity' => 1,
            'booked_count' => 0,
            'status' => 'available',
        ]);

        $this->postJson('/api/service-requests', [
            'service_slot_id' => $slot->id,
            'name' => 'Петр',
            'phone' => '+79991112233',
            'email' => 'service@example.com',
            'motorcycle_model' => 'AVANTIS Enduro 250',
            'service_type' => 'Диагностика',
            'preferred_date' => now()->addDay()->toDateString(),
            'comment' => 'Проверить двигатель перед сезоном.',
        ])->assertCreated()
            ->assertJsonPath('service_request.status', 'new');

        $this->assertDatabaseHas('service_requests', [
            'motorcycle_model' => 'AVANTIS Enduro 250',
            'service_type' => 'Диагностика',
            'service_slot_id' => $slot->id,
            'status' => 'new',
        ]);
        $this->assertSame(1, $slot->fresh()->booked_count);
    }

    public function test_non_admin_receives_json_forbidden_for_admin_api(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->getJson('/api/admin/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'Доступ разрешён только менеджеру или администратору.');
    }

    public function test_manager_can_access_staff_dashboard(): void
    {
        foreach ([User::ROLE_MANAGER, User::ROLE_SALES_MANAGER, User::ROLE_SERVICE_MANAGER, User::ROLE_WAREHOUSE_MANAGER] as $role) {
            $manager = User::factory()->create(['role' => $role]);

            $this->actingAs($manager)
                ->getJson('/api/admin/dashboard')
                ->assertOk();
        }
    }

    public function test_admin_can_assign_user_roles(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_CLIENT]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$user->id}/role", [
                'role' => User::ROLE_MANAGER,
            ])
            ->assertOk()
            ->assertJsonPath('user.role', User::ROLE_MANAGER)
            ->assertJsonPath('user.can_manage', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => User::ROLE_MANAGER,
            'is_admin' => false,
        ]);
    }

    public function test_manager_cannot_assign_user_roles(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $user = User::factory()->create(['role' => User::ROLE_CLIENT]);

        $this->actingAs($manager)
            ->patchJson("/api/admin/users/{$user->id}/role", [
                'role' => User::ROLE_ADMIN,
            ])
            ->assertForbidden();
    }

    public function test_service_status_update_notifies_customer(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create();
        $serviceRequest = ServiceRequest::create([
            'user_id' => $customer->id,
            'name' => 'Клиент сервиса',
            'phone' => '+79990000111',
            'motorcycle_model' => 'AVANTIS Enduro 250',
            'service_type' => 'Диагностика',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/service-requests/{$serviceRequest->id}/status", [
                'status' => 'confirmed',
                'status_comment' => 'Согласовали дату визита.',
            ])
            ->assertOk()
            ->assertJsonPath('service_request.status', 'confirmed');

        $this->assertDatabaseHas('client_notifications', [
            'user_id' => $customer->id,
            'title' => 'Запись на сервис подтверждена',
            'type' => 'service_request',
            'is_read' => false,
        ]);
    }

    public function test_admin_status_update_creates_history_record(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $salesRequest = SalesRequest::create([
            'user_id' => $customer->id,
            'name' => 'Клиент',
            'phone' => '+79990000001',
            'email' => 'client@example.com',
            'type' => 'consultation',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/sales-requests/{$salesRequest->id}/status", [
                'status' => 'in_progress',
                'status_comment' => 'Клиенту позвонили, ждём решение.',
            ])
            ->assertOk()
            ->assertJsonPath('sales_request.status', 'in_progress');

        $this->assertDatabaseHas('status_histories', [
            'entity_type' => SalesRequest::class,
            'entity_id' => $salesRequest->id,
            'old_status' => 'new',
            'new_status' => 'in_progress',
            'user_id' => $admin->id,
            'comment' => 'Клиенту позвонили, ждём решение.',
        ]);
        $this->assertDatabaseHas('client_notifications', [
            'user_id' => $customer->id,
            'title' => 'Заявка принята в работу',
            'type' => 'sales_request',
            'is_read' => false,
        ]);
    }

    public function test_checkout_uses_current_motorcycle_price_from_database(): void
    {
        $motorcycle = Motorcycle::factory()->create([
            'brand' => 'AVANTIS',
            'model' => 'Enduro 250',
            'price' => 100000,
            'is_available' => true,
            'stock_quantity' => 1,
            'reserved_quantity' => 0,
        ]);

        $this->postJson("/api/cart/{$motorcycle->id}")
            ->assertOk()
            ->assertJsonPath('cart.total', 100000);

        $motorcycle->update(['price' => 125000]);

        $this->postJson('/api/checkout', [
            'name' => 'Покупатель',
            'phone' => '+79992223344',
            'email' => 'buyer@example.com',
            'payment_method' => 'cash_pickup',
            'pickup_point_id' => $this->pickupPoint()->id,
        ])->assertCreated();

        $order = Order::with('items')->firstOrFail();

        $this->assertSame(125000, $order->total);
        $this->assertSame(125000, $order->items->first()->price);
    }

    public function test_checkout_reserves_motorcycle_and_saves_payment_pickup_details(): void
    {
        $user = User::factory()->create();
        $pickupPoint = $this->pickupPoint();
        $motorcycle = Motorcycle::factory()->create([
            'price' => 180000,
            'is_available' => true,
            'stock_quantity' => 1,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($user)
            ->postJson("/api/cart/{$motorcycle->id}")
            ->assertOk();

        $this->actingAs($user)
            ->postJson('/api/checkout', [
                'name' => 'Покупатель',
                'phone' => '+79992223344',
                'email' => 'buyer@example.com',
                'payment_method' => 'online_mock',
                'pickup_point_id' => $pickupPoint->id,
            ])
            ->assertCreated();

        $order = Order::firstOrFail();

        $this->assertSame('online_mock', $order->payment_method);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame($pickupPoint->id, $order->pickup_point_id);
        $this->assertFalse($motorcycle->fresh()->is_available);
        $this->assertSame(1, $motorcycle->fresh()->reserved_quantity);
        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'motorcycle_id' => $motorcycle->id,
            'quantity' => 1,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('client_notifications', [
            'user_id' => $user->id,
            'title' => 'Заказ оформлен',
            'type' => 'order',
            'is_read' => false,
        ]);
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'amount' => 180000,
            'method' => 'online_mock',
            'status' => 'paid',
        ]);
    }

    public function test_admin_order_status_update_notifies_customer_and_releases_reservation_on_cancel(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $pickupPoint = $this->pickupPoint();
        $motorcycle = Motorcycle::factory()->create([
            'is_available' => false,
            'stock_quantity' => 1,
            'reserved_quantity' => 1,
        ]);
        $order = Order::create([
            'user_id' => $customer->id,
            'name' => 'Клиент',
            'phone' => '+79990000006',
            'email' => 'customer@example.com',
            'total' => 150000,
            'status' => 'new',
            'payment_method' => 'cash_pickup',
            'payment_status' => 'pending',
            'pickup_point_id' => $pickupPoint->id,
        ]);
        $order->items()->create([
            'motorcycle_id' => $motorcycle->id,
            'name' => 'AVANTIS Enduro 250',
            'price' => 150000,
            'quantity' => 1,
        ]);
        Reservation::create([
            'user_id' => $customer->id,
            'order_id' => $order->id,
            'motorcycle_id' => $motorcycle->id,
            'status' => 'active',
            'expires_at' => now()->addDays(2),
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'approved',
            ])
            ->assertOk()
            ->assertJsonPath('order.status', 'approved');

        $this->assertDatabaseHas('client_notifications', [
            'user_id' => $customer->id,
            'title' => 'Заказ подтверждён',
            'is_read' => false,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'cancelled',
                'status_comment' => 'Клиент отказался от покупки.',
            ])
            ->assertOk()
            ->assertJsonPath('order.status', 'cancelled');

        $this->assertSame('released', $order->reservations()->firstOrFail()->status);
        $this->assertTrue($motorcycle->fresh()->is_available);
        $this->assertDatabaseHas('status_histories', [
            'entity_type' => Order::class,
            'entity_id' => $order->id,
            'old_status' => 'approved',
            'new_status' => 'cancelled',
            'user_id' => $admin->id,
            'comment' => 'Клиент отказался от покупки.',
        ]);
    }

    public function test_unavailable_motorcycle_cannot_be_added_to_cart(): void
    {
        $motorcycle = Motorcycle::factory()->create(['is_available' => false]);

        $this->postJson("/api/cart/{$motorcycle->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motorcycle');
    }

    public function test_admin_can_filter_sales_requests_by_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        SalesRequest::create([
            'name' => 'Новая заявка',
            'phone' => '+79990000002',
            'type' => 'purchase',
            'status' => 'new',
        ]);
        SalesRequest::create([
            'name' => 'Заявка в работе',
            'phone' => '+79990000003',
            'type' => 'consultation',
            'status' => 'in_progress',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/sales-requests?status=in_progress')
            ->assertOk()
            ->assertJsonCount(1, 'sales_requests')
            ->assertJsonPath('sales_requests.0.status', 'in_progress');
    }

    public function test_admin_can_filter_orders_by_status_with_pagination(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pickupPoint = $this->pickupPoint();
        Order::create([
            'name' => 'Новый клиент',
            'phone' => '+79990000201',
            'total' => 100000,
            'status' => 'new',
            'payment_method' => 'cash_pickup',
            'payment_status' => 'pending',
            'pickup_point_id' => $pickupPoint->id,
        ]);
        Order::create([
            'name' => 'Готовый клиент',
            'phone' => '+79990000202',
            'total' => 120000,
            'status' => 'ready_for_pickup',
            'payment_method' => 'card_pickup',
            'payment_status' => 'pending',
            'pickup_point_id' => $pickupPoint->id,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/orders?status=ready_for_pickup&per_page=1')
            ->assertOk()
            ->assertJsonPath('orders.data.0.status', 'ready_for_pickup')
            ->assertJsonPath('orders.per_page', 1);
    }

    public function test_admin_can_update_payment_status_and_notify_customer(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create();
        $order = Order::create([
            'user_id' => $customer->id,
            'name' => 'Клиент оплаты',
            'phone' => '+79990000401',
            'total' => 210000,
            'status' => 'approved',
            'payment_method' => 'cash_pickup',
            'payment_status' => 'pending',
            'pickup_point_id' => $this->pickupPoint()->id,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'amount' => 210000,
            'method' => 'cash_pickup',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/payments/{$payment->id}/status", [
                'status' => 'paid',
            ])
            ->assertOk()
            ->assertJsonPath('payment.status', 'paid');

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotNull($payment->fresh()->transaction_id);
        $this->assertDatabaseHas('client_notifications', [
            'user_id' => $customer->id,
            'title' => 'Оплата подтверждена',
            'type' => 'payment',
            'is_read' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'payment_status_updated',
        ]);
    }

    public function test_admin_can_update_motorcycle_stock(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $motorcycle = Motorcycle::factory()->create([
            'is_available' => false,
            'stock_quantity' => 0,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/motorcycles/{$motorcycle->id}/stock", [
                'stock_quantity' => 5,
                'reserved_quantity' => 2,
                'reason' => 'Приход техники после поставки.',
            ])
            ->assertOk()
            ->assertJsonPath('motorcycle.stock_quantity', 5)
            ->assertJsonPath('motorcycle.reserved_quantity', 2)
            ->assertJsonPath('motorcycle.is_available', true);

        $this->assertDatabaseHas('stock_movements', [
            'motorcycle_id' => $motorcycle->id,
            'user_id' => $admin->id,
            'type' => 'correction',
            'stock_before' => 0,
            'stock_after' => 5,
            'reserved_before' => 0,
            'reserved_after' => 2,
            'reason' => 'Приход техники после поставки.',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/motorcycles/{$motorcycle->id}/stock", [
                'stock_quantity' => 1,
                'reserved_quantity' => 2,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reserved_quantity');
    }

    public function test_admin_can_create_service_slot_and_customer_card(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = User::factory()->create(['name' => 'Клиент карточки']);
        ServiceRequest::create([
            'user_id' => $customer->id,
            'name' => $customer->name,
            'phone' => '+79990000402',
            'motorcycle_model' => 'AVANTIS Enduro 250',
            'service_type' => 'Диагностика',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/service-slots', [
                'service_date' => now()->addDays(2)->toDateString(),
                'starts_at' => '12:00',
                'ends_at' => '13:00',
                'service_type' => 'Диагностика',
                'capacity' => 2,
                'status' => 'available',
            ])
            ->assertCreated()
            ->assertJsonPath('service_slot.capacity', 2);

        $this->actingAs($admin)
            ->getJson("/api/admin/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('customer.name', 'Клиент карточки')
            ->assertJsonCount(1, 'service_requests');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'service_slot_created',
        ]);
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create(['role' => User::ROLE_MANAGER, 'name' => 'Менеджер сервиса']);
        User::factory()->create(['role' => User::ROLE_CLIENT, 'name' => 'Обычный клиент']);

        $this->actingAs($admin)
            ->getJson('/api/admin/users?role=manager')
            ->assertOk()
            ->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.role', User::ROLE_MANAGER);
    }

    public function test_admin_dashboard_contains_analytics(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        ServiceRequest::create([
            'name' => 'Сервис аналитика',
            'phone' => '+79990000301',
            'motorcycle_model' => 'AVANTIS Enduro 250',
            'service_type' => 'Диагностика',
            'status' => 'new',
        ]);
        SalesRequest::create([
            'name' => 'Заявка аналитика',
            'phone' => '+79990000302',
            'type' => 'purchase',
            'status' => 'completed',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'analytics' => [
                    'monthlyRevenue',
                    'ordersByStatus',
                    'popularMotorcycles',
                    'serviceTypes',
                    'salesConversion',
                ],
            ])
            ->assertJsonPath('analytics.salesConversion', 100);
    }

    public function test_admin_can_filter_service_requests_by_search(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        ServiceRequest::create([
            'name' => 'Сервис 1',
            'phone' => '+79990000004',
            'motorcycle_model' => 'AVANTIS Enduro 250',
            'service_type' => 'Диагностика',
            'status' => 'new',
        ]);
        ServiceRequest::create([
            'name' => 'Сервис 2',
            'phone' => '+79990000005',
            'motorcycle_model' => 'Other ATV',
            'service_type' => 'Шиномонтаж',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/service-requests?search=Enduro')
            ->assertOk()
            ->assertJsonCount(1, 'service_requests')
            ->assertJsonPath('service_requests.0.motorcycle_model', 'AVANTIS Enduro 250');
    }

    private function pickupPoint(): PickupPoint
    {
        return PickupPoint::firstOrCreate(
            ['name' => 'Мотосалон AVANTIS Центр'],
            [
                'address' => 'г. Москва, Варшавское шоссе, 132',
                'phone' => '+7 (800) 200-25-49',
                'work_hours' => 'Пн-Вс 10:00-20:00',
                'is_active' => true,
            ]
        );
    }
}
