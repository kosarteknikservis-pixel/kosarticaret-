@extends('layouts.admin')
@section('title', 'Pazaryeri kanalları')

@section('content')
    <x-admin.page-header title="Pazaryeri kanalları" subtitle="API bağlantıları ve kanal kuralları" />

    <x-admin.integrations-nav active="marketplace-channels" />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($channels as $channel)
            <article class="admin-card p-5 flex flex-col gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $channel->name }}</h2>
                    <p class="text-sm text-slate-500 mt-1">
                        {{ $channel->type === 'feed' ? 'Feed / trafik kanalı' : 'Ürün + sipariş pazaryeri' }}
                    </p>
                </div>
                <dl class="text-sm space-y-2 text-slate-600">
                    <div class="flex justify-between gap-3"><dt>Durum</dt><dd class="font-semibold">{{ $channel->is_active ? 'Aktif' : 'Pasif' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>Ortam</dt><dd>{{ $channel->environment === 'sandbox' ? 'Test' : 'Canlı' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt>API</dt><dd>{{ $channel->isConfigured() ? 'Kayıtlı' : 'Eksik' }}</dd></div>
                </dl>
                <a href="{{ route('admin.integrations.marketplace.channels.edit', $channel) }}" class="admin-btn admin-btn-secondary mt-auto">Ayarları aç</a>
            </article>
        @endforeach
    </div>
@endsection
