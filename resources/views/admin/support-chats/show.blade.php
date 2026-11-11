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

    <div class="max-w-3xl admin-card p-5 sm:p-8"
         id="support-live"
         data-messages="{{ route('admin.support-chats.messages', $conversation) }}"
         data-release="{{ route('admin.support-chats.release', $conversation) }}"
         data-after="{{ $conversation->messages->max('id') ?? 0 }}">
        <div class="mb-5 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-relaxed text-sky-950" role="status">
            <p class="font-semibold">Bu sohbet sizde açık</p>
            <p class="mt-1">Bu sayfa açıkken yapay zekâ yalnızca bu müşteriye cevap yazmaz. Yeni mesajlar burada görünür. Sayfadan çıkınca asistan bu sohbette yeniden yanıtlar. Diğer sohbetler etkilenmez.</p>
        </div>
        @if($conversation->page_url)
            <p class="text-xs text-slate-500 mb-5 break-all">Başladığı sayfa: {{ $conversation->page_url }}</p>
        @endif

        <ol class="space-y-4" id="support-thread" aria-live="polite">
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
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-sky-800">Temsilci yanıtı · {{ $message->author_name ?: 'Mağaza ekibi' }}</p>
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
                        <p class="mt-2 text-xs {{ $isUser ? 'text-slate-300' : 'text-slate-400' }}">
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

            <form method="post" action="{{ route('admin.support-chats.reply', $conversation) }}">
                @csrf
                <label for="support-reply" class="sr-only">Yanıt</label>
                <textarea id="support-reply" name="reply" rows="4" maxlength="1500" required class="admin-input" placeholder="Merhaba, sorduğunuz ürünle ilgili bilgi…">{{ old('reply') }}</textarea>
                @error('reply') <p class="admin-error">{{ $message }}</p> @enderror
                <div class="mt-3 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-relaxed text-slate-500">
                        @if($pushSubscribed)
                            Yanıtınız müşterinin telefonuna veya bilgisayarına bildirim olarak gider; siteye girdiğinde asistan penceresinde de görür.
                        @elseif($conversation->canReceiveAgentReply())
                            Müşteri bildirim izni vermedi. Yanıtı 30 gün içinde siteye döndüğünde asistan penceresinde görür.
                        @else
                            Bu sohbet bağlantıdan önce açıldı. Yanıt kaydolur; müşteri aynı tarayıcıdan dönerse pencerede görür. Oturum kapandıysa telefona bildirim gidemez.
                        @endif
                    </p>
                    <x-admin.button variant="primary" type="submit" class="w-full shrink-0 sm:w-auto">Müşteriye gönder</x-admin.button>
                </div>
            </form>
        </section>

        <div class="admin-form-actions border-t mt-6 pt-4">
            <x-admin.button variant="secondary" :href="route('admin.support-chats.index')">Listeye dön</x-admin.button>
            <form method="post" action="{{ route('admin.support-chats.destroy', $conversation) }}" onsubmit="return confirm('Sohbet silinsin mi?')">
                @csrf @method('DELETE')
                <x-admin.button variant="secondary" type="submit" class="text-rose-700 border-rose-200 hover:bg-rose-50">Sil</x-admin.button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const root = document.getElementById('support-live');
            const list = document.getElementById('support-thread');
            if (!root || !list || !root.dataset.messages) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const tabKey = 'kc-admin-chat-tab';
            let tab = '';
            try {
                tab = sessionStorage.getItem(tabKey) || '';
                if (!/^[A-Za-z0-9]{12,40}$/.test(tab)) {
                    const bytes = new Uint8Array(12);
                    crypto.getRandomValues(bytes);
                    tab = Array.from(bytes, (b) => (b % 36).toString(36)).join('').padEnd(12, 'a');
                    sessionStorage.setItem(tabKey, tab);
                }
            } catch (e) {
                tab = ('panel' + Date.now().toString(36) + 'abcdef123456').replace(/[^A-Za-z0-9]/g, '').slice(0, 20);
            }

            let after = Number(root.dataset.after) || 0;
            let stopped = false;

            const append = (message) => {
                const isUser = message.role === 'user';
                const isAgent = message.role === 'agent';
                const li = document.createElement('li');
                li.className = 'flex ' + (isUser ? 'justify-end' : 'justify-start');
                const bubble = document.createElement('div');
                bubble.className = 'max-w-[85%] rounded-2xl px-4 py-3 ' + (
                    isUser ? 'bg-slate-800 text-white'
                        : isAgent ? 'bg-sky-50 border border-sky-200 text-slate-800'
                            : message.unanswered ? 'bg-amber-50 border border-amber-200 text-slate-800'
                                : 'bg-slate-50 border border-slate-200 text-slate-800'
                );
                if (isAgent) {
                    const label = document.createElement('p');
                    label.className = 'mb-1 text-xs font-semibold uppercase tracking-wide text-sky-800';
                    label.textContent = 'Temsilci yanıtı · ' + (message.author || 'Mağaza ekibi');
                    bubble.appendChild(label);
                }
                const text = document.createElement('p');
                text.className = 'text-sm whitespace-pre-wrap break-words leading-relaxed';
                text.textContent = message.content || '';
                bubble.appendChild(text);
                if (Array.isArray(message.products) && message.products.length) {
                    const products = document.createElement('ul');
                    products.className = 'mt-2 space-y-1';
                    message.products.forEach((product) => {
                        const item = document.createElement('li');
                        item.className = 'text-xs';
                        const link = document.createElement('a');
                        link.href = product.url || '#';
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.className = 'font-semibold text-teal-700 hover:text-teal-900';
                        link.textContent = product.name || 'Ürün';
                        item.appendChild(link);
                        if (product.price) {
                            const price = document.createElement('span');
                            price.className = 'text-slate-500';
                            price.textContent = ' · ' + product.price;
                            item.appendChild(price);
                        }
                        products.appendChild(item);
                    });
                    bubble.appendChild(products);
                }
                const meta = document.createElement('p');
                meta.className = 'mt-2 text-xs ' + (isUser ? 'text-slate-300' : 'text-slate-400');
                meta.textContent = (message.at || '') + (isAgent ? (message.seen ? ' · müşteri gördü' : ' · henüz görülmedi') : '');
                bubble.appendChild(meta);
                li.appendChild(bubble);
                list.appendChild(li);
            };

            const poll = async () => {
                if (stopped) return;
                try {
                    const url = new URL(root.dataset.messages, window.location.origin);
                    url.searchParams.set('after', String(after));
                    url.searchParams.set('tab', tab);
                    const response = await fetch(url, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) return;
                    const data = await response.json();
                    (data.messages || []).forEach((message) => {
                        if (Number(message.id) > after) {
                            append(message);
                            after = Number(message.id);
                        }
                    });
                } catch (e) {}
            };

            poll();
            const timer = window.setInterval(poll, 4000);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) poll();
            });
            window.addEventListener('pagehide', () => {
                stopped = true;
                window.clearInterval(timer);
                if (!root.dataset.release || !csrf) return;
                const body = new FormData();
                body.append('_token', csrf);
                body.append('tab', tab);
                navigator.sendBeacon(root.dataset.release, body);
            });
        })();
    </script>
@endpush
