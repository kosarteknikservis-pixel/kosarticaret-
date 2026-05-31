@extends('layouts.admin')

@section('title', 'Profil')

@section('content')
    <x-admin.page-header
        title="Profil"
        subtitle="Panel hesabınızın ad, e-posta ve şifre bilgilerini yönetin."
    />

    <form method="post" action="{{ route('admin.profile.update') }}" class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <section class="admin-card p-6 sm:p-8 space-y-5">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Hesap bilgileri</h3>
                <p class="mt-1 text-sm text-slate-500">Bu bilgiler panel girişinde ve üst barda kullanılır.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="profile-name" class="admin-label">Ad soyad</label>
                    <input
                        id="profile-name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="admin-input"
                        autocomplete="name"
                    >
                </div>

                <div>
                    <label for="profile-email" class="admin-label">E-posta</label>
                    <input
                        id="profile-email"
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        class="admin-input"
                        autocomplete="username"
                    >
                </div>
            </div>
        </section>

        <section class="admin-card p-6 sm:p-8 space-y-5">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Şifre değiştir</h3>
                <p class="mt-1 text-sm text-slate-500">Şifreyi değiştirmek istemiyorsanız bu alanları boş bırakın.</p>
            </div>

            <div>
                <label for="profile-current-password" class="admin-label">Mevcut şifre</label>
                <input
                    id="profile-current-password"
                    type="password"
                    name="current_password"
                    class="admin-input"
                    autocomplete="current-password"
                >
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="profile-password" class="admin-label">Yeni şifre</label>
                    <input
                        id="profile-password"
                        type="password"
                        name="password"
                        class="admin-input"
                        autocomplete="new-password"
                        placeholder="En az 8 karakter"
                    >
                </div>

                <div>
                    <label for="profile-password-confirmation" class="admin-label">Yeni şifre tekrar</label>
                    <input
                        id="profile-password-confirmation"
                        type="password"
                        name="password_confirmation"
                        class="admin-input"
                        autocomplete="new-password"
                    >
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="admin-btn admin-btn-primary px-8 py-2.5">Profili kaydet</button>
            <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn-secondary px-6 py-2.5">Vazgeç</a>
        </div>
    </form>
@endsection
