<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;

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
            'stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
        ]);
        Ingredient::create($data);
        return redirect()->route('ingredients.index')->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:20',
            'stock' => 'required|numeric|min:0',
            'max_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);
        $ingredient->update($data);
        return redirect()->route('ingredients.index')->with('success', 'Bahan baku berhasil diperbarui.');
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();
        return redirect()->route('ingredients.index')->with('success', 'Bahan baku berhasil dihapus.');
    }
}
