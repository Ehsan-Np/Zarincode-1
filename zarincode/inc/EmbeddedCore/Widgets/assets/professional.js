(function () {
    'use strict';
    function init(root) {
        root.querySelectorAll('[data-zc-compare]').forEach(function (range) {
            if (range.dataset.zcReady) return;
            range.dataset.zcReady = '1';
            const surface = range.closest('.zc-pro-compare');
            let pointer = null;
            function update() {
                const value = Math.max(0, Math.min(100, Number(range.value) || 0));
                surface.style.setProperty('--zc-split', value + '%');
                range.setAttribute('aria-valuetext', value + '%');
            }
            function move(event) {
                const rect = surface.getBoundingClientRect();
                if (!rect.width) return;
                range.value = String(Math.round(Math.max(0, Math.min(100, (event.clientX - rect.left) / rect.width * 100))));
                range.dispatchEvent(new Event('input', { bubbles: true }));
            }
            range.addEventListener('input', update);
            range.addEventListener('change', update);
            surface.addEventListener('pointerdown', function (event) {
                if (event.button !== 0 || event.target.closest('input,.elementor-inline-editing,[contenteditable="true"]')) return;
                pointer = event.pointerId;
                surface.setPointerCapture(pointer);
                move(event);
            });
            surface.addEventListener('pointermove', function (event) {
                if (event.pointerId === pointer) move(event);
            });
            function stop(event) {
                if (event.pointerId !== pointer) return;
                if (surface.hasPointerCapture(pointer)) surface.releasePointerCapture(pointer);
                pointer = null;
            }
            surface.addEventListener('pointerup', stop);
            surface.addEventListener('pointercancel', stop);
            surface.addEventListener('dragstart', function (event) { event.preventDefault(); });
            update();
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { init(document); });
    else init(document);
    function hook() {
        if (window.elementorFrontend) window.elementorFrontend.hooks.addAction('frontend/element_ready/global', function (scope) { init(scope[0]); });
    }
    if (window.jQuery) window.jQuery(window).on('elementor/frontend/init', hook);
    else window.addEventListener('elementor/frontend/init', hook);
})();
