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
            @if($conversation->last_agent_reply_at)
                <span class="admin-badge admin-badge-muted">Yanıtlandı · {{ $conversation->last_agent_reply_at->format('d.m H:i') }}</span>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="max-w-3xl admin-card p-5 sm:p-8">
        @if($conversation->page_url)
            <p class="text-xs text-slate-500 mb-5 break-all">Başladığı sayfa: {{ $conversation->page_url }}</p>
        @endif

        <ol class="space-y-4">
            @foreach($conversation->messages as $message)
                @php
                    $isUser = $message->role === 'user';
                    $isAgent = $message->role === 'agent';
                    $bubble = match (true) {
                        $isUser => 'bg-slate-800 text-white',
                        $isAgent => 'bg-sky-50 border border-sky-200 text-slate-800',
                        (bool) $message->unanswered => 'bg-amber-50 border border-amber-200 text-slate-800',
                        default => 'bg-slate-50 border border-slate-200 text-slate-800',
                    };
                @endphp
                <li class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl px-4 py-3 {{ $bubble }}">
                        @if($isAgent)
                            <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-sky-800">Temsilci yanıtı · {{ $message->author_name ?: 'Mağaza ekibi' }}</p>
                        @endif
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
                            @if($isAgent)
                                · {{ $message->id <= $conversation->agent_seen_message_id ? 'müşteri gördü' : 'henüz görülmedi' }}
                            @endif
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

        <section class="mt-8 border-t border-slate-200 pt-6" id="yanit" aria-labelledby="support-reply-title">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 id="support-reply-title" class="text-sm font-semibold text-slate-800">Müşteriye yanıt yaz</h2>
                @if($conversation->canReceiveAgentReply())
                    <span class="admin-badge {{ $pushSubscribed ? 'admin-badge-success' : 'admin-badge-muted' }}">{{ $pushSubscribed ? 'Bildirim açık' : 'Bildirim kapalı' }}</span>
                @endif
            </div>

            @if($conversation->canReceiveAgentReply())
                <form method="post" action="{{ route('admin.support-chats.reply', $conversation) }}">
                    @csrf
                    <label for="support-reply" class="sr-only">Yanıt</label>
                    <textarea id="support-reply" name="reply" rows="4" maxlength="1500" required class="admin-input" placeholder="Merhaba, sorduğunuz ürünle ilgili bilgi…">{{ old('reply') }}</textarea>
                    @error('reply') <p class="admin-error">{{ $message }}</p> @enderror
                    <div class="mt-3 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs leading-relaxed text-slate-500">
                            @if($pushSubscribed)
                                Yanıtınız müşterinin telefonuna veya bilgisayarına bildirim olarak gider; siteye girdiğinde asistan penceresinde de görür.
                            @else
                                Müşteri bildirim izni vermedi. Yanıtı 30 gün içinde siteye döndüğünde asistan penceresinde görür.
                            @endif
                        </p>
                        <button type="submit" class="admin-btn admin-btn-primary w-full shrink-0 sm:w-auto">Müşteriye gönder</button>
                    </div>
                </form>
            @else
                <p class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-relaxed text-slate-600">Bu sohbet yanıt özelliği eklenmeden önce başladığı için müşteriye ulaştırılamaz. Müşteri iletişim bilgisi bıraktıysa WhatsApp veya telefonla dönüş yapabilirsiniz.</p>
            @endif
        </section>

        <div class="admin-form-actions border-t mt-6 pt-4">
            <a href="{{ route('admin.support-chats.index') }}" class="admin-btn admin-btn-secondary">Listeye dön</a>
            <form method="post" action="{{ route('admin.support-chats.destroy', $conversation) }}" onsubmit="return confirm('Sohbet silinsin mi?')">
                @csrf @method('DELETE')
                <button type="submit" class="admin-btn text-rose-700 border-rose-200 hover:bg-rose-50">Sil</button>
            </form>
        </div>
    </div>
@endsection
