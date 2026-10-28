/** Shared controller for native template and Elementor header triggers. */
(() => {
    'use strict';
    let active = null;
    let opener = null;
    let oldOverflow = '';
    let isolated = [];
    const triggers = () => document.querySelectorAll('[data-zc-drawer-open]');
    const focusable = root => [...root.querySelectorAll('a[href],button,input,select,textarea,[tabindex]')]
        .filter(el => !el.disabled && el.tabIndex >= 0 && !el.closest('[inert]') && el.getClientRects().length && getComputedStyle(el).visibility !== 'hidden');
    function sync(open) {
        triggers().forEach(button => {
            const id = button.getAttribute('data-zc-drawer-open');
            button.setAttribute('aria-controls', id);
            button.setAttribute('aria-expanded', String(open && active?.id === id));
        });
    }
    function close(restore = true) {
        if (!active) return;
        active.classList.remove('is-open'); active.setAttribute('aria-hidden', 'true');
        active.inert = true;
        document.body.style.overflow = oldOverflow;
        isolated.forEach(([element, wasInert]) => { element.inert = wasInert; }); isolated = [];
        sync(false); active = null;
        if (restore && opener?.isConnected) opener.focus({preventScroll: true});
        opener = null;
    }
    function open(button) {
        const drawer = document.getElementById(button.getAttribute('data-zc-drawer-open'));
        if (!drawer) return;
        if (active) close(false);
        active = drawer; opener = button; oldOverflow = document.body.style.overflow;
        const panel = drawer.querySelector('.zc-mobile-drawer') || drawer;
        panel.setAttribute('role', 'dialog'); panel.setAttribute('aria-modal', 'true');
        panel.setAttribute('tabindex', '-1');
        drawer.inert = false; drawer.setAttribute('aria-hidden', 'false'); drawer.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        // Isolate siblings at every level; supports a drawer nested in a builder.
        for (let node = drawer; node.parentElement && node !== document.body; node = node.parentElement) {
            [...node.parentElement.children].forEach(sibling => {
                if (sibling !== node && !['SCRIPT', 'STYLE', 'LINK'].includes(sibling.tagName)) {
                    isolated.push([sibling, sibling.inert]); sibling.inert = true;
                }
            });
        }
        sync(true);
        // Visibility transitions may still be hidden on the first animation frame.
        const focusPanel = () => {
            if (active !== drawer) return;
            if (getComputedStyle(panel).visibility !== 'visible') { requestAnimationFrame(focusPanel); return; }
            (focusable(panel)[0] || panel).focus({preventScroll: true});
        };
        requestAnimationFrame(focusPanel);
    }
    function init() {
        sync(false);
        document.querySelectorAll('.zc-mobile-drawer-backdrop').forEach(drawer => { drawer.inert = true; });
        document.querySelectorAll('[data-zc-mobile-sub-toggle]').forEach((button, i) => {
            const list = button.closest('.zc-mobile-has-sub')?.querySelector('.zc-mobile-sub-list');
            if (!list) return;
            if (!list.id) list.id = 'zc-mobile-sub-' + i;
            button.setAttribute('aria-controls', list.id); button.setAttribute('aria-expanded', 'false'); list.hidden = true;
        });
    }
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-zc-drawer-open]');
        if (button) { event.preventDefault(); open(button); return; }
        const sub = event.target.closest('[data-zc-mobile-sub-toggle]');
        if (sub) {
            event.preventDefault();
            const item = sub.closest('.zc-mobile-has-sub');
            const list = item?.querySelector('.zc-mobile-sub-list');
            if (!list) return;
            const expanded = sub.getAttribute('aria-expanded') !== 'true';
            item.classList.toggle('is-open', expanded); sub.setAttribute('aria-expanded', String(expanded)); list.hidden = !expanded;
            return;
        }
        if (!active) return;
        if (event.target === active || event.target.closest('[data-zc-drawer-close]') || event.target.closest('.zc-mobile-nav-list a[href]')) close();
    });
    document.addEventListener('keydown', event => {
        if (!active) return;
        if (event.key === 'Escape') { event.preventDefault(); close(); return; }
        if (event.key !== 'Tab') return;
        const items = focusable(active);
        if (!items.length) { event.preventDefault(); return; }
        const first = items[0], last = items[items.length - 1];
        if (event.shiftKey && (document.activeElement === first || !items.includes(document.activeElement))) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && (document.activeElement === last || !items.includes(document.activeElement))) { event.preventDefault(); first.focus(); }
    });
    document.addEventListener('focusin', event => {
        if (active && !active.contains(event.target)) (focusable(active)[0] || active).focus();
    });
    window.addEventListener('pageshow', () => close(false));
    // Close when a desktop header replaces the menu trigger (also container widths).
    window.addEventListener('resize', () => { if (active && opener && !opener.getClientRects().length) close(false); }, {passive: true});
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once: true}); else init();
})();
