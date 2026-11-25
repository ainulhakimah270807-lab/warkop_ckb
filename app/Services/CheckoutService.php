<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Post;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function checkout(User $cashier, array $data): Sale
    {
        $items = collect($data['items'])->sortBy('product_id')->values();
        $hash = hash('sha256', json_encode([$data['order_type'], $data['payment_method'], $items->map(fn ($i) => [(int) $i['product_id'], (int) $i['quantity'], (int) $i['expected_price']])->all(), (float) $data['expected_tax_percent']]));
        return DB::transaction(function () use ($cashier, $data, $items, $hash) {
            // Serialize retries from the same cashier, then lock products and shared ingredients in ID order.
            User::whereKey($cashier->id)->lockForUpdate()->firstOrFail();
            $existing = Sale::where('request_id', $data['request_id'])->first();
            if ($existing) {
                abort_unless($existing->user_id === $cashier->id && hash_equals($existing->payload_hash, $hash), 409, 'ID transaksi sudah dipakai untuk pesanan lain.');
                return $existing->load('items', 'cashier:id,name');
            }
            $products = Post::whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $recipes = DB::table('product_recipes')->whereIn('post_id', $products->keys())->orderBy('ingredient_id')->get()->groupBy('post_id');
            $ingredients = Ingredient::whereIn('id', $recipes->flatten()->pluck('ingredient_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $needed = [];
            $lines = [];
            $subtotal = 0;
            foreach ($items as $line) {
                $product = $products->get($line['product_id']);
                if (!$product || !$product->is_active) throw ValidationException::withMessages(['items' => 'Menu berubah atau tidak aktif. Muat ulang katalog.']);
                $quantity = (int) $line['quantity'];
                $price = (int) round((float) $product->price);
                abort_if($price !== (int) $line['expected_price'], 409, 'Harga menu berubah. Muat ulang katalog dan periksa pesanan.');
                if ($product->stock_type === 'static' && $product->stock < $quantity) throw ValidationException::withMessages(['items' => "Stok {$product->title} tidak cukup."]);
                $usage = [];
                if ($product->stock_type === 'bom') {
                    $recipe = $recipes->get($product->id, collect());
                    if ($recipe->isEmpty()) throw ValidationException::withMessages(['items' => "Resep {$product->title} belum diatur Owner."]);
                    foreach ($recipe as $part) {
                        $ingredient = $ingredients->get($part->ingredient_id);
                        abort_unless($ingredient, 422, 'Bahan resep tidak tersedia.');
                        $amount = InventoryUnits::micro(InventoryUnits::convert((float) $part->quantity, $part->unit, $ingredient->unit)) * $quantity;
                        $needed[$ingredient->id] = ($needed[$ingredient->id] ?? 0) + $amount;
                        $usage[$ingredient->id] = $amount;
                    }
                }
                $subtotal += $price * $quantity;
                $lines[] = compact('product', 'quantity', 'price', 'usage');
            }
            foreach ($needed as $id => $amount) {
                if (InventoryUnits::micro($ingredients[$id]->stock) < $amount) {
                    throw ValidationException::withMessages(['stock' => 'Bahan '.$ingredients[$id]->name.' tidak cukup untuk seluruh pesanan.']);
                }
            }
            $settings = DB::table('pos_settings')->where('id', 1)->lockForUpdate()->first();
            $taxPercent = (float) $settings->tax_percent;
            abort_if(abs($taxPercent - (float) $data['expected_tax_percent']) > 0.0001, 409, 'Pengaturan pajak berubah. Muat ulang katalog.');
            $tax = (int) round($subtotal * $taxPercent / 100);
            $sale = Sale::create([
                'request_id' => $data['request_id'], 'payload_hash' => $hash, 'user_id' => $cashier->id,
                'business_date' => now('Asia/Jakarta')->toDateString(), 'order_type' => $data['order_type'],
                'payment_method' => $data['payment_method'], 'subtotal' => $subtotal, 'tax_percent' => $taxPercent,
                'tax' => $tax, 'total' => $subtotal + $tax, 'paid_at' => now(),
            ]);
            foreach ($lines as $line) {
                $product = $line['product'];
                $saleItem = $sale->items()->create(['post_id' => $product->id, 'product_name' => $product->title, 'quantity' => $line['quantity'], 'unit_price' => $line['price'], 'total' => $line['price'] * $line['quantity']]);
                foreach ($line['usage'] as $id => $amount) {
                    $ingredient = $ingredients[$id];
                    $before = InventoryUnits::micro($ingredient->stock);
                    $after = $before - $amount;
                    $ingredient->stock = InventoryUnits::decimal($after);
                    $ingredient->save();
                    InventoryLedger::record($cashier->id, 'sale', $ingredient, $before, $after, ['sale_id' => $sale->id, 'sale_item_id' => $saleItem->id]);
                }
                if ($product->stock_type === 'static') {
                    $before = InventoryUnits::micro($product->stock);
                    $product->stock -= $line['quantity'];
                    $product->save();
                    InventoryLedger::record($cashier->id, 'sale', $product, $before, InventoryUnits::micro($product->stock), ['sale_id' => $sale->id, 'sale_item_id' => $saleItem->id]);
                }
            }
            return $sale->load('items', 'cashier:id,name');
        }, 3);
    }
}
