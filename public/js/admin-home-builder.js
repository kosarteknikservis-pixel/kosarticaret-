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
    sync();
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

    function initSortables() {
        if (typeof Sortable === 'undefined') return;

        const blockGroup = {
            name: 'hp-blocks',
            pull: true,
            put: true,
        };

        canvas?.querySelectorAll('.hp-col__drop').forEach((drop) => {
            new Sortable(drop, {
                group: blockGroup,
                animation: 150,
                handle: '.hp-block__handle',
                ghostClass: 'sortable-ghost',
                onEnd: scheduleSave,
            });
        });

        if (canvas) {
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
            panelContent.querySelector('form')?.addEventListener('submit', () => {
                setTimeout(() => window.location.reload(), 600);
            });
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
