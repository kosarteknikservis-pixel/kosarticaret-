@extends('layouts.admin')

@section('title', 'Ödeme')

@section('content')
    <x-admin.page-header
        title="Ödeme"
        subtitle="Kredi kartı sanal POS entegrasyonları — yapılandırmak istediğiniz sağlayıcıyı seçin"
    />

    <x-admin.integrations-nav active="index" />

    <p class="text-sm text-slate-600 mb-6">
        Mağazada aktif kredi kartı sağlayıcısı: <strong>{{ $activeLabel }}</strong>
        · Havale / kapıda ödeme:
        <a href="{{ route('admin.settings.edit', ['tab' => 'shipping']) }}" class="text-teal-700 font-semibold">Site ayarları → Kargo & ödeme</a>
    </p>

    <div class="grid sm:grid-cols-2 gap-4 max-w-3xl">
        @foreach($providers as $provider)
            <a href="{{ $provider['url'] }}" class="admin-integration-card group">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-bold text-slate-900 group-hover:text-teal-800">{{ $provider['name'] }}</h2>
                    @if($provider['active'])
                        <span class="shrink-0 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Aktif</span>
                    @elseif($provider['configured'])
                        <span class="shrink-0 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Hazır</span>
                    @else
                        <span class="shrink-0 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-amber-50 text-amber-800">Kurulum</span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $provider['description'] }}</p>
                <p class="mt-4 text-sm font-semibold text-teal-700 group-hover:text-teal-900">Ayarlara git →</p>
            </a>
        @endforeach
    </div>
@endsection
