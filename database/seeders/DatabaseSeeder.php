<?php

namespace Database\Seeders;

use App\Models\User;
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

        $this->call([
            MotorcycleSeeder::class,
            PickupPointSeeder::class,
        ]);
    }
}
