/** WordPress's close icon hides locally; persist this user's choice server-side. */
(() => {
    'use strict';
    document.addEventListener('click', async event => {
        if (!event.target.closest('#zc-demo-install-notice .notice-dismiss')) return;
        const notice = document.getElementById('zc-demo-install-notice');
        if (!notice || !window.ZarincodeDemoNotice) return;
        const data = new URLSearchParams({action: 'zc_dismiss_demo_notice', nonce: ZarincodeDemoNotice.nonce});
        try {
            const response = await fetch(ZarincodeDemoNotice.url, {method: 'POST', credentials: 'same-origin', body: data});
            if (!response.ok || !(await response.json()).success) throw new Error('Dismissal not saved');
        } catch (_) {
            // Restore a fallback notice: a visual dismissal must not imply persistence.
            const fallback = notice.cloneNode(true);
            fallback.style.display = ''; fallback.style.opacity = '';
            notice.remove(); document.querySelector('#wpbody-content')?.prepend(fallback);
            const message = document.createElement('p');
            message.textContent = ZarincodeDemoNotice.retry;
            fallback.append(message);
        }
    });
})();
