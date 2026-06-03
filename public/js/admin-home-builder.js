function initHomeBannerPanel(root) {
    const scope = root || document;
    const type = scope.querySelector('#panel-banner-type');
    if (!type) return;

    const product = scope.querySelector('#panel-product');
    const productList = scope.querySelector('#panel-product-list');
    const listSource = scope.querySelector('#panel-product-source');
    const categoryBox = scope.querySelector('#panel-category');
    const categoryLabel = scope.querySelector('#panel-category-label');
    const listBrand = scope.querySelector('#panel-list-brand');
    const listManual = scope.querySelector('#panel-list-manual');
    const imageBlock = scope.querySelector('#panel-image-block');
    const links = scope.querySelector('#panel-link-fields');

    function syncImageSpec() {
        const slot = scope.querySelector('#panel-image-spec-slot');
        const t = type.value || 'slider';
        const tpl = scope.querySelector('#admin-spec-tpl-' + t);
        if (slot && tpl) slot.innerHTML = tpl.innerHTML;
    }

    function syncListSource() {
        const src = listSource?.value || 'latest';
        listBrand?.toggleAttribute('hidden', src !== 'brand');
        listManual?.toggleAttribute('hidden', src !== 'manual');
        const showCat = type.value === 'category' || (type.value === 'product_list' && src === 'category');
        categoryBox?.toggleAttribute('hidden', !showCat);
        if (categoryLabel) {
            categoryLabel.textContent = type.value === 'product_list' ? 'Hangi kategori?' : 'Kategori (kutu)';
        }
    }

    function sync() {
        const v = type.value;
        const isList = v === 'product_list';
        product?.toggleAttribute('hidden', v !== 'product');
        productList?.toggleAttribute('hidden', !isList);
        imageBlock?.toggleAttribute('hidden', isList);
        links?.toggleAttribute('hidden', v === 'product' || v === 'category' || isList);
        syncListSource();
        if (!isList) syncImageSpec();
    }

    type.addEventListener('change', sync);
    listSource?.addEventListener('change', syncListSource);
    initProductSorter(scope);
    sync();
}

function initProductSorter(scope) {
    const select = scope.querySelector('[data-product-picker-select]');
    const list = scope.querySelector('[data-product-sort-list]');
    if (!select || !list) return;

    function selectedOptionIds() {
        return [...select.selectedOptions].map((option) => option.value);
    }

    function ensureSelectedOptions() {
        const ids = new Set([...list.querySelectorAll('[data-product-id]')].map((item) => item.dataset.productId));
        [...select.options].forEach((option) => {
            option.selected = ids.has(option.value);
        });
    }

    function itemTemplate(option) {
        const item = document.createElement('div');
        item.className = 'hp-product-sort-item';
        item.dataset.productId = option.value;
        item.innerHTML = `
            <span class="hp-product-sort-item__handle" aria-hidden="true">⋮⋮</span>
            <span class="hp-product-sort-item__name"></span>
            <button type="button" class="hp-product-sort-item__remove" data-product-remove aria-label="Ürünü listeden çıkar">×</button>
            <input type="hidden" name="product_ids[]" value="${option.value}">
        `;
        item.querySelector('.hp-product-sort-item__name').textContent = option.textContent.trim();

        return item;
    }

    function syncListFromSelect() {
        const selectedIds = new Set(selectedOptionIds());

        [...list.querySelectorAll('[data-product-id]')].forEach((item) => {
            if (!selectedIds.has(item.dataset.productId)) {
                item.remove();
            }
        });

        selectedIds.forEach((id) => {
            const exists = [...list.querySelectorAll('[data-product-id]')].some((item) => item.dataset.productId === id);
            if (exists) return;
            const option = [...select.options].find((candidate) => candidate.value === id);
            if (option) list.appendChild(itemTemplate(option));
        });
    }

    select.addEventListener('change', syncListFromSelect);
    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-product-remove]');
        if (!button) return;

        const item = button.closest('[data-product-id]');
        const id = item?.dataset.productId;
        if (id) {
            const option = [...select.options].find((candidate) => candidate.value === id);
            if (option) option.selected = false;
        }
        item?.remove();
    });

    if (typeof Sortable !== 'undefined') {
        new Sortable(list, {
            animation: 150,
            handle: '.hp-product-sort-item__handle',
            ghostClass: 'sortable-ghost',
            onEnd: ensureSelectedOptions,
        });
    }

    ensureSelectedOptions();
}

