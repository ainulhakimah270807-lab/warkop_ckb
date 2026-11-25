<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Supplier;
use App\Services\InventoryLedger;
use App\Services\InventoryUnits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockPurchaseController extends Controller
{
    public function index()
    {
        return view('purchases.index', ['suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(), 'ingredients' => Ingredient::orderBy('name')->get(), 'purchases' => DB::table('stock_purchases')->join('suppliers', 'suppliers.id', '=', 'stock_purchases.supplier_id')->select('stock_purchases.*', 'suppliers.name as supplier_name')->latest('stock_purchases.id')->paginate(30)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id', 'nomor_invoice' => 'required|string|max:100',
            'tanggal_beli' => 'required|date|before_or_equal:today', 'items' => 'required|array|min:1|max:100',
            'items.*.ingredient_id' => 'required|integer|distinct|exists:ingredients,id',
            'items.*.quantity' => 'required|numeric|gt:0|lte:1000000|decimal:0,6',
            'items.*.unit' => 'required|string|max:20', 'items.*.cost' => 'required|integer|between:0,1000000000',
        ]);
        DB::transaction(function () use ($request, $data) {
            $supplier = Supplier::whereKey($data['supplier_id'])->lockForUpdate()->firstOrFail();
            abort_unless($supplier->is_active, 422, 'Supplier tidak aktif.');
            if (DB::table('stock_purchases')->where('supplier_id', $supplier->id)->where('invoice', $data['nomor_invoice'])->exists()) throw ValidationException::withMessages(['nomor_invoice' => 'Invoice supplier ini sudah dicatat. Stok tidak ditambahkan ulang.']);
            $ingredients = Ingredient::whereIn('id', collect($data['items'])->pluck('ingredient_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $id = DB::table('stock_purchases')->insertGetId(['supplier_id' => $supplier->id, 'user_id' => $request->user()->id, 'invoice' => $data['nomor_invoice'], 'purchased_on' => $data['tanggal_beli'], 'total_cost' => collect($data['items'])->sum('cost'), 'created_at' => now(), 'updated_at' => now()]);
            foreach ($data['items'] as $row) {
                $ingredient = $ingredients->get($row['ingredient_id']);
                abort_unless($ingredient, 422, 'Bahan berubah. Muat ulang formulir.');
                $amount = InventoryUnits::micro(InventoryUnits::convert($row['quantity'], $row['unit'], $ingredient->unit));
                $before = InventoryUnits::micro($ingredient->stock);
                abort_if($before + $amount > InventoryUnits::micro(1000000000), 422, 'Stok melebihi batas penyimpanan.');
                $ingredient->stock = InventoryUnits::decimal($before + $amount);
                $ingredient->save();
                InventoryLedger::record($request->user()->id, 'purchase', $ingredient, $before, $before + $amount, ['stock_purchase_id' => $id], 'Invoice '.$data['nomor_invoice'].'; biaya Rp '.$row['cost']);
            }
        }, 3);
        return back()->with('success', 'Pembelian tersimpan dan stok bahan bertambah.');
    }
}
