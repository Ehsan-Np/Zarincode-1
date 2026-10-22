<?php
namespace Zarincode\Theme\Compatibility;
defined( 'ABSPATH' ) || exit;
/** SEO plugins own general metadata/schema. No plugin files or internal graphs are modified. */
final class SEO {
    public static function has_owner(): bool {
        return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' );
    }
    public static function theme_schema_allowed(): bool {
        return ! self::has_owner() && (bool) apply_filters( 'zarincode_theme_schema_enabled', true );
    }
}
