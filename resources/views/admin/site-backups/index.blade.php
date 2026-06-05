@extends('layouts.admin')

@section('title', 'Site Yedekleri')

@section('content')
    <section class="admin-dashboard-hero">
        <div>
            <p class="admin-dashboard-eyebrow">Sistem güvenliği</p>
            <h2>Site Yedekleri</h2>
            <p>SQLite veritabanı ve yüklenen medya dosyalarını tek zip içinde yedekleyin. Yedekleri indirebilir, daha sonra yükleyip geri dönebilirsiniz.</p>
        </div>
        <div class="admin-dashboard-hero__actions">
            <a href="{{ \Illuminate\Support\Facades\Route::has('admin.theme.edit') ? route('admin.theme.edit') : route('admin.settings.edit') }}" class="admin-btn admin-btn-secondary">Tema yedekleri</a>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="admin-btn admin-btn-primary">Mağazayı aç</a>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <section class="admin-card p-5">
            <p class="admin-dashboard-eyebrow">Kapsam</p>
            <h3 class="mt-1 text-lg font-black text-slate-900">DB + medya</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Ürünler, siparişler, ayarlar, tema, banner kayıtları ve `storage/app/public` içindeki görseller yedeğe girer.</p>
        </section>
        <section class="admin-card p-5">
            <p class="admin-dashboard-eyebrow">Güvenlik</p>
            <h3 class="mt-1 text-lg font-black text-slate-900">Restore öncesi yedek</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Geri yükleme başlatıldığında mevcut site otomatik olarak ayrıca zip yedeğe alınır.</p>
        </section>
        <section class="admin-card p-5">
            <p class="admin-dashboard-eyebrow">Hariç</p>
            <h3 class="mt-1 text-lg font-black text-slate-900">Kod ve gizli anahtar yok</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">`.env`, vendor, node_modules ve uygulama kodu bu pakete dahil edilmez.</p>
        </section>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[1.15fr_0.85fr]">
        <section class="admin-card p-5 sm:p-7">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="admin-dashboard-eyebrow">Yeni yedek</p>
                    <h3 class="mt-1 text-xl font-black text-slate-900">Komple Site Yedeği Al</h3>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Oluşturulan zip dosyası özel storage alanında saklanır ve listeden indirilebilir.</p>
                </div>
                <form method="post" action="{{ route('admin.site-backups.store') }}" class="flex w-full flex-col gap-2 sm:max-w-md sm:flex-row">
                    @csrf
                    <label class="sr-only" for="backup_name">Yedek adı</label>
                    <input id="backup_name" name="backup_name" type="text" maxlength="80" placeholder="Yedek adı" class="admin-input">
                    <button type="submit" class="admin-btn admin-btn-primary shrink-0">Yedek Al</button>
                </form>
            </div>
        </section>

        <section class="admin-card p-5 sm:p-7">
            <p class="admin-dashboard-eyebrow">Dışarıdan yedek</p>
            <h3 class="mt-1 text-xl font-black text-slate-900">Zip Yükle</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Sadece bu panelin oluşturduğu `kosar_site_backup` manifestli zip dosyaları kabul edilir.</p>
            <form method="post" action="{{ route('admin.site-backups.upload') }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                @csrf
                <input type="file" name="backup_file" accept="application/zip,.zip" class="admin-input">
                <button type="submit" class="admin-btn admin-btn-secondary w-full">Yedeği Yükle</button>
            </form>
        </section>
    </div>

    <section class="admin-card mt-5 p-5 sm:p-7">
        <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="admin-dashboard-eyebrow">Kayıtlı yedekler</p>
                <h3 class="mt-1 text-xl font-black text-slate-900">Yedek Listesi</h3>
            </div>
            <p class="text-xs text-slate-500 break-all">Klasör: {{ $backupDirectory }}</p>
        </div>

        <div class="mt-4 space-y-3">
            @forelse($backups as $backup)
                @php
                    $manifest = $backup['manifest'] ?? [];
                    $sizeMb = number_format(((int) $backup['size']) / 1024 / 1024, 2, ',', '.');
                    $storageCount = data_get($manifest, 'storage.file_count');
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-[0_1px_2px_rgb(15_23_42/0.04)]">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <h4 class="truncate text-sm font-black text-slate-900">{{ $backup['name'] }}</h4>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-500">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ $backup['created_at'] }}</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ $sizeMb }} MB</span>
                                @if($storageCount !== null)
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ $storageCount }} medya dosyası</span>
                                @endif
                                @if(($manifest['reason'] ?? '') === 'pre-restore')
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-700">Restore öncesi</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 lg:min-w-[28rem]">
                            <form method="get" action="{{ route('admin.site-backups.download') }}">
                                <input type="hidden" name="file" value="{{ $backup['name'] }}">
                                <button type="submit" class="admin-btn admin-btn-secondary w-full justify-center text-xs">İndir</button>
                            </form>
                            <form method="post" action="{{ route('admin.site-backups.restore') }}" onsubmit="return confirm('Bu yedek geri yüklenecek. Mevcut site önce otomatik yedeklenecek. Devam edilsin mi?')">
                                @csrf
                                <input type="hidden" name="file" value="{{ $backup['name'] }}">
                                <input type="hidden" name="confirm_restore" value="1">
                                <button type="submit" class="admin-btn admin-btn-primary w-full justify-center text-xs">Geri Yükle</button>
                            </form>
                            <form method="post" action="{{ route('admin.site-backups.destroy') }}" onsubmit="return confirm('Bu yedek silinsin mi?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="file" value="{{ $backup['name'] }}">
                                <button type="submit" class="admin-btn admin-btn-secondary w-full justify-center text-xs">Sil</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
                    Henüz site yedeği yok. Canlıya büyük değişiklik almadan önce ilk yedeği oluşturun.
                </div>
            @endforelse
        </div>
    </section>
@endsection
