<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireOwner
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->role === 'owner', 403, 'Halaman ini hanya untuk Owner. Gunakan aplikasi untuk kasir dan presensi.');
        return $next($request);
    }
}
