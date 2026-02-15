<?php

namespace Database\Factories;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        $menus = [
            ['name' => 'Nasi Goreng Spesial', 'price' => 25000, 'cost' => 12000],
            ['name' => 'Mie Goreng', 'price' => 23000, 'cost' => 10000],
            ['name' => 'Nasi Padang', 'price' => 30000, 'cost' => 15000],
            ['name' => 'Ayam Bakar', 'price' => 35000, 'cost' => 18000],
            ['name' => 'Soto Ayam', 'price' => 20000, 'cost' => 9000],
            ['name' => 'Gado-Gado', 'price' => 18000, 'cost' => 8000],
            ['name' => 'Rendang', 'price' => 38000, 'cost' => 20000],
            ['name' => 'Sate Ayam', 'price' => 28000, 'cost' => 14000],
            ['name' => 'Bakso', 'price' => 20000, 'cost' => 9000],
            ['name' => 'Pecel Lele', 'price' => 22000, 'cost' => 10000],
            ['name' => 'Es Teh Manis', 'price' => 5000, 'cost' => 1500],
            ['name' => 'Es Jeruk', 'price' => 7000, 'cost' => 2500],
            ['name' => 'Jus Alpukat', 'price' => 12000, 'cost' => 5000],
            ['name' => 'Kopi Hitam', 'price' => 8000, 'cost' => 3000],
            ['name' => 'Air Mineral', 'price' => 5000, 'cost' => 1500],
            ['name' => 'Teh Tarik', 'price' => 10000, 'cost' => 4000],
            ['name' => 'Pisang Goreng', 'price' => 10000, 'cost' => 4000],
            ['name' => 'Kentang Goreng', 'price' => 15000, 'cost' => 6000],
            ['name' => 'Kerupuk', 'price' => 5000, 'cost' => 1500],
            ['name' => 'Es Campur', 'price' => 12000, 'cost' => 5000],
        ];

        $menu = fake()->randomElement($menus);

        return [
            'restaurant_id' => Restaurant::factory(),
            'category_id' => MenuCategory::factory(),
            'name' => $menu['name'],
            'description' => fake()->sentence(),
            'price' => $menu['price'],
            'cost_price' => $menu['cost'],
            'image_url' => null,
            'is_available' => true,
            'is_featured' => fake()->boolean(30),
            'preparation_time' => fake()->randomElement([5, 10, 15, 20, 25, 30]),
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn() => ['is_available' => false]);
    }
}
