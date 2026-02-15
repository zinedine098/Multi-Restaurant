<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySalesSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'date',
        'total_orders',
        'total_items_sold',
        'gross_sales',
        'net_sales',
        'total_discount',
        'total_tax',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_orders' => 'integer',
            'total_items_sold' => 'integer',
            'gross_sales' => 'decimal:2',
            'net_sales' => 'decimal:2',
            'total_discount' => 'decimal:2',
            'total_tax' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
