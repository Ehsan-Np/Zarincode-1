(function ($) {
    'use strict';
    $(window).on('elementor/frontend/init', function () {
        if (!window.elementorModules || !window.elementorFrontend) return;
        class ZarincodeProfessional extends elementorModules.frontend.handlers.Base {
            onInit() {
                super.onInit();
                this.listeners = [];
                this.$element[0].querySelectorAll('[data-zc-compare]').forEach(range => {
                    const surface = range.closest('.zc-pro-compare');
                    let pointer = null;
                    const listen = (target, name, fn) => { target.addEventListener(name, fn); this.listeners.push([target, name, fn]); };
                    const update = () => {
                        const value = Math.max(0, Math.min(100, Number(range.value) || 0));
                        surface.style.setProperty('--zc-split', value + '%');
                        range.setAttribute('aria-valuetext', value + '%');
                    };
                    const move = event => {
                        const rect = surface.getBoundingClientRect();
                        if (!rect.width) return;
                        range.value = String(Math.round(Math.max(0, Math.min(100, (event.clientX - rect.left) / rect.width * 100))));
                        range.dispatchEvent(new Event('input', { bubbles: true }));
                    };
                    const stop = event => {
                        if (event.pointerId !== pointer) return;
                        if (surface.hasPointerCapture(pointer)) surface.releasePointerCapture(pointer);
                        pointer = null;
                    };
                    listen(range, 'input', update);
                    listen(range, 'change', update);
                    listen(surface, 'pointerdown', event => {
                        if (event.button !== 0 || event.target.closest('input,.elementor-inline-editing,[contenteditable="true"]')) return;
                        pointer = event.pointerId; surface.setPointerCapture(pointer); move(event);
                    });
                    listen(surface, 'pointermove', event => { if (event.pointerId === pointer) move(event); });
                    listen(surface, 'pointerup', stop);
                    listen(surface, 'pointercancel', stop);
                    listen(surface, 'dragstart', event => event.preventDefault());
                    update();
                });
                this.$element[0].querySelectorAll('[data-zc-slider]').forEach(root => {
                    const track = root.querySelector('.zc-slider-track');
                    if (!track) return;
                    const slides = Array.from(track.querySelectorAll('.zc-slider-slide'));
                    const nav = root.querySelector('.zc-slider-nav');
                    if (slides.length < 2) { if (nav) nav.hidden = true; return; }
                    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
                    const prev = root.querySelector('[data-zc-slider-prev]');
                    const next = root.querySelector('[data-zc-slider-next]');
                    const dots = Array.from(root.querySelectorAll('[data-zc-slider-dot]'));
                    const listen = (target, name, fn, opts) => { target.addEventListener(name, fn, opts); this.listeners.push([target, name, fn]); };
                    const rtl = () => getComputedStyle(track).direction === 'rtl';
                    root.setAttribute('data-zc-dir', rtl() ? 'rtl' : 'ltr');
                    let current = 0;
                    let ticking = false;
                    const mark = index => {
                        current = index;
                        dots.forEach((dot, i) => { if (i === current) dot.setAttribute('aria-current', 'true'); else dot.removeAttribute('aria-current'); });
                    };
                    const goTo = index => {
                        const target = Math.max(0, Math.min(slides.length - 1, index));
                        slides[target].scrollIntoView({ behavior: reduce.matches ? 'auto' : 'smooth', inline: 'start', block: 'nearest' });
                        mark(target);
                    };
                    const nearest = () => {
                        const box = track.getBoundingClientRect();
                        let best = 0;
                        let distance = Infinity;
                        slides.forEach((slide, i) => {
                            const rect = slide.getBoundingClientRect();
                            const delta = Math.abs((rtl() ? rect.right : rect.left) - (rtl() ? box.right : box.left));
                            if (delta < distance) { distance = delta; best = i; }
                        });
                        return best;
                    };
                    if (prev) listen(prev, 'click', () => goTo(current - 1));
                    if (next) listen(next, 'click', () => goTo(current + 1));
                    dots.forEach((dot, i) => listen(dot, 'click', () => goTo(i)));
                    listen(track, 'scroll', () => {
                        if (ticking) return;
                        ticking = true;
                        window.requestAnimationFrame(() => {
                            ticking = false;
                            const index = nearest();
                            if (index !== current) mark(index);
                        });
                    }, { passive: true });
                    listen(root, 'keydown', event => {
                        if (event.key === 'ArrowLeft') { event.preventDefault(); goTo(rtl() ? current + 1 : current - 1); }
                        else if (event.key === 'ArrowRight') { event.preventDefault(); goTo(rtl() ? current - 1 : current + 1); }
                        else if (event.key === 'Home') { event.preventDefault(); goTo(0); }
                        else if (event.key === 'End') { event.preventDefault(); goTo(slides.length - 1); }
                    });
                });
            }
            onDestroy() {
                (this.listeners || []).forEach(([target, name, fn]) => target.removeEventListener(name, fn));
                this.listeners = [];
                super.onDestroy();
            }
        }
        ['service_spotlight', 'project_roadmap', 'seo_audit', 'before_after', 'team_experts', 'service_matrix', 'products_slider'].forEach(name => {
            elementorFrontend.hooks.addAction('frontend/element_ready/zarincode_' + name + '.default', $element => elementorFrontend.elementsHandler.addHandler(ZarincodeProfessional, { $element }));
        });
    });
})(jQuery);
