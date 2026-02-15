<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'username' => fake()->unique()->userName(),
            'password' => 'password123',
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##-####-####'),
            'role' => fake()->randomElement(['manager', 'waiter', 'kitchen']),
            'avatar_url' => null,
            'is_active' => true,
            'last_login_at' => null,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn() => ['role' => 'owner']);
    }

    public function admin(): static
    {
        return $this->state(fn() => ['role' => 'admin']);
    }

    public function manager(): static
    {
        return $this->state(fn() => ['role' => 'manager']);
    }

    public function waiter(): static
    {
        return $this->state(fn() => ['role' => 'waiter']);
    }

    public function kitchen(): static
    {
        return $this->state(fn() => ['role' => 'kitchen']);
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }
}
