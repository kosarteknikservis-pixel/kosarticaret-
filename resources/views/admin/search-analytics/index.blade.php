@extends('layouts.admin')
@section('title', 'Arama analitiği')

@section('content')
    <x-admin.page-header title="Arama analitiği" :subtitle="$periodLabel">
        <x-slot:actions>
            <div class="flex gap-2">
                @foreach(['7d' => '7 gün', '30d' => '30 gün', '90d' => '90 gün'] as $key => $label)
                    <a href="{{ route('admin.search-analytics.index', ['period' => $key]) }}"
                       class="admin-btn {{ $period === $key ? 'admin-btn-primary' : 'admin-btn-secondary' }} text-xs py-1.5">{{ $label }}</a>
                @endforeach
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="admin-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Toplam arama</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format((int) ($totals->total_searches ?? 0)) }}</p>
        </div>
        <div class="admin-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Benzersiz sorgu</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format((int) ($totals->unique_queries ?? 0)) }}</p>
        </div>
        <div class="admin-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sonuçsuz arama</p>
            <p class="mt-2 text-2xl font-bold text-amber-700">{{ number_format((int) ($totals->zero_result_searches ?? 0)) }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="admin-card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="font-semibold text-slate-900">En çok arananlar</h2>
            </div>
            <table class="admin-table">
                <thead><tr><th>Sorgu</th><th>Arama</th><th>Ort. sonuç</th></tr></thead>
                <tbody>
                    @forelse($topQueries as $row)
                        <tr>
                            <td class="font-medium text-slate-900 max-w-xs truncate">{{ $row->sample_query }}</td>
                            <td>{{ $row->searches }}</td>
                            <td>{{ $row->avg_results }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-8 text-slate-500">Bu dönemde arama kaydı yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="font-semibold text-slate-900">Sonuç bulunamayan aramalar</h2>
                <p class="text-xs text-slate-500 mt-1">Yeni ürün veya eş anlamlı kelime fırsatları</p>
            </div>
            <table class="admin-table">
                <thead><tr><th>Sorgu</th><th>Arama</th></tr></thead>
                <tbody>
                    @forelse($zeroResults as $row)
                        <tr>
                            <td class="font-medium text-slate-900 max-w-xs truncate">{{ $row->sample_query }}</td>
                            <td>{{ $row->searches }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center py-8 text-slate-500">Sonuçsuz arama yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
