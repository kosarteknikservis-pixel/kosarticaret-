/* Koşar destek asistanı — layout içindeki başlatıcı ilk tıklamada yükler. */
(function () {
    'use strict';

    if (window.KosarSupportChat) return;

    const STORE_KEY = 'kc-chat-v1';
    const OPEN_KEY = 'kc-chat-open';
    const MAX_STORED = 40;
    const MAX_LENGTH = 600;

    const SUGGESTIONS = [
        'Ürün fiyatı ve stok durumu',
        'Kargo ve iade koşulları',
        'Siparişim nerede?',
        'Bana uygun pompayı bul',
        'Taksit seçenekleri',
    ];

    const icons = {
        avatar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12.5c0 3.6-3.6 6.5-8 6.5-1.1 0-2.2-.2-3.1-.5L4 20l1.3-3.4C4.5 15.4 4 14 4 12.5 4 8.9 7.6 6 12 6s8 2.9 8 6.5Z"/><path d="M8.5 12.5h.01M12 12.5h.01M15.5 12.5h.01" stroke-width="2.4"/></svg>',
        reset: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 1 0 2.4-5.7"/><path d="M4 4v4.5h4.5"/></svg>',
        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
        send: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="m5.5 11.5 6.5-6.5 6.5 6.5"/></svg>',
        chevron: '<svg class="kc-card__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>',
        whatsapp: '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.08-.13-.28-.2-.57-.35M12.05 21.79h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.83 9.83 0 0 1 2.89 6.99c0 5.45-4.43 9.88-9.88 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.69 1.45h.01c6.55 0 11.89-5.34 11.89-11.89 0-3.18-1.24-6.16-3.48-8.41"/></svg>',
    };

    let root = null;
    let body = null;
    let input = null;
    let sendButton = null;
    let launcher = null;
    let config = {};
    let busy = false;
    let messages = [];

    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const sameOriginLink = (raw, label) => {
        try {
            const parsed = new URL(raw.replace(/&amp;/g, '&'), window.location.origin);
            if (parsed.origin !== window.location.origin) return null;
            return '<a href="' + escapeHtml(parsed.href) + '">' + label + '</a>';
        } catch (e) {
            return null;
        }
    };

    const inline = (text) => {
        let html = escapeHtml(text);
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+|\/[^\s)]*)\)/g, (match, label, url) => sameOriginLink(url, label) || label);
        html = html.replace(/(^|[^"=>])(https?:\/\/[^\s<]+[^\s<.,;:!?)])/g, (match, lead, url) => {
            const parsed = (() => { try { return new URL(url.replace(/&amp;/g, '&')); } catch (e) { return null; } })();
            if (!parsed) return match;
            const link = sameOriginLink(url, escapeHtml(parsed.pathname === '/' ? parsed.host : decodeURIComponent(parsed.pathname)));
            return link ? lead + link : match;
        });
        return html;
    };

    const formatReply = (text) => {
        const blocks = [];
        let list = [];
        const flushList = () => {
            if (list.length) {
                blocks.push('<ul>' + list.map((item) => '<li>' + inline(item) + '</li>').join('') + '</ul>');
                list = [];
            }
        };
        String(text).split(/\n/).forEach((line) => {
            const trimmed = line.trim();
            if (/^[-•*]\s+/.test(trimmed)) {
                list.push(trimmed.replace(/^[-•*]\s+/, ''));
                return;
            }
            flushList();
            if (trimmed !== '') blocks.push('<p>' + inline(trimmed) + '</p>');
        });
        flushList();
        return blocks.join('');
    };

    const save = () => {
        try {
            sessionStorage.setItem(STORE_KEY, JSON.stringify(messages.slice(-MAX_STORED)));
        } catch (e) {}
    };

    const restore = () => {
        try {
            const parsed = JSON.parse(sessionStorage.getItem(STORE_KEY) || '[]');
            messages = Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            messages = [];
        }
    };

    const setOpenFlag = (open) => {
        try {
            sessionStorage.setItem(OPEN_KEY, open ? '1' : '0');
        } catch (e) {}
    };

    const track = (name, params) => {
        if (typeof window.gtag === 'function') {
            window.gtag('event', name, params || {});
        }
    };

    const isMobile = () => window.matchMedia('(max-width: 639px)').matches;

    const scrollToEnd = () => {
        body.scrollTop = body.scrollHeight;
    };

    const handoffLink = (url) => {
        const link = document.createElement('a');
        link.className = 'kc-handoff';
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener';
        link.innerHTML = icons.whatsapp + '<span>WhatsApp\'tan ekibimizle devam et</span>';
        link.addEventListener('click', () => track('support_chat_handoff'));
        return link;
    };

    const productCards = (products) => {
        const wrap = document.createElement('div');
        wrap.className = 'kc-cards';
        products.forEach((product) => {
            let url;
            try {
                url = new URL(product.url, window.location.origin);
            } catch (e) {
                return;
            }
            if (url.origin !== window.location.origin) return;

            const card = document.createElement('a');
            card.className = 'kc-card';
            card.href = url.href;
            card.innerHTML =
                '<span class="kc-card__media">' +
                (product.image ? '<img src="' + escapeHtml(product.image) + '" alt="" width="56" height="56" loading="lazy" decoding="async">' : '') +
                '</span>' +
                '<span class="kc-card__body">' +
                (product.brand ? '<span class="kc-card__brand">' + escapeHtml(product.brand) + '</span>' : '') +
                '<span class="kc-card__name">' + escapeHtml(product.name || '') + '</span>' +
                '<span class="kc-card__meta">' +
                '<strong class="kc-card__price">' + escapeHtml(product.price || '') + '</strong>' +
                (product.compare_price ? '<span class="kc-card__compare">' + escapeHtml(product.compare_price) + '</span>' : '') +
                '<span class="kc-card__stock' + (product.in_stock ? '' : ' is-out') + '">' + (product.in_stock ? 'Stokta' : 'Stokta yok') + '</span>' +
                '</span>' +
                '</span>' +
                icons.chevron;
            const image = card.querySelector('img');
            if (image) image.addEventListener('error', () => image.remove(), { once: true });
            card.addEventListener('click', () => {
                track('support_chat_product_click');
                if (isMobile()) setOpenFlag(false);
            });
            wrap.appendChild(card);
        });
        return wrap;
    };

    const renderMessage = (message) => {
        const row = document.createElement('div');
        row.className = 'kc-msg kc-msg--' + (message.role === 'user' ? 'user' : 'bot') + (message.error ? ' kc-msg--error' : '');

        const bubble = document.createElement('div');
        bubble.className = 'kc-msg__bubble';
        if (message.role === 'user') {
            bubble.textContent = message.text;
        } else {
            bubble.innerHTML = formatReply(message.text);
        }
        row.appendChild(bubble);

        if (Array.isArray(message.products) && message.products.length) {
            row.appendChild(productCards(message.products));
        }
        if (message.handoffUrl) {
            row.appendChild(handoffLink(message.handoffUrl));
        }
        body.appendChild(row);
        return row;
    };

    const renderWelcome = () => {
        const row = renderMessage({
            role: 'assistant',
            text: 'Merhaba, ben Koşar destek asistanı. Ürün, fiyat, stok, kargo, taksit ve sipariş durumu hakkında site bilgileriyle yardımcı olurum. Emin olmadığım konuda sizi ekibimize aktarırım.',
        });
        const chips = document.createElement('div');
        chips.className = 'kc-chips';
        SUGGESTIONS.forEach((label) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'kc-chip';
            chip.textContent = label;
            chip.addEventListener('click', () => send(label));
            chips.appendChild(chip);
        });
        row.appendChild(chips);
    };

    const renderAll = () => {
        body.innerHTML = '';
        renderWelcome();
        messages.forEach(renderMessage);
        scrollToEnd();
    };

    const showTyping = () => {
        const row = document.createElement('div');
        row.className = 'kc-msg kc-msg--bot';
        row.setAttribute('data-kc-typing', '');
        row.innerHTML = '<div class="kc-msg__bubble" aria-label="Yanıt hazırlanıyor"><span class="kc-typing"><span></span><span></span><span></span></span></div>';
        body.appendChild(row);
        scrollToEnd();
        return row;
    };

    const csrfHeaders = () => {
        const headers = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
        if (match) {
            headers['X-XSRF-TOKEN'] = decodeURIComponent(match[1]);
        } else {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) headers['X-CSRF-TOKEN'] = meta.getAttribute('content');
        }
        return headers;
    };

    const setBusy = (value) => {
        busy = value;
        sendButton.disabled = value || input.value.trim() === '';
        input.setAttribute('aria-busy', value ? 'true' : 'false');
    };

    const addMessage = (message) => {
        messages.push(message);
        save();
        renderMessage(message);
        scrollToEnd();
    };

    const errorText = (status) => {
        if (status === 429) return 'Çok hızlı mesaj gönderildi. Lütfen birkaç saniye bekleyip tekrar deneyin.';
        if (status === 419) return 'Oturumunuz yenilendi. Sayfayı yenileyip tekrar deneyebilirsiniz.';
        return 'Şu an yanıt veremiyorum. Ekibimiz WhatsApp üzerinden hemen yardımcı olabilir.';
    };

    async function send(text) {
        const value = String(text || '').trim().slice(0, MAX_LENGTH);
        if (!value || busy) return;

        addMessage({ role: 'user', text: value });
        input.value = '';
        autoGrow();
        setBusy(true);
        const typing = showTyping();
        track('support_chat_message');

        try {
            const response = await fetch(config.endpoint, {
                method: 'POST',
                headers: csrfHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({ message: value, page: window.location.pathname }),
            });
            const data = await response.json().catch(() => ({}));
            typing.remove();

            if (!response.ok || typeof data.reply !== 'string') {
                addMessage({ role: 'assistant', text: errorText(response.status), handoffUrl: response.status === 429 ? null : config.whatsapp, error: true });
                return;
            }

            addMessage({
                role: 'assistant',
                text: data.reply,
                products: Array.isArray(data.products) ? data.products : [],
                handoffUrl: data.handoff && data.handoff_url ? data.handoff_url : null,
            });
        } catch (e) {
            typing.remove();
            addMessage({ role: 'assistant', text: errorText(0), handoffUrl: config.whatsapp, error: true });
        } finally {
            setBusy(false);
            if (!isMobile()) input.focus();
        }
    }

    const autoGrow = () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        sendButton.disabled = busy || input.value.trim() === '';
    };

    const reset = () => {
        if (busy) return;
        messages = [];
        save();
        renderAll();
        fetch(config.resetEndpoint, { method: 'POST', headers: csrfHeaders(), credentials: 'same-origin' }).catch(() => {});
        if (!isMobile()) input.focus();
    };

    const build = () => {
        root = document.createElement('div');
        root.className = 'kc-chat';
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-labelledby', 'kc-chat-title');
        root.setAttribute('aria-hidden', 'true');
        root.innerHTML =
            '<div class="kc-chat__head">' +
            '<span class="kc-chat__avatar" aria-hidden="true">' + icons.avatar + '</span>' +
            '<div class="kc-chat__titles">' +
            '<p class="kc-chat__title" id="kc-chat-title">Koşar Destek</p>' +
            '<p class="kc-chat__status">Yapay zekâ asistanı</p>' +
            '</div>' +
            '<button type="button" class="kc-chat__icon-btn" data-kc-reset aria-label="Yeni sohbet başlat" title="Yeni sohbet">' + icons.reset + '</button>' +
            '<button type="button" class="kc-chat__icon-btn" data-kc-close aria-label="Kapat">' + icons.close + '</button>' +
            '</div>' +
            '<div class="kc-chat__body" data-kc-body aria-live="polite"></div>' +
            '<form class="kc-chat__composer" data-kc-form novalidate>' +
            '<div class="kc-chat__input-wrap">' +
            '<textarea class="kc-chat__input" data-kc-input rows="1" maxlength="' + MAX_LENGTH + '" placeholder="Sorunuzu yazın…" aria-label="Mesajınız" enterkeyhint="send"></textarea>' +
            '<button type="submit" class="kc-chat__send" aria-label="Gönder" disabled>' + icons.send + '</button>' +
            '</div>' +
            '<p class="kc-chat__foot">' +
            '<span>Yapay zekâ yanıtıdır, hata yapabilir.' +
            (config.privacyUrl ? ' <a href="' + escapeHtml(config.privacyUrl) + '" target="_blank" rel="noopener">KVKK</a>' : '') +
            '</span>' +
            (config.whatsapp ? '<a href="' + escapeHtml(config.whatsapp) + '" target="_blank" rel="noopener" data-kc-human>Temsilciye bağlan</a>' : '') +
            '</p>' +
            '</form>';
        document.body.appendChild(root);

        body = root.querySelector('[data-kc-body]');
        input = root.querySelector('[data-kc-input]');
        sendButton = root.querySelector('.kc-chat__send');

        root.querySelector('[data-kc-close]').addEventListener('click', close);
        root.querySelector('[data-kc-reset]').addEventListener('click', reset);
        const human = root.querySelector('[data-kc-human]');
        if (human) human.addEventListener('click', () => track('support_chat_handoff', { source: 'footer' }));

        root.querySelector('[data-kc-form]').addEventListener('submit', (event) => {
            event.preventDefault();
            send(input.value);
        });
        input.addEventListener('input', autoGrow);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                send(input.value);
            }
        });
        root.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
        });

        restore();
        renderAll();
    };

    function open(button, options) {
        if (button) launcher = button;
        if (!root) {
            config = {
                endpoint: launcher.dataset.endpoint,
                resetEndpoint: launcher.dataset.resetEndpoint,
                whatsapp: launcher.dataset.whatsapp || '',
                privacyUrl: launcher.dataset.privacyUrl || '',
            };
            build();
        }
        root.setAttribute('aria-hidden', 'false');
        root.classList.add('is-open');
        document.documentElement.classList.add('kc-chat-lock');
        if (launcher) launcher.setAttribute('aria-expanded', 'true');
        setOpenFlag(true);
        scrollToEnd();
        if (!isMobile() && !(options && options.restore)) {
            window.setTimeout(() => input.focus(), 60);
        }
        if (!(options && options.restore)) track('support_chat_open');
    }

    function close() {
        if (!root) return;
        root.classList.remove('is-open');
        root.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('kc-chat-lock');
        setOpenFlag(false);
        if (launcher) {
            launcher.setAttribute('aria-expanded', 'false');
            launcher.focus({ preventScroll: true });
        }
    }

    window.KosarSupportChat = {
        open,
        close,
        toggle(button) {
            if (root && root.classList.contains('is-open')) {
                close();
            } else {
                open(button);
            }
        },
    };
})();
