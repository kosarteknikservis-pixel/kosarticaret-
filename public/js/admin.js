(function () {
    const overlay = document.getElementById('admin-sidebar-overlay');
    const panel = document.getElementById('admin-sidebar-panel');
    const openBtn = document.getElementById('admin-sidebar-open');
    const closeBtn = document.getElementById('admin-sidebar-close');

    function openSidebar() {
        if (!overlay || !panel) return;
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(() => { panel.style.transform = 'translateX(0)'; });
    }

    function closeSidebar() {
        if (!overlay || !panel) return;
        panel.style.transform = 'translateX(-100%)';
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => overlay.classList.add('hidden'), 280);
    }

    openBtn?.addEventListener('click', openSidebar);
    closeBtn?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', (e) => {
        if (e.target === overlay) closeSidebar();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSidebar();
    });

    document.querySelectorAll('[data-admin-nav-group]').forEach((group) => {
        const toggle = group.querySelector('[data-admin-nav-group-toggle]');
        const panelId = toggle?.getAttribute('aria-controls');
        const panel = panelId ? document.getElementById(panelId) : null;
        if (!toggle || !panel) return;

        const setOpen = (open) => {
            group.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        toggle.addEventListener('click', () => {
            setOpen(!group.classList.contains('is-open'));
        });
    });

    document.querySelectorAll('[data-admin-seo-fields]').forEach((block) => {
        const update = (field, max, key) => {
            const input = block.querySelector(`[data-seo-field="${field}"]`);
            const counter = block.querySelector(`[data-seo-count="${key}"]`);
            if (!input || !counter) return;
            const len = input.value.length;
            counter.textContent = `${len} / ${max}`;
            counter.classList.toggle('text-amber-600', len > max);
            counter.classList.toggle('text-emerald-600', len > 0 && len <= max);
        };
        block.querySelectorAll('[data-seo-field]').forEach((input) => {
            const field = input.dataset.seoField;
            const max = field === 'title' ? 60 : 160;
            input.addEventListener('input', () => update(field, max, field));
            update(field, max, field);
        });
    });

    const maintCard = document.querySelector('[data-maint-card]');
    const maintToggle = document.querySelector('[data-maint-toggle]');
    if (maintCard && maintToggle) {
        const stateEl = maintCard.querySelector('[data-maint-state]');
        const hintEl = maintCard.querySelector('[data-maint-hint]');
        const syncMaint = () => {
            const on = maintToggle.checked;
            maintCard.classList.toggle('admin-maint-card--on', on);
            if (stateEl) stateEl.textContent = on ? 'Bakım modu açık' : 'Mağaza yayında';
            if (hintEl) {
                hintEl.textContent = on
                    ? 'Ziyaretçiler bakım sayfasını görür (HTTP 503). Değişiklikler kaydedildikten sonra geçerli olur.'
                    : 'Açtığınızda ziyaretçiler özel bakım sayfasına yönlendirilir; panel erişimi etkilenmez.';
            }
        };
        maintToggle.addEventListener('change', syncMaint);
    }
})();
