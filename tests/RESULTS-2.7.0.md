# Zarincode 2.7.0 — verification record

Release date: 2026-10-22 (user timezone).

## Environment and scope

PHP 8.4.27, WordPress 7.1.3, Elementor Free 4.3.5, WooCommerce 11.2.1,
Redux Framework 4.5.16; disposable SQLite-backed WordPress. Chromium/Playwright,
desktop 1440×1000 and mobile 390×844. Theme and active companion use synchronized
2.7.0 source; namespace Runtime260 is deliberately retained for compatibility.

No customer site was imported or migrated. Tests that seed/import data must only
be run on a disposable installation. No WordPress or Elementor vendor file was
modified; errors were not suppressed to obtain passing results.

## PHP / WordPress checks

| Test | Result |
|---|---|
| `professional-inline.php` | **97 assertions passed** |
| `restore-and-identity.php` | **1067 assertions passed** |
| `wordpress-integration.php` | **232 assertions passed** |
| `professional-kit.php` | Native Elementor JSON import, six rendered widgets, actual generated CSS: PASS |
| `legacy-upgrade.php` | Legacy core from `7518db9`, missing the formerly failing CPT method, loaded before the theme; callbacks detached and same-request demo import: PASS |
| Missing DOM extension (`php -n`) | Original defaults safely empty; original/custom render annotation returns input without fatal: PASS |
| PHP source lint | **47 theme/plugin/embedded PHP files passed**; test PHP files also linted |
| JS syntax | Main theme, generic widgets, professional widgets and browser test passed `node --check` |
| Source synchronization | Standalone includes identical to EmbeddedCore; bootstraps identical |

The three assertion suites total **1396 passing assertions**. Counts include
checks on registered control defaults and repeater identities; they are not a
claim of 1396 independent browser scenarios.

### Important assertions

- Every original widget kind and every original page-section variant retains
  byte-for-byte unedited HTML with default original-text settings.
- Edited original text is applied to the correct DOM location. Decorated heading
  leaves are independently editable; original pill and icon classes survive.
- Multiple sibling text edits survive XPath changes introduced by rich text or
  temporary editor wrappers. All targets are resolved before DOM mutation.
- Stale baselines, malformed paths/settings, scripts and event handlers are
  rejected or sanitized rather than overwriting unrelated source content.
- Native custom bindings cover heading/card title, role, stage number, manual
  price and period, navigation titles, legal heading and legal clause body.
  Shared visual classes cannot overwrite the role/number setting key.
- Six professional widgets are registered, render saved titles/records and repair
  missing repeater IDs. Grid controls only appear for widgets that have a grid.
- Score output is clamped to 0–100. Comparison retains a native accessible range.
- Actual original/custom Elementor CSS generation completes without missing `_id`
  warnings. Importing the optional professional JSON also generates real CSS.
- Existing custom renderer escaping, safe link relations, legal controls, CPTs
  and repeat-import post-count stability remain covered by the 232-check suite.

## Real Elementor/browser checks

The reproducible `professional-browser.cjs` ran successfully against the published
fixture page after the final source/style-control changes:

1. Login and open Elementor; close its parent-page floating Navigator overlay.
2. Click the original decorated headline text leaf, type replacement text and
   verify the native Elementor settings model changes.
3. Click the first professional service repeater title, type replacement text and
   verify `records.0.title` changes in the native model.
4. Publish using Elementor's native save command, then load the public page.
5. Both saved strings appear once; original headline pill is retained; six new
   widgets render; public `data-elementor-setting-key` attributes count is zero.
6. Comparison range 75 updates CSS to 75%; ArrowRight changes the value to 76;
   dragging across the image changes the native range value to 60.
7. Mobile viewport width 390 equals document scroll width 390; no horizontal page
   overflow. Collected uncaught browser exceptions: **none**.

A separate real editor run also clicked/typed/published a custom hero heading and
its first custom repeater card title. Both values persisted exactly once on the
public page; uncaught browser exceptions: none.

### Reproduction

Install Playwright/Chromium in your test environment. Populate the disposable
fixture with WP-CLI, note the returned page ID, and provide credentials only via
environment variables:

```bash
php wp-cli.phar eval-file tests/professional-fixture.php --path=/path/to/test-wordpress
# Set ZC_TEST_BASE_URL, ZC_TEST_PAGE_ID, ZC_TEST_ADMIN_USER,
# and ZC_TEST_ADMIN_PASSWORD securely in your shell/environment.
node tests/professional-browser.cjs
```

The browser test **edits and publishes** that dedicated page; do not point it at
an existing customer page. The fixture deliberately resets only its own named test
page. The kit test imports a library template and deletes that temporary template
in a finally block.

## Known upstream issue and untested boundaries

Elementor emits a PHP 8.4 implicit-nullable deprecation in its own
`modules/global-classes/atomic-global-styles.php:410`. It remains visible in CLI
logs and was not patched or hidden. It is distinct from the corrected Zarincode
missing-repeater-ID warning.

No live SEO data connector is implemented: audit values are manually supplied and
sample values are labelled as demonstrations. The optional upstream Xpro download
entry is not an installed runtime dependency and its current plugin was not
installed/compatibility-certified by these tests.

MySQL/MariaDB production hosting, Safari/Firefox, arbitrary optimizer/security
plugins, all PHP versions, independent penetration testing, asset-rights review,
legal review and formal ThemeForest/Zhaket acceptance are outside this test run.
No claim of universal compatibility or marketplace approval is made.
