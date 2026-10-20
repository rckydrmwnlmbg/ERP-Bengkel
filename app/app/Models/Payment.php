<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'paid_at',
        'amount',
        'method',
        'reference',
        'status',
        'void_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'amount' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }
}
