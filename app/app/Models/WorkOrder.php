<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'wo_number',
        'customer_id',
        'equipment_unit_id',
        'complaint',
        'diagnosis',
        'status',
        'mechanic_id',
        'opened_at',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function equipmentUnit(): BelongsTo
    {
        return $this->belongsTo(EquipmentUnit::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    public function partItems(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class)->where('item_type', 'part');
    }

    public function serviceItems(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class)->where('item_type', 'service');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function activeInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->where('status', '!=', 'void');
    }

    /**
     * Hitung total nilai order servis (subtotal part + subtotal jasa).
     */
    public function calculateTotal(): int
    {
        return (int) $this->items()->sum('line_total');
    }

    public function calculatePartsTotal(): int
    {
        return (int) $this->partItems()->sum('line_total');
    }

    public function calculateServicesTotal(): int
    {
        return (int) $this->serviceItems()->sum('line_total');
    }

    /**
     * BR-W2: Item hanya bisa ditambah/diubah/dihapus saat status open atau in_progress.
     */
    public function canModifyItems(): bool
    {
        return in_array($this->status, ['open', 'in_progress'], true);
    }

    /**
     * Generate nomor WO unik dan berurutan bulanan (WO-YYMM-####)
     */
    public static function generateNextNumber(?string $date = null): string
    {
        $targetDate = $date ? \Carbon\Carbon::parse($date) : now();
        $prefix = 'WO-' . $targetDate->format('ym') . '-';

        $latestNumber = self::where('wo_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('wo_number');

        if ($latestNumber) {
            $lastSequence = (int) substr($latestNumber, strlen($prefix));
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
