@extends('layouts.admin')
@section('title', 'AI sohbet #'.$conversation->id)

@section('content')
    <x-admin.page-header :title="'AI sohbet #'.$conversation->id" :subtitle="$conversation->created_at->format('d.m.Y H:i').' · '.$conversation->message_count.' mesaj'">
        <x-slot:actions>
            @if($conversation->unanswered_count > 0)
                <span class="admin-badge admin-badge-warning">Cevapsız {{ $conversation->unanswered_count }}</span>
            @endif
            @if($conversation->handed_off_at)
                <span class="admin-badge admin-badge-success">WhatsApp'a yönlendi · {{ $conversation->handed_off_at->format('H:i') }}</span>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="max-w-3xl admin-card p-5 sm:p-8">
        @if($conversation->page_url)
            <p class="text-xs text-slate-500 mb-5 break-all">Başladığı sayfa: {{ $conversation->page_url }}</p>
        @endif

        <ol class="space-y-4">
            @foreach($conversation->messages as $message)
                @php $isUser = $message->role === 'user'; @endphp
                <li class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl px-4 py-3 {{ $isUser ? 'bg-slate-800 text-white' : ($message->unanswered ? 'bg-amber-50 border border-amber-200 text-slate-800' : 'bg-slate-50 border border-slate-200 text-slate-800') }}">
                        <p class="text-sm whitespace-pre-wrap break-words leading-relaxed">{{ $message->content }}</p>
                        @if(! $isUser && ! empty($message->products))
                            <ul class="mt-2 space-y-1">
                                @foreach($message->products as $product)
                                    <li class="text-xs">
                                        <a href="{{ $product['url'] ?? '#' }}" target="_blank" rel="noopener" class="font-semibold text-teal-700 hover:text-teal-900">{{ $product['name'] ?? 'Ürün' }}</a>
                                        <span class="text-slate-500">· {{ $product['price'] ?? '' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <p class="mt-2 text-[11px] {{ $isUser ? 'text-slate-300' : 'text-slate-400' }}">
                            {{ $message->created_at->format('H:i') }}
                            @if(! $isUser && ! empty($message->tools))
                                · {{ implode(', ', $message->tools) }}
                            @endif
                            @if($message->unanswered)
                                · cevaplanamadı
                            @endif
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="admin-form-actions border-t mt-6 pt-4">
            <a href="{{ route('admin.support-chats.index') }}" class="admin-btn admin-btn-secondary">Listeye dön</a>
            <form method="post" action="{{ route('admin.support-chats.destroy', $conversation) }}" onsubmit="return confirm('Sohbet silinsin mi?')">
                @csrf @method('DELETE')
                <button type="submit" class="admin-btn text-rose-700 border-rose-200 hover:bg-rose-50">Sil</button>
            </form>
        </div>
    </div>
@endsection
