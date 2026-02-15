<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $types = [
            ['type' => 'new_order', 'title' => 'Pesanan Baru!', 'message' => 'Ada pesanan baru menunggu diproses.'],
            ['type' => 'order_completed', 'title' => 'Pesanan Selesai!', 'message' => 'Pesanan telah selesai dimasak.'],
            ['type' => 'low_stock', 'title' => 'Stok Menipis!', 'message' => 'Stok bahan baku sudah di bawah minimum.'],
            ['type' => 'order_cancelled', 'title' => 'Pesanan Dibatalkan', 'message' => 'Ada pesanan yang dibatalkan.'],
        ];

        $notif = fake()->randomElement($types);
        $isRead = fake()->boolean(40);

        return [
            'restaurant_id' => Restaurant::factory(),
            'user_id' => User::factory(),
            'type' => $notif['type'],
            'title' => $notif['title'],
            'message' => $notif['message'],
            'data' => json_encode(['order_id' => fake()->numberBetween(1, 100)]),
            'is_read' => $isRead,
            'read_at' => $isRead ? now()->subMinutes(fake()->numberBetween(1, 120)) : null,
            'created_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
