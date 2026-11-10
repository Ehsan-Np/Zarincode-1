# Footer audit — 4.13.1 (2026-10-21)

Owner's request: «طرح فوتر رو باید به همین حالت قبلی خودش برگردونی … حاشیه رنگ مشکی
بالای فوتر اصلا نیاز نیست» — bring the footer back to its previous, healthy look and
remove the black band above it.

## What the black band actually was

It was **not** the plate and **not** a border. 4.9.0 painted a page-to-footer
"veil" above the plate: a 9rem block whose background is a gradient from
transparent to `#2E3138`, plus `margin-top: 9rem` on the footer landmark. On the
light cream page it reads exactly like a black bar sitting on the footer.

It lived in two places and both are now gone:

| File | Removed |
| --- | --- |
| `zarincode/assets/css/hf-footer.css` | `.zc-footer-host { margin-top: 9rem }`, `.zc-footer-host::before` (the 9rem veil) and the 768 / 480px variants |
| `zarincode/assets/css/zarincode-main.css` | `.zc-footer { padding: 9rem 0 0 }` and `.zc-footer::before` (veil of the coded fallback footer), plus the two now-unused `--zc-footer-veil` tokens |

## What was deliberately NOT touched

The plate itself — gradient `#2E3138 → #22242A`, radius `32px 32px 0 0`, gold
hairline, the two decorative gold glows, `48px 64px 56px` padding — and every
Container / widget inside it. The Elementor tree is byte-identical to 4.13.0
(17 Containers, 23 widgets, 41 Navigator entries).

## Measured after the change

| Metric | 4.13.0 | 4.13.1 |
| --- | --- | --- |
| Footer landmark margin above the plate | 144px (9rem veil band) | **0px** |
| `footer.zc-footer-host::before` | 9rem dark gradient | **none** |
| Plate height at 1440 | 1005.94px | 1005.94px (unchanged) |
| Plate height at 390 | 1797.98px | 1797.98px (unchanged) |
| Editor: navigator items / page errors | 41 / 0 | 41 / 0 |

## Files in this folder

| File | What it shows |
| --- | --- |
| `footer-4.13.1-desktop-1440.png` | the footer at 1440 — no band above the plate |
| `footer-4.13.1-mobile-390.png` | the 390 stack, unchanged |
| `footer-4.13.1-page-junction.png` | the moment the page meets the footer: light page → plate, no dark strip |
| `footer-4.13.1-elementor-editor.png` | the document still opens in the Elementor editor |

## Upgrade path

`HF_Builder` now stamps generated documents with `_zc_hf_builder`. A site updated
from 4.13.0 gets the new tree on the next `ensure()` even though it has a saved
footer document — but only while the document is still ours (`_zc_hf_generated`)
and untouched (`_zc_hf_user_edited` absent). Verified in the QA install:
`4.13.0 → 4.13.1` rebuilt with a backup, while a document flagged as edited kept
its own version.
