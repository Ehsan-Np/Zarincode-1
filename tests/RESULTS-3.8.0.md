# Zarincode 3.8.0 — executed QA, 2026-10-22

Environment: disposable WordPress **7.1.3**, Elementor **4.3.5**, WooCommerce **11.2.1**, PHP **8.4.27**, SQLite integration; Playwright Chromium **145**. All product names, prices and imagery in these screenshots are **QA fixtures**, not production product specifications, customer proof or checkout evidence. No QA database/content is bundled into the demo.

## Passed
- PHP lint: **82 source files**; `node --check` for the changed frontend script; `git diff --check`.
- Existing native-design suite: **2,034 assertions / 26 layouts**; discussion/security **15**; interface **16**. These historical test sources remain unchanged in the repository.
- New presentation suite: **191 assertions** covering 15%/100% widths, six model families, title/scene/copy structure, editable parts, the aspect-ratio selector and native notice hook.
- Existing fields, repeater defaults/IDs and original part IDs compare equal to the preceding source version. EmbeddedCore is byte-identical to the companion core. Model refinement is idempotent.
- **21 page/viewport cases**: product, article and widget cards at 320/360/390/768/1024/1440/1920 pixels; **zero document horizontal overflow and zero browser page errors** after layout settlement.
- **138 card geometries** across six widths: every card title above its scene, every copy overlay contained within the scene. Original plus six optional skins yield **seven distinct style signatures**; optional skins fit 320 and 1440 after resize transitions settle.
- **25 functional assertions**: physical LEFT artwork/RIGHT summary, one native purchase form, actual Woo price, native thumbnails and lightbox, specs shortcut, real add-to-cart success notice and cart quantity, native variable selection/variation ID/enabled purchase, out-of-stock behavior, copy feedback, Iranian native chooser/copy fallbacks, opened details without overflow, dark sharing surface, and one horizontal name/email/rating row for article/product at 320/390/768/1440.
- RTL keyboard ArrowLeft/Home changes the selected tab and visible pane. Native Woo Product JSON-LD contains the real fixture price.
- At 1920: shared variable **1564px**; header pill, product hero and footer bento each **1500px** wide at **x=210px** (64px combined inner gutters). Existing base setting is 1360px; the scale is 115%.
- Install ZIPs (**229 theme / 67 core files**) built directly from source, with no embedded plugin ZIPs in the theme. Every ZIP member is checked byte-for-byte against source; checksums cover theme/core/XML/kit. Curated XML and professional kit are unchanged.

## Evidence and reproducibility
- [Desktop hero](product-hero-3.8.0.png), [mobile fold](product-mobile-3.8.0.png), [composed cards](cards-desktop-3.8.0.png), [one service card](service-card-3.8.0.png).
- [Desktop sharing](share-ribbon-3.8.0.png), [mobile sharing](share-ribbon-mobile-3.8.0.png), [dark/open sharing](share-dark-3.8.0.png).
- `presentation-*.php` fixtures must run only on disposable WordPress with this source installed; never on a live store. Browser drivers expect fixture JSON under `/tmp/zc38qa/` and Playwright available through Node's module resolution. The archived drivers retain explicit fixture paths; adapt the repository path if it is not `/home/user`. Start a disposable PHP/router server on port 8080 and generate the fixtures before running them.
- PHP: `wp eval-file tests/presentation-3.8.0.php`. Fixtures: `presentation-fixture-3.8.0.php`, then `presentation-commerce-fixture-3.8.0.php`, then `presentation-skins-fixture-3.8.0.php`. Browser drivers cover geometry, composed cards, commerce and final width/keyboard/schema checks.

## Limitations / discovered test issues
- The initial staging copy used symlinks. PHP's persistent realpath cache produced wrong asset URLs; staging was changed to physical copies and its server restarted. All final browser runs use physical copies and have no such script errors.
- Persian cart counts require numeral normalization in the test assertion. A failed initial assertion was a test assumption, not a failed cart transaction; the final transaction suite passes.
- Resizing optional skins is checked after the drawer/layout transition settles. Card geometry is additionally checked from freshly loaded pages at each viewport.
- The installed Elementor version emits its own PHP 8.4 implicit-nullable deprecation notice. No claim is made that the complete third-party stack is warning-free.
- No real payment gateway transaction, email delivery, full Elementor editor interaction, Firefox/Safari, MySQL, PHP 8.0 or marketplace approval was tested or claimed. Checkout/payment integrations remain the merchant's responsibility.

Temporary WordPress, databases, browser dependencies/cache, logs, downloads and local outputs are removed after the verified GitHub push; archived tests and packages stay remote via sparse checkout.
