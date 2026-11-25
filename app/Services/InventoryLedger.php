<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InventoryLedger
{
    public static function record(int $userId, string $kind, object $item, int $before, int $after, array $references = [], ?string $note = null): void
    {
        $ingredient = $item instanceof \App\Models\Ingredient;
        DB::table('inventory_movements')->insert($references + [
            'ingredient_id' => $ingredient ? $item->id : null,
            'post_id' => $ingredient ? null : $item->id,
            'user_id' => $userId, 'kind' => $kind,
            'item_name' => $ingredient ? $item->name : $item->title,
            'unit' => $ingredient ? $item->unit : 'pcs',
            'quantity' => InventoryUnits::decimal($after - $before),
            'balance_before' => InventoryUnits::decimal($before), 'balance_after' => InventoryUnits::decimal($after),
            'created_at' => now(), 'note' => $note,
        ]);
    }
}
