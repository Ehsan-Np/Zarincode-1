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
        let selectedPlan = "__all__";
        let planSignature = "";
        function apply() {
            queued = false;
            if (!alive) return;
            bindPreview();
            if (type === 'zarincode_pricing') {
                const settings = model.get('settings');
                const groups = ['pricing_grid_items_1','pricing_grid_items_2','pricing_grid_items_3','pricing_grid_items_4'];
                const array = value => value?.toJSON ? value.toJSON() : (Array.isArray(value) ? value : []);
                const plans = groups.flatMap(group => array(settings.get('n_pricing_' + group)).map((row,index) => ({group,index,id:row._id,key:row.plan_key||row._id,title:row.heading_text_1||'پلن بدون عنوان'})));
                let bar = root.querySelector('.zc-pricing-manager');
                if (!bar) {
                    bar = document.createElement('div'); bar.className='zc-pricing-manager';
                    const label=document.createElement('label');label.textContent='مدیریت متمرکز پلن‌ها';
                    const select=document.createElement('select');select.setAttribute('aria-label','انتخاب پلن برای ویرایش');label.append(select);bar.append(label);
                    const help=document.createElement('p');help.textContent='با انتخاب پلن، فقط کارت و امکانات همان پلن در پنل دیده می‌شود؛ داده‌ای حذف نمی‌شود. برای چیدمان جدید از «کارت تعرفه مستقل» استفاده کنید.';bar.append(help);
                    (root.querySelector('#elementor-controls')||root).prepend(bar);
                }
                const signature=JSON.stringify(plans.map(p=>[p.group,p.id,p.title]));
                const select=bar.querySelector('select');
                if(signature!==planSignature){
                    planSignature=signature; select.replaceChildren(new Option('همه پلن‌ها', '__all__'));
                    plans.forEach(p=>select.add(new Option(p.title,p.group+'|'+p.id)));
                    if(!plans.some(p=>p.group+'|'+p.id===selectedPlan))selectedPlan='__all__';select.value=selectedPlan;
                }
                const plan=plans.find(p=>p.group+'|'+p.id===selectedPlan);
                const filter=(el,hidden)=>{if(el)el.classList.toggle('zc-editor-plan-filtered',!!hidden);};
                groups.forEach(group=>{
                    filter(root.querySelector('.elementor-control-n_pricing_section_'+group),plan&&plan.group!==group);
                    const control=root.querySelector('.elementor-control-n_pricing_'+group);filter(control,plan&&plan.group!==group);
                    control?.querySelectorAll('.elementor-repeater-fields').forEach((row,index)=>filter(row,plan&&(plan.group!==group||plan.index!==index)));
                });
                const features=array(settings.get('n_pricing_features'));
                root.querySelectorAll('.elementor-control-n_pricing_features .elementor-repeater-fields').forEach((row,index)=>{
                    const value=features[index]||{};const key=value.plan_key==='__custom__'?value.custom_plan_key:value.plan_key;
                    filter(row,plan&&key!==plan.key&&String(value.text||'').trim()!=='');
                });
            }

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
        $(root).on('change.zcNativePanel','.zc-pricing-manager select',function(){selectedPlan=this.value;schedule();});
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
