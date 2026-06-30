@extends('layouts.admin')
@section('title', 'Telegram Bildirimleri')

@section('content')
    <x-admin.page-header title="Telegram sipariş bildirimi" subtitle="Yeni siparişler ödeme onaylandığında Telegram’a düşer">
        <x-slot:actions>
            <form method="post" action="{{ route('admin.integrations.notifications.telegram.test') }}">
                @csrf
                <button type="submit" class="admin-btn admin-btn-secondary px-4 py-2.5">Test bildirimi gönder</button>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.integrations-nav active="notifications-telegram" />

    @if($enabled && $configured)
        <div class="admin-card p-4 mb-5 border-emerald-200 bg-emerald-50/60">
            <p class="text-sm font-semibold text-emerald-900">Telegram bildirimleri aktif. Yeni siparişler tek chat’e gönderilir.</p>
        </div>
    @elseif($enabled)
        <div class="admin-card p-4 mb-5 border-amber-200 bg-amber-50/70">
            <p class="text-sm font-semibold text-amber-900">Bildirimler açık ancak bot token veya chat ID eksik.</p>
        </div>
    @endif

    <form method="post" action="{{ route('admin.integrations.notifications.telegram.update') }}" class="admin-card p-5 sm:p-6 max-w-3xl space-y-5">
        @csrf @method('PUT')

        <div>
            <h2 class="font-bold text-slate-900">Bot ayarları</h2>
            <p class="text-sm text-slate-500 mt-1">BotFather’dan aldığınız token ve bildirim gidecek chat ID.</p>
        </div>

        <label class="admin-checkbox">
            <input type="checkbox" name="telegram_enabled" value="1" @checked(($values['telegram_enabled'] ?? '0') === '1')>
            Telegram sipariş bildirimlerini aktif et
        </label>

        <div>
            <label class="admin-label">Bot token</label>
            <input type="password" name="telegram_bot_token" value="" class="admin-input font-mono text-sm" autocomplete="off" placeholder="{{ !empty($values['telegram_bot_token']) ? 'Kayıtlı — değiştirmek için yazın' : '123456789:ABCdefGHI...' }}">
        </div>

        <div>
            <label class="admin-label">Chat ID</label>
            <input name="telegram_chat_id" value="{{ old('telegram_chat_id', $values['telegram_chat_id'] ?? '') }}" class="admin-input font-mono text-sm" placeholder="-1001234567890">
            <p class="text-xs text-slate-500 mt-1">Grup/kanal için negatif ID kullanılır. Botu gruba ekleyip bir kez mesaj atın; @userinfobot veya getUpdates ile ID alabilirsiniz.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 leading-relaxed">
            <p class="font-semibold text-slate-800 mb-2">Ne zaman bildirim gider?</p>
            <ul class="list-disc pl-5 space-y-1">
                <li>Havale / kapıda ödeme: sipariş oluşunca</li>
                <li>Kredi kartı: ödeme başarılı olunca</li>
                <li>Trendyol / pazaryeri: yeni sipariş içeri alınınca</li>
            </ul>
            <p class="mt-3">Mesaj başlığı: <strong>Yeni Sipariş ✅</strong> — panel linki ve site adresi eklenir.</p>
        </div>

        <button type="submit" class="admin-btn admin-btn-primary px-6 py-2.5">Kaydet</button>
    </form>
@endsection
