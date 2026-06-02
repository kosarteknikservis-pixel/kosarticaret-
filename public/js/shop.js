(function () {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const headers = {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    function bumpBadges(els) {
        els.forEach((el) => {
            if (el.classList.contains('hidden') || el.classList.contains('is-empty')) return;
            el.classList.remove('is-bump');
            void el.offsetWidth;
            el.classList.add('is-bump');
        });
    }

    function updateCartBadge(count) {
        const badges = [...document.querySelectorAll('[data-cart-count]')];
        const prev = badges[0] ? parseInt(badges[0].textContent || '0', 10) : 0;
        badges.forEach((el) => {
            el.textContent = String(count);
            el.classList.toggle('hidden', count < 1);
            el.classList.toggle('is-empty', count < 1);
        });
        if (count > prev) bumpBadges(badges);
    }

    function updateFavoriteBadge(count) {
        const badges = [...document.querySelectorAll('[data-favorite-count]')];
        const prev = badges[0] ? parseInt(badges[0].textContent || '0', 10) : 0;
        badges.forEach((el) => {
            el.textContent = String(count);
            el.classList.toggle('hidden', count < 1);
            el.classList.toggle('is-empty', count < 1);
        });
        if (count > prev) bumpBadges(badges);
    }

    function toast(message) {
        const box = document.getElementById('shop-toast');
        if (!box) return;
        box.textContent = message;
        box.classList.remove('hidden');
        box.classList.add('show');
        clearTimeout(box._toastTimer);
        box._toastTimer = setTimeout(() => {
            box.classList.add('hidden');
            box.classList.remove('show');
        }, 2800);
    }

    async function cartRequest(url, method, body) {
        const res = await fetch(url, {
            method,
            headers: { ...headers, ...(body ? { 'Content-Type': 'application/json' } : {}) },
            body: body ? JSON.stringify(body) : undefined,
        });
        return res.json();
    }

    const drawer = document.getElementById('cart-drawer');
    const drawerBody = document.getElementById('cart-drawer-body');
    const drawerSubtotal = document.getElementById('cart-drawer-subtotal');

    function closeCartDrawer() {
        if (!drawer) return;
        drawer.classList.add('hidden');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    }

    function openCartDrawer() {
        if (!drawer) return;
        drawer.classList.remove('hidden');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        refreshCartDrawer();
    }

    async function updateCartLine(slug, qty) {
        return cartRequest(`/sepet/ajax/${slug}`, 'PATCH', { quantity: qty });
    }

    function bindDrawerLineControls() {
        drawerBody?.querySelectorAll('[data-drawer-line]').forEach((row) => {
            const slug = row.dataset.slug;
            const qtyEl = row.querySelector('[data-drawer-qty]');
            const getQty = () => parseInt(qtyEl?.textContent || '1', 10);
            row.querySelector('[data-drawer-qty-minus]')?.addEventListener('click', async () => {
                const data = await updateCartLine(slug, Math.max(0, getQty() - 1));
                if (data?.ok) { updateCartBadge(data.count); refreshCartDrawer(); }
            });
            row.querySelector('[data-drawer-qty-plus]')?.addEventListener('click', async () => {
                const data = await updateCartLine(slug, getQty() + 1);
                if (data?.ok) { updateCartBadge(data.count); refreshCartDrawer(); }
            });
            row.querySelector('[data-drawer-remove]')?.addEventListener('click', async () => {
                const data = await updateCartLine(slug, 0);
                if (data?.ok) { updateCartBadge(data.count); refreshCartDrawer(); }
            });
        });
    }

    async function refreshCartDrawer() {
        if (!drawerBody) return;
        drawerBody.innerHTML = '<p class="py-8 text-center text-slate-400">Yükleniyor…</p>';
        try {
            const data = await fetch('/sepet/ajax/detay', { headers }).then((r) => r.json());
            if (!data.ok) return;
            updateCartBadge(data.count);
            if (drawerSubtotal) drawerSubtotal.textContent = data.subtotal_formatted;
            if (data.empty) {
                drawerBody.innerHTML = '<div class="py-12 text-center"><p class="text-slate-500">Sepetiniz boş.</p><a href="/urunler" class="mt-4 inline-block text-sm font-semibold text-brand-700 hover:underline">Alışverişe başla</a></div>';
                return;
            }
            drawerBody.innerHTML = data.lines.map((line) => `
                <div class="flex gap-3 py-4 border-b border-slate-100 last:border-0" data-drawer-line data-slug="${line.slug}">
                    ${line.image ? `<img src="${line.image}" alt="" class="w-14 h-14 object-cover rounded-lg shrink-0">` : '<div class="w-14 h-14 bg-slate-100 rounded-lg shrink-0"></div>'}
                    <div class="flex-1 min-w-0">
                        <a href="${line.url}" class="font-semibold text-sm text-slate-900 line-clamp-2 hover:text-brand-700">${line.name}</a>
                        <p class="text-xs text-brand-700 font-medium mt-0.5">${Number(line.price).toLocaleString('tr-TR')} ₺</p>
                        <div class="mt-2 flex items-center gap-2">
                            <button type="button" data-drawer-qty-minus class="w-7 h-7 rounded-lg border text-sm font-bold hover:bg-slate-50">−</button>
                            <span data-drawer-qty class="text-sm font-bold w-6 text-center">${line.quantity}</span>
                            <button type="button" data-drawer-qty-plus class="w-7 h-7 rounded-lg border text-sm font-bold hover:bg-slate-50">+</button>
                            <button type="button" data-drawer-remove class="ml-auto text-xs text-red-600 font-medium">Kaldır</button>
                        </div>
                    </div>
                </div>
            `).join('');
            bindDrawerLineControls();
        } catch {
            drawerBody.innerHTML = '<p class="py-8 text-center text-red-600 text-sm">Sepet yüklenemedi.</p>';
        }
    }

    document.querySelectorAll('[data-open-cart-drawer]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openCartDrawer();
        });
    });

    document.querySelectorAll('[data-cart-drawer-close]').forEach((btn) => {
        btn.addEventListener('click', closeCartDrawer);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCartDrawer();
            closeMobileNav();
            if (typeof closeFilterDrawer === 'function') closeFilterDrawer();
        }
    });

    document.querySelectorAll('[data-add-cart]').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const slug = btn.dataset.addCart;
            let qty = parseInt(btn.dataset.qty || '1', 10);
            if (btn.dataset.qtyFrom) {
                const input = document.querySelector(btn.dataset.qtyFrom);
                if (input) qty = parseInt(input.value || '1', 10);
            }
            btn.disabled = true;
            try {
                const data = await cartRequest(`/sepet/ajax/ekle/${slug}`, 'POST', { quantity: qty });
                if (data.ok) {
                    updateCartBadge(data.count);
                    toast(data.message || 'Sepete eklendi');
                    btn.classList.remove('is-added');
                    void btn.offsetWidth;
                    btn.classList.add('is-added');
                    clearTimeout(btn._addedTimer);
                    btn._addedTimer = setTimeout(() => btn.classList.remove('is-added'), 500);
                    openCartDrawer();
                }
            } finally {
                btn.disabled = false;
            }
        });
    });

    document.querySelectorAll('[data-toggle-favorite]').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const slug = btn.dataset.toggleFavorite;
            const res = await fetch(`/favoriler/${slug}`, { method: 'POST', headers });
            const data = await res.json();
            if (data.ok) {
                updateFavoriteBadge(data.count);
                btn.setAttribute('aria-pressed', data.added ? 'true' : 'false');
                btn.classList.toggle('is-favorite', data.added);
                btn.classList.toggle('text-rose-600', data.added);
                btn.classList.toggle('bg-rose-50', data.added);
                toast(data.message);
            }
        });
    });

    const cookieKey = 'kosar-cerez-onay';
    const banner = document.getElementById('cookie-banner');
    if (banner && !localStorage.getItem(cookieKey)) {
        const hideBanner = (value) => {
            localStorage.setItem(cookieKey, value);
            banner.classList.remove('is-visible');
            banner.addEventListener(
                'transitionend',
                () => banner.classList.add('hidden'),
                { once: true },
            );
            setTimeout(() => banner.classList.add('hidden'), 400);
        };

        requestAnimationFrame(() => {
            banner.classList.remove('hidden');
            requestAnimationFrame(() => banner.classList.add('is-visible'));
        });

        banner.querySelector('[data-cookie-accept]')?.addEventListener('click', () => hideBanner('accepted'));
        banner.querySelector('[data-cookie-reject]')?.addEventListener('click', () => hideBanner('rejected'));
    }

    /* Mobile navigation */
    const mobileOverlay = document.getElementById('mobile-nav-overlay');
    const mobilePanel = document.getElementById('mobile-nav-panel');
    const mobileOpen = document.getElementById('mobile-menu-open');
    const mobileClose = document.getElementById('mobile-menu-close');

    function openMobileNav() {
        if (!mobileOverlay || !mobilePanel) return;
        mobileOverlay.classList.remove('hidden');
        mobileOverlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => mobileOverlay.classList.add('is-open'));
        mobileOpen?.setAttribute('aria-expanded', 'true');
    }

    function closeMobileNav() {
        if (!mobileOverlay || !mobilePanel) return;
        mobileOverlay.classList.remove('is-open');
        mobileOverlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        mobileOpen?.setAttribute('aria-expanded', 'false');
        setTimeout(() => mobileOverlay.classList.add('hidden'), 320);
    }

    mobileOpen?.addEventListener('click', openMobileNav);
    mobileClose?.addEventListener('click', closeMobileNav);
    mobileOverlay?.addEventListener('click', (e) => {
        if (e.target === mobileOverlay) closeMobileNav();
    });

    /* Auth modal */
    const authModal = document.getElementById('shop-auth-modal');
    const authDialog = authModal?.querySelector('.shop-auth-modal__dialog');
    const authPanels = authModal ? [...authModal.querySelectorAll('[data-auth-panel]')] : [];
    const authTabs = authModal ? [...authModal.querySelectorAll('.shop-auth-modal__tab')] : [];
    let lastAuthTrigger = null;

    function setAuthMode(mode) {
        if (!authModal) return;
        const nextMode = mode === 'register' ? 'register' : 'login';
        authPanels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.authPanel !== nextMode));
        authTabs.forEach((tab) => {
            const active = tab.dataset.authSwitch === nextMode;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.setAttribute('tabindex', active ? '0' : '-1');
        });
    }

    function openAuthModal(mode = 'login', trigger = null) {
        if (!authModal) return;
        lastAuthTrigger = trigger || document.activeElement;
        setAuthMode(mode);
        closeMobileNav();
        authModal.classList.remove('hidden');
        authModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        window.requestAnimationFrame(() => {
            authDialog?.focus();
            const firstInput = authModal.querySelector(`[data-auth-panel="${mode === 'register' ? 'register' : 'login'}"] input:not([type="hidden"])`);
            firstInput?.focus({ preventScroll: true });
        });
    }

    function closeAuthModal() {
        if (!authModal) return;
        authModal.classList.add('hidden');
        authModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        if (lastAuthTrigger && typeof lastAuthTrigger.focus === 'function') {
            lastAuthTrigger.focus({ preventScroll: true });
        }
    }

    document.querySelectorAll('[data-open-auth-modal]').forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            if (!authModal) return;
            e.preventDefault();
            openAuthModal(trigger.dataset.authMode || 'login', trigger);
        });
    });

    authModal?.querySelectorAll('[data-auth-close]').forEach((trigger) => {
        trigger.addEventListener('click', closeAuthModal);
    });

    authModal?.querySelectorAll('[data-auth-switch]').forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            setAuthMode(trigger.dataset.authSwitch || 'login');
            const panel = authModal.querySelector(`[data-auth-panel="${trigger.dataset.authSwitch}"]`);
            panel?.querySelector('input:not([type="hidden"])')?.focus({ preventScroll: true });
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && authModal && !authModal.classList.contains('hidden')) {
            closeAuthModal();
        }
    });

    if (authModal?.hasAttribute('data-auth-open-on-load')) {
        openAuthModal(authModal.dataset.authDefaultMode || 'login');
    } else {
        setAuthMode(authModal?.dataset.authDefaultMode || 'login');
    }

    /* PDP gallery */
    const pdpMain = document.getElementById('pdp-main-img');
    const pdpMainWrap = document.getElementById('product-main-image');
    const pdpGalleryEl = document.querySelector('[data-pdp-gallery]');
    let pdpGalleryItems = [];

    if (pdpGalleryEl?.dataset.pdpGallery) {
        try {
            pdpGalleryItems = JSON.parse(pdpGalleryEl.dataset.pdpGallery);
        } catch {
            pdpGalleryItems = [];
        }
    }

    let pdpGalleryIndex = 0;

    function setPdpMainImage(index) {
        const item = pdpGalleryItems[index];
        if (!item?.url || !pdpMain) return;
        pdpGalleryIndex = index;
        pdpMainWrap?.classList.add('is-switching');
        pdpMain.src = item.url;
        if (item.alt) pdpMain.alt = item.alt;
        pdpMain.addEventListener('load', () => pdpMainWrap?.classList.remove('is-switching'), { once: true });
        document.querySelectorAll('.pdp-thumb').forEach((t, i) => {
            const active = i === index;
            t.classList.toggle('is-active', active);
            t.setAttribute('aria-current', active ? 'true' : 'false');
        });
    }

    document.querySelectorAll('[data-gallery-thumb]').forEach((thumbBtn) => {
        thumbBtn.addEventListener('click', () => {
            const url = thumbBtn.dataset.galleryThumb;
            const idx = pdpGalleryItems.findIndex((item) => item.url === url);
            setPdpMainImage(idx >= 0 ? idx : 0);
        });
    });

    const pdpLightbox = document.getElementById('pdp-lightbox');
    const pdpLightboxImg = document.getElementById('pdp-lightbox-img');

    function showPdpLightbox(index) {
        if (!pdpLightbox || !pdpLightboxImg || !pdpGalleryItems.length) return;
        pdpGalleryIndex = ((index % pdpGalleryItems.length) + pdpGalleryItems.length) % pdpGalleryItems.length;
        const item = pdpGalleryItems[pdpGalleryIndex];
        pdpLightboxImg.src = item.url;
        pdpLightboxImg.alt = item.alt || '';
        if (!pdpLightbox.open) pdpLightbox.showModal();
        document.body.classList.add('overflow-hidden');
    }

    function closePdpLightbox() {
        if (!pdpLightbox?.open) return;
        pdpLightbox.close();
        document.body.classList.remove('overflow-hidden');
    }

    function stepPdpLightbox(delta) {
        if (pdpGalleryItems.length < 2) return;
        const next = (pdpGalleryIndex + delta + pdpGalleryItems.length) % pdpGalleryItems.length;
        showPdpLightbox(next);
    }

    pdpMainWrap?.addEventListener('click', () => {
        if (!pdpGalleryItems.length) return;
        const idx = pdpGalleryItems.findIndex((item) => item.url === pdpMain?.src);
        showPdpLightbox(idx >= 0 ? idx : 0);
    });

    pdpLightbox?.addEventListener('click', (e) => {
        if (e.target === pdpLightbox) closePdpLightbox();
    });

    document.querySelectorAll('[data-pdp-lightbox-close]').forEach((el) => {
        el.addEventListener('click', closePdpLightbox);
    });

    document.querySelector('[data-pdp-lightbox-prev]')?.addEventListener('click', (e) => {
        e.stopPropagation();
        stepPdpLightbox(-1);
    });

    document.querySelector('[data-pdp-lightbox-next]')?.addEventListener('click', (e) => {
        e.stopPropagation();
        stepPdpLightbox(1);
    });

    pdpLightbox?.addEventListener('cancel', () => {
        document.body.classList.remove('overflow-hidden');
    });

    document.addEventListener('keydown', (e) => {
        if (!pdpLightbox?.open) return;
        if (e.key === 'Escape') {
            closePdpLightbox();
        } else if (e.key === 'ArrowLeft') {
            stepPdpLightbox(-1);
        } else if (e.key === 'ArrowRight') {
            stepPdpLightbox(1);
        }
    });

    /* PDP quantity */
    const qtyInput = document.getElementById('pdp-qty');
    document.querySelector('[data-qty-minus]')?.addEventListener('click', () => {
        if (qtyInput) qtyInput.value = String(Math.max(1, parseInt(qtyInput.value || '1', 10) - 1));
    });
    document.querySelector('[data-qty-plus]')?.addEventListener('click', () => {
        if (qtyInput) {
            const max = parseInt(qtyInput.max || '99', 10);
            qtyInput.value = String(Math.min(max, parseInt(qtyInput.value || '1', 10) + 1));
        }
    });

    /* PDP WhatsApp sipariş — adet ile mesaj */
    document.querySelectorAll('[data-pdp-wa-order]').forEach((link) => {
        link.addEventListener('click', (e) => {
            const phone = link.dataset.waPhone;
            if (!phone) return;

            const qtySel = link.dataset.qtyFrom || '#pdp-qty';
            const qtyInput = qtySel ? document.querySelector(qtySel) : null;
            const qty = qtyInput ? Math.max(1, parseInt(qtyInput.value || '1', 10)) : 1;

            const fmt = (n) => Number(n).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const unit = parseFloat(link.dataset.waProductPrice || '0');
            const total = unit * qty;

            const rep = (tpl, map) => Object.entries(map).reduce((s, [k, v]) => s.split(k).join(String(v)), tpl);

            const lines = [
                link.dataset.waIntro || '',
                '',
                rep(link.dataset.waLabelProduct || '', { '__NAME__': link.dataset.waProductName || '' }),
                rep(link.dataset.waLabelSku || '', { '__SKU__': link.dataset.waProductSku || '' }),
                rep(link.dataset.waLabelQty || '', { '__QTY__': qty }),
                rep(link.dataset.waLabelUnit || '', { '__PRICE__': fmt(unit) }),
                rep(link.dataset.waLabelTotal || '', { '__PRICE__': fmt(total) }),
                rep(link.dataset.waLabelLink || '', { '__URL__': link.dataset.waProductUrl || '' }),
            ].filter((line, i) => line !== '' || i === 1);

            const url = `https://wa.me/${phone}?text=${encodeURIComponent(lines.join('\n'))}`;
            link.setAttribute('href', url);
        });
    });

    /* PDP tabs */
    function activatePdpTab(id) {
        const tab = document.querySelector(`[data-pdp-tab="${id}"]`);
        if (!tab) return;
        document.querySelectorAll('.pdp-tab').forEach((t) => {
            t.classList.remove('is-active');
            t.setAttribute('aria-selected', 'false');
        });
        tab.classList.add('is-active');
        tab.setAttribute('aria-selected', 'true');
        document.querySelectorAll('.pdp-panel').forEach((p) => p.classList.add('hidden'));
        document.getElementById(`pdp-panel-${id}`)?.classList.remove('hidden');
    }

    document.querySelectorAll('[data-pdp-tab]').forEach((tab) => {
        tab.addEventListener('click', () => activatePdpTab(tab.dataset.pdpTab));
    });

    const openReviewsTab = window.location.hash === '#yorumlar'
        || document.getElementById('pdp-panel-reviews')?.hasAttribute('data-open-reviews-tab')
        || (document.querySelector('.shop-flash--success') && document.getElementById('pdp-review-form'));
    if (openReviewsTab) {
        activatePdpTab('reviews');
        if (window.location.hash === '#yorumlar') {
            document.getElementById('pdp-panel-reviews')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    /* PDP taksit tablosu — adet değişince güncelle */
    const installmentsRoot = document.querySelector('[data-installments]');
    if (installmentsRoot) {
        const url = installmentsRoot.dataset.installmentsUrl;
        const qtySel = installmentsRoot.dataset.installmentsQty;
        const bodyEl = installmentsRoot.querySelector('[data-installments-body]');
        const amountLabel = installmentsRoot.querySelector('[data-installments-amount-label]');
        const unitPrice = parseFloat(installmentsRoot.dataset.installmentsAmount || '0');
        let installmentTimer;

        function refreshInstallments() {
            if (!url || !bodyEl) return;
            const qtyInput = qtySel ? document.querySelector(qtySel) : null;
            const qty = qtyInput ? Math.max(1, parseInt(qtyInput.value || '1', 10)) : 1;
            installmentsRoot.classList.add('is-loading');

            fetch(`${url}?qty=${qty}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then((r) => r.json())
                .then((data) => {
                    if (data.html) bodyEl.innerHTML = data.html;
                    if (amountLabel && data.formatted_amount) amountLabel.textContent = data.formatted_amount;
                })
                .catch(() => {})
                .finally(() => installmentsRoot.classList.remove('is-loading'));
        }

        const qtyInput = qtySel ? document.querySelector(qtySel) : null;
        if (qtyInput) {
            qtyInput.addEventListener('change', () => {
                clearTimeout(installmentTimer);
                installmentTimer = setTimeout(refreshInstallments, 350);
            });
        }

        document.querySelectorAll('[data-qty-plus], [data-qty-minus]').forEach((btn) => {
            btn.addEventListener('click', () => {
                clearTimeout(installmentTimer);
                installmentTimer = setTimeout(refreshInstallments, 350);
            });
        });
    }

    /* PDP yıldız puanı */
    document.querySelectorAll('[data-star-rating]').forEach((wrap) => {
        const input = wrap.querySelector('[data-star-input]');
        const hint = wrap.querySelector('[data-star-hint]');
        const buttons = [...wrap.querySelectorAll('[data-star-value]')];
        if (!input || !buttons.length) return;

        const hints = wrap.dataset.starHints ? JSON.parse(wrap.dataset.starHints) : null;

        function setRating(value) {
            const v = Math.max(1, Math.min(5, parseInt(String(value), 10) || 5));
            input.value = String(v);
            buttons.forEach((btn) => {
                const n = parseInt(btn.dataset.starValue, 10);
                const active = n <= v;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-checked', n === v ? 'true' : 'false');
            });
            if (hint && hints) {
                hint.textContent = hints[v] ?? hint.textContent;
            }
        }

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => setRating(btn.dataset.starValue));
        });

        setRating(input.value || 5);
    });

    /* Catalog sort auto-submit */
    document.querySelectorAll('[data-auto-submit]').forEach((el) => {
        el.addEventListener('change', () => el.closest('form')?.submit());
    });

    /* Filter drawer (mobile) */
    const filterOverlay = document.getElementById('filter-drawer-overlay');
    const filterPanel = document.getElementById('filter-drawer-panel');
    const filterOpen = document.getElementById('filter-drawer-open');
    const filterClose = document.getElementById('filter-drawer-close');

    function openFilterDrawer() {
        if (!filterOverlay || !filterPanel) return;
        filterOverlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => { filterPanel.style.transform = 'translateX(0)'; });
    }

    function closeFilterDrawer() {
        if (!filterOverlay || !filterPanel) return;
        filterPanel.style.transform = 'translateX(-100%)';
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => filterOverlay.classList.add('hidden'), 280);
    }

    filterOpen?.addEventListener('click', openFilterDrawer);
    filterClose?.addEventListener('click', closeFilterDrawer);
    filterOverlay?.addEventListener('click', (e) => {
        if (e.target === filterOverlay) closeFilterDrawer();
    });

    /* Search autocomplete */
    const suggestPairs = [
        ['header-search', 'search-suggest-desktop'],
        ['header-search-mobile', 'search-suggest-mobile'],
    ];
    let suggestTimer;
    let suggestAbort;

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderSuggest(panel, data) {
        if (!panel) return;
        const results = data?.results || [];
        if (!results.length) {
            panel.classList.add('hidden');
            panel.innerHTML = '';
            return;
        }
        const items = results.map((r) => {
            const img = r.image
                ? `<img src="${escapeHtml(r.image)}" alt="" loading="lazy">`
                : '<span class="w-10 h-10 rounded-lg bg-slate-100 shrink-0"></span>';
            const meta = r.meta ? `<span class="search-suggest-meta">${escapeHtml(r.meta)}</span>` : '';
            const price = r.price ? `<span class="search-suggest-meta ml-auto">${escapeHtml(r.price)}</span>` : '';
            return `<a href="${escapeHtml(r.url)}" class="search-suggest-item" role="option">${img}<span class="min-w-0 flex-1"><span class="block truncate font-medium">${escapeHtml(r.name)}</span>${meta}</span>${price}</a>`;
        }).join('');
        const footer = data.search_url
            ? `<div class="search-suggest-footer"><a href="${escapeHtml(data.search_url)}">Tüm sonuçları gör →</a></div>`
            : '';
        panel.innerHTML = items + footer;
        panel.classList.remove('hidden');
    }

    function bindSearchSuggest(inputId, panelId) {
        const input = document.getElementById(inputId);
        const panel = document.getElementById(panelId);
        if (!input || !panel) return;

        const hide = () => {
            panel.classList.add('hidden');
        };

        input.addEventListener('input', () => {
            const q = input.value.trim();
            clearTimeout(suggestTimer);
            if (q.length < 2) {
                hide();
                return;
            }
            suggestTimer = setTimeout(async () => {
                suggestAbort?.abort();
                suggestAbort = new AbortController();
                try {
                    const res = await fetch(`/ara/oneri?q=${encodeURIComponent(q)}`, {
                        headers: { Accept: 'application/json' },
                        signal: suggestAbort.signal,
                    });
                    const data = await res.json();
                    if (data?.ok) renderSuggest(panel, data);
                } catch (e) {
                    if (e.name !== 'AbortError') hide();
                }
            }, 220);
        });

        input.addEventListener('blur', () => setTimeout(hide, 180));
        input.addEventListener('focus', () => {
            if (panel.innerHTML && input.value.trim().length >= 2) panel.classList.remove('hidden');
        });
    }

    suggestPairs.forEach(([inputId, panelId]) => bindSearchSuggest(inputId, panelId));
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#search-autocomplete-desktop, #search-autocomplete-mobile')) {
            document.querySelectorAll('.search-suggest').forEach((p) => p.classList.add('hidden'));
        }
    });

    fetch('/sepet/ajax/ozet', { headers })
        .then((r) => r.json())
        .then((d) => { if (d.ok) updateCartBadge(d.count); })
        .catch(() => {});

    /* Sticky header — scroll gölgesi */
    const siteHeader = document.getElementById('shop-site-header');
    if (siteHeader) {
        let ticking = false;
        const onScroll = () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => {
                siteHeader.classList.toggle('is-scrolled', window.scrollY > 12);
                ticking = false;
            });
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* Yukarı çık butonu */
    const scrollTopButton = document.querySelector('[data-scroll-top]');
    if (scrollTopButton) {
        let ticking = false;
        const toggleScrollTop = () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => {
                scrollTopButton.classList.toggle('is-visible', window.scrollY > 360);
                ticking = false;
            });
        };

        scrollTopButton.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        toggleScrollTop();
        window.addEventListener('scroll', toggleScrollTop, { passive: true });
    }

    /* Scroll reveal — tüm varyantlar dahil */
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const revealSelectors = '.shop-reveal, .shop-reveal-group, .shop-reveal--left, .shop-reveal--right, .shop-reveal--scale';
    if (!prefersReduced && 'IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        revealObserver.unobserve(entry.target);
                    }
                });
            },
            { rootMargin: '0px 0px -6% 0px', threshold: 0.08 }
        );
        document.querySelectorAll(revealSelectors).forEach((el) => revealObserver.observe(el));
    } else {
        document.querySelectorAll(revealSelectors).forEach((el) => el.classList.add('is-visible'));
    }

    /* Ana sayfa banner carousel */
    const bannerSlider = document.querySelector('.shop-banner-slider');
    if (bannerSlider) {
        const slides = [...bannerSlider.querySelectorAll('.shop-banner-slide')];
        const dots = [...bannerSlider.querySelectorAll('[data-banner-dot]')];
        let index = slides.findIndex((s) => s.classList.contains('is-active'));
        if (index < 0) index = 0;
        let timer = null;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const autoplayMs = parseInt(bannerSlider.dataset.autoplayMs || '6000', 10);

        function goTo(i) {
            if (!slides.length) return;
            index = (i + slides.length) % slides.length;
            slides.forEach((slide, n) => {
                const active = n === index;
                slide.classList.toggle('is-active', active);
                slide.hidden = !active;
                if (active && !reducedMotion) {
                    const img = slide.querySelector('.shop-banner-slide__img');
                    if (img) {
                        img.classList.remove('is-animating');
                        void img.offsetWidth;
                        img.classList.add('is-animating');
                    }
                }
            });
            dots.forEach((dot, n) => {
                const active = n === index;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        }

        function next() {
            goTo(index + 1);
        }

        function startAutoplay() {
            if (reducedMotion || slides.length < 2) return;
            stopAutoplay();
            timer = setInterval(next, autoplayMs);
        }

        function stopAutoplay() {
            if (timer) clearInterval(timer);
            timer = null;
        }

        bannerSlider.querySelector('[data-banner-prev]')?.addEventListener('click', () => {
            goTo(index - 1);
            startAutoplay();
        });
        bannerSlider.querySelector('[data-banner-next]')?.addEventListener('click', () => {
            next();
            startAutoplay();
        });
        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                goTo(parseInt(dot.dataset.bannerDot, 10));
                startAutoplay();
            });
        });
        bannerSlider.addEventListener('mouseenter', stopAutoplay);
        bannerSlider.addEventListener('mouseleave', startAutoplay);
        bannerSlider.addEventListener('focusin', stopAutoplay);
        bannerSlider.addEventListener('focusout', startAutoplay);

        if (!reducedMotion) {
            slides[index]?.querySelector('.shop-banner-slide__img')?.classList.add('is-animating');
        }

        startAutoplay();
    }

    /* Ana sayfa ürün listesi — ok ile kaydırma */
    document.querySelectorAll('[data-product-carousel]').forEach((root) => {
        const viewport = root.querySelector('[data-product-carousel-viewport]');
        const track = root.querySelector('[data-product-carousel-track]');
        const prevBtn = root.querySelector('[data-product-carousel-prev]');
        const nextBtn = root.querySelector('[data-product-carousel-next]');
        if (!viewport || !track) return;

        let offset = 0;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const mobileCarousel = window.matchMedia('(max-width: 639px)');

        function maxOffset() {
            return Math.max(0, track.scrollWidth - viewport.clientWidth);
        }

        function pageStep() {
            const first = track.querySelector('.shop-home-product-carousel__item');
            if (!first) return viewport.clientWidth;
            const gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || '16') || 16;
            const card = first.offsetWidth + gap;
            const visible = Math.max(1, Math.floor(viewport.clientWidth / card));
            return card * visible;
        }

        function apply() {
            if (mobileCarousel.matches) {
                offset = 0;
                track.style.transform = 'none';
                root.classList.remove('is-static');
                if (prevBtn) prevBtn.disabled = true;
                if (nextBtn) nextBtn.disabled = true;
                return;
            }

            const max = maxOffset();
            offset = Math.max(0, Math.min(offset, max));
            track.style.transform = `translate3d(${-offset}px, 0, 0)`;
            const staticMode = max <= 2;
            root.classList.toggle('is-static', staticMode);
            if (prevBtn) prevBtn.disabled = staticMode || offset <= 0;
            if (nextBtn) nextBtn.disabled = staticMode || offset >= max - 1;
        }

        function go(delta) {
            offset += delta;
            apply();
        }

        prevBtn?.addEventListener('click', () => go(-pageStep()));
        nextBtn?.addEventListener('click', () => go(pageStep()));

        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(apply, 120);
        });
        mobileCarousel.addEventListener?.('change', apply);

        if (reducedMotion) {
            track.style.transition = 'none';
        }

        apply();
    });
})();

/* ═══════════════════════════════════════════════════════════════
   FAQ ACCORDION
   ═══════════════════════════════════════════════════════════════ */
(function () {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-faq-trigger]');
        if (!btn) return;

        const item = btn.closest('.shop-faq__item');
        if (!item) return;

        const isOpen = item.classList.contains('is-open');

        // Close all open items in this list
        const list = item.closest('.shop-faq__list');
        if (list) {
            list.querySelectorAll('.shop-faq__item.is-open').forEach(function (openItem) {
                if (openItem !== item) {
                    openItem.classList.remove('is-open');
                    const openBtn = openItem.querySelector('[data-faq-trigger]');
                    const openPanel = openItem.querySelector('.shop-faq__a');
                    if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
                    if (openPanel) openPanel.setAttribute('aria-hidden', 'true');
                }
            });
        }

        // Toggle current item
        item.classList.toggle('is-open', !isOpen);
        btn.setAttribute('aria-expanded', String(!isOpen));
        const panel = item.querySelector('.shop-faq__a');
        if (panel) panel.setAttribute('aria-hidden', String(isOpen));
    });
})();

/* ═══════════════════════════════════════════════════════════════
   READING PROGRESS BAR
   ═══════════════════════════════════════════════════════════════ */
(function () {
    const bar = document.querySelector('.shop-reading-progress__bar');
    if (!bar) return;

    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) { bar.style.width = '100%'; return; }

    function updateProgress() {
        const doc = document.documentElement;
        const scrollTop = window.scrollY || doc.scrollTop;
        const scrollHeight = doc.scrollHeight - doc.clientHeight;
        const pct = scrollHeight > 0 ? Math.min(100, (scrollTop / scrollHeight) * 100) : 0;
        bar.style.width = pct + '%';
    }

    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
})();

/* ═══════════════════════════════════════════════════════════════
   ESTIMATED READING TIME
   ═══════════════════════════════════════════════════════════════ */
(function () {
    const target = document.querySelector('[data-reading-time]');
    if (!target) return;

    const contentEl = document.querySelector('.shop-panel--prose');
    if (!contentEl) return;

    const text = contentEl.innerText || contentEl.textContent || '';
    const words = text.trim().split(/\s+/).filter(Boolean).length;
    const minutes = Math.max(1, Math.round(words / 200));
    target.textContent = minutes + ' dk okuma';
})();
