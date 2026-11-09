# Audit — v4.7.1 (2026-11-09)

Evidence for the hardening pass on top of 4.7.0 (demo-import fix + widget rework).

| file | what it shows |
| --- | --- |
| `home-desktop-1440.png` | full homepage at 1440 px (8,651 px tall): design unchanged — 43 px differ from the approved render (0.0003 %) |
| `home-mobile-390.png` | full homepage at 390 px (18,217 px tall) |
| `style-override-proof.png` | a test page with two homepage widgets: the services section uses a background colour set in the Elementor panel (`#EEF6FF`) while the testimonials section keeps the shipped gradient tint; the 0-star testimonial correctly renders no stars and an emptied heading renders without an empty gold pill |

Environment: WordPress 6.9 · Elementor 3.32.4 · WooCommerce 11.2.1 · fa_IR ·
`WP_DEBUG` + `WP_DEBUG_LOG`. `debug.log` after the import holds only Elementor's
own PHP 8.4 deprecations and WooCommerce's textdomain notice — nothing from the
theme or `zarincode-core`.

Also verified in this pass (not screenshot-able):

* widget on a **non-front** Elementor page → `home-cheerful.css` now loads
  (before: handle unknown, section rendered unstyled);
* `[zarincode_widget type="home_services"]` on a plain page → same;
* simulated 4.6.0 homepage (11 widgets with `settings: []`) → one admin request
  refills all 11, keeps a hand-edited title, and sets the one-off flag.
