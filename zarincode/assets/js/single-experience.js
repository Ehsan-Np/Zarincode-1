(() => {
    'use strict';
    async function copyText(value, input) {
        if (navigator.clipboard && window.isSecureContext) {
            try { await navigator.clipboard.writeText(value); return; } catch (_) { /* Older browsers use selection. */ }
        }
        const field = input || document.createElement('textarea');
        if (!input) { field.value = value; field.style.cssText = 'position:fixed;opacity:0;inset:0;pointer-events:none'; document.body.append(field); }
        field.focus(); field.select();
        const copied = document.execCommand('copy');
        if (!input) field.remove();
        if (!copied) throw new Error('copy-unavailable');
    }
    document.addEventListener('click', async event => {
        const copy = event.target.closest('[data-zc-copy]');
        const share = event.target.closest('[data-zc-native-share]');
        if (!copy && !share) return;
        const root = (copy || share).closest('.zc-story-share');
        if (!root) return;
        const status = root.querySelector('[data-zc-copy-status]');
        const input = copy && document.getElementById(copy.dataset.zcCopy);
        const value = input ? input.value : share.dataset.url;
        try {
            if (share && navigator.share) {
                try { await navigator.share({url:value,title:share.dataset.title}); return; }
                catch (error) { if (error.name === 'AbortError') return; }
            }
            await copyText(value, input);
            status.textContent = share?.dataset.zcNetwork ? `لینک کپی شد؛ در ${share.dataset.zcNetwork} جای‌گذاری کنید.` : 'لینک کپی شد.';
        } catch (_) { status.textContent = 'کپی خودکار ممکن نشد؛ نشانی کوتاه را انتخاب و کپی کنید.'; }
    });
})();
