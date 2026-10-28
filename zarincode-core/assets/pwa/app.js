/* Progressive enhancement: no automatic install prompts or checkout reloads. */
(() => {
    'use strict';
    if (!('serviceWorker' in navigator) || !window.isSecureContext || !window.ZarincodePWA) return;
    const config = window.ZarincodePWA;
    let installEvent;
    let registration;
    let bar;
    const standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone;
    const ios = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    let dismissed = false;
    try { dismissed = sessionStorage.getItem('zc-pwa-dismissed') === '1'; } catch (_) {}
    function hide() { bar?.remove(); bar = null; }
    function offer(text, action) {
        if (dismissed) return;
        hide();
        bar = document.createElement('aside');
        bar.className = 'zc-pwa-offer';
        bar.setAttribute('aria-label', config.installLabel);
        const button = document.createElement('button');
        button.type = 'button'; button.textContent = text;
        button.addEventListener('click', action);
        const close = document.createElement('button');
        close.type = 'button'; close.textContent = '×'; close.setAttribute('aria-label', 'بستن');
        close.addEventListener('click', () => {
            dismissed = true; hide();
            try { sessionStorage.setItem('zc-pwa-dismissed', '1'); } catch (_) {}
        });
        bar.append(button, close); document.body.append(bar);
    }
    function installOffer() {
        if (standalone || !installEvent) return;
        offer(config.installLabel, async () => {
            const prompt = installEvent; installEvent = null; hide();
            try { await prompt.prompt(); await prompt.userChoice; } catch (_) {}
        });
    }
    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault(); installEvent = event;
        if (registration) installOffer();
    });
    window.addEventListener('appinstalled', () => { installEvent = null; hide(); });
    // Expose a hook for a site's own install menu item.
    document.addEventListener('click', event => {
        if (!event.target.closest('[data-zc-pwa-install]')) return;
        if (ios && !standalone) showIOS(); else installOffer();
    });
    function showIOS() {
        offer(config.iosHelp, () => hide());
    }
    const start = async () => {
        try {
            const scopeURL = new URL(config.scope, location.origin).href;
            const existing = await navigator.serviceWorker.getRegistration(scopeURL);
            // Do not overwrite another PWA plugin's service worker.
            if (existing) {
                const worker = existing.active || existing.waiting || existing.installing;
                if (worker && new URL(worker.scriptURL).searchParams.get('zc_pwa') !== 'worker') return;
            }
            registration = await navigator.serviceWorker.register(config.worker, {scope: config.scope, updateViaCache: 'none'});
            const update = () => {
                if (!registration.waiting || !navigator.serviceWorker.controller) return;
                // User explicitly opts in. Never refresh a form automatically.
                offer(config.updateLabel, () => {
                    registration.waiting?.postMessage({type: 'ZC_ACTIVATE_UPDATE'});
                    hide();
                });
            };
            update();
            registration.addEventListener('updatefound', () => {
                registration.installing?.addEventListener('statechange', update);
            });
            if (registration.waiting && navigator.serviceWorker.controller) update();
            else if (ios && !standalone) offer(config.installLabel, showIOS);
            else installOffer();
        } catch (_) { /* Normal site operation must never depend on PWA support. */ }
    };
    if (document.readyState === 'complete') start();
    else window.addEventListener('load', start, {once: true});
})();
