(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    /* Tür alanları */
    const typeSelect = document.getElementById('banner-type');
    const panelProduct = document.getElementById('panel-product');
    const panelCategory = document.getElementById('panel-category');
    const panelLinkFields = document.getElementById('panel-link-fields');
    function syncImageSpec() {
        const type = typeSelect?.value || 'slider';
        const tpl = document.getElementById('admin-spec-tpl-' + type);
        const slot = document.getElementById('form-image-spec-slot') || document.getElementById('panel-image-spec-slot');
        if (slot && tpl) slot.innerHTML = tpl.innerHTML;
    }

    function syncTypePanels() {
        if (!typeSelect) return;
        const type = typeSelect.value;
        const isProduct = type === 'product';
        const isCategory = type === 'category';

        panelProduct?.toggleAttribute('hidden', !isProduct);
        panelCategory?.toggleAttribute('hidden', !isCategory);
        panelLinkFields?.toggleAttribute('hidden', isProduct || isCategory);
        syncImageSpec();
    }

    typeSelect?.addEventListener('change', syncTypePanels);
    syncTypePanels();

    /* Dropzone */
    const dropzone = document.getElementById('banner-dropzone');
    const fileInput = document.getElementById('banner-image-input');
    const inner = document.getElementById('banner-dropzone-inner');

    function showPreview(file) {
        if (!inner || !file?.type?.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            inner.innerHTML = `<img src="${e.target.result}" alt="" id="banner-preview-img" class="admin-dropzone__preview">`;
            dropzone?.classList.add('admin-dropzone--has-preview');
        };
        reader.readAsDataURL(file);
    }

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', (e) => {
            if (e.target.closest('input[type="checkbox"]')) return;
            fileInput.click();
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files?.[0]) showPreview(fileInput.files[0]);
        });
        ['dragenter', 'dragover'].forEach((ev) => {
            dropzone.addEventListener(ev, (e) => {
                e.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach((ev) => {
            dropzone.addEventListener(ev, (e) => {
                e.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });
        dropzone.addEventListener('drop', (e) => {
            const file = e.dataTransfer?.files?.[0];
            if (!file) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            showPreview(file);
        });
    }

    /* Liste sıralama */
    const list = document.getElementById('banner-sort-list');
    if (!list || !csrf) return;

    const url = list.dataset.reorderUrl;
    let dragged = null;

    list.querySelectorAll('.admin-banner-row').forEach((row) => {
        row.addEventListener('dragstart', () => {
            dragged = row;
            row.classList.add('is-dragging');
        });
        row.addEventListener('dragend', () => {
            row.classList.remove('is-dragging');
            list.querySelectorAll('.admin-banner-row').forEach((r) => r.classList.remove('is-drop-target'));
            dragged = null;
        });
        row.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (!dragged || dragged === row) return;
            row.classList.add('is-drop-target');
            const rect = row.getBoundingClientRect();
            const after = e.clientY > rect.top + rect.height / 2;
            if (after) {
                row.after(dragged);
            } else {
                row.before(dragged);
            }
        });
        row.addEventListener('dragleave', () => row.classList.remove('is-drop-target'));
    });

    list.addEventListener('dragend', async () => {
        const order = [...list.querySelectorAll('.admin-banner-row')].map((r) => parseInt(r.dataset.id, 10));
        try {
            await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ order }),
            });
        } catch (_) { /* ignore */ }
    });
})();
