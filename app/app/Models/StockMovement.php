<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    // BR-S3: stock_movements append-only, tanpa updated_at
    public const UPDATED_AT = null;

    protected $fillable = [
        'part_id',
        'type',
        'qty_change',
        'unit_cost_snapshot',
        'balance_after',
        'ref_type',
        'ref_id',
        'reason',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'qty_change' => 'integer',
            'unit_cost_snapshot' => 'integer',
            'balance_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
