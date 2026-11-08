# 2026-11-08 — v4.6.0 homepage rebuild (Elementor widgets)

Fresh WordPress + Elementor 3.32.4 + WooCommerce env, `Demo_Seeder::seed_all()`.

Homepage is now composed of **11 dedicated Elementor widgets** (all editable):
zarincode_home_hero, _partners, _services, _products, _portfolio, _why,
_steps, _pricing, _testimonials, _blog, _promo.

- home-desktop.png — full page (1440px) rendered from the live site via CDP.
- home-mobile-390.png — same page at 390px (single column, no overflow).

Verified: 11 widget types inside `_elementor_data` (1 each), `.zc-hp` wrapper on
every section, scoped CSS (`assets/css/home-cheerful.css`) enqueued only on the
front page, Font Awesome 7 glyphs resolving, zero legacy hero markup left.
