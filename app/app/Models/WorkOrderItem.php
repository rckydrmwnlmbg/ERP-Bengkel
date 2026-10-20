<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'item_type',
        'part_id',
        'service_id',
        'description',
        'qty',
        'unit_price',
        'unit_cost_snapshot',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'unit_price' => 'integer',
            'unit_cost_snapshot' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class)->withTrashed();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function isPart(): bool
    {
        return $this->item_type === 'part';
    }

    public function isService(): bool
    {
        return $this->item_type === 'service';
    }

    public function getItemName(): string
    {
        if ($this->isPart()) {
            return $this->part?->name ?? $this->description ?? 'Sparepart';
        }

        return $this->service?->name ?? $this->description ?? 'Jasa Servis';
    }
}
