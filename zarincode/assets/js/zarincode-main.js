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

    function getMode() {
        try { return localStorage.getItem(MODE_KEY) === 'dark' ? 'dark' : 'light'; } catch (e) { return 'light'; }
    }

    function getPreset() {
        try {
            const id = localStorage.getItem(COLOR_KEY);
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
        document.querySelectorAll('[data-zc-toggle-dark]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                currentMode = currentMode === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem(MODE_KEY, currentMode); } catch (e) {}
                applyTheme(currentPreset, currentMode);
                updateSwitcherUI();
            });
        });

        // Filter Tabs (Products, Portfolio, Pricing)
        document.querySelectorAll('[data-zc-tabs]').forEach(function (tabContainer) {
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
        document.querySelectorAll('[data-zc-pricing-tabs]').forEach(function (nav) {
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
        document.querySelectorAll('.zc-faq-btn, .zc-faq-trigger').forEach(function (btn) {
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

        // Consultation & Search Modals
        document.querySelectorAll('[data-zc-modal-open]').forEach(function (trigger) {
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                const id = trigger.getAttribute('data-zc-modal-open');
                const modal = document.getElementById(id);
                if (modal) modal.classList.add('is-open');
            });
        });

        document.querySelectorAll('[data-zc-modal-close]').forEach(function (closeBtn) {
            closeBtn.addEventListener('click', function () {
                const modal = closeBtn.closest('.zc-modal-backdrop');
                if (modal) modal.classList.remove('is-open');
            });
        });

        document.querySelectorAll('.zc-modal-backdrop').forEach(function (backdrop) {
            backdrop.addEventListener('click', function (e) {
                if (e.target === backdrop) backdrop.classList.remove('is-open');
            });
        });

        // Mobile Navigation Drawer Open / Close / Submenu Accordion
        document.querySelectorAll('[data-zc-drawer-open]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const id = btn.getAttribute('data-zc-drawer-open');
                const drawer = document.getElementById(id);
                if (drawer) {
                    drawer.classList.add('is-open');
                    drawer.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }
            });
        });

        document.querySelectorAll('[data-zc-drawer-close]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const drawer = btn.closest('.zc-mobile-drawer-backdrop') || document.getElementById('zc-mobile-drawer');
                if (drawer) {
                    drawer.classList.remove('is-open');
                    drawer.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }
            });
        });

        document.querySelectorAll('.zc-mobile-drawer-backdrop').forEach(function (backdrop) {
            backdrop.addEventListener('click', function (e) {
                if (e.target === backdrop) {
                    backdrop.classList.remove('is-open');
                    backdrop.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }
            });
        });

        document.querySelectorAll('[data-zc-mobile-sub-toggle]').forEach(function (subBtn) {
            subBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const parentLi = subBtn.closest('.zc-mobile-has-sub');
                if (parentLi) {
                    parentLi.classList.toggle('is-open');
                }
            });
        });

        // Ctrl+K shortcut for search modal
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                const searchModal = document.getElementById('zc-search-modal');
                if (searchModal) {
                    searchModal.classList.add('is-open');
                    const input = searchModal.querySelector('input');
                    if (input) input.focus();
                }
            }
            if (e.key === 'Escape') {
                document.querySelectorAll('.zc-modal-backdrop.is-open').forEach(m => m.classList.remove('is-open'));
                document.querySelectorAll('.zc-mobile-drawer-backdrop.is-open').forEach(d => {
                    d.classList.remove('is-open');
                    d.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                });
            }
        });

        // Consultation Form Submission Toast
        document.querySelectorAll('.zc-Ajax-form').forEach(function (form) {
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
        buildCrextioSwitcher();
    });

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
