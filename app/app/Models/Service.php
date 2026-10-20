<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'default_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function customerRates(): HasMany
    {
        return $this->hasMany(CustomerServiceRate::class);
    }
}
