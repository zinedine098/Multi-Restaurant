<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'cooking', 'completed', 'paid', 'cancelled']);

        return [
            'restaurant_id' => Restaurant::factory(),
            'user_id' => User::factory(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('08##-####-####'),
            'order_number' => 'ORD-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('####'),
            'status' => $status,
            'total_amount' => fake()->numberBetween(20000, 200000),
            'payment_amount' => $status === 'paid' ? fake()->numberBetween(50000, 300000) : null,
            'change_amount' => null,
            'payment_method' => $status === 'paid' ? fake()->randomElement(['cash', 'qris', 'transfer', 'debit', 'credit']) : null,
            'notes' => fake()->optional(0.3)->sentence(),
            'completed_at' => in_array($status, ['completed', 'paid']) ? now()->subMinutes(fake()->numberBetween(5, 60)) : null,
            'paid_at' => $status === 'paid' ? now()->subMinutes(fake()->numberBetween(1, 30)) : null,
            'cancelled_at' => $status === 'cancelled' ? now()->subMinutes(fake()->numberBetween(1, 60)) : null,
            'cancellation_reason' => $status === 'cancelled' ? fake()->sentence() : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => [
            'status' => 'pending',
            'payment_amount' => null,
            'change_amount' => null,
            'payment_method' => null,
            'completed_at' => null,
            'paid_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn() => [
            'status' => 'completed',
            'completed_at' => now()->subMinutes(fake()->numberBetween(5, 30)),
            'payment_amount' => null,
            'change_amount' => null,
            'payment_method' => null,
            'paid_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total_amount'] ?? 50000;
            $paid = ceil($total / 10000) * 10000 + 10000;
            return [
                'status' => 'paid',
                'payment_amount' => $paid,
                'change_amount' => $paid - $total,
                'payment_method' => fake()->randomElement(['cash', 'qris', 'transfer']),
                'completed_at' => now()->subMinutes(30),
                'paid_at' => now()->subMinutes(10),
                'cancelled_at' => null,
            ];
        });
    }
}
