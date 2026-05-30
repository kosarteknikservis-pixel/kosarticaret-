(function () {
    const form = document.getElementById('bulk-update-form');
    const countEl = document.getElementById('bulk-match-count');
    const previewUrl = window.AdminBulkUpdate?.previewUrl;

    if (!form || !countEl || !previewUrl) {
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    let timer = null;

    const refreshCount = () => {
        const body = new FormData(form);
        fetch(previewUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token || '',
                Accept: 'application/json',
            },
            body,
        })
            .then((r) => r.json())
            .then((data) => {
                countEl.textContent = typeof data.count === 'number' ? data.count.toLocaleString('tr-TR') : '—';
            })
            .catch(() => {
                countEl.textContent = '—';
            });
    };

    const schedule = () => {
        clearTimeout(timer);
        timer = setTimeout(refreshCount, 350);
    };

    form.querySelectorAll('[data-bulk-filter]').forEach((el) => {
        el.addEventListener('input', schedule);
        el.addEventListener('change', schedule);
    });

    refreshCount();
})();
