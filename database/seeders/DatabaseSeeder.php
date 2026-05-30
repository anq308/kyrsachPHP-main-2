<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ServiceSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'admin@avantis.ru'],
            [
                'name' => 'Администратор',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ],
        );

        User::updateOrCreate(
            ['email' => 'manager@avantis.ru'],
            [
                'name' => 'Менеджер AVANTIS',
                'password' => Hash::make('password'),
                'role' => User::ROLE_MANAGER,
            ],
        );

        User::updateOrCreate(
            ['email' => 'client@avantis.ru'],
            [
                'name' => 'Клиент AVANTIS',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CLIENT,
            ],
        );

        User::updateOrCreate(
            ['email' => 'sales@avantis.ru'],
            [
                'name' => 'Менеджер продаж AVANTIS',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SALES_MANAGER,
            ],
        );

        User::updateOrCreate(
            ['email' => 'service@avantis.ru'],
            [
                'name' => 'Менеджер сервиса AVANTIS',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SERVICE_MANAGER,
            ],
        );

        User::updateOrCreate(
            ['email' => 'warehouse@avantis.ru'],
            [
                'name' => 'Кладовщик AVANTIS',
                'password' => Hash::make('password'),
                'role' => User::ROLE_WAREHOUSE_MANAGER,
            ],
        );

        $this->call([
            MotorcycleSeeder::class,
            PickupPointSeeder::class,
        ]);

        collect([
            ['service_date' => now()->addDay()->toDateString(), 'starts_at' => '10:00', 'ends_at' => '11:00', 'service_type' => 'Диагностика'],
            ['service_date' => now()->addDay()->toDateString(), 'starts_at' => '12:00', 'ends_at' => '13:00', 'service_type' => 'Техническое обслуживание'],
            ['service_date' => now()->addDays(2)->toDateString(), 'starts_at' => '15:00', 'ends_at' => '16:00', 'service_type' => null],
        ])->each(function (array $slot) {
            ServiceSlot::firstOrCreate(
                [
                    'service_date' => $slot['service_date'],
                    'starts_at' => $slot['starts_at'],
                ],
                [
                    ...$slot,
                    'capacity' => 2,
                    'booked_count' => 0,
                    'status' => 'available',
                ],
            );
        });
    }
}
