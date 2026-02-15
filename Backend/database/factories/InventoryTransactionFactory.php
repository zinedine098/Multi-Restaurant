<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryTransactionFactory extends Factory
{
    protected $model = InventoryTransaction::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['in', 'out', 'adjustment']);
        $qty = fake()->randomFloat(2, 1, 50);
        $unitCost = fake()->randomFloat(2, 5000, 50000);

        return [
            'restaurant_id' => Restaurant::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'type' => $type,
            'quantity' => $qty,
            'unit_cost' => $unitCost,
            'total_cost' => round($qty * $unitCost, 2),
            'reference_type' => $type === 'out' ? 'order' : ($type === 'in' ? 'purchase' : 'stock_opname'),
            'reference_id' => null,
            'notes' => fake()->optional(0.5)->sentence(),
            'created_by' => User::factory(),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
