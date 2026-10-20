<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'phone',
        'address',
        'payment_term_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_term_days' => 'integer',
        ];
    }

    public function equipmentUnits(): HasMany
    {
        return $this->hasMany(EquipmentUnit::class);
    }

    public function serviceRates(): HasMany
    {
        return $this->hasMany(CustomerServiceRate::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * BR-R6: Total piutang customer = Σ (total − pembayaran valid) untuk invoice non-void dengan sisa > 0.
     */
    public function calculateTotalReceivables(): int
    {
        $invoices = $this->invoices()->where('status', '!=', 'void')->with('validPayments')->get();
        return $invoices->sum(fn (Invoice $inv) => $inv->remainingBalance());
    }
}
