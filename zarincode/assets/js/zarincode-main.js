/**
 * Zarincode Main Frontend Interactive Engine + Crextio Color/Mode Switcher
 * Developer: Ehsan NaderiPanah | https://zarincode.com | 09024561001
 */
(function () {
    'use strict';

    const COLOR_KEY = 'zarincode-crextio-preset';
    const MODE_KEY = 'zarincode-crextio-mode';

    const PRESETS = [
        { id: 'yellow',   label: 'زرد سانشاین (Crextio)', accent: '#FDD05B', accentText: '#2A2C31', bgFrom: '#F8F6F0', bgTo: '#FFF5D1', darkFrom: '#15171C', darkTo: '#1F1B14' },
        { id: 'mint',     label: 'نعنایی مدرن',          accent: '#7DD3A8', accentText: '#0F3D26', bgFrom: '#F0F9F4', bgTo: '#D4F5E5', darkFrom: '#13201A', darkTo: '#0F2C1F' },
        { id: 'coral',    label: 'مرجانی',               accent: '#FF9485', accentText: '#3D1410', bgFrom: '#FFF4F1', bgTo: '#FFE0D6', darkFrom: '#1F1715', darkTo: '#2C1814' },
        { id: 'lavender', label: 'یاسی استارتاپی',       accent: '#B8A5F0', accentText: '#22153D', bgFrom: '#F5F1FB', bgTo: '#E5DCFA', darkFrom: '#181428', darkTo: '#1F1830' },
        { id: 'sky',      label: 'آبی آسمانی',           accent: '#7FC0FF', accentText: '#0F2740', bgFrom: '#F0F7FE', bgTo: '#D6ECFF', darkFrom: '#121A24', darkTo: '#0F2030' },
        { id: 'rose',     label: 'رز خلاقانه',           accent: '#FF9DC2', accentText: '#3D0F25', bgFrom: '#FDF2F7', bgTo: '#FFE0EE', darkFrom: '#21161B', darkTo: '#2C1822' },
        { id: 'lime',     label: 'لیمویی نئون',          accent: '#D3E96B', accentText: '#2A3D0A', bgFrom: '#F8FBEC', bgTo: '#EBF5C8', darkFrom: '#1A1D14', darkTo: '#212818' },
        { id: 'graphite', label: 'گرافیتی رسمی',         accent: '#B8BCC4', accentText: '#1E1E20', bgFrom: '#F2F2F4', bgTo: '#DCDEE3', darkFrom: '#16171A', darkTo: '#1E1F23' }
    ];

    const sitePalette = window.ZarincodeData?.sitePalette;
    if (sitePalette) PRESETS.unshift(sitePalette);

    function getMode() {
        const fallback = window.ZarincodeData?.defaultMode === 'dark' || document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        try { const saved = localStorage.getItem(MODE_KEY); return ['dark', 'light'].includes(saved) ? saved : fallback; } catch (e) { return fallback; }
    }

    function getPreset() {
        try {
            const id = window.ZarincodeData?.colorSwitcher === false ? null : localStorage.getItem(COLOR_KEY);
            return PRESETS.find(p => p.id === id) || PRESETS[0];
        } catch (e) {
            return PRESETS[0];
        }
    }

    function applyTheme(preset, mode) {
        const root = document.documentElement;
        root.classList.toggle('dark', mode === 'dark');
        root.style.setProperty('--color-yellow', preset.accent);
        root.style.setProperty('--color-accent', preset.accent);
        root.style.setProperty('--color-accent-contrast', preset.accentText);
        const from = mode === 'dark' ? preset.darkFrom : preset.bgFrom;
        const to = mode === 'dark' ? preset.darkTo : preset.bgTo;
        root.style.setProperty('--zc-bg-from', from);
        root.style.setProperty('--zc-bg-to', to);
        if (document.body) {
            document.body.style.background = 'linear-gradient(135deg, ' + from + ' 0%, ' + to + ' 100%)';
            document.body.style.backgroundAttachment = 'fixed';
        }
    }

    let currentMode = getMode();
    let currentPreset = getPreset();
    applyTheme(currentPreset, currentMode);

    document.addEventListener('DOMContentLoaded', function () {
        applyTheme(currentPreset, currentMode);

        // Header Dark/Light mode toggle button
        legacyNodes('[data-zc-toggle-dark]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                currentMode = currentMode === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem(MODE_KEY, currentMode); } catch (e) {}
                applyTheme(currentPreset, currentMode);
                updateSwitcherUI();
            });
        });

        // Filter Tabs (Products, Portfolio, Pricing)
        legacyNodes('[data-zc-tabs]').forEach(function (tabContainer) {
            const targetSelector = tabContainer.getAttribute('data-zc-tabs');
            const buttons = tabContainer.querySelectorAll('[data-filter]');
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const filter = btn.getAttribute('data-filter');
                    buttons.forEach(b => b.classList.remove('is-active'));
                    btn.classList.add('is-active');

                    const scope = targetSelector ? document.querySelector(targetSelector) : tabContainer.parentElement;
                    if (!scope) return;

                    scope.querySelectorAll('[data-category]').forEach(function (item) {
                        const cats = (item.getAttribute('data-category') || '').split(/\s+/);
                        if (filter === 'all' || cats.includes(filter)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        });

        // Pricing Group Tabs (Switch entire pricing panels)
        legacyNodes('[data-zc-pricing-tabs]').forEach(function (nav) {
            const buttons = nav.querySelectorAll('[data-pricing-target]');
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const target = btn.getAttribute('data-pricing-target');
                    buttons.forEach(b => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    const wrapper = nav.closest('.zc-pricing-section') || document;
                    wrapper.querySelectorAll('[data-pricing-panel]').forEach(function (panel) {
                        panel.style.display = panel.getAttribute('data-pricing-panel') === target ? 'grid' : 'none';
                    });
                });
            });
        });

        // FAQ Accordions (.zc-faq-btn & .zc-faq-trigger)
        legacyNodes('.zc-faq-btn, .zc-faq-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const item = btn.closest('.zc-faq-item');
                if (!item) return;
                const list = item.closest('.zc-faq-list');
                const wasOpen = item.classList.contains('is-open');
                if (list) {
                    list.querySelectorAll('.zc-faq-item.is-open').forEach(function (other) {
                        if (other !== item) {
                            other.classList.remove('is-open');
                        }
                    });
                }
                if (wasOpen) {
                    item.classList.remove('is-open');
                } else {
                    item.classList.add('is-open');
                }
            });
        });

        // Dialog interactions are managed by the delegated, focus-safe dialogs module.

        // Mobile navigation is owned by mobile-navigation.js (including native headers).

        // Consultation Form Submission Toast
        legacyNodes('.zc-Ajax-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const msgBox = form.querySelector('.zc-form-feedback');
                if (msgBox) {
                    msgBox.style.display = 'block';
                    msgBox.innerHTML = '<i class="fa-solid fa-circle-check"></i> درخواست شما با موفقیت ثبت شد! احسان نادری پناه و تیم زرین کد (09024561001) در سریع‌ترین زمان با شما تماس می‌گیرند.';
                }
                form.reset();
            });
        });

        // Crextio Hero Live Timer Simulation
        const timerEl = document.getElementById('zc-live-timer');
        const editorMode = document.body.classList.contains('elementor-editor-active') || (window.elementorFrontend && window.elementorFrontend.isEditMode());
        const initial = timerEl && timerEl.textContent.trim().match(/^(\d{1,3}):(\d{2}):(\d{2})$/);
        if (timerEl && !editorMode && initial) {
            let seconds = Number(initial[1]) * 3600 + Number(initial[2]) * 60 + Number(initial[3]);
            setInterval(function () {
                seconds++;
                const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
                const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
                const s = String(seconds % 60).padStart(2, '0');
                timerEl.textContent = h + ':' + m + ':' + s;
            }, 1000);
        }

        // Build Floating Crextio Switcher
        if (window.ZarincodeData?.colorSwitcher !== false) buildCrextioSwitcher();
    });

    function legacyNodes(selector) { return Array.from(document.querySelectorAll(selector)).filter(el => !el.closest('.zc-native-layout')); }

    function updateSwitcherUI() {
        const panel = document.getElementById('zc-ccs-panel');
        if (!panel) return;
        panel.querySelectorAll('[data-mode]').forEach(btn => {
            btn.classList.toggle('is-active', btn.getAttribute('data-mode') === currentMode);
        });
        panel.querySelectorAll('[data-preset]').forEach(sw => {
            sw.classList.toggle('is-active', sw.getAttribute('data-preset') === currentPreset.id);
        });
        const label = panel.querySelector('#zc-ccs-current');
        if (label) label.textContent = currentPreset.label;
    }

    function buildCrextioSwitcher() {
        if (document.getElementById('zarincode-crextio-switcher')) return;
        const wrap = document.createElement('div');
        wrap.id = 'zarincode-crextio-switcher';
        wrap.style.cssText = 'position:fixed;left:1.25rem;bottom:1.25rem;z-index:950;font-family:var(--zc-font-family);';

        const swatchesHtml = PRESETS.map(p =>
            `<button type="button" data-preset="${p.id}" title="${p.label}" style="background:${p.accent};width:100%;aspect-ratio:1/1;border-radius:12px;border:2px solid transparent;cursor:pointer;position:relative;" class="zc-swatch ${p.id === currentPreset.id ? 'is-active' : ''}"></button>`
        ).join('');

        wrap.innerHTML = `
            <style>
                #zarincode-crextio-switcher .zc-swatch.is-active { border-color: #2A2C31 !important; transform: scale(1.07); }
                #zarincode-crextio-switcher .zc-mode-btn.is-active { background: var(--zc-dark); color: var(--color-accent); }
            </style>
            <button id="zc-ccs-toggle" type="button" title="تنظیمات رنگ و تم Crextio" style="width:52px;height:52px;border-radius:9999px;border:none;cursor:pointer;background:var(--color-accent);color:var(--color-accent-contrast);box-shadow:0 8px 24px rgba(0,0,0,0.22),0 0 0 4px rgba(255,255,255,0.75);display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
                <i class="fa-solid fa-palette"></i>
            </button>
            <div id="zc-ccs-panel" hidden style="position:absolute;bottom:64px;left:0;width:280px;padding:18px;background:var(--zc-surface-solid);border-radius:24px;border:1px solid var(--zc-surface-border);box-shadow:0 20px 50px rgba(0,0,0,0.25);color:var(--zc-text-main);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;font-weight:800;font-size:0.88rem;">
                    <span>تنظیمات استایل Crextio</span>
                    <button type="button" id="zc-ccs-close" style="width:24px;height:24px;border-radius:50%;border:none;cursor:pointer;background:rgba(0,0,0,0.08);color:inherit;">×</button>
                </div>
                <div style="display:flex;gap:6px;padding:4px;background:rgba(0,0,0,0.06);border-radius:999px;margin-bottom:14px;">
                    <button type="button" data-mode="light" class="zc-mode-btn ${currentMode === 'light' ? 'is-active' : ''}" style="flex:1;padding:7px;border:none;border-radius:999px;cursor:pointer;font-family:inherit;font-size:0.78rem;font-weight:700;">☀️ روز (روشن)</button>
                    <button type="button" data-mode="dark" class="zc-mode-btn ${currentMode === 'dark' ? 'is-active' : ''}" style="flex:1;padding:7px;border:none;border-radius:999px;cursor:pointer;font-family:inherit;font-size:0.78rem;font-weight:700;">🌙 شب (تیره)</button>
                </div>
                <div style="font-size:0.75rem;font-weight:700;margin-bottom:8px;opacity:0.75;">پالت رنگ (پیش‌فرض: زرد سانشاین):</div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
                    ${swatchesHtml}
                </div>
                <div style="margin-top:12px;padding-top:10px;border-top:1px solid var(--zc-border-subtle);font-size:0.75rem;text-align:center;">
                    پالت فعال: <strong id="zc-ccs-current">${currentPreset.label}</strong>
                </div>
            </div>
        `;

        document.body.appendChild(wrap);

        const toggle = wrap.querySelector('#zc-ccs-toggle');
        const panel = wrap.querySelector('#zc-ccs-panel');
        const close = wrap.querySelector('#zc-ccs-close');

        toggle.addEventListener('click', () => { panel.hidden = !panel.hidden; });
        close.addEventListener('click', () => { panel.hidden = true; });

        panel.querySelectorAll('[data-mode]').forEach(btn => {
            btn.addEventListener('click', () => {
                currentMode = btn.getAttribute('data-mode');
                try { localStorage.setItem(MODE_KEY, currentMode); } catch (e) {}
                applyTheme(currentPreset, currentMode);
                updateSwitcherUI();
            });
        });

        panel.querySelectorAll('[data-preset]').forEach(sw => {
            sw.addEventListener('click', () => {
                const id = sw.getAttribute('data-preset');
                const found = PRESETS.find(p => p.id === id);
                if (!found) return;
                currentPreset = found;
                try { localStorage.setItem(COLOR_KEY, currentPreset.id); } catch (e) {}
                applyTheme(currentPreset, currentMode);
                updateSwitcherUI();
            });
        });
    }
})();
// Close a contact disclosure with Escape/outside click; retain the native details control.
(()=>{'use strict';document.addEventListener('click',e=>{document.querySelectorAll('.zc-floating-socials[open]').forEach(d=>{if(!d.contains(e.target))d.open=false})});document.addEventListener('keydown',e=>{if(e.key!=='Escape')return;document.querySelectorAll('.zc-floating-socials[open]').forEach(d=>{d.open=false;d.querySelector('summary').focus()})})})();
// Manual site-width overrides: react to actual content width, not just viewport width.
// Avoid layout containment on Elementor's flex descendants: it can inflate intrinsic block heights.
(()=>{'use strict';const init=()=>{const seen=new WeakSet(),selector='.zc-container,.zc-single-product-main';
 const flag=(el,name,on)=>{if(el.hasAttribute(name)!==on)el.toggleAttribute(name,on)};
 const update=(el,width)=>{if(el.matches('.zc-container')){const state=width<=640?'small':width<=1000?'medium':'wide';if(el.dataset.zcLayout!==state)el.dataset.zcLayout=state;flag(el,'data-zc-compact-header',width<=1280);flag(el,'data-zc-narrow-header',width<=320)}else flag(el,'data-zc-product-narrow',width<=620)};
 const measure=el=>{const s=getComputedStyle(el);update(el,Math.max(0,el.clientWidth-parseFloat(s.paddingLeft)-parseFloat(s.paddingRight)))};
 const observer='ResizeObserver'in window?new ResizeObserver(entries=>entries.forEach(e=>update(e.target,e.contentRect.width))):null;
 const scan=root=>{const elements=[];if(root.matches?.(selector))elements.push(root);root.querySelectorAll?.(selector).forEach(el=>elements.push(el));elements.forEach(el=>{if(seen.has(el))return;seen.add(el);measure(el);observer?.observe(el)})};
 scan(document);new MutationObserver(entries=>entries.forEach(e=>e.addedNodes.forEach(n=>{if(n.nodeType===1)scan(n)}))).observe(document.body,{childList:true,subtree:true});
 if(!observer){let frame;window.addEventListener('resize',()=>{cancelAnimationFrame(frame);frame=requestAnimationFrame(()=>document.querySelectorAll(selector).forEach(measure))},{passive:true})}
 };if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init()})();

