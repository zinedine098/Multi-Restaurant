<?php

namespace Database\Factories;

use App\Models\DailySalesSummary;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailySalesSummaryFactory extends Factory
{
    protected $model = DailySalesSummary::class;

    public function definition(): array
    {
        $totalOrders = fake()->numberBetween(10, 80);
        $grossSales = $totalOrders * fake()->numberBetween(25000, 50000);
        $totalDiscount = round($grossSales * fake()->randomFloat(2, 0, 0.05), 2);
        $totalTax = round(($grossSales - $totalDiscount) * 0.1, 2);
        $netSales = $grossSales - $totalDiscount;

        return [
            'restaurant_id' => Restaurant::factory(),
            'date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'total_orders' => $totalOrders,
            'total_items_sold' => $totalOrders * fake()->numberBetween(2, 5),
            'gross_sales' => $grossSales,
            'net_sales' => $netSales,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
        ];
    }
}
