(function ($) {
    'use strict';
    let cleanup = () => {};
    let frameDocument = null;
    let pendingKey = '';
    let currentApply = () => {};
    function previewClick(event) {
        const target = event.target.closest('[data-elementor-setting-key]');
        if (!target) return;
        pendingKey = target.getAttribute('data-elementor-setting-key') || '';
        currentApply();
    }
    function bindPreview() {
        const frame = document.getElementById('elementor-preview-iframe');
        const doc = frame && frame.contentDocument;
        if (!doc || doc === frameDocument) return;
        if (frameDocument) frameDocument.removeEventListener('pointerdown', previewClick, true);
        frameDocument = doc;
        doc.addEventListener('pointerdown', previewClick, true);
    }
    function mount(panel, model) {
        cleanup();
        const type = model.get('widgetType') || '';
        if (!type.startsWith('zarincode_')) return;
        const root = panel.$el[0];
        let queued = false;
        let alive = true;
        function apply() {
            queued = false;
            if (!alive) return;
            bindPreview();
            if (pendingKey && !pendingKey.includes('.')) {
                const field = root.querySelector('.elementor-control-' + CSS.escape(pendingKey));
                if (field && field.classList.contains('zc-editor-field')) {
                    const group = Array.from(field.classList).find(c => c.startsWith('zc-editor-group-'));
                    const modelClass = Array.from(field.classList).find(c => c.startsWith('zc-editor-model-'));
                    const picker = modelClass && root.querySelector('.zc-editor-part-picker.' + modelClass + ' select');
                    const search = modelClass && root.querySelector('.zc-editor-field-search.' + modelClass + ' input');
                    if (search && search.value) { $(search).val('').trigger('input').trigger('change'); }
                    if (picker && group && picker.value !== group.slice(16)) $(picker).val(group.slice(16)).trigger('change');
                    const tab = Array.from(field.classList).find(c => c.startsWith('zc-editor-tab-'));
                    if (tab && modelClass) {
                        const name = modelClass.slice(16);
                        root.querySelector('.elementor-control-n_' + name + '_tab_' + tab.slice(14))?.click();
                    }
                    pendingKey = '';
                }
            }
            root.querySelectorAll('.zc-editor-part-picker').forEach(pickerRow => {
                const modelClass = Array.from(pickerRow.classList).find(c => c.startsWith('zc-editor-model-'));
                if (!modelClass) return;
                const picker = pickerRow.querySelector('select');
                const search = root.querySelector('.zc-editor-field-search.' + modelClass + ' input');
                const query = (search?.value || '').trim().toLocaleLowerCase();
                const selected = picker?.value || '__all__';
                root.querySelectorAll('.zc-editor-field.' + modelClass).forEach(field => {
                    const matches = query ? field.textContent.toLocaleLowerCase().includes(query) : selected === '__all__' || field.classList.contains('zc-editor-group-' + selected);
                    const hidden = !matches;
                    if (field.classList.contains('zc-editor-filtered') !== hidden) field.classList.toggle('zc-editor-filtered', hidden);
                });
            });
        }
        function schedule() { if (!queued && alive) { queued = true; requestAnimationFrame(apply); } }
        const observer = new MutationObserver(schedule);
        observer.observe(root, { childList: true, subtree: true });
        $(root).on('change.zcNativePanel input.zcNativePanel', '.zc-editor-part-picker select,.zc-editor-field-search input', schedule);
        $(root).on('click.zcNativePanel', '.elementor-control-type-tab,.elementor-control-type-section', schedule);
        currentApply = schedule;
        cleanup = () => { alive = false; observer.disconnect(); $(root).off('.zcNativePanel'); currentApply = () => {}; };
        schedule();
    }
    $(window).on('elementor:init', function () {
        elementor.hooks.addAction('panel/open_editor/widget', mount);
        elementor.on('preview:loaded', bindPreview);
    });
    window.addEventListener('pagehide', () => { cleanup(); if (frameDocument) frameDocument.removeEventListener('pointerdown', previewClick, true); });
})(jQuery);
