<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'key' => fake()->unique()->randomElement([
                'tax_percentage',
                'service_charge',
                'currency',
                'timezone',
                'receipt_header',
                'receipt_footer',
                'auto_print_receipt',
                'order_prefix',
                'low_stock_alert',
                'notification_sound',
            ]),
            'value' => fake()->word(),
            'type' => 'string',
            'description' => fake()->sentence(),
        ];
    }
}
