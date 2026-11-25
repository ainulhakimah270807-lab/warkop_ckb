<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\User;
use App\Services\OwnerData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OwnerController extends Controller
{
    public const WEB_TARGETS = ['dashboard' => '/admin', 'products' => '/posts', 'ingredients' => '/ingredients', 'purchases' => '/purchases', 'reports' => '/reports', 'notifications' => '/notifications', 'settings' => '/settings', 'owner' => '/owner'];

    public function apiOverview(OwnerData $data) { return response()->json($data->overview()); }
    public function apiData(Request $request, string $section, OwnerData $data) { return response()->json($data->listing($section, mb_substr((string) $request->query('search', ''), 0, 100))); }
    public function index(Request $request, OwnerData $data)
    {
        $section = $request->query('section', 'sales');
        return view('owner.index', ['overview' => $data->overview(), 'sections' => OwnerData::SECTIONS, 'section' => $section, 'rows' => $data->listing($section, $request->query('search'))]);
    }

    public function sale(Sale $sale)
    {
        return view('owner.sale', ['sale' => $sale->load('items', 'cashier:id,name'), 'movements' => DB::table('inventory_movements')->where('sale_id', $sale->id)->orderBy('id')->get()]);
    }

    public function ticket(Request $request)
    {
        $data = $request->validate(['target' => 'required|in:'.implode(',', array_keys(self::WEB_TARGETS))]);
        $token = bin2hex(random_bytes(32));
        DB::table('owner_web_tickets')->where('expires_at', '<', now())->delete();
        DB::table('owner_web_tickets')->insert(['token_hash' => hash('sha256', $token), 'user_id' => $request->user()->id, 'target' => self::WEB_TARGETS[$data['target']], 'expires_at' => now()->addSeconds(60)]);
        return response()->json(['url' => $request->getSchemeAndHttpHost().'/owner/mobile-login?ticket='.$token])->header('Cache-Control', 'no-store');
    }

    public function webLogin(Request $request)
    {
        $request->validate(['ticket' => 'required|string|size:64']);
        [$user, $target] = DB::transaction(function () use ($request) {
            $ticket = DB::table('owner_web_tickets')->where('token_hash', hash('sha256', $request->query('ticket')))->lockForUpdate()->first();
            abort_unless($ticket && now()->lt($ticket->expires_at), 403, 'Tautan login sudah kedaluwarsa atau sudah digunakan. Buka lagi dari aplikasi.');
            $user = User::find($ticket->user_id);
            abort_unless($user?->role === 'owner', 403);
            DB::table('owner_web_tickets')->where('id', $ticket->id)->delete();
            return [$user, $ticket->target];
        });
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        return redirect($target)->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function settings()
    {
        return view('settings.index', ['settings' => (array) DB::table('pos_settings')->find(1)]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate(['store_name' => 'required|string|max:255', 'store_address' => 'nullable|string|max:255', 'receipt_footer' => 'nullable|string|max:255', 'tax_percent' => 'required|numeric|between:0,100|decimal:0,2']);
        $data['store_address'] = $data['store_address'] ?? '';
        $data['receipt_footer'] = $data['receipt_footer'] ?? '';
        DB::table('pos_settings')->where('id', 1)->update($data + ['low_stock_alert' => $request->boolean('low_stock_alert'), 'auto_print' => $request->boolean('auto_print')]);
        return back()->with('success', 'Pengaturan toko tersimpan dan berlaku di kasir aplikasi.');
    }
}
