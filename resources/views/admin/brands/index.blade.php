@extends('layouts.admin')

@section('title', 'Markalar')



@section('content')

    <x-admin.page-header title="Markalar" subtitle="Ana sayfa şeridi ve marka sayfaları">

        <x-slot:actions><a href="{{ route('admin.brands.create') }}" class="admin-btn admin-btn-primary">+ Marka ekle</a></x-slot:actions>

    </x-admin.page-header>



    <div class="admin-card p-4 mb-6 text-sm text-slate-600 border-l-4 border-teal-500 max-w-3xl">

        <p><strong class="text-slate-900">Ana sayfa:</strong> “Ana sayfa marka şeridi” işaretli ve aktif markaların logosu vitrinde gösterilir.</p>

        <p class="mt-2"><strong class="text-slate-900">Tıklama:</strong> Logo veya isme tıklanınca ziyaretçi o markaya ait ürün listesine gider.</p>

        <p class="mt-2">Bölüm başlığını <a href="{{ route('admin.settings.edit', ['tab' => 'home']) }}" class="text-teal-700 font-medium">Site ayarları → Ana sayfa</a> sekmesinden değiştirebilirsiniz.</p>

    </div>



    <div class="admin-card overflow-hidden">

        <table class="admin-table">

            <thead>

                <tr>

                    <th>Logo</th>

                    <th>Ad</th>

                    <th>Ana sayfa</th>

                    <th>Durum</th>

                    <th></th>

                </tr>

            </thead>

            <tbody>

                @forelse($brands as $b)

                    <tr>

                        <td class="w-28">

                            @if($b->logoUrl())

                                <img src="{{ $b->logoUrl() }}" alt="" class="h-10 max-w-[120px] object-contain rounded bg-white border border-slate-100 p-1">

                            @else

                                <span class="text-xs text-slate-400">Logo yok</span>

                            @endif

                        </td>

                        <td class="font-semibold">{{ $b->name }}</td>

                        <td>

                            @if($b->featured)

                                <span class="admin-badge admin-badge-success">Şeritte</span>

                            @else

                                <span class="admin-badge admin-badge-muted">—</span>

                            @endif

                        </td>

                        <td>

                            <span class="admin-badge {{ $b->active ? 'admin-badge-success' : 'admin-badge-muted' }}">{{ $b->active ? 'Aktif' : 'Pasif' }}</span>

                        </td>

                        <td class="text-right whitespace-nowrap space-x-2">

                            @if($b->active)

                                <a href="{{ route('brands.show', $b) }}" target="_blank" rel="noopener" class="admin-btn admin-btn-secondary text-xs py-1.5">Mağaza</a>

                            @endif

                            <a href="{{ route('admin.brands.edit', $b) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a>

                        </td>

                    </tr>

                @empty

                    <tr><td colspan="5" class="text-center text-slate-500 py-8">Henüz marka yok.</td></tr>

                @endforelse

            </tbody>

        </table>

    </div>

@endsection

