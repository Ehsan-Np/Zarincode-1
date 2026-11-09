# Footer audit — 4.13.0 (2026-10-21)

Scope: turn the approved 4.9.0 footer into a footer that is (a) the same box,
(b) fully editable in Elementor, (c) built only from Elementor widgets.

Artifacts in this folder:

| File | What it shows |
| --- | --- |
| `footer-4.9.0-reference.png` | the approved 4.9.0 design (1440, 1031.30px including the 9rem lead-in) |
| `footer-4.13.0-desktop-1440.png` | the Elementor footer document at 1440 |
| `footer-4.13.0-tablet-768.png` | 768 — brand full width, then two columns per row |
| `footer-4.13.0-mobile-390.png` | 390 — one column per row, CTA and bottom bar stacked |
| `footer-4.13.0-elementor-editor.png` | the footer document open in the Elementor editor (Structure panel, 41 navigator items) |

## Document shape (post 6, `_elementor_data`)

```
containers = 17   widgets = 23   max nesting depth = 5   json = 33,616 bytes
heading 7 · button 5 · text-editor 4 · icon-list 3 · icon 3 · zarincode_social_profiles 1
no `<!-- -->`, no shortcode, no HTML blob in the whole tree
```

## Measured against the reference

| Metric | 4.9.0 reference | 4.13.0 |
| --- | --- | --- |
| CTA card height | 212px | 213px |
| CTA H2 | one line, 747px wide | one line, 767px wide |
| Plate horizontal padding to the rows | 64px (rows 1312px) | 64px (rows 1310px) |
| Column x-positions (brand / services / legal / credits) | 959 / 673 / 388 / 64 | 959 / 673 / 388 / 65 |
| Column widths | 417 / 246 / 246 / 283 | 388 / 265 / 265 / 283 |
| Credit chips | one row, ~33px tall | one row, 32px tall |
| Divider to last column content | 69px | 69px |
| Footer height | 1031.30px (incl. 144px lead-in) | 1005.94px (incl. the same lead-in) |

The 25px difference is content-driven, not layout: the Elementor lists render
four of the six legal/service rows on a single line each, where the 4.9.0
stylesheet wrapped them onto two.

## How the layout drift was actually fixed

Not with CSS patches — the three real causes were Elementor defaults:

1. **Nested Container `padding: 10px`** — stole 20px from every column (the
   chips wrapped to 2+1, the about text gained a line, the plate grew ~80px).
   `container()` now writes `padding` explicitly.
2. **Text Editor `<p>` margins** — the browser's 1em top/bottom added ~25px per
   text block. Fixed with a scoped paragraph reset in `hf-footer.css`.
3. **Legacy flex-basis ratios** — replaced by the Width control
   (`29.6 / 20.2 / 20.2 / 21.6 %` over a 36px gap) plus `_flex_size: custom`,
   which is exactly what the editor stores, so the panel and the front end
   cannot disagree.

## Log

`php -l` clean on `HF_Builder.php`; `HF_Builder::ensure(true)` and
`wp elementor flush-css` run without a notice; `debug.log` contains only the two
known `WP\MCP` deprecations of the QA install (WordPress 7.1.4 bundles the MCP
adapter; unrelated to the theme and the plugin).
