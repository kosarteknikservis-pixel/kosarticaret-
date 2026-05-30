@extends('layouts.admin')
@section('title', 'Ürünler')

@section('content')
    <x-admin.page-header title="Ürünler" subtitle="Katalogdaki tüm ürünler">
        <x-slot:actions>
            <a href="{{ route('admin.products.bulk-update') }}" class="admin-btn admin-btn-secondary">Toplu güncelleme</a>
            <a href="{{ route('admin.products.create') }}" class="admin-btn admin-btn-primary">+ Yeni ürün</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ürün</th>
                    <th>SKU</th>
                    <th>Fiyat</th>
                    <th>Stok</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $p)
                    <tr>
                        <td class="font-semibold text-slate-900">{{ $p->name }}</td>
                        <td class="font-mono text-xs text-slate-500">{{ $p->sku }}</td>
                        <td>{{ number_format($p->price, 2, ',', '.') }} ₺</td>
                        <td>
                            @if($p->stock === 0)
                                <span class="text-red-600 font-bold">0</span>
                            @elseif($p->stock <= 3)
                                <span class="text-amber-600 font-bold">{{ $p->stock }}</span>
                            @else
                                {{ $p->stock }}
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.products.edit', $p) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-slate-500 py-8">Henüz ürün yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())
        <div class="mt-4">{{ $products->links() }}</div>
    @endif
@endsection
