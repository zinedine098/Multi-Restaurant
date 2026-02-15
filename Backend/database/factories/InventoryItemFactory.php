<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        $items = [
            ['name' => 'Beras', 'unit' => 'kg', 'min' => 10, 'cost' => 12000],
            ['name' => 'Minyak Goreng', 'unit' => 'liter', 'min' => 5, 'cost' => 18000],
            ['name' => 'Telur', 'unit' => 'butir', 'min' => 30, 'cost' => 2500],
            ['name' => 'Ayam', 'unit' => 'kg', 'min' => 5, 'cost' => 35000],
            ['name' => 'Daging Sapi', 'unit' => 'kg', 'min' => 3, 'cost' => 120000],
            ['name' => 'Tepung Terigu', 'unit' => 'kg', 'min' => 5, 'cost' => 10000],
            ['name' => 'Gula Pasir', 'unit' => 'kg', 'min' => 3, 'cost' => 15000],
            ['name' => 'Garam', 'unit' => 'kg', 'min' => 2, 'cost' => 5000],
            ['name' => 'Kecap Manis', 'unit' => 'botol', 'min' => 5, 'cost' => 12000],
            ['name' => 'Cabai Merah', 'unit' => 'kg', 'min' => 2, 'cost' => 40000],
            ['name' => 'Bawang Merah', 'unit' => 'kg', 'min' => 3, 'cost' => 30000],
            ['name' => 'Bawang Putih', 'unit' => 'kg', 'min' => 2, 'cost' => 25000],
            ['name' => 'Teh Celup', 'unit' => 'box', 'min' => 5, 'cost' => 8000],
            ['name' => 'Kopi Bubuk', 'unit' => 'kg', 'min' => 2, 'cost' => 50000],
            ['name' => 'Susu Kental Manis', 'unit' => 'kaleng', 'min' => 10, 'cost' => 10000],
        ];

        $item = fake()->randomElement($items);

        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => $item['name'],
            'unit' => $item['unit'],
            'min_stock' => $item['min'],
            'current_stock' => fake()->numberBetween($item['min'], $item['min'] * 10),
            'unit_cost' => $item['cost'],
            'supplier_name' => 'PT ' . fake()->company(),
            'supplier_phone' => fake()->numerify('021-####-####'),
            'is_active' => true,
        ];
    }
}