(function () {
    const root = document.getElementById('hp-builder');
    if (!root) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const layoutSaveUrl = root.dataset.layoutSave;
    const rowStoreUrl = root.dataset.rowStore;
    const rowDestroyTpl = root.dataset.rowDestroy || '';
    const panel = document.getElementById('builder-panel');
    const panelContent = document.getElementById('panel-content');
    const panelPlaceholder = document.getElementById('panel-placeholder');
    const saveStatus = document.getElementById('hp-save-status');
    const canvas = document.getElementById('hp-canvas');

    let selectedBlock = null;
    let saveTimer = null;

    function setStatus(msg) {
        if (saveStatus) saveStatus.textContent = msg;
    }

    function collectLayout() {
        const rows = [...canvas?.querySelectorAll('.hp-row') || []].map((rowEl) => {
            const columns = [...rowEl.querySelectorAll('.hp-col__drop')].map((drop) =>
                [...drop.querySelectorAll('.hp-block')].map((b) => parseInt(b.dataset.id, 10))
            );
            return { id: parseInt(rowEl.dataset.rowId, 10), columns };
        });
        return { rows };
    }

    async function saveLayout() {
        if (!layoutSaveUrl || !csrf) return;
        setStatus('Kaydediliyor…');
        try {
            const res = await fetch(layoutSaveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify(collectLayout()),
            });
            if (res.ok) {
                setStatus('Kaydedildi');
                setTimeout(() => setStatus(''), 2000);
            } else {
                setStatus('Kayıt hatası');
            }
        } catch (_) {
            setStatus('Kayıt hatası');
        }
    }

    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveLayout, 400);
    }

    function initSortables(attempt = 0) {
        if (typeof Sortable === 'undefined') {
            if (attempt < 20) {
                window.setTimeout(() => initSortables(attempt + 1), 150);
            } else {
                setStatus('Sürükle-bırak yüklenemedi');
            }
            return;
        }

        const blockGroup = {
            name: 'hp-blocks',
            pull: true,
            put: true,
        };

        canvas?.querySelectorAll('.hp-col__drop').forEach((drop) => {
            if (drop.dataset.sortableReady === '1') return;
            drop.dataset.sortableReady = '1';

            new Sortable(drop, {
                group: blockGroup,
                animation: 150,
                handle: '.hp-block',
                filter: '.hp-block__active-input, .hp-block__toggle',
                ghostClass: 'sortable-ghost',
                onEnd: scheduleSave,
            });
        });

        if (canvas && canvas.dataset.sortableReady !== '1') {
            canvas.dataset.sortableReady = '1';

            new Sortable(canvas, {
                animation: 150,
                handle: '.hp-row__handle',
                ghostClass: 'sortable-ghost-row',
                onEnd: scheduleSave,
            });
        }
    }

    initSortables();
    bindBlocks();
    bindColumnAdds();

    async function loadPanel(url) {
        if (!panelContent || !panelPlaceholder) return;
        panelContent.innerHTML = '<p class="text-sm text-slate-500 p-4">Yükleniyor…</p>';
        panelContent.classList.remove('hidden');
        panelPlaceholder.classList.add('hidden');
        panel?.classList.add('is-open');
        try {
            const res = await fetch(url, { headers: { Accept: 'text/html' } });
            panelContent.innerHTML = await res.text();
            panelContent.querySelector('#panel-close')?.addEventListener('click', closePanel);
            initHomeBannerPanel(panelContent);
        } catch (_) {
            panelContent.innerHTML = '<p class="text-red-600 text-sm">Yüklenemedi.</p>';
        }
    }

    function closePanel() {
        panel?.classList.remove('is-open');
        panelContent?.classList.add('hidden');
        panelPlaceholder?.classList.remove('hidden');
        selectedBlock?.classList.remove('is-selected');
        selectedBlock = null;
    }

    function selectBlock(block) {
        selectedBlock?.classList.remove('is-selected');
        selectedBlock = block;
        block?.classList.add('is-selected');
        if (block?.dataset.panelUrl) loadPanel(block.dataset.panelUrl);
    }

    function bindBlocks() {
        root.querySelectorAll('.hp-block').forEach((block) => {
            block.onclick = (e) => {
                if (e.target.closest('.hp-block__active-input')) return;
                selectBlock(block);
            };
            const toggle = block.querySelector('.hp-block__active-input');
            if (toggle) {
                toggle.onchange = async (e) => {
                    e.stopPropagation();
                    const url = (root.dataset.quickTemplate || '').replace('__ID__', block.dataset.id);
                    try {
                        await fetch(url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                Accept: 'application/json',
                            },
                            body: JSON.stringify({ active: e.target.checked }),
                        });
                        block.classList.toggle('hp-block--off', !e.target.checked);
                    } catch (_) {
                        e.target.checked = !e.target.checked;
                    }
                };
            }
        });
    }

    function bindColumnAdds() {
        root.querySelectorAll('.hp-col__add').forEach((btn) => {
            btn.onclick = () => loadPanel(btn.dataset.createUrl);
        });
    }

    root.querySelectorAll('[data-row-preset]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!rowStoreUrl || !csrf) return;
            setStatus('Satır ekleniyor…');
            try {
                const res = await fetch(rowStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ preset: btn.dataset.rowPreset }),
                });
                if (res.ok) window.location.reload();
            } catch (_) {
                setStatus('Hata');
            }
        });
    });

    root.querySelectorAll('[data-delete-row]').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            if (!confirm('Bu satır silinsin mi? Bloklar listeden ayrılır.')) return;
            const id = btn.dataset.deleteRow;
            const url = rowDestroyTpl.replace('__ID__', id);
            try {
                await fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                });
                btn.closest('.hp-row')?.remove();
                scheduleSave();
            } catch (_) { /* ignore */ }
        });
    });

    root.querySelectorAll('[data-add-type]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const firstDrop = canvas?.querySelector('.hp-col__drop');
            if (!firstDrop) return;
            const url = `${root.dataset.panelCreate}?type=${btn.dataset.addType}&row_id=${firstDrop.dataset.rowId}&col_index=${firstDrop.dataset.colIndex}`;
            loadPanel(url);
        });
    });

    document.getElementById('panel-close')?.addEventListener('click', closePanel);
})();
