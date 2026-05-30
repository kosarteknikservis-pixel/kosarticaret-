@extends('layouts.admin')
@section('title', 'Mesaj: '.$message->subject)

@section('content')
    <x-admin.page-header :title="$message->subject" :subtitle="$message->name.' · '.$message->created_at->format('d.m.Y H:i')" />
    <div class="max-w-2xl admin-card p-6 sm:p-8 space-y-4">
        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">Ad soyad</dt><dd class="font-medium">{{ $message->name }}</dd></div>
            <div><dt class="text-slate-500">E-posta</dt><dd><a href="mailto:{{ $message->email }}" class="text-teal-700 font-medium">{{ $message->email }}</a></dd></div>
            @if($message->phone)
                <div><dt class="text-slate-500">Telefon</dt><dd>{{ $message->phone }}</dd></div>
            @endif
        </dl>
        <div class="border-t pt-4">
            <p class="text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $message->body }}</p>
        </div>
        <div class="admin-form-actions border-t pt-4">
            <a href="{{ route('admin.contact-messages.index') }}" class="admin-btn admin-btn-secondary">Listeye dön</a>
            <form method="post" action="{{ route('admin.contact-messages.destroy', $message) }}" onsubmit="return confirm('Mesaj silinsin mi?')">
                @csrf @method('DELETE')
                <button type="submit" class="admin-btn text-rose-700 border-rose-200 hover:bg-rose-50">Sil</button>
            </form>
        </div>
    </div>
@endsection
