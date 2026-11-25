<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\InventoryLedger;
use App\Services\InventoryUnits;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $query = Ingredient::query();
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            if ($request->status === 'kritis') {
                $query->whereColumn('stock', '<=', 'minimum_stock');
            } elseif ($request->status === 'aman') {
                $query->whereColumn('stock', '>', 'minimum_stock');
            }
        }

        $ingredients = $query->latest()->get();
        return view('ingredients.index', compact('ingredients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:20',
            'stock' => 'nullable|numeric|min:0|lte:1000000000|decimal:0,6',
            'max_stock' => 'nullable|numeric|min:0|lte:1000000000|decimal:0,6',
            'minimum_stock' => 'nullable|numeric|min:0|lte:1000000000|decimal:0,6',
        ]);
        $data['unit'] = InventoryUnits::canonical($data['unit']);
        foreach (['stock', 'max_stock', 'minimum_stock'] as $field) $data[$field] = $data[$field] ?? 0;
        DB::transaction(function () use ($data, $request) {
            $ingredient = Ingredient::create($data);
            if ($ingredient->stock > 0) InventoryLedger::record($request->user()->id, 'opening', $ingredient, 0, InventoryUnits::micro($ingredient->stock));
        });
        return redirect()->route('ingredients.index')->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $data = $request->validate([
            'expected_stock' => 'required|numeric|min:0|lte:1000000000|decimal:0,6',
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:20',
            'stock' => 'required|numeric|min:0|lte:1000000000|decimal:0,6',
            'max_stock' => 'required|numeric|min:0|lte:1000000000|decimal:0,6',
            'minimum_stock' => 'required|numeric|min:0|lte:1000000000|decimal:0,6',
        ]);
        DB::transaction(function () use ($data, $ingredient, $request) {
            $locked = Ingredient::whereKey($ingredient->id)->lockForUpdate()->firstOrFail();
            if (InventoryUnits::micro($data['expected_stock']) !== InventoryUnits::micro($locked->stock)) {
                throw ValidationException::withMessages(['stock' => 'Stok sudah berubah karena transaksi lain. Muat ulang sebelum menyimpan penyesuaian.']);
            }
            unset($data['expected_stock']);
            $data['unit'] = InventoryUnits::canonical($data['unit']);
            if ($data['unit'] !== InventoryUnits::canonical($locked->unit) &&
                (DB::table('product_recipes')->where('ingredient_id', $locked->id)->exists() || DB::table('inventory_movements')->where('ingredient_id', $locked->id)->exists())) {
                throw ValidationException::withMessages(['unit' => 'Satuan bahan yang memiliki resep/riwayat tidak boleh diganti. Buat bahan baru agar takaran dan audit tetap benar.']);
            }
            $before = InventoryUnits::micro($locked->stock);
            $locked->update($data);
            $after = InventoryUnits::micro($locked->stock);
            if ($before !== $after) InventoryLedger::record($request->user()->id, 'adjustment', $locked, $before, $after, [], 'Penyesuaian stok oleh Owner');
        });
        return redirect()->route('ingredients.index')->with('success', 'Bahan baku berhasil diperbarui.');
    }

    public function destroy(Ingredient $ingredient)
    {
        DB::transaction(function () use ($ingredient) {
            $locked = Ingredient::whereKey($ingredient->id)->lockForUpdate()->firstOrFail();
            if (DB::table('product_recipes')->where('ingredient_id', $locked->id)->exists() || DB::table('inventory_movements')->where('ingredient_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['ingredient' => 'Bahan memiliki resep atau riwayat stok dan tidak boleh dihapus.']);
            }
            $locked->delete();
        });
        return redirect()->route('ingredients.index')->with('success', 'Bahan baku berhasil dihapus.');
    }
}
