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

    const chart = document.querySelector('[data-dashboard-chart]');
    if (chart) {
        let series = {};
        try {
            series = JSON.parse(chart.dataset.chartSeries || '{}');
        } catch {
            series = {};
        }

        const totalEl = document.querySelector('[data-dashboard-chart-total]');
        const ordersEl = document.querySelector('[data-dashboard-chart-orders]');
        const currency = (value) => Number(value || 0).toLocaleString('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        function renderChart(range) {
            const points = series[range]?.points || [];
            const max = Math.max(1, ...points.map((point) => Number(point.revenue || 0)));
            const total = points.reduce((sum, point) => sum + Number(point.revenue || 0), 0);
            const orderCount = points.reduce((sum, point) => sum + Number(point.orders || 0), 0);

            chart.classList.remove('is-animating');
            chart.innerHTML = points.map((point) => {
                const revenue = Number(point.revenue || 0);
                const height = Math.max(8, Math.round((revenue / max) * 100));

                return `
                    <div class="admin-sales-chart__bar-wrap">
                        <span class="admin-sales-chart__tooltip">${currency(revenue)} ₺ · ${point.orders || 0} sipariş</span>
                        <span class="admin-sales-chart__bar" style="height: ${height}%"></span>
                        <span class="admin-sales-chart__label">${point.label || ''}</span>
                    </div>
                `;
            }).join('');

            if (totalEl) totalEl.textContent = `Toplam: ${currency(total)} ₺`;
            if (ordersEl) ordersEl.textContent = `${orderCount} sipariş`;
            requestAnimationFrame(() => chart.classList.add('is-animating'));
        }

        document.querySelectorAll('[data-dashboard-chart-range]').forEach((button) => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-dashboard-chart-range]').forEach((item) => item.classList.remove('is-active'));
                button.classList.add('is-active');
                renderChart(button.dataset.dashboardChartRange || 'month');
            });
        });
    }
})();
