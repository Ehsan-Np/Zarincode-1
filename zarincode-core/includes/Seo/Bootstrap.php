<?php
/**
 * Wires the indexing / AI-visibility module (v4.8.0).
 *
 * Kept deliberately thin: each part registers its own hooks, the front end only
 * gains a few string compares, and the admin screens never load on a public
 * request.
 *
 * @package Zarincode\Runtime260\Seo
 */

namespace Zarincode\Runtime260\Seo;

defined( 'ABSPATH' ) || exit;

final class Bootstrap {

	/** Called once from the plugin constructor. */
	public static function boot(): void {
		Indexing::boot();
		Llms::boot();

		if ( is_admin() ) {
			Settings_Page::boot();
		}

		// Rebuild the virtual routes only when the rules they depend on change.
		add_action( 'init', array( __CLASS__, 'maybe_flush_rules' ), 99 );
	}

	/** Flushes rewrite rules once after a change that affects /llms.txt or the key file. */
	public static function maybe_flush_rules(): void {
		$hash = md5(
			implode(
				'|',
				array(
					(string) Store::get( 'llms' ),
					(string) Store::get( 'indexnow' ),
					(string) Store::get( 'indexnow_key' ),
				)
			)
		);
		if ( (string) Store::get( 'rules_hash' ) === $hash ) {
			return;
		}
		Llms::add_route();
		Indexing::routes();
		flush_rewrite_rules( false );
		// Stored inside the autoloaded option on purpose: zero extra queries.
		Store::remember( 'rules_hash', $hash );
	}
}
