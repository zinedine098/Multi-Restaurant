<?php

namespace Database\Factories;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    public function definition(): array
    {
        $cities = ['Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang', 'Yogyakarta', 'Makassar', 'Bali', 'Palembang', 'Malang'];
        $city = fake()->randomElement($cities);

        return [
            'name' => 'Resto ' . fake()->unique()->company(),
            'address' => fake()->streetAddress() . ', ' . $city,
            'phone' => fake()->numerify('08##-####-####'),
            'email' => fake()->unique()->companyEmail(),
            'tax_id' => fake()->numerify('##.###.###.#-###.###'),
            'logo_url' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }
}
