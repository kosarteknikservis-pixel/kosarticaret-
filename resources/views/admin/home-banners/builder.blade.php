@extends('layouts.admin')
@section('title', 'Ana sayfa düzenleyici')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/admin-home-builder.css') }}">
@endpush

@section('content')
    <div class="hp-builder" id="hp-builder"
         data-layout-save="{{ route('admin.home-banners.layout.save') }}"
         data-row-store="{{ route('admin.home-banners.rows.store') }}"
         data-row-destroy="{{ str_replace('/0', '/__ID__', route('admin.home-banners.rows.destroy', ['homeRow' => 0])) }}"
         data-quick-template="{{ str_replace('/0/', '/__ID__/', route('admin.home-banners.quick', ['home_banner' => 0])) }}"
         data-panel-create="{{ route('admin.home-banners.panel.create') }}">
        <header class="hp-builder__topbar">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Ana sayfa düzenleyici</h2>
                <p class="text-xs text-slate-500">Satır (konteyner) → kolonlar yan yana · blokları sürükleyip bırakın</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span id="hp-save-status" class="text-xs text-slate-500"></span>
                <a href="{{ route('admin.home-banners.index') }}" class="admin-btn admin-btn-secondary text-xs">Liste</a>
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary text-xs">Mağazayı aç</a>
            </div>
        </header>

        <div class="hp-builder__workspace">
            <aside class="hp-builder__widgets">
                <p class="hp-builder__widgets-title">Satır ekle</p>
                @foreach($layoutPresets as $preset => $cols)
                    <button type="button" class="hp-widget hp-widget--row" data-row-preset="{{ $preset }}">
                        <span class="hp-widget__icon">▦</span>
                        <span class="hp-widget__label">
                            @switch($preset)
                                @case('12') Tam genişlik @break
                                @case('6-6') 2 kolon %50-%50 @break
                                @case('4-4-4') 3 kolon @break
                                @case('8-4') 2/3 + 1/3 @break
                                @case('4-8') 1/3 + 2/3 @break
                                @case('3-3-3-3') 4 kolon @break
                                @default {{ $preset }}
                            @endswitch
                        </span>
                    </button>
                @endforeach

                <p class="hp-builder__widgets-title mt-6">Blok ekle</p>
                <p class="text-[10px] text-slate-400 mb-2">Önce kolondaki + ile hedef seçin veya blok tipi:</p>
                @foreach(\App\Models\HomeBanner::TYPES as $type)
                    <button type="button" class="hp-widget" data-add-type="{{ $type }}">
                        <span class="hp-widget__icon">+</span>
                        <span class="hp-widget__label">@lang('shop.banner_type_'.$type)</span>
                    </button>
                @endforeach

                <details class="hp-builder__spec mt-4" open>
                    <summary class="text-xs font-semibold text-slate-400 cursor-pointer">Ölçü rehberi (px)</summary>
                    <div class="mt-2 space-y-2 text-[10px] text-slate-400">
                        <p><strong class="text-slate-300">Slider:</strong> {{ $spec['ratio_label'] }}</p>
                        <p><strong class="text-slate-300">Kutu:</strong> {{ \App\Support\ImageUploadSpec::label('home_banner_tile') }}</p>
                        <p><strong class="text-slate-300">Ürün kutusu:</strong> {{ \App\Support\ImageUploadSpec::label('product_cover') }}</p>
                        <p><strong class="text-slate-300">Ürün listesi:</strong> kategori / marka / seçili ürünler</p>
                        <p><strong class="text-slate-300">Kategori:</strong> {{ \App\Support\ImageUploadSpec::label('category') }}</p>
                    </div>
                </details>
                <details class="hp-builder__spec mt-2">
                    <summary class="text-xs font-semibold text-slate-400 cursor-pointer">Slider ölçüsü ayarla</summary>
                    <form method="post" action="{{ route('admin.home-banners.dimensions') }}" class="mt-2 space-y-2">
                        @csrf @method('PUT')
                        <input type="hidden" name="from_builder" value="1">
                        <input type="number" name="home_banner_width" value="{{ $spec['width'] }}" class="admin-input w-full text-sm" min="400" max="3840">
                        <input type="number" name="home_banner_height" value="{{ $spec['height'] }}" class="admin-input w-full text-sm" min="200" max="2160">
                        <button type="submit" class="admin-btn admin-btn-secondary w-full text-xs py-1.5">Kaydet</button>
                    </form>
                </details>
            </aside>

            <main class="hp-builder__canvas-wrap">
                <div class="hp-builder__canvas" id="hp-canvas">
                    @foreach($rows as $row)
                        @include('admin.home-banners.partials.builder-row', ['row' => $row])
                    @endforeach
                </div>
                <p class="text-center text-xs text-slate-400 py-3">Hero ve ürün listeleri ayrı ayarlardan gelir · bu editör üst vitrin satırlarını yönetir</p>
            </main>

            <aside class="hp-builder__panel" id="builder-panel">
                <div class="hp-builder__panel-placeholder" id="panel-placeholder">
                    <p class="font-semibold text-slate-700">Blok veya kolon</p>
                    <p class="text-sm text-slate-500 mt-2">Bloka tıklayın — ayarlar burada. Kolondaki <strong>+</strong> ile o hücreye blok ekleyin.</p>
                </div>
                <div class="hp-builder__panel-content hidden" id="panel-content"></div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" defer></script>
    <script src="{{ asset('js/admin-home-builder.js') }}" defer></script>
@endpush
