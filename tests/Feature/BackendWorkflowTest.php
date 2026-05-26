<?php

namespace Tests\Feature;

use App\Models\Motorcycle;
use App\Models\Order;
use App\Models\SalesRequest;
use App\Models\ServiceRequest;
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
        $this->postJson('/api/service-requests', [
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
            'status' => 'new',
        ]);
    }

    public function test_non_admin_receives_json_forbidden_for_admin_api(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->getJson('/api/admin/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'Доступ запрещён.');
    }

    public function test_admin_status_update_creates_history_record(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $salesRequest = SalesRequest::create([
            'name' => 'Клиент',
            'phone' => '+79990000001',
            'email' => 'client@example.com',
            'type' => 'consultation',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/sales-requests/{$salesRequest->id}/status", [
                'status' => 'in_progress',
            ])
            ->assertOk()
            ->assertJsonPath('sales_request.status', 'in_progress');

        $this->assertDatabaseHas('status_histories', [
            'entity_type' => SalesRequest::class,
            'entity_id' => $salesRequest->id,
            'old_status' => 'new',
            'new_status' => 'in_progress',
            'user_id' => $admin->id,
        ]);
    }

    public function test_checkout_uses_current_motorcycle_price_from_database(): void
    {
        $motorcycle = Motorcycle::factory()->create([
            'brand' => 'AVANTIS',
            'model' => 'Enduro 250',
            'price' => 100000,
            'is_available' => true,
        ]);

        $this->postJson("/api/cart/{$motorcycle->id}")
            ->assertOk()
            ->assertJsonPath('cart.total', 100000);

        $motorcycle->update(['price' => 125000]);

        $this->postJson('/api/checkout', [
            'name' => 'Покупатель',
            'phone' => '+79992223344',
            'email' => 'buyer@example.com',
        ])->assertCreated();

        $order = Order::with('items')->firstOrFail();

        $this->assertSame(125000, $order->total);
        $this->assertSame(125000, $order->items->first()->price);
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
}
