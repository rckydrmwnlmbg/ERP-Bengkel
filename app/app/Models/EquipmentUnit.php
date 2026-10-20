<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'brand',
        'model',
        'serial_number',
        'unit_code',
        'hour_meter',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hour_meter' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
