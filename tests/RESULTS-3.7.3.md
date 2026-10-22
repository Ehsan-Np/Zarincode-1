# Responsive QA — 3.7.3, 2026-10-22

Actual disposable WordPress 7.1.3 + Elementor + WooCommerce + SQLite, with physical source copies. Not a PHP markup mock. Native theme header/footer, Elementor widgets and actual simple product, article, account and shop pages were used. QA posts/prices are temporary fixtures, not release demo content.

## Executed checks
- **45 page/width cases**: home/product/article/account/shop at 320, 360, 390, 414, 600, 768, 1024, 1440, 1920. Final scan: no horizontal document overflow, including with root/body overflow clipping explicitly disabled. Browser page errors: zero.
- Real discovered component problem: pricing grid 1fr intrinsic min-content pushed the 1024 tablet page 53px sideways when guard was disabled. Replaced with zero-minimum tracks and two columns at the intermediate breakpoint; final raw overflow is zero.
- Drawer open/Escape close, consultation modal, technical specification tab and floating details checked at 320/390/768; no document overflow. Inputs/modal grids are constrained at phone sizes.
- At 1920, shared variable is **1469px**. Header pill, product main grid and footer bento: **1405px wide at x=257.5px**; the remaining 64px are internal desktop gutters.
- 17 unit assertions: 108% widths, opt-out 100%, repeatability, invalid option input.
- PHP lint: 82 source files. Existing native-design suite: 2,031 assertions / 26 layouts. Core/EmbeddedCore mirrors checked. Product PHP and its approved 3.7.2 layout CSS unchanged.
- Install ZIP members verified byte-for-byte against source; XML and professional kit unchanged; four checksums verified.

## Evidence/reproduction
`home-390-3.7.3.png`, `home-1440-3.7.3.png`, `product-390-3.7.3.png`, `product-1440-3.7.3.png`, `article-390-3.7.3.png`, `article-1440-3.7.3.png`.

`responsive-fixture-3.7.3.php` is **only for disposable WordPress**. Its WordPress location is `/tmp/zc374qa/wordpress`; Node drivers expect Playwright and `/tmp/zc374qa/fixture.json`, and a PHP/router server on port 8080. In staging, set **both home and siteurl to the server root**, not a /wordpress subdirectory. The initial wrong staging siteurl caused bad asset responses; that was fixed before the final scans. A third-party Elementor/Woo WP-CLI activation conflict was avoided by activating Woo with Elementor skipped, then loading normal wp-load.php for fixtures/tests; production source was not patched for that conflict.

No production-site access, Safari/Firefox/device hardware, payment gateway transaction or every possible customer widget/plugin combination was tested. Native header/footer variant tests here use the default theme header/footer. Root clipping is a fallback guard, not evidence that arbitrary custom plugin content will always fit. Temporary environment, browser downloads/cache and local artifacts are removed after the verified push; source/evidence remain archived on GitHub.
