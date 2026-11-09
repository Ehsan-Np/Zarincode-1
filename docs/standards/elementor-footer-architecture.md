# Elementor footer architecture — the standards this project follows

Status: applies from 4.13.0. Written after reading the Elementor developer
documentation, the Elementor container CSS bundle, and the generated
`uploads/elementor/css/post-*.css` of a real 4.4.0 install, then checking every
rule against the browser's computed styles at 1440 / 768 / 390 px.

The goal is not "a footer that looks right". It is a footer the owner can open
in the Elementor editor, click any box, and edit — while the approved 4.9.0
design stays byte-for-byte intact.

---

## 1. Structure: Containers + stock widgets, nothing else

| Rule | Why |
| --- | --- |
| Every box is an `elType: container` node with `isInner: true` below the root. | Containers are the modern Elementor layout primitive (default since 3.16) and they are what the editor's Navigator understands. A `<div>` pasted into a Text Editor widget cannot be resized, re-ordered or deleted element-by-element. |
| Every piece of content is a stock widget: `heading`, `text-editor`, `button`, `icon-list`, `icon`, plus the project's own `zarincode_social_profiles`. | Stock widgets ship with the full control set, so the owner keeps typography, colour, spacing, icon and link controls. Custom HTML would freeze all of that. |
| No shortcode, no `[elementor-template]`, no PHP-rendered markup inside the document. | Any of those turns the footer back into "one piece" that Elementor cannot edit. |
| The document is a real `wp-post` Elementor document (`_elementor_edit_mode = builder`, `_elementor_template_type`, `_elementor_data`). | `wp-header` / `wp-footer` document types belong to Elementor Pro and make the editor fall back to a document it cannot save. |

Current tree (post 6, theme demo): **17 Containers, 23 widgets, 5 levels deep** —
7 headings, 5 buttons, 4 text editors, 3 icon lists, 3 icons, 1 social-profiles widget.

```
Container (plate, editable: gradient, border, radius, padding, shadow)
├─ Container (CTA card)
│  ├─ Heading (badge)
│  ├─ Heading (H2)
│  └─ Container (buttons) → Button, Button
├─ Container (4 columns)
│  ├─ Container (brand)  → Container(brandhead: Heading monogram + Container(name, tagline)),
│  │                       Text Editor (about), Icon List (contact), Social Profiles widget
│  ├─ Container (services) → Container(dot Heading + Heading title), Icon List
│  ├─ Container (legal)    → Container(dot Heading + Heading title), Icon List
│  └─ Container (credits)  → Container(dot Heading + Heading title), Container(chips → 3 Buttons)
└─ Container (bottom bar) → Container(copyright Text Editor), Container(support Text Editor)
```

## 2. Control names — always the value the editor itself saves

| Need | Settings key (Elementor 3.x and 4.x) |
| --- | --- |
| Direction / wrap | `flex_direction`, `flex_wrap`, `flex_wrap_tablet`, `flex_wrap_mobile` |
| Alignment | `flex_justify_content`, `flex_align_items` |
| Gap | `flex_gap { column, row, unit, isLinked }` |
| Column width | `width` / `width_tablet` / `width_mobile` as `{ unit: '%', size }` **and** `_flex_size: custom`, `_flex_grow: 0`, `_flex_shrink: 1` |
| Box | `padding(_tablet/_mobile)`, `margin`, `background_background`, `background_color(_b)`, `background_gradient_type/angle`, `border_*`, `border_radius`, `box_shadow_*` |
| Widget width | `_element_width`, `_flex_size` (`none` = hug content) |
| Text alignment | `align` = `start \| center \| end \| justify` — **logical**, never `left` / `right` |
| Typography | `typography_typography: custom`, `typography_font_size(_tablet/_mobile)`, `typography_font_weight`, `typography_line_height`, `typography_letter_spacing`, `typography_text_transform` |

Hard-won details, all verified in the browser:

* `0` (or an empty flex-basis) is dropped by Elementor — a column width must be a
  real percentage.
