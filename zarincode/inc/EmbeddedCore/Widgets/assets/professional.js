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
            }
            onDestroy() {
                (this.listeners || []).forEach(([target, name, fn]) => target.removeEventListener(name, fn));
                this.listeners = [];
                super.onDestroy();
            }
        }
        ['service_spotlight', 'project_roadmap', 'seo_audit', 'before_after', 'team_experts', 'service_matrix'].forEach(name => {
            elementorFrontend.hooks.addAction('frontend/element_ready/zarincode_' + name + '.default', $element => elementorFrontend.elementsHandler.addHandler(ZarincodeProfessional, { $element }));
        });
    });
})(jQuery);
