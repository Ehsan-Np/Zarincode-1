/* Delegated dialogs: one binding, focus containment, Escape, inert background and focus return. */
(() => {
    'use strict';
    if (document.documentElement.dataset.zcDialogsBound) return;
    document.documentElement.dataset.zcDialogsBound = 'true';
    let active = null, opener = null, background = [], overflow = '';
    const focusable = () => active ? [...active.querySelectorAll('a[href],button,input,select,textarea,[tabindex]')]
        .filter(node => !node.disabled && node.tabIndex >= 0 && node.getClientRects().length && !node.closest('[inert]')) : [];
    function close() {
        if (!active) return;
        active.classList.remove('is-open');
        active.setAttribute('aria-hidden', 'true');
        background.forEach(([node, wasInert]) => { node.inert = wasInert; });
        background = [];
        document.body.style.overflow = overflow;
        active = null;
        if (opener && opener.isConnected) opener.focus();
        opener = null;
    }
    function open(dialog, trigger) {
        if (!dialog || !dialog.matches('.zc-modal-backdrop')) return;
        if (active === dialog) return;
        close();
        opener = trigger || document.activeElement;
        active = dialog;
        overflow = document.body.style.overflow;
        let branch = active;
        while (branch && branch.parentElement) {
            [...branch.parentElement.children].forEach(node => {
                if (node !== branch && !node.matches('script,style,link')) {
                    background.push([node, node.inert]); node.inert = true;
                }
            });
            if (branch.parentElement === document.body) break;
            branch = branch.parentElement;
        }
        document.body.style.overflow = 'hidden';
        active.classList.add('is-open'); active.setAttribute('aria-hidden', 'false');
        active.tabIndex = -1;
        (focusable()[0] || active).focus();
    }
    document.addEventListener('click', event => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) return;
        const trigger = target.closest('[data-zc-modal-open]');
        if (trigger) { event.preventDefault(); open(document.getElementById(trigger.dataset.zcModalOpen), trigger); return; }
        if (active && (target === active || target.closest('[data-zc-modal-close]'))) close();
    });
    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault(); open(document.getElementById('zc-search-modal'), document.activeElement); return;
        }
        if (!active) return;
        if (!active.isConnected) { close(); return; }
        if (event.key === 'Escape') { event.preventDefault(); close(); return; }
        if (event.key !== 'Tab') return;
        const nodes = focusable();
        if (!nodes.length) { event.preventDefault(); active.focus(); return; }
        const first = nodes[0], last = nodes[nodes.length - 1];
        if (event.shiftKey && (document.activeElement === first || document.activeElement === active)) {
            event.preventDefault(); last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault(); first.focus();
        }
    });
})();
