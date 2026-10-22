(() => {
    'use strict';
    async function copyText(value, input) {
        if (navigator.clipboard && window.isSecureContext) {
            try { await navigator.clipboard.writeText(value); return; } catch (_) { /* Older browsers use selection. */ }
        }
        const field = input || document.createElement('textarea');
        if (!input) { field.value = value; field.style.cssText = 'position:fixed;opacity:0;inset:0;pointer-events:none'; document.body.append(field); }
        const previous = document.activeElement;
        field.focus(); field.select();
        const copied = document.execCommand('copy');
        if (!input) field.remove();
        previous?.focus({preventScroll:true});
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
            if(copy){copy.classList.add('is-copied');const label=copy.querySelector('span');if(label){copy.dataset.zcOriginalLabel ||= label.textContent;label.textContent='کپی شد';clearTimeout(copy._zcCopyTimer);copy._zcCopyTimer=setTimeout(()=>{label.textContent=copy.dataset.zcOriginalLabel;copy.classList.remove('is-copied')},2500)}}
            status.textContent = share?.dataset.zcNetwork ? `لینک کپی شد؛ در ${share.dataset.zcNetwork} جای‌گذاری کنید.` : 'لینک کپی شد.';
        } catch (_) { status.textContent = 'کپی خودکار ممکن نشد؛ نشانی کوتاه را انتخاب و کپی کنید.'; }
    });
})();
/* Keep WP's native reply/cancel movement. Remove the whole rating control while answering,
 * including Woo's generated stars, so Woo's root-review validation cannot block a reply. */
(()=>{'use strict';const form=document.getElementById('commentform');if(!form)return;const parent=form.querySelector('#comment_parent');if(!parent)return;const controls=[...form.querySelectorAll('.comment-form-rating,.zc-rating-input')].map(node=>({node,anchor:document.createComment('zc-rating-home')}));controls.forEach(({node,anchor})=>node.before(anchor));const button=form.querySelector('[type=submit]');const original=button?.value;const sync=()=>{const replying=Number(parent.value)>0;controls.forEach(({node,anchor})=>{if(replying)node.remove();else if(!node.isConnected)anchor.after(node)});if(button){button.value=replying?'ارسال پاسخ':original;if(button.dataset.zcReplyOnly)button.disabled=!replying;}form.classList.toggle('zc-is-replying',replying)};document.addEventListener('click',e=>{if(e.target.closest('.comment-reply-link,#cancel-comment-reply-link'))queueMicrotask(sync)});sync()})();
(()=>{'use strict';document.addEventListener('change',e=>{if(e.target.name!=='zc_article_rating')return;const root=e.target.closest('.zc-rating-input');if(root)root.querySelector('output').textContent=new Intl.NumberFormat('fa-IR').format(e.target.value)+' از ۵'});document.addEventListener('click',e=>{const clear=e.target.closest('.zc-rating-clear');if(!clear)return;const root=clear.closest('.zc-rating-input');root.querySelectorAll('input[type=radio]').forEach(i=>i.checked=false);root.querySelector('output').textContent='بدون امتیاز';root.querySelector('input[type=radio]').focus()})})();
