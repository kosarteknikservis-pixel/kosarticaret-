(function () {
    const cfg = window.AdminAi || {};
    const routes = cfg.routes || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function slugify(text) {
        const map = { ç: 'c', ğ: 'g', ı: 'i', ö: 'o', ş: 's', ü: 'u', Ç: 'c', Ğ: 'g', İ: 'i', Ö: 'o', Ş: 's', Ü: 'u' };
        let s = (text || '').trim().toLowerCase();
        s = s.replace(/[çğıöşüÇĞİÖŞÜ]/g, (ch) => map[ch] || ch);
        s = s.replace(/[^a-z0-9\s-]/g, '');
        s = s.replace(/[\s_]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        return s || 'item';
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || 'İstek başarısız.');
        }
        return data;
    }

    function toast(msg, isError) {
        let el = document.getElementById('admin-ai-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'admin-ai-toast';
            el.className = 'admin-ai-toast';
            document.body.appendChild(el);
        }
        el.textContent = msg;
        el.classList.toggle('admin-ai-toast--error', !!isError);
        el.classList.add('is-visible');
        clearTimeout(el._hideTimer);
        el._hideTimer = setTimeout(() => el.classList.remove('is-visible'), 4000);
    }

    function formContext(form) {
        const val = (n) => form.querySelector(`[name="${n}"]`)?.value?.trim() ?? '';
        const ctx = {
            name: val('name'),
            title: val('title'),
            slug: val('slug'),
            sku: val('sku'),
            short_description: val('short_description'),
            description: val('description'),
            excerpt: val('excerpt'),
            content: val('content'),
            meta_title: val('meta_title'),
            meta_description: val('meta_description'),
            tags: val('tags'),
            site_description: val('site_description'),
            contact_page_intro: val('contact_page_intro'),
            tagline: val('tagline'),
            promo_text: val('promo_text'),
            cookie_text: val('cookie_text'),
        };
        const brand = form.querySelector('[name="brand_id"]');
        if (brand?.value) {
            ctx.brand_id = brand.value;
            ctx.brand_name = brand.selectedOptions[0]?.text?.trim() || '';
        }
        return ctx;
    }

    function initSlugFields() {
        document.querySelectorAll('[data-slug-field]').forEach((wrap) => {
            const form = wrap.closest('form');
            const input = wrap.querySelector('[data-slug-input]');
            const sourceName = wrap.dataset.slugSource || 'name';
            const source = form?.querySelector(`[name="${sourceName}"]`);
            if (!input || !source || !form) return;

            let manual = !!input.value.trim();

            input.addEventListener('input', () => {
                manual = true;
            });

            const applySlug = async (text) => {
                if (!text.trim()) return;
                try {
                    const data = await postJson(routes.slug, {
                        text,
                        entity: wrap.dataset.slugEntity,
                        exclude_id: wrap.dataset.slugExcludeId ? Number(wrap.dataset.slugExcludeId) : null,
                    });
                    input.value = data.slug;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                } catch {
                    input.value = slugify(text);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            };

            const onSourceInput = () => {
                if (manual) return;
                applySlug(source.value);
            };

            source.addEventListener('input', debounce(onSourceInput, 400));
            source.addEventListener('blur', () => {
                if (!manual && !input.value.trim()) applySlug(source.value);
            });

            wrap.querySelector('[data-slug-refresh]')?.addEventListener('click', () => {
                manual = false;
                applySlug(source.value);
            });

            if (!input.value.trim() && source.value.trim()) {
                applySlug(source.value);
            }
        });
    }

    function initMetaSuggest() {
        document.querySelectorAll('[data-meta-suggest]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const form = btn.closest('form');
                if (!form) return;
                const type = form.dataset.aiType;
                if (!type) return;

                btn.disabled = true;
                const useAi = btn.dataset.metaUseAi === '1';
                try {
                    const data = await postJson(routes.meta, {
                        type,
                        use_ai: useAi,
                        context: formContext(form),
                    });
                    const title = form.querySelector('[name="meta_title"]');
                    const desc = form.querySelector('[name="meta_description"]');
                    if (title) title.value = data.meta_title || '';
                    if (desc) desc.value = data.meta_description || '';
                    title?.dispatchEvent(new Event('input', { bubbles: true }));
                    desc?.dispatchEvent(new Event('input', { bubbles: true }));
                    toast(useAi ? 'OpenAI meta önerisi uygulandı.' : 'Meta önerisi uygulandı.');
                } catch (e) {
                    toast(e.message, true);
                } finally {
                    btn.disabled = false;
                }
            });
        });
    }

    function initAiGenerate() {
        document.querySelectorAll('[data-ai-generate]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const form = btn.closest('form');
                if (!form) return;
                const type = form.dataset.aiType;
                const field = btn.dataset.aiField;
                let target = btn.dataset.aiTarget
                    ? form.querySelector(btn.dataset.aiTarget)
                    : form.querySelector(`[name="${field}"]`);

                if (!target && field === 'description') {
                    target = form.querySelector('[data-rich-textarea]');
                }

                if (!type || !field || !target) return;

                btn.disabled = true;
                btn.classList.add('is-loading');
                try {
                    const data = await postJson(routes.generate, {
                        type,
                        field: field.includes('[') ? 'description' : field,
                        context: formContext(form),
                    });
                    target.value = data.content || '';
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                    toast('İçerik oluşturuldu.');
                } catch (e) {
                    toast(e.message, true);
                } finally {
                    btn.disabled = false;
                    btn.classList.remove('is-loading');
                }
            });
        });
    }

    function debounce(fn, ms) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    }

    initSlugFields();
    initMetaSuggest();
    initAiGenerate();
})();
