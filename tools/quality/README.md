# Scoped critical-services quality harness

This is **not** whole-project Skill 3 certification. The checks cover only
the `Content/`, `Admin/` and `Features/` directories plus `Support/Settings_Schema.php`.

- WPCS: WordPress rules; PSR-4 filename convention replaces the conflicting
  WordPress class-file convention. No security sniff is disabled. A local,
  documented database-query sniff suppression permits the repository's single
  prepared, read-only aggregate: WordPress has no canonical-per-comment AVG API.
  No persistent cache is used, so moderation changes are immediately reflected.
- PHPStan: level 10, no baseline or ignored errors, WordPress 7.1 stubs.
- PHPUnit: actual disposable WordPress + MariaDB, including `wp_new_comment` and
  real nonce verification. Xdebug measures only the explicitly included files (coverage floor applies to this expanded eight-file scope).
- Coverage floor: 80% lines, enforced in CI. 100% line coverage does **not** imply
  complete branch coverage, fuzz testing, security certification or full-app coverage.

## Reproduce

1. `composer install --working-dir=tools/quality`
2. `composer --working-dir=tools/quality standards`
3. `composer --working-dir=tools/quality analyse`
4. Create an expendable WordPress installation in `/tmp/zc-*`, set
   `WP_ENVIRONMENT_TYPE=local` and `ZARINCODE_DISPOSABLE_QA=true`, activate the
   current companion plugin and WooCommerce 11.2.0, leave a stock theme active and Elementor absent.
   This database will be modified. Never use a real site.
5. `ZARINCODE_QA_WP=/tmp/zc-fixture XDEBUG_MODE=coverage tools/quality/vendor/bin/phpunit -c tools/quality/phpunit.xml --coverage-clover /tmp/zc-coverage.xml`
6. `python3 tools/quality/check-coverage.py /tmp/zc-coverage.xml 80`

The CI job is defined in `.github/workflows/engineering.yml`; addition of that job
does not establish that a remote GitHub Actions run has passed.
