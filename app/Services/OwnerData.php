<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Ingredient;
use App\Models\Post;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OwnerData
{
    public const SECTIONS = ['sales' => 'Penjualan', 'movements' => 'Pemakaian & mutasi stok', 'products' => 'Menu & resep', 'ingredients' => 'Stok bahan baku', 'attendances' => 'Presensi karyawan', 'employees' => 'Akun & jabatan', 'suppliers' => 'Supplier', 'purchases' => 'Pembelian'];

    public function overview(): array
    {
        $today = now('Asia/Jakarta')->toDateString();
        return ['business_date' => $today, 'sales_today' => Sale::where('business_date', $today)->count(), 'revenue_today' => (int) Sale::where('business_date', $today)->sum('total'),
            'products' => Post::count(), 'ingredients' => Ingredient::count(), 'low_stock' => Ingredient::whereColumn('stock', '<=', 'minimum_stock')->count(),
            'employees' => User::count(), 'attendances_today' => Attendance::where('work_date', $today)->count(), 'sections' => self::SECTIONS];
    }

    public function listing(string $section, ?string $search = null)
    {
        abort_unless(isset(self::SECTIONS[$section]), 404);
        // All records can be reached through pagination; no silent latest-N cutoff.
        $query = match ($section) {
            'sales' => Sale::with('items', 'cashier:id,name')->latest('id'),
            'movements' => DB::table('inventory_movements')->orderByDesc('id'),
            'products' => Post::with('ingredients')->latest('id'),
            'ingredients' => Ingredient::orderBy('name'),
            'attendances' => Attendance::query()->join('users', 'users.id', '=', 'attendances.user_id')->select('attendances.*', 'users.name as employee_name')->latest('attendances.id'),
            'employees' => User::select('id', 'name', 'email', 'role')->orderBy('name'),
            'suppliers' => DB::table('suppliers')->orderBy('name'),
            'purchases' => DB::table('stock_purchases')->join('suppliers', 'suppliers.id', '=', 'stock_purchases.supplier_id')->select('stock_purchases.*', 'suppliers.name as supplier_name')->latest('stock_purchases.id'),
        };
        if ($search) {
            $column = match ($section) { 'products' => 'title', 'ingredients', 'employees', 'suppliers' => 'name', 'movements' => 'item_name', 'attendances' => 'users.name', 'purchases' => 'invoice', default => 'sales.id' };
            $query->where($column, 'like', '%'.$search.'%');
        }
        $page = $query->paginate(30)->withQueryString();
        $page->through(function ($row) use ($section) {
            $id = $row->id;
            $details = [];
            switch ($section) {
                case 'sales':
                    $title = 'Transaksi #'.$id.' — Rp '.number_format($row->total, 0, ',', '.');
                    $subtitle = $row->business_date.' · '.$row->cashier->name.' · '.$row->payment_method;
                    foreach ($row->items as $item) $details[] = $item->quantity.' × '.$item->product_name.' = Rp '.number_format($item->total, 0, ',', '.');
                    $details[] = 'Subtotal Rp '.$row->subtotal.'; pajak '.$row->tax_percent.'% = Rp '.$row->tax;
                    break;
                case 'products':
                    $title = $row->title; $subtitle = 'Rp '.$row->price.' · '.$row->category.' · '.($row->is_active ? 'Aktif' : 'Nonaktif');
                    $details[] = 'Tipe stok: '.$row->stock_type.($row->stock_type === 'static' ? '; stok '.$row->stock.' pcs' : '');
                    foreach ($row->ingredients as $ingredient) $details[] = $ingredient->name.': '.((float) $ingredient->pivot->quantity).' '.$ingredient->pivot->unit.' per porsi';
                    if ($row->stock_type === 'bom' && $row->ingredients->isEmpty()) $details[] = 'Resep belum diatur — belum dapat dijual';
                    break;
                case 'ingredients':
                    $title = $row->name; $subtitle = ((float) $row->stock).' '.$row->unit;
                    $details[] = 'Minimum '.((float) $row->minimum_stock).' '.$row->unit.($row->stock <= $row->minimum_stock ? ' · STOK MENIPIS' : ' · Aman');
                    break;
                case 'movements':
                    $title = $row->item_name.' · '.((float) $row->quantity).' '.$row->unit;
                    $subtitle = $row->kind.' · '.$row->created_at;
                    $details[] = 'Stok '.((float) $row->balance_before).' → '.((float) $row->balance_after).' '.$row->unit;
                    if ($row->sale_id) $details[] = 'Penjualan #'.$row->sale_id.' / item #'.$row->sale_item_id;
                    if ($row->stock_purchase_id) $details[] = 'Pembelian #'.$row->stock_purchase_id;
                    if ($row->note) $details[] = $row->note;
                    break;
                case 'attendances':
                    $title = $row->employee_name.' · '.$row->type; $subtitle = $row->work_date.' · '.$row->recorded_at;
                    $details[] = 'GPS '.$row->latitude.', '.$row->longitude.' · jarak '.round($row->distance_meters).' m';
                    $details[] = 'Wajah cocok · presensi #'.$id;
                    break;
                case 'employees': $title = $row->name; $subtitle = $row->email.' · '.$row->role; break;
                case 'suppliers': $title = $row->name; $subtitle = ($row->phone ?? 'Tanpa telepon').' · '.($row->is_active ? 'Aktif' : 'Nonaktif'); $details[] = $row->address ?? ''; break;
                case 'purchases': $title = $row->invoice.' · '.$row->supplier_name; $subtitle = $row->purchased_on.' · Rp '.number_format($row->total_cost, 0, ',', '.'); break;
            }
            return compact('id', 'title', 'subtitle', 'details');
        });
        return $page;
    }
}
