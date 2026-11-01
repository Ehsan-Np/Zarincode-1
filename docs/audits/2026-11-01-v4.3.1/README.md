# 4.3.1 share studio verification (2026-11-01)

- 431-share-uniform.png — desktop (900px): all 10 destinations uniform dark tile + white glyph, compact height; no halo/gradient/special colors; bale glyph white on shared tile
- 431-share-mobile.png — 390px: 4-col compact grid, same uniform styling

Method: chromium headless static harness rendering the exact Single_Experience::share() markup against the real single-experience.css + fontawesome + persian icons (bale.svg/eita.svg) via file://. Chosen because the full WP env was wiped this snapshot; CSS and markup are the changed artifacts and are byte-identical to production sources.
