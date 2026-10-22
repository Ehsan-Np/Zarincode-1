# QA — Zarincode 3.7.4, 2026-10-22

Actual disposable WordPress 7.1.3 / Elementor 4.3.5 / WooCommerce 11.2.1 / SQLite / PHP 8.4 / Playwright Chromium. No production host access or deployment.

## Executed
- Actual authenticated options form: **all 18 tabs**, including backup, submitted with a valid WordPress nonce. The form action deliberately omitted the hash so assertions verify the server-side POST restoration, not only client-side state. Each save retained the correct active panel and hidden tab value. Header hash navigation and reload also passed.
- Account label policies `always`, `never`, `desktop` saved via the real panel and verified on the frontend at **320, 390, 768, 1024, 1440, 1920**: 18 combinations. Accessible name present in all; no visible label element in never mode; label visibility correct in the others. No horizontal root overflow with clipping disabled. Existing button transitions were allowed 350ms to settle after viewport changes.
- **15 manual card variants** (6 portfolio + 3 blog + 6 projects-page) checked at six widths: one image per card, image inside the shared cover, image/cover dimensions equal, title bounds inside cover. Mobile and desktop screenshots archived. Existing controls/defaults/repeater IDs/legacy maps byte-equivalent as parsed JSON to 3.7.3. Structural upgrade script idempotent.
- Published-query portfolio/blog cards tested at four widths, including a project without a featured image. Fallback readable; no root overflow or clipped title.
- **54 full-page cases**, six pages × nine widths 320–1920: home/product/article/account/shop/cards, root clipping on **and off**. Root and body width overflow both zero, page errors zero. This scan predates adding the separate query fixture; query has its own four-width checks.
- Existing native design suite: **2,031 assertions / 26 layouts**. PHP lint: **82 files**. Core and EmbeddedCore byte parity. Product PHP and approved `product-reference.css` unchanged from 3.7.3. Shared Layout width calculation unchanged.
- Both install ZIPs byte-matched to complete source members; unchanged XML/kit imports and four SHA256 checks verified before push.

## Real defects caught while testing
- Account label on at 320 needed a bounded/wrapping label; desktop text at 1440 needed compact menu padding. Both corrected and full label-policy matrix rerun.
- The old 165px blog-cover minimum and a generated Elementor stylesheet from an intermediate test caused a long title to leave the cover. Removed the obsolete minimum, switched to natural-height cover with container-based ratio minimum and cleared generated CSS; all 90 manual-card geometry checks then passed. On release, the already-existing admin CSS identity refresh is versioned to 3.7.4.

## Reproduction/evidence
Use the previous `responsive-fixture-3.7.3.php` for a disposable base site; run `cards-fixture-3.7.4.php`, then `cards-query-fixture-3.7.4.php` for the added pages. These scripts insert QA posts only; do not execute on a production database. Drivers expect a temporary install at `/tmp/zc374qa/wordpress`, fixture JSON in `/tmp/zc374qa/fixture.json`, and a router at localhost:8080 with both home/siteurl set to that root. Node requires Playwright/Chromium. Authenticated driver uses temporary `qa` administrator and the **ZC_QA_PASSWORD environment variable**, never an embedded password. Initial stock WordPress post plus the fixture article explain the query blog count of two.

Logs: `cards-options-results-3.7.4.log`, `cards-query-results-3.7.4.log`, `responsive-results-3.7.4.log`, `native-results-3.7.4.log`. Screenshots: `portfolio-390-3.7.4.png`, `portfolio-1440-3.7.4.png`, `blog-390-3.7.4.png`, `blog-1440-3.7.4.png`.

Not tested: physical devices, Safari/Firefox, production DB, payment transaction, every external header builder/plugin, every optional card skin combination. This change targets native cards and the standard article archive, not arbitrary third-party markup. Temporary runtime/downloads/dependencies/local evidence are removed after verified push; artifacts remain in GitHub.
