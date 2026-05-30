@extends('layouts.admin')
@section('title', 'Ana sayfa banner')

@section('content')
    <x-admin.page-header title="Ana sayfa vitrin (liste)" subtitle="Klasik liste görünümü">
        <x-slot:actions>
            <a href="{{ route('admin.home-banners.builder') }}" class="admin-btn admin-btn-primary">Görsel düzenleyici</a>
            <a href="{{ route('admin.home-banners.create') }}" class="admin-btn admin-btn-secondary">+ Yeni öğe</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="post" action="{{ route('admin.home-banners.dimensions') }}" class="admin-card p-5 mb-6 border-l-4 border-teal-500 bg-teal-50/40 space-y-4">
        @csrf @method('PUT')
        <div>
            <p class="text-sm font-bold text-slate-900">Slider / geniş banner ölçüsü</p>
            <p class="mt-1 text-sm text-slate-700">
                Sadece <strong>Slider</strong> ve <strong>Banner</strong> türleri bu <strong>genişlik × yükseklik</strong> oranını kullanır.
                Ürün ve kategori kutuları ayrı ızgarada gösterilir.
                Tasarımları bu piksel ölçüsüne göre export edin; farklı oranlar kenardan hafif kırpılır.
            </p>
        </div>
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="admin-label">Genişlik (px)</label>
                <input type="number" name="home_banner_width" value="{{ old('home_banner_width', $spec['width']) }}" min="400" max="3840" required class="admin-input w-32">
            </div>
            <div>
                <label class="admin-label">Yükseklik (px)</label>
                <input type="number" name="home_banner_height" value="{{ old('home_banner_height', $spec['height']) }}" min="200" max="2160" required class="admin-input w-32">
            </div>
            <button type="submit" class="admin-btn admin-btn-primary py-2">Ölçüyü kaydet</button>
        </div>
        <x-admin.image-spec key="home_banner_slider" class="!mt-2" />
        <p class="text-xs text-slate-600">Bu ölçü slider ve geniş banner blokları içindir. Kutu alanı ölçüsü düzenleyicide kolon başlığında gösterilir.</p>
    </form>

    @if($banners->isEmpty())
        <div class="admin-card p-10 text-center text-slate-500">
            <p>Henüz banner yok.</p>
            <a href="{{ route('admin.home-banners.create') }}" class="admin-btn admin-btn-primary mt-4 inline-flex">İlk bannerı ekle</a>
        </div>
    @else
        <p class="text-sm text-slate-500 mb-3">Satırları tutup sürükleyerek sırayı değiştirin. Sıra otomatik kaydedilir.</p>
        <ul id="banner-sort-list" class="space-y-3" data-reorder-url="{{ route('admin.home-banners.reorder') }}">
            @foreach($banners as $banner)
                <li class="admin-banner-row admin-card flex flex-wrap sm:flex-nowrap items-center gap-4 p-4 cursor-grab active:cursor-grabbing" data-id="{{ $banner->id }}" draggable="true">
                    <span class="admin-banner-row__handle text-slate-400 select-none" aria-hidden="true" title="Sürükle">⋮⋮</span>
                    <div class="admin-banner-row__thumb shrink-0 w-32 h-[4.5rem] rounded-lg overflow-hidden border border-slate-200 bg-slate-100">
                        @if($banner->imageUrl())
                            <img src="{{ $banner->imageUrl() }}" alt="" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded bg-slate-100 text-slate-600">{{ $banner->typeLabel() }}</span>
                            <p class="font-semibold text-slate-900 truncate">{{ $banner->displayTitle() ?: '— Başlıksız —' }}</p>
                        </div>
                        @if($banner->product)
                            <p class="text-xs text-teal-700 mt-0.5">Ürün: {{ $banner->product->name }}</p>
                        @elseif($banner->category)
                            <p class="text-xs text-teal-700 mt-0.5">Kategori: {{ $banner->category->name }}</p>
                        @elseif($banner->link_url)
                            <p class="text-xs text-teal-700 truncate mt-0.5">{{ $banner->link_url }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($banner->active)
                            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-1 rounded">Yayında</span>
                        @else
                            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded">Kapalı</span>
                        @endif
                        <a href="{{ route('admin.home-banners.edit', $banner) }}" class="admin-btn admin-btn-secondary text-xs py-1.5">Düzenle</a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/admin-banners.js') }}" defer></script>
@endpush
