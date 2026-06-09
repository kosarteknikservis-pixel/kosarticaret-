@extends('layouts.admin')
@section('title', 'İletişim mesajları')

@section('content')
    <x-admin.page-header title="İletişim mesajları" :subtitle="$messages->total().' mesaj'" />
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tür</th>
                    <th>Durum</th>
                    <th>Gönderen</th>
                    <th>Konu</th>
                    <th>Tarih</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $msg)
                    <tr class="{{ $msg->isUnread() ? 'bg-amber-50/50' : '' }}">
                        <td>
                            @if($msg->isQuote())
                                <span class="inline-flex rounded-full bg-brand-100 text-brand-800 text-xs font-semibold px-2 py-0.5">Teklif</span>
                            @else
                                <span class="text-xs text-slate-400">İletişim</span>
                            @endif
                        </td>
                        <td>
                            @if($msg->isUnread())
                                <span class="inline-flex rounded-full bg-amber-100 text-amber-800 text-xs font-semibold px-2 py-0.5">Yeni</span>
                            @else
                                <span class="text-xs text-slate-400">Okundu</span>
                            @endif
                        </td>
                        <td>
                            <p class="font-medium text-slate-900">{{ $msg->name }}</p>
                            <p class="text-xs text-slate-500">{{ $msg->email }}</p>
                        </td>
                        <td class="max-w-xs truncate">{{ $msg->subject }}</td>
                        <td class="text-slate-500 whitespace-nowrap">{{ $msg->created_at->format('d.m.Y H:i') }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.contact-messages.show', $msg) }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">Oku</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-10 text-slate-500">Henüz mesaj yok.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($messages->hasPages())<div class="p-4 border-t">{{ $messages->links() }}</div>@endif
    </div>
@endsection
