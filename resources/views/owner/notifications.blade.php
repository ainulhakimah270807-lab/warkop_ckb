@extends('layouts.dashmin')
@section('page_title', 'Notifikasi stok')
@section('content')
<h1 class="h3">Stok menipis</h1><div class="warkop-card">@forelse($ingredients as $ingredient)<div class="border-bottom py-3"><strong>{{ $ingredient->name }}</strong><div class="text-danger">Tersisa {{ (float)$ingredient->stock }} {{ $ingredient->unit }} · batas minimum {{ (float)$ingredient->minimum_stock }}</div></div>@empty<p>Semua stok bahan di atas batas minimum.</p>@endforelse<a class="btn btn-dark mt-3" href="{{ route('purchases.index') }}">Catat pembelian bahan</a></div>
@endsection
