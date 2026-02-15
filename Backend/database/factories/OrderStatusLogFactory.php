<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderStatusLogFactory extends Factory
{
    protected $model = OrderStatusLog::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'old_status' => null,
            'new_status' => 'pending',
            'changed_by' => User::factory(),
            'changed_at' => now(),
            'notes' => 'Order dibuat.',
        ];
    }
}
