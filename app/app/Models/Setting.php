<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }

    /**
     * Tarif pajak dibaca dari setting (default 0, jangan di-hardcode)
     */
    public static function getTaxRate(): float
    {
        $val = self::get('tax_rate_percent', self::get('tax_rate', 0));
        return (float) $val;
    }
}
