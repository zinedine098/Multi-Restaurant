<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'name',
        'unit',
        'min_stock',
        'current_stock',
        'unit_cost',
        'supplier_name',
        'supplier_phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_stock' => 'decimal:2',
            'current_stock' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    // ── Helpers ────────────────────────────────────────────────

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }
}