* A container `width` has no effect unless the container also has
  `content_width: full`; otherwise the boxed wrapper eats it.
* A Heading widget hugs its text only with `_element_width: auto` **plus**
  `_flex_size: none`; the width controls alone collapse it to 0px.
* `left` / `right` are not valid `align` values in Elementor 4.x; RTL is handled
  by the logical values.
* Full-width containers have **no** `.e-con-inner` wrapper; boxed ones do. Never
  measure a tree by assuming `.e-con-inner`.

## 3. Elementor's own defaults that must be neutralised in the data

| Default | Effect | Fix in the tree |
| --- | --- | --- |
| Nested Container `padding: 10px` | 20px lost from every column, 20px of extra height per nested box — the credit chips wrapped, the about text gained a line, the plate grew by ~80px. | `container()` always writes `padding` (zero unless the design asks for more). |
| Text Editor prints `<p>` verbatim | Browser `1em` top/bottom margins add ~25px per text block. | Scoped typographic reset in `hf-footer.css` (`.zc-footer-host .elementor-widget-text-editor p { margin-block: 0 }`) — a reset, not an override of any Elementor control. |

## 4. CSS discipline

* The theme sets **no layout property** on `.zc-hf` or on Elementor widget
  internals. Everything the design needs is written as a control value, so the
  editor and the front end can never disagree.
* `hf-footer.css` is scoped to the `.zc-footer-host` landmark and carries only
  what a control cannot express: the page-continuity veil above the plate, the
  two decorative gold glows, the paragraph reset, and the mobile lead-in.
* Design CSS is scoped per area (`.zc-footer-host`, `.zc-header-host`), so it
  cannot bleed between header, footer and page content.

## 5. Responsive behaviour is control-driven

| Breakpoint | Elementor control | Result |
| --- | --- | --- |
| ≥ 1025 | `width` 29.6 / 20.2 / 20.2 / 21.6 %, gap 36px | columns at x 959 / 673 / 388 / 65 of a 1310px row |
| 768 – 1024 | `flex_wrap_tablet: wrap` + `width_tablet` 100 / 45 / 45 / 45 % | brand full width, then two per row |
| ≤ 767 | `flex_wrap_mobile: wrap` + `width_mobile: 100%` | one column per row; CTA buttons and the bottom bar stack |
| font sizes | `typography_font_size_tablet` / `_mobile` | 23.2 → 21 → 17.5px for the CTA H2, etc. |

## 6. Sources read before coding

* Elementor developers — *Container element / data structure*:
  <https://developers.elementor.com/docs/data-structure/container-element>
* Elementor developers — *Widgets & controls* (control naming, `_element_width`,
  responsive `_tablet` / `_mobile` suffixes):
  <https://developers.elementor.com/docs/controls/>
* Elementor — *Flexbox Container* (wrap / direction per device):
  <https://elementor.com/help/flexbox-container/>
* Elementor — *Create a custom widget* (widget registration, `get_*` vs
  `get_element_types()`):
  <https://developers.elementor.com/docs/widgets/>
* Elementor CSS bundle of the tested build
  (`assets/css/frontend.min.css`) and the generated
  `uploads/elementor/css/post-*.css`: the authoritative list of which settings
  actually emit a CSS variable.
* WordPress coding standards — <https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/>
* WordPress theme security / escaping —
  <https://developer.wordpress.org/themes/theme-security/>

## 7. Regression checklist (run for every footer/header change)

1. `php -l` on every touched file.
2. `HF_Builder::ensure(true)` + `wp elementor flush-css` — no PHP notice.
3. 1440 / 768 / 390 screenshots compared against `docs/audits/`.
4. Editor: the document opens with `elementor-editor-active`, the Navigator lists
   every container and widget by its Persian name, a colour or text change
   survives save + reload.
5. `debug.log` unchanged (the only accepted lines are the two `WP\MCP`
   deprecations of the QA install).
6. `git status` clean; packages rebuilt in `release-<version>/`.
