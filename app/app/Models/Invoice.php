<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'source_type',
        'work_order_id',
        'issue_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'status',
        'void_reason',
        'voided_at',
        'voided_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'total' => 'integer',
            'voided_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function partItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->where('item_type', 'part');
    }

    public function serviceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->where('item_type', 'service');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function validPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('status', 'valid');
    }

    /**
     * Jumlah pembayaran valid yang sudah diterima (bebas N+1 jika relasi ter-load)
     */
    public function totalPaid(): int
    {
        if ($this->relationLoaded('validPayments')) {
            return (int) $this->validPayments->sum('amount');
        }

        if ($this->relationLoaded('payments')) {
            return (int) $this->payments->where('status', 'valid')->sum('amount');
        }

        return (int) $this->validPayments()->sum('amount');
    }

    /**
     * Sisa tagihan piutang invoice
     */
    public function remainingBalance(): int
    {
        if ($this->status === 'void') {
            return 0;
        }

        return max(0, (int) $this->total - $this->totalPaid());
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    public function isOverdue(): bool
    {
        if ($this->isPaid() || $this->isVoid()) {
            return false;
        }

        return $this->due_date && $this->due_date->isPast();
    }

    /**
     * BR-R2: HPP = Σ (qty × unit_cost_snapshot) pada item part.
     */
    public function calculateHpp(): int
    {
        return (int) $this->partItems()->sum(\Illuminate\Support\Facades\DB::raw('qty * unit_cost_snapshot'));
    }

    /**
     * BR-R3: Laba kotor = Omzet (Total) - HPP.
     */
    public function calculateGrossProfit(): int
    {
        return (int) $this->total - $this->calculateHpp();
    }

    /**
     * BR-I1: Nomor INV-YYMM-####, berurutan per bulan, unik, dibuat dalam transaksi tanpa celah race.
     */
    public static function generateNextNumber(?string $date = null): string
    {
        $targetDate = $date ? \Carbon\Carbon::parse($date) : now();
        $prefix = 'INV-' . $targetDate->format('ym') . '-';

        $latestNumber = self::where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('invoice_number');

        if ($latestNumber) {
            $lastSequence = (int) substr($latestNumber, strlen($prefix));
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
