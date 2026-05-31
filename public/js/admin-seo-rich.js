(function () {
    const RichSnippets = {
        h2: '<h2>Başlık</h2>\n',
        h3: '<h3>Alt başlık</h3>\n',
        p: '<p>Paragraf metni.</p>\n',
        ul: '<ul>\n<li>Madde 1</li>\n<li>Madde 2</li>\n</ul>\n',
        strong: '<strong>kalın metin</strong>',
        link: '<a href="https://">link metni</a>',
    };

    function isHtml(text) {
        return text !== '' && text !== text.replace(/<[^>]+>/g, '');
    }

    function plainText(text) {
        const t = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        return t;
    }

    function wordCount(text) {
        const t = plainText(text);
        return t ? t.split(/\s+/).filter(Boolean).length : 0;
    }

    function hasHeading(text) {
        return /<h[2-4][^>]*>/i.test(text);
    }

    function renderPreview(text) {
        if (!text.trim()) return '<p class="text-slate-400">Önizleme için metin yazın.</p>';
        if (!isHtml(text)) {
            return text.split(/\n\s*\n/).map((p) => {
                const e = p.trim().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                return `<p>${e}</p>`;
            }).join('');
        }
        return text;
    }

    document.querySelectorAll('[data-rich-editor]').forEach((root) => {
        const textarea = root.querySelector('[data-rich-textarea]');
        const preview = root.querySelector('[data-rich-preview]');
        const toolbar = root.querySelector('[data-rich-toolbar]');
        const modes = root.querySelectorAll('[data-rich-mode]');
        if (!textarea) return;

        const updatePreview = () => {
            if (preview) preview.innerHTML = renderPreview(textarea.value);
        };

        const setMode = (mode) => {
            modes.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.richMode === mode));
            toolbar?.classList.toggle('hidden', mode === 'html');
            textarea.classList.toggle('font-mono', mode === 'html');
            if (mode === 'plain' && isHtml(textarea.value)) {
                if (!window.confirm('HTML etiketleri düz metne çevrilsin mi? Etiketler kaldırılır.')) {
                    return;
                }
                textarea.value = plainText(textarea.value);
            }
            updatePreview();
        };

        modes.forEach((btn) => {
            btn.addEventListener('click', () => setMode(btn.dataset.richMode));
        });

        toolbar?.querySelectorAll('[data-rich-action]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const action = btn.dataset.richAction;
                const insert = RichSnippets[action] || '';
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const val = textarea.value;
                textarea.value = val.slice(0, start) + insert + val.slice(end);
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = start + insert.length;
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                updatePreview();
            });
        });

        textarea.addEventListener('input', updatePreview);
        setMode(isHtml(textarea.value) ? 'html' : 'plain');
    });

    function checkItem(id, label, points, max, message) {
        let status = 'bad';
        if (points >= max) status = 'good';
        else if (points > 0) status = 'warn';
        return { id, label, status, message, points, max };
    }

    function analyze(type, data) {
        const checks = [];
        const name = data.name || '';
        const metaTitle = data.meta_title || '';
        const metaDesc = data.meta_description || '';
        const body = data.description || '';

        const lenScore = (val, min, max, pts, label, id) => {
            const len = val.length;
            if (!len) return checkItem(id, label, Math.round(pts * 0.35), pts, 'Boş — otomatik üretilebilir.');
            if (len >= min && len <= max) return checkItem(id, label, pts, pts, `${len} karakter — ideal.`);
            if (len < min) return checkItem(id, label, Math.round(pts * 0.6), pts, `${len} karakter; hedef ${min}–${max}.`);
            return checkItem(id, label, Math.round(pts * 0.75), pts, `${len} karakter; kısaltın (${max}).`);
        };

        const nameLabel = type === 'blog' ? 'Başlık' : (type === 'page' ? 'Sayfa başlığı' : 'Ad');
        checks.push(checkItem('name', nameLabel, name ? 10 : 0, 10, name ? 'Tamam.' : 'Zorunlu alan.'));
        checks.push(checkItem('slug', 'Slug', data.slug ? 5 : 0, 5, data.slug ? 'Tamam.' : 'Slug girin.'));

        if (type === 'product') {
            checks.push(lenScore(metaTitle, 45, 65, 15, 'SEO başlık', 'meta_title'));
            checks.push(lenScore(metaDesc, 120, 165, 15, 'SEO açıklama', 'meta_description'));
            checks.push(lenScore(data.short_description || '', 60, 200, 10, 'Kısa açıklama', 'short_description'));
            const words = wordCount(body);
            if (words >= 200) checks.push(checkItem('description', 'Açıklama', 15, 15, `${words} kelime.`));
            else if (words >= 80) checks.push(checkItem('description', 'Açıklama', 8, 15, `${words} kelime; hedef 200+.`));
            else checks.push(checkItem('description', 'Açıklama', 0, 15, `En az 200 kelime (${words}).`));
            if (hasHeading(body)) checks.push(checkItem('description_structure', 'Başlık yapısı', 10, 10, 'H2/H3 var.'));
            else checks.push(checkItem('description_structure', 'Başlık yapısı', 0, 10, 'H2/H3 veya paragraflar ekleyin.'));
            const tags = (data.tags || '').split(',').map((t) => t.trim()).filter(Boolean);
            const tc = tags.length;
            if (tc >= 3 && tc <= 10) checks.push(checkItem('tags', 'Anahtar kelimeler', 10, 10, `${tc} kelime.`));
            else if (tc > 0) checks.push(checkItem('tags', 'Anahtar kelimeler', 5, 10, `${tc} kelime; 3–10 ideal.`));
            else checks.push(checkItem('tags', 'Anahtar kelimeler', 0, 10, 'En az 3 anahtar kelime.'));
            checks.push(checkItem('image', 'Kapak görseli', data.has_image ? 10 : 0, 10, data.has_image ? 'Var.' : 'Görsel ekleyin.'));
            checks.push(checkItem('sku', 'SKU', data.sku ? 5 : 0, 5, data.sku ? 'Var.' : 'SKU önerilir.'));
        } else {
            checks.push(lenScore(metaTitle, 45, 65, 20, 'SEO başlık', 'meta_title'));
            checks.push(lenScore(metaDesc, 120, 165, 20, 'SEO açıklama', 'meta_description'));
            if (type === 'blog') {
                checks.push(lenScore(data.excerpt || '', 80, 200, 15, 'Özet', 'excerpt'));
            }
            const minW = type === 'blog' ? 300 : (type === 'page' ? 150 : (type === 'brand' ? 100 : 120));
            const words = wordCount(body);
            const bodyLabel = type === 'blog' ? 'İçerik' : (type === 'page' ? 'Sayfa içeriği' : 'Açıklama');
            const bodyMax = type === 'blog' ? 25 : 20;
            if (words >= minW) checks.push(checkItem('description', bodyLabel, bodyMax, bodyMax, `${words} kelime.`));
            else if (words >= minW * 0.4) checks.push(checkItem('description', bodyLabel, Math.round(bodyMax * 0.55), bodyMax, `${words} kelime; hedef ${minW}+.`));
            else checks.push(checkItem('description', bodyLabel, 0, bodyMax, `En az ${minW} kelime.`));
            if (hasHeading(body)) checks.push(checkItem('description_structure', 'Başlık yapısı', 10, 10, 'H2/H3 var.'));
            else checks.push(checkItem('description_structure', 'Başlık yapısı', 0, 10, 'H2/H3 ekleyin.'));
            const imgLabel = type === 'brand' ? 'Logo' : 'Görsel';
            checks.push(checkItem('image', imgLabel, data.has_image ? 5 : 0, 5, data.has_image ? 'Var.' : 'Önerilir.'));
        }

        const earned = checks.reduce((s, c) => s + c.points, 0);
        const max = checks.reduce((s, c) => s + c.max, 0);
        const score = max ? Math.round((earned / max) * 100) : 0;
        let grade = 'Geliştirilmeli';
        let gradeClass = 'poor';
        if (score >= 85) { grade = 'Mükemmel'; gradeClass = 'excellent'; }
        else if (score >= 70) { grade = 'İyi'; gradeClass = 'good'; }
        else if (score >= 50) { grade = 'Orta'; gradeClass = 'fair'; }

        return { score, grade, grade_class: gradeClass, checks };
    }

    function collectData(form, type) {
        const val = (n) => form.querySelector(`[name="${n}"]`)?.value?.trim() ?? '';
        const primaryName = type === 'blog' || type === 'page' ? val('title') : val('name');
        const body = type === 'blog' || type === 'page' ? val('content') : val('description');
        return {
            name: primaryName,
            slug: val('slug'),
            meta_title: val('meta_title'),
            meta_description: val('meta_description'),
            short_description: val('short_description'),
            description: body,
            excerpt: val('excerpt'),
            tags: val('tags'),
            sku: val('sku'),
            has_image: form.dataset.seoHasImage === '1',
        };
    }

    function renderScore(root, result) {
        root.classList.remove('admin-seo-score--excellent', 'admin-seo-score--good', 'admin-seo-score--fair', 'admin-seo-score--poor');
        root.classList.add(`admin-seo-score--${result.grade_class}`);
        const val = root.querySelector('[data-seo-score-value]');
        const grade = root.querySelector('[data-seo-score-grade]');
        if (val) val.textContent = String(result.score);
        if (grade) grade.textContent = result.grade;
        const list = root.querySelector('[data-seo-score-checks]');
        if (!list) return;
        list.innerHTML = result.checks.map((c) => `
            <li class="admin-seo-score__check admin-seo-score__check--${c.status}" data-check-id="${c.id}">
                <span class="admin-seo-score__check-icon" aria-hidden="true"></span>
                <span class="min-w-0">
                    <span class="admin-seo-score__check-label">${c.label}</span>
                    <span class="admin-seo-score__check-msg">${c.message}</span>
                </span>
            </li>`).join('');
    }

    document.querySelectorAll('[data-seo-score]').forEach((root) => {
        const form = root.closest('form');
        if (!form) return;
        const type = root.dataset.seoType || 'product';

        const refresh = () => renderScore(root, analyze(type, collectData(form, type)));

        form.addEventListener('input', refresh);
        form.addEventListener('change', refresh);
        refresh();
    });

    document.querySelectorAll('form[data-seo-has-image] input[type="file"]').forEach((input) => {
        input.addEventListener('change', () => {
            const form = input.closest('form');
            if (form && input.files?.length) {
                form.dataset.seoHasImage = '1';
                form.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });
})();
