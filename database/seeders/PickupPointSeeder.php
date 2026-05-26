<?php

namespace Database\Seeders;

use App\Models\PickupPoint;
use Illuminate\Database\Seeder;

class PickupPointSeeder extends Seeder
{
    public function run(): void
    {
        $points = [
            [
                'name' => 'Мотосалон AVANTIS Центр',
                'address' => 'г. Москва, Варшавское шоссе, 132',
                'phone' => '+7 (800) 200-25-49',
                'work_hours' => 'Пн-Вс 10:00-20:00',
            ],
            [
                'name' => 'Склад выдачи AVANTIS Север',
                'address' => 'г. Москва, Дмитровское шоссе, 100',
                'phone' => '+7 (800) 200-25-49',
                'work_hours' => 'Пн-Сб 10:00-19:00',
            ],
            [
                'name' => 'Сервисный центр AVANTIS',
                'address' => 'г. Москва, ул. Механиков, 7',
                'phone' => '+7 (800) 200-25-49',
                'work_hours' => 'Пн-Сб 09:00-21:00',
            ],
        ];

        foreach ($points as $point) {
            PickupPoint::updateOrCreate(
                ['name' => $point['name']],
                [...$point, 'is_active' => true]
            );
        }
    }
}
