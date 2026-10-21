# Zarincode 2.6.0 — verification record

Release date: 2026-10-22.

## Environment

- PHP 8.4.27, WordPress 7.1.3.
- Elementor 4.3.5 (Free), WooCommerce 11.2.1, Redux Framework 4.5.16.
- SQLite Database Integration in a disposable test installation (not the shipped theme).
- Chromium / Playwright; desktop 1440×1000 and mobile 390×844.

## Results

- PHP syntax validation: 37 source/embedded PHP files passed.
- `wordpress-integration.php`: **232 assertions passed**. Checks all 14 widget registrations, content/style controls, renderer use of custom titles/cards/URLs, escaping, external-link relations, legal clause editing, valid layout control IDs, CPT registration, and repeat-import post-count stability.
- `legacy-upgrade.php`: PASS with plugin source from commit `7518db9` active before theme load. Its `CPT_Manager` is already loaded and lacks `register_cpts_and_taxonomies`. No legacy core callbacks remain; the new engine imports the demo in the same request without the original fatal error.
- Current 2.6.0 companion bootstrap installed through the theme plugin manager: activation succeeded; all 232 integration assertions also passed with the current plugin active.
- Browser form POST: full demo import displays the completion message; no fatal error.
- Actual Elementor editor: opened custom widget controls, Content/Style/Advanced available, title changed to `LIVE EDIT CHECK`, iframe output updated. No browser exceptions or HTTP 5xx responses during the successful run.
- Fixed an editor issue found during this test: dynamic-tag support must not be enabled indiscriminately on ICONS/SELECT/SWITCHER controls. It is now limited to supported content control types.
- Actual rendered custom style fixture: title color `rgb(204, 0, 51)`; font size `37px`; two computed grid columns; repeater card background `rgb(224, 255, 224)`.
- Filtering fixture: selecting FIRST leaves one matching card visible.
- Homepage: 13 rendered widgets (11 page sections plus active Elementor header/footer); desktop and mobile without horizontal overflow.

### Admin width measurements

| Screen | Desktop content / wrapper | Mobile content / wrapper | Horizontal overflow |
|---|---:|---:|---|
| Theme options | 1280 / 1280 px | 390 / 390 px | No |
| Demo importer | 1280 / 1280 px | 390 / 390 px | No |
| Plugin manager | 1280 / 1280 px | 390 / 390 px | No |

Native WordPress sidebar space is intentionally retained on desktop.

## Reproduction

Use a disposable WordPress installation with Elementor, WooCommerce and Redux active, and the Zarincode theme. Import the demo once, then:

```sh
wp eval-file /path/to/tests/wordpress-integration.php
```

The legacy test additionally needs the old plugin fixture active. `style-fixture.php` creates a temporary published test page and generates its Elementor CSS; delete that page after verification. These scripts alter a test database and must not run on production.

## Boundaries and observed upstream issues

- Not a ThemeForest/Zhaket certification or a guarantee of compatibility with every hosting stack or plugin combination.
- MySQL/MariaDB, older supported PHP releases, Elementor Pro, commercial plugin licenses, payment processing, and real email delivery were not verified in this run.
- During CLI activation of WooCommerce with Elementor already loaded in the same process, an upstream Elementor common-component fatal occurred. Activating WooCommerce with Elementor skipped in that CLI operation succeeded. Subsequent normal requests, demo import and widget tests with both plugins active passed.
- Elementor 4.3.5 emitted a PHP 8.4 nullable-parameter deprecation notice from `modules/global-classes/atomic-global-styles.php`. This is upstream, not the original demo-seeder fatal.
- No live customer site was accessed or changed.
