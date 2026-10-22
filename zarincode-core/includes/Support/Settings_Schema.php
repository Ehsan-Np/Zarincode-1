<?php
/**
 * Settings schema compatibility helpers.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Support;

defined( 'ABSPATH' ) || exit;
/** Pure, opt-in schema upgrade. Never writes posts/options or rewrites repeater IDs. */
final class Settings_Schema {
	public const VERSION = 1;
	/**
	 * Add the supported schema marker while preserving unknown settings and IDs.
	 *
	 * @param array<string,mixed> $settings Original widget settings.
	 * @return array<string,mixed>
	 * @throws \InvalidArgumentException When the schema version is unsupported.
	 */
	public static function upgrade( array $settings ): array {
		$version = array_key_exists( '_zc_settings_schema', $settings ) ? $settings['_zc_settings_schema'] : 0;
		if ( ! is_int( $version ) || $version < 0 || $version > self::VERSION ) {
			throw new \InvalidArgumentException( 'Unsupported settings schema; preserve the original record and use a compatible release.' );
		}
		$settings['_zc_settings_schema'] = self::VERSION;
		return $settings;
	}
}
