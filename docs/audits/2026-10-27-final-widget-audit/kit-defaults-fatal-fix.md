# Errata: Kit_Defaults public-request fatal — reproduction, fix, verification (2026-10-27)

Same-day erratum to the `2026-10-27-final-widget-audit` evidence set. A user installation reported the
theme fataling every plain page view after activation:

```
Fatal error: Uncaught Exception: Access denied.
  in .../wp-content/plugins/elementor/core/settings/page/manager.php on line 105
Called from
  .../zarincode/inc/Elementor/Kit_Defaults.php(77)
  -> elementor\Core\Base\Document->update_settings()
  -> do_action('elementor/init')
```

## Root cause (verified against pinned Elementor 4.3.2 source)

1. `Document::update_settings()` → `save_settings()` → `Page\Manager::ajax_before_save_settings()`
   (`elementor/core/settings/page/manager.php`, ~line 105) throws `Access denied.` when
   `!Utils::is_wp_cli() && !current_user_can( 'edit_post', $id )` — i.e. for **every logged-out
   (and every non-editor) web request**, because the kit post can never be edited anonymously.
2. The old `Kit_Defaults::apply()` ran on `elementor/init` (fired during every front-end load) and
   called `update_settings()`, so the exception was uncaught on public requests: fatal before the
   idempotency flag could be written → fatal on *every* subsequent request.
3. Local WP-CLI QA never caught it: `Utils::is_wp_cli()` bypasses exactly that guard under CLI, so
   the same code path always succeeded in fixtures.

## Fix (`zarincode/inc/Elementor/Kit_Defaults.php`)

* Persistence moved to the **capability-free page-settings storage** Elementor itself uses:
  `Document::get_meta( Page\Manager::META_KEY )` / `Document::update_meta( …, wp_slash( $settings ) )`
  — byte-identical storage to `Page\Manager::save_settings_to_db()`, no capability guard, no AJAX
  layer. `Document::update_settings()` is no longer called anywhere.
* Fresh kits (no stored `system_colors` meta) fall back to the read-only control defaults via
  `get_settings()`; stored rows always win, so author customisations are never overwritten.
* Factory-only migration unchanged: accent `#61CE70→#202227`, primary `#6EC1E4→#1F6B8F`,
  text `#7A7A7A→#5F6368`, case-insensitive exact match, one flag option
  (`zc_elementor_kit_defaults_kit_id`) compared numerically.
* After a successful write: `refresh_post()`, `files_manager->clear_cache()` (forces kit CSS
  regeneration), then the flag.
* The entire routine runs inside `catch ( \Throwable )` — a palette migration can never fatal a
  public request again; failures simply retry on a later request.

## Reproduction (before fix)

Disposable fixture: WP 7.1.3 + Elementor 4.3.2 + zarincode (kit id 4), PHP 8.4 built-in server,
logged-out `GET /`:

```
HTTP 200 with body: Fatal error: Uncaught Exception: Access denied. …
elementor/core/settings/page/manager.php … trace contains zarincode/inc/Elementor/Kit_Defaults.php
```

Identical to the user-reported stack (`manager.php:105` via `Kit_Defaults.php(77)`).

## Verification (after fix) — all over real HTTP, logged out

| Step | Result |
| --- | --- |
| Clean state (flag deleted, kit `_elementor_page_settings` meta deleted, Elementor CSS purged) confirmed by direct SQL | flag absent, meta absent |
| `GET /?page_id=12` with final code | **HTTP 200, 0 `Fatal error`/`Access denied`** |
| Direct SQL after that single request (no CLI in between) | flag `4` written, meta rows `#1F6B8F` / `#5F6368` / `#202227` written |
| Elementor page view | kit CSS file regenerated: `.elementor-kit-4{--e-global-color-primary:#1F6B8F;…}` |
| Repeat GETs ×3 | HTTP 200, meta byte-identical (idempotent), flag stays `4` |
| Author customisation scenario (primary set to `#ABCDEF`, flag reset) | 200, primary stays `#ABCDEF`, other rows stay migrated, flag set |
| Server log during all of the above | only pre-existing Elementor Pro `get_main_id()` vendor notice; zero fatals |
| WP-CLI runs (fixtures, tooling) | unaffected (`is_wp_cli` path no longer needed by our code at all) |

## Quality battery after the fix

* PHP lint: 82 files, 0 errors.
* WPCS (10 scoped files incl. `Kit_Defaults.php`): 0 errors / 0 warnings.
* PHPStan level 10 (10 files incl. `Kit_Defaults.php`): 0 / 0.
* PHPUnit: **37 tests / 209 assertions OK** (includes 3 new pure `KitDefaultsTest` cases:
  factory migration map, author/malformed-row preservation, idempotency).
* Coverage: **507/573 = 88.48 %** ≥ 80 floor (denominator grew because `Kit_Defaults.php`
  is now inside the measured scope).
* Audit-contract node tests 6/6, packaging guards 6/6, architecture pass,
  multilingual none+polylang 6/6 each, skill-v3 unit pass.
* Browser/axe suites unchanged: this erratum touches PHP only — no markup, CSS or JS shipped.

## Emergency unblock (for an already-bricked site)

1. Set option `zc_elementor_kit_defaults_kit_id` = the site's kit id (e.g. `6`) in `wp_options`
   (phpMyAdmin: `UPDATE wp_options SET option_value='6' WHERE option_name='zc_elementor_kit_defaults_kit_id';`
   or `wp option update zarincode_kit_defaults… 6`) — the flag makes `apply()` return before any
   write, which is enough to restore pages **even with the old file still in place**.
2. Replace `wp-content/themes/zarincode/inc/Elementor/Kit_Defaults.php` with the fixed file from
   the new package (step 1 is optional afterwards, but harmless).

`commercial_approved` and `final_skill3_approved` remain `false`; this erratum does not change
any other gate.
