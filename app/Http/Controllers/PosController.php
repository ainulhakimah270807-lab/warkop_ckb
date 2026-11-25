<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Sale;
use App\Services\CheckoutService;
use App\Services\InventoryUnits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PosController extends Controller
{
    public function catalog(Request $request)
    {
        $products = Post::with('ingredients')->where('is_active', true)->orderBy('title')->get()->map(function ($product) use ($request) {
            $available = null;
            if ($product->stock_type === 'static') $available = (int) $product->stock;
            if ($product->stock_type === 'bom') {
                $available = 0;
                if ($product->ingredients->isNotEmpty()) {
                    $available = $product->ingredients->map(function ($ingredient) {
                        try {
                            $needed = InventoryUnits::micro(InventoryUnits::convert((float) $ingredient->pivot->quantity, $ingredient->pivot->unit, $ingredient->unit));
                            return $needed > 0 ? intdiv(InventoryUnits::micro($ingredient->stock), $needed) : 0;
                        } catch (\Illuminate\Validation\ValidationException $e) { return 0; }
                    })->min();
                }
            }
            return ['id' => $product->id, 'name' => $product->title, 'category' => $product->category, 'description' => $product->content ?? '',
                'price' => (int) round((float) $product->price), 'stock_type' => $product->stock_type, 'available_quantity' => $available,
                'image_url' => $product->photo_path ? $request->getSchemeAndHttpHost().'/menu-images/'.$product->id : null];
        });
        return response()->json(['products' => $products, 'settings' => DB::table('pos_settings')->find(1)])->header('Cache-Control', 'no-store');
    }

    public function checkout(Request $request, CheckoutService $checkout)
    {
        $data = $request->validate([
            'request_id' => 'required|uuid', 'order_type' => 'required|in:Dine In,Take Away',
            'payment_method' => 'required|in:QRIS,Tunai,Debit / EDC',
            'expected_tax_percent' => 'required|numeric|between:0,100',
            'items' => 'required|array|min:1|max:100', 'items.*.product_id' => 'required|integer|distinct',
            'items.*.quantity' => 'required|integer|between:1,1000', 'items.*.expected_price' => 'required|integer|between:0,1000000000',
        ]);
        return response()->json(['sale' => $checkout->checkout($request->user(), $data)], 201);
    }

    public function receipt(Request $request, Sale $sale)
    {
        abort_unless($request->user()->role === 'owner' || $sale->user_id === $request->user()->id, 403);
        return response()->json(['sale' => $sale->load('items', 'cashier:id,name'), 'usage' => $request->user()->role === 'owner' ? DB::table('inventory_movements')->where('sale_id', $sale->id)->orderBy('id')->get() : []]);
    }

    public function image(Post $post)
    {
        abort_unless($post->photo_path && Storage::disk('local')->exists($post->photo_path), 404);
        return response()->file(Storage::disk('local')->path($post->photo_path), ['X-Content-Type-Options' => 'nosniff']);
    }
}
