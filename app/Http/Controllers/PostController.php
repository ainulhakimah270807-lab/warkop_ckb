<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Post;
use App\Services\InventoryLedger;
use App\Services\InventoryUnits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query();
        if ($request->filled('search')) $query->where(fn ($q) => $q->where('title', 'like', '%'.$request->search.'%')->orWhere('category', 'like', '%'.$request->search.'%'));
        if ($request->filled('category')) $query->where('category', $request->category);
        $posts = $query->latest()->get();
        $categories = Post::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        return view('posts.index', compact('posts', 'categories'));
    }

    public function create() { return view('posts.create', ['ingredients' => Ingredient::orderBy('name')->get()]); }
    public function edit($id) { return view('posts.edit', ['post' => Post::with('ingredients')->findOrFail($id), 'ingredients' => Ingredient::orderBy('name')->get()]); }
    public function store(Request $request) { return $this->save($request); }
    public function update(Request $request, $id) { return $this->save($request, (int) $id); }

    private function save(Request $request, ?int $id = null)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255', 'category' => 'required|string|max:100', 'content' => 'nullable|string|max:10000',
            'price' => 'required|integer|between:0,1000000000', 'stock_type' => 'required|in:bom,static,none',
            'stock' => 'required_if:stock_type,static|nullable|integer|between:0,1000000000',
            'expected_stock' => 'nullable|integer|min:0',
            'photo' => 'nullable|file|image|mimes:jpg,jpeg,png|max:2048|dimensions:max_width=4096,max_height=4096',
            'recipe' => 'required_if:stock_type,bom|array|max:100',
            'recipe.*.ingredient_id' => 'required|integer|distinct|exists:ingredients,id',
            'recipe.*.quantity' => 'required|numeric|gt:0|lte:1000000|decimal:0,6',
            'recipe.*.unit' => 'required|string|max:20',
        ]);
        $path = $request->hasFile('photo') ? $request->file('photo')->store('menu-images', 'local') : null;
        try {
            DB::transaction(function () use ($request, $data, $id, $path) {
                $post = $id ? Post::whereKey($id)->lockForUpdate()->firstOrFail() : new Post();
                $before = InventoryUnits::micro($post->stock ?? 0);
                if ($id && $data['stock_type'] === 'static' && (!isset($data['expected_stock']) || $before !== InventoryUnits::micro($data['expected_stock']))) {
                    throw ValidationException::withMessages(['stock' => 'Stok berubah sejak formulir dibuka. Muat ulang agar penjualan terbaru tidak tertimpa.']);
                }
                $parts = [];
                if ($data['stock_type'] === 'bom') {
                    $ingredients = Ingredient::whereIn('id', collect($data['recipe'])->pluck('ingredient_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                    foreach ($data['recipe'] as $row) {
                        $ingredient = $ingredients->get($row['ingredient_id']);
                        if (!$ingredient) throw ValidationException::withMessages(['recipe' => 'Bahan baku berubah. Muat ulang formulir.']);
                        $unit = InventoryUnits::canonical($row['unit']);
                        InventoryUnits::convert((float) $row['quantity'], $unit, $ingredient->unit);
                        $parts[$ingredient->id] = ['quantity' => $row['quantity'], 'unit' => $unit];
                    }
                }
                $post->fill(collect($data)->only(['title', 'category', 'content', 'price', 'stock_type'])->all());
                $post->content = $data['content'] ?? '';
                $post->is_active = $request->boolean('status');
                if ($data['stock_type'] === 'static') $post->stock = $data['stock'];
                if ($path) $post->photo_path = $path;
                $post->save();
                $post->ingredients()->sync($parts);
                $after = InventoryUnits::micro($post->stock);
                if ($data['stock_type'] === 'static' && $after !== $before) InventoryLedger::record($request->user()->id, $id ? 'adjustment' : 'opening', $post, $before, $after, [], 'Stok barang jadi dari formulir menu');
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
        return redirect()->route('posts.index')->with('success', 'Menu dan resep disimpan. Muat ulang katalog aplikasi untuk melihat perubahan.');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $post = Post::whereKey($id)->lockForUpdate()->firstOrFail();
            if (DB::table('sale_items')->where('post_id', $id)->exists() || DB::table('inventory_movements')->where('post_id', $id)->exists()) {
                throw ValidationException::withMessages(['product' => 'Menu memiliki riwayat stok/penjualan. Nonaktifkan menu agar riwayat tetap tersimpan.']);
            }
            $post->delete();
        });
        return back()->with('success', 'Menu dihapus.');
    }

    public function toggle(Post $post)
    {
        DB::transaction(function () use ($post) {
            $locked = Post::whereKey($post->id)->lockForUpdate()->firstOrFail();
            $locked->update(['is_active' => !$locked->is_active]);
        });
        return back()->with('success', 'Status produk diperbarui.');
    }
}
