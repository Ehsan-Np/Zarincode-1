# Dispositions — remaining automated accessibility findings (2026-10-27)

After the fresh 438-case rerun, **26 axe records across 22 cases remain failed**. Every record
is inside third-party widget markup (Elementor core, Elementor Pro, WooCommerce-facing widgets).
Skill 3 forbids patching vendor files and forbids synthetic labels/ARIA to force a pass, so each
item is dispositioned below instead of hidden or "fixed" in vendor code.

Theme- and fixture-owned findings from earlier runs (contrast, RTL/direction, JavaScript errors,
missing widget output, floating-control overlap, atomic-form label association, scrollable code
regions) were fixed in this cycle and are verified clean in `installed-widgets.json`.

| Widget (both locales) | Rule | Records | Component | Root cause in vendor markup | Disposition |
|---|---|---:|---|---|---|
| `nested-tabs` | `aria-valid-attr-value` | 2 | Elementor core | `aria-controls` references `e-n-tab-content-*` id that is not present in the rendered DOM with empty default settings. | Vendor defect; report upstream. Not patchable via public API. |
| `tabs` | `aria-required-children` | 2 | Elementor core | `.elementor-tabs-content-wrapper` carries `role="tablist"` while its children are not `role="tab"` in this default fixture. | Vendor authoring/markup defect; upstream report. |
| `accordion` | `aria-allowed-attr` | 2 | Elementor core | `aria-selected` used on `role="button"` title element (disallowed attribute). | Vendor ARIA defect; upstream report. |
| `icon` | `link-name` | 2 | Elementor core | Icon-only link renders no accessible name and the link control exposes no `custom_attributes` field to let authors add `aria-label`. | Vendor gap: no legitimate authoring or public-API path. Upstream request. |
| `call-to-action` | `role-img-alt` | 2 | Elementor core | Decorative background div is emitted as `role="img" aria-label=""` (empty label prohibited). | Vendor ARIA defect; upstream report. |
| `media-carousel` | `role-img-alt` | 2 | Elementor core | Same empty `role="img" aria-label=""` background-image pattern. | Vendor ARIA defect; upstream report. |
| `link-in-bio-var-6` | `link-name` | 2 | Elementor Pro | Default image-link items render `<a href=""...><img alt="">` — no accessible name for empty social items. | Vendor default-content defect; upstream report. |
| `floating-bars-var-1` | `aria-valid-attr-value` | 2 | Elementor Pro | Close button `aria-controls="e-floating-bars"` targets an id that does not exist in rendered output. | Vendor ARIA defect; upstream report. |
| `floating-bars-var-3` | `aria-valid-attr-value` | 2 | Elementor Pro | Same dangling `aria-controls` reference. | Vendor ARIA defect; upstream report. |
| `reviews` | `aria-prohibited-attr` | 2 | Elementor Pro | Non-widget `div` receives `aria-label` without a role that permits it. | Vendor ARIA defect; upstream report. |
| `video-playlist` | `aria-required-children` | 2 | Elementor Pro | `.e-tabs-content-wrapper` declared `role="tablist"` containing `role="tabpanel"` children. | Vendor ARIA defect; upstream report. |
| `video-playlist` | `nested-interactive` | 2 | Elementor Pro | `role="tab"` title contains a focusable inner `button`. | Vendor interaction-design defect; upstream report. |
| `video-playlist` | `target-size` | 2 | Elementor Pro | Inner playlist button renders 189×15px, below the 24×24 target minimum. | Vendor styling defect; upstream report. |

Notes:

* All 26 records occur twice (en_US/mobile-width and fa_IR/desktop-width) across the 13 widget/rule
  pairs above; Zarincode-owned widgets contribute **zero** records (52/52 custom-widget cases pass).
* Runner outcome is therefore still exit code 1 / `passed: false` — the baseline gate is not
  claimed as passing, and no WCAG 2.2 AA certification is implied by the automated runs.
* Evidence: `installed-widgets.json` (per-node HTML/summaries), `summary.json` (aggregate counts).
