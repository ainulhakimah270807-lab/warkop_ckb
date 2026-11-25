<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class InventoryUnits
{
    public static function canonical(string $unit): string
    {
        return match (strtolower(trim($unit))) {
            'l', 'ltr', 'liter', 'litre' => 'liter',
            'g', 'gr', 'gram' => 'gram',
            'kilogram', 'kg' => 'kg',
            'ml', 'mililiter' => 'ml',
            'pcs', 'buah', 'unit' => 'pcs',
            default => strtolower(trim($unit)),
        };
    }

    public static function convert(float $quantity, string $source, string $target): float
    {
        $source = self::canonical($source);
        $target = self::canonical($target);
        $units = ['liter' => ['volume', 1000], 'ml' => ['volume', 1], 'kg' => ['mass', 1000], 'gram' => ['mass', 1]];
        if ($source === $target) return round($quantity, 6);
        if (!isset($units[$source], $units[$target]) || $units[$source][0] !== $units[$target][0]) {
            throw ValidationException::withMessages(['recipe' => "Satuan {$source} tidak cocok dengan stok {$target}."]);
        }
        $converted = round($quantity * $units[$source][1] / $units[$target][1], 6);
        if ($converted <= 0) throw ValidationException::withMessages(['recipe' => 'Takaran terlalu kecil untuk satuan stok. Gunakan satuan stok yang lebih kecil.']);
        return $converted;
    }

    public static function micro(float|string $quantity): int { return (int) round((float) $quantity * 1000000); }
    public static function decimal(int $micro): string { return number_format($micro / 1000000, 6, '.', ''); }
}
