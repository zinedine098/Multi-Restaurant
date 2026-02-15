<?php

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $price = fake()->randomElement([5000, 8000, 10000, 15000, 20000, 23000, 25000, 28000, 30000, 35000]);
        return [
            'order_id' => Order::factory(),
            'menu_item_id' => MenuItem::factory(),
            'quantity' => $quantity,
            'price_at_time' => $price,
            'subtotal' => $quantity * $price,
            'notes' => fake()->optional(0.2)->randomElement(['Pedas', 'Tidak pedas', 'Tambah nasi', 'Tanpa bawang', 'Extra sambal']),
        ];
    }
}
