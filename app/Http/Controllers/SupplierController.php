<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:1000',
        ]);
        Supplier::create($data);
        return redirect()->route('purchases.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:1000',
        ]);
        $supplier->update($data);
        return back()->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        DB::transaction(function () use ($supplier) {
            $locked = Supplier::whereKey($supplier->id)->lockForUpdate()->firstOrFail();
            if (DB::table('stock_purchases')->where('supplier_id', $locked->id)->exists()) throw ValidationException::withMessages(['supplier' => 'Supplier memiliki riwayat pembelian dan tidak boleh dihapus.']);
            $locked->delete();
        });
        return back()->with('success', 'Supplier berhasil dihapus.');
    }
}
