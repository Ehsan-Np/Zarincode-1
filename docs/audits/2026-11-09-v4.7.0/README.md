# Audit — v4.7.0 (2026-11-09)

Evidence for the demo-import fix + the Elementor widget-editing rework.

| file | what it shows |
| --- | --- |
| `home-desktop-1440.png` | full homepage at 1440 px (8,651 px tall) after the rewrite — 43 px differ from the approved 4.6.0 render (0.000%), i.e. the design is intact |
| `home-mobile-390.png` | full homepage at 390 px (18,217 px tall) |
| `editor-content-tab.png` | Elementor editor, Services widget, **Content** tab: two content sections, every field pre-filled with the real copy |
| `editor-style-tab.png` | same widget, **Style** tab: پوستهٔ بخش / عنوان بخش / کارت خدمات |

Environment: WordPress 6.9 + Elementor 3.32.4 + WooCommerce 11.2.1 + Yoast SEO,
fa_IR, `WP_DEBUG` + `WP_DEBUG_LOG` on, demo imported through the admin panel
(`admin.php?page=zarincode-theme-options&zc_tool=demo` → «شروع درون‌ریزی»).

Result of that import: success notice rendered, **zero** fatals, and
`wp-content/debug.log` stayed empty (before 4.7.0 that same click produced
`Call to undefined method … Home_Templates::home_hero()` and the
`Cannot redeclare control with same name "intro"` notice from the client's log).