// Keep theme floating controls clear of Elementor contact buttons/bars.
(()=>{'use strict';const adjust=()=>{const targets=[document.getElementById('zarincode-crextio-switcher'),...document.querySelectorAll('.zc-floating-quick-contact'),...([...document.querySelectorAll('.zc-floating-socials')].filter(el=>!el.closest('.zc-floating-quick-contact')))].filter(Boolean);if(!targets.length)return;const floaties=[...document.querySelectorAll('.e-contact-buttons,.e-floating-bars,.elementor-widget-contact-buttons,.elementor-widget-floating-bars')];for(const el of targets){if(el._zcBaseBottom===undefined)el._zcBaseBottom=getComputedStyle(el).bottom||'1.25rem';el.style.bottom=el._zcBaseBottom;const r1=el.getBoundingClientRect();let extra=0;for(const f of floaties){const r2=f.getBoundingClientRect();const ovH=Math.min(r1.right,r2.right)-Math.max(r1.left,r2.left);const ovV=Math.min(r1.bottom,r2.bottom)-Math.max(r1.top,r2.top);if(ovH>2&&ovV>2)extra=Math.max(extra,r2.height+16);}if(extra)el.style.bottom='calc('+el._zcBaseBottom+' + '+extra+'px)';}};const run=()=>{adjust();setTimeout(adjust,350);setTimeout(adjust,1200);};if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run,{once:true});else run();window.addEventListener('load',run);window.addEventListener('resize',()=>{clearTimeout(window._zcCcsT);window._zcCcsT=setTimeout(run,150)});})();
// Scrollable code regions must be reachable from the keyboard (WCAG 2.1.1).
(()=>{'use strict';const mark=pre=>{const scrollable=pre.scrollWidth>pre.clientWidth+1||pre.scrollHeight>pre.clientHeight+1;if(scrollable)pre.setAttribute('tabindex','0');else pre.removeAttribute('tabindex');};const run=()=>{document.querySelectorAll('.elementor-widget-code-highlight pre,.elementor-widget-code-highlight code[class*="language-"]').forEach(mark);};if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run,{once:true});else run();window.addEventListener('load',run);window.addEventListener('resize',()=>{clearTimeout(window._zcPreT);window._zcPreT=setTimeout(run,200)});})();
