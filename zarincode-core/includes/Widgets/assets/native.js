(function ($) {
    'use strict';
    function register() {
        if (!window.elementorModules || !window.elementorFrontend) return;
        class ZarincodeNative extends elementorModules.frontend.handlers.Base {
            onInit() {
                super.onInit();
                this.root = this.$element[0].querySelector('.zc-native-layout');
                if (!this.root) return;
                this.click = this.onClick.bind(this);
                this.root.addEventListener('click', this.click);
                this.submit = event => { const form = event.target.closest('.zc-Ajax-form'); if (!form) return; event.preventDefault(); const feedback = form.querySelector('[data-native-form-message]'); if (feedback) feedback.hidden = false; };
                this.root.addEventListener('submit', this.submit);
                const timer = this.root.querySelector('[data-zc-original-id="zc-live-timer"]');
                if (timer && !elementorFrontend.isEditMode()) {
                    const match = timer.textContent.trim().match(/^(\d+):(\d{2}):(\d{2})$/);
                    if (match) {
                        let seconds = +match[1] * 3600 + +match[2] * 60 + +match[3];
                        this.timer = setInterval(() => { seconds++; timer.textContent = [Math.floor(seconds / 3600), Math.floor(seconds % 3600 / 60), seconds % 60].map(v => String(v).padStart(2, '0')).join(':'); }, 1000);
                    }
                }
            }
            onClick(event) {
                const button = event.target.closest('[data-filter],[data-pricing-target],.zc-faq-btn,.zc-faq-trigger,[data-zc-modal-open],[data-zc-drawer-open]');
                if (!button || !this.root.contains(button)) return;
                // Shared theme controller owns drawer focus, scroll and ARIA state.
                if (button.hasAttribute('data-zc-drawer-open')) return;
                if (elementorFrontend.isEditMode() && event.target.closest('.elementor-inline-editing')) return;
                if (button.hasAttribute('data-filter')) {
                    const group = button.closest('[data-zc-tabs]');
                    if (!group) return;
                    group.querySelectorAll('[data-filter]').forEach(b => { b.classList.toggle('is-active', b === button); b.setAttribute('aria-pressed', b === button ? 'true' : 'false'); });
                    const target = group.getAttribute('data-zc-tabs');
                    let grid; try { grid = this.root.querySelector(target); } catch (_) { return; }
                    if (!grid) return;
                    const value = button.getAttribute('data-filter');
                    grid.querySelectorAll('[data-category]').forEach(card => { card.hidden = !['all', '*', ''].includes(value) && !(card.getAttribute('data-category') || '').split(/\s+/).includes(value); });
                } else if (button.hasAttribute('data-pricing-target')) {
                    const value = button.getAttribute('data-pricing-target');
                    this.root.querySelectorAll('[data-pricing-target]').forEach(b => b.classList.toggle('is-active', b === button));
                    this.root.querySelectorAll('[data-pricing-panel]').forEach(panel => { panel.style.display = panel.getAttribute('data-pricing-panel') === value ? 'grid' : 'none'; });
                } else if (button.matches('.zc-faq-btn,.zc-faq-trigger')) {
                    const item = button.closest('.zc-faq-item');
                    if (item) { this.root.querySelectorAll('.zc-faq-item').forEach(other => { if (other !== item) { other.classList.remove('is-open'); const trigger = other.querySelector('.zc-faq-btn,.zc-faq-trigger'); if (trigger) trigger.setAttribute('aria-expanded', 'false'); } }); item.classList.toggle('is-open'); button.setAttribute('aria-expanded', item.classList.contains('is-open') ? 'true' : 'false'); }
                } else {
                    event.preventDefault();
                    const id = button.getAttribute('data-zc-modal-open') || button.getAttribute('data-zc-drawer-open');
                    const target = document.getElementById(id);
                    if (target) { target.classList.add('is-open'); if (button.hasAttribute('data-zc-drawer-open')) { target.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; } }
                }
            }
            onDestroy() {
                if (this.root && this.click) this.root.removeEventListener('click', this.click);
                if (this.root && this.submit) this.root.removeEventListener('submit', this.submit);
                if (this.timer) clearInterval(this.timer);
                super.onDestroy();
            }
        }
        ['header','footer','hero','hero_intro','hero_cta','brands','services','products','portfolio','why_us','pricing','promo','steps','testimonials','blog','page_section'].forEach(name => {
            elementorFrontend.hooks.addAction('frontend/element_ready/zarincode_' + name + '.default', $element => elementorFrontend.elementsHandler.addHandler(ZarincodeNative, { $element }));
        });
    }
    $(window).on('elementor/frontend/init', register);
})(jQuery);
