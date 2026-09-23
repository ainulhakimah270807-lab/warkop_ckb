<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query();
        if ($request->filled('search')) {
            $query->where(function ($builder) use ($request) {
                $builder->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('category', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $posts = $query->latest()->get();
        $categories = Post::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        // Kirim data ke View
        return view('posts.index', compact('posts', 'categories'));
    }

    // Form Tambah Produk
    public function create()
    {
        return view('posts.create');
    }

    // Simpan Produk Baru
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'content' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_type' => 'required|string|in:bom,static,none',
        ]);

        Post::create([
            'title' => $request->input('title'),
            'category' => $request->input('category'),
            'content' => $request->input('content', ''),
            'price' => $request->input('price'),
            'stock_type' => $request->input('stock_type'),
            'is_active' => $request->boolean('status'),
        ]);
        return redirect('/posts')->with('success', 'Data berhasil ditambahkan!');
    }

    // Form Edit Produk
    public function edit($id)
    {
        $post = Post::findOrFail($id);
        return view('posts.edit', compact('post'));
    }

    // Update Produk
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'content' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_type' => 'required|string|in:bom,static,none',
        ]);

        $post = Post::findOrFail($id);
        $post->update([
            'title'   => $request->input('title'),
            'category' => $request->input('category'),
            'content' => $request->input('content', ''),
            'price' => $request->input('price'),
            'stock_type' => $request->input('stock_type'),
            'is_active' => $request->boolean('status'),
        ]);

        return redirect('/posts')->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $post->delete();

        return redirect('/posts')->with('success', 'Data berhasil dihapus dengan method DELETE!');
    }

    public function toggle(Post $post)
    {
        $post->update(['is_active' => ! $post->is_active]);
        return back()->with('success', 'Status produk berhasil diperbarui.');
    }
}
