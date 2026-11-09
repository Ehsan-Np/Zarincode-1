<?php
/**
 * `/llms.txt` — a curated, cached map of the site for AI agents (v4.8.0).
 *
 * Research note (2026): no search-integrated AI crawler relies on llms.txt yet,
 * and the standard has no governing body, but coding agents and some retrieval
 * systems read it. It therefore has to be cheap, honest and curated:
 *   • built from real, published content only (no invented pages, no duplicates);
 *   • capped (≈40 links) instead of dumping a sitemap;
 *   • cached in a transient and rebuilt only when content changes;
 *   • served as plain text by a virtual route — no template, no theme, no query
 *     overhead on normal page views.
 *
 * @package Zarincode\Runtime260\Seo
 */

namespace Zarincode\Runtime260\Seo;

defined( 'ABSPATH' ) || exit;

final class Llms {

	public const CACHE_KEY = 'zarincode_llms_txt';
	public const QUERY_VAR = 'zarincode_llms';
	private const TTL     = 12 * HOUR_IN_SECONDS;
	private const MAX_LINKS = 40;

	/** Registers the virtual route. Called once from Bootstrap. */
	public static function boot(): void {
		add_action( 'init', array( __CLASS__, 'add_route' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ), 0 );
		// Rebuild when content actually changes (cheap, no front-end cost).
		foreach ( array( 'save_post', 'deleted_post', 'transition_post_status', 'edited_term' ) as $hook ) {
			add_action( $hook, static function () {
				delete_transient( self::CACHE_KEY );
			}, 10, 0 );
		}
	}

	/** Adds /llms.txt (plus the optional /llms-full.txt alias) as a rewrite rule. */
	public static function add_route(): void {
		if ( ! Store::on( 'llms' ) ) {
			return;
		}
		add_rewrite_rule( '^llms\.txt$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?' . self::QUERY_VAR . '=2', 'top' );
		add_rewrite_tag( '%' . self::QUERY_VAR . '%', '([12])' );
	}

	/** Serves the file early, before any template is loaded. */
	public static function maybe_render(): void {
		$mode = (int) get_query_var( self::QUERY_VAR );
		if ( ! $mode ) {
			// Also answer the literal path when rewrites are disabled.
			$path = (string) parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
			if ( ! in_array( trailingslashit( $path ), array( '/llms.txt', '/llms-full.txt' ), true ) ) {
				return;
			}
			$mode = str_contains( $path, 'llms-full' ) ? 2 : 1;
		}
		if ( ! Store::on( 'llms' ) || is_admin() ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex' );

		echo self::body( 2 === $mode ); // phpcs:ignore WordPress.Security.EscapeOutput -- plain text, escaped while building.
		exit;
	}

	/** Full file content, cached. */
	public static function body( bool $full = false ): string {
		$key    = self::CACHE_KEY . ( $full ? '_full' : '' );
		$cached = get_transient( $key );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$text = $full ? self::build_full() : self::build();
		set_transient( $key, $text, self::TTL );
		return $text;
	}

	/** One-line description helper: strips markup/shortcodes and keeps it short. */
	private static function line( string $text, int $limit = 180 ): string {
		// `strip_shortcodes()` first: a page built from [zarincode_widget …] must not
		// describe itself with its own markup.
		$text = strip_shortcodes( $text );
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) ?? '' );
		$text = str_replace( array( "\r", "\n", '|' ), ' ', $text );
		return mb_strlen( $text ) > $limit ? mb_substr( $text, 0, $limit - 1 ) . '…' : $text;
	}

	/** Markdown table row. */
	private static function row( string $title, string $url, string $note ): string {
		return '| [' . $title . '](' . $url . ') | ' . ( '' !== $note ? $note : '—' ) . ' |';
	}

	/** Builds the curated document. */
	private static function build(): string {
		$site  = get_bloginfo( 'name' );
		$desc  = (string) Store::get( 'ai_summary' );
		if ( '' === trim( $desc ) ) {
			$desc = (string) get_bloginfo( 'description' );
		}
		$desc = self::line( $desc, 300 );

		$out   = array();
		$out[] = '# ' . $site;
		$out[] = '';
		if ( '' !== $desc ) {
			$out[] = '> ' . $desc;
			$out[] = '';
		}
		$out[] = '<!-- Generated ' . gmdate( 'Y-m-d' ) . ' by ' . $site . '. Curated list, not a sitemap. -->';
		$out[] = '';

		$links = 0;

		// 1) Core pages.
		$core = array();
		$core[] = array( $site . ' — صفحه اصلی', home_url( '/' ), self::line( (string) get_bloginfo( 'description' ), 120 ) );

		foreach ( self::page_candidates() as $page ) {
			$core[] = array( get_the_title( $page ), (string) get_permalink( $page ), self::short_excerpt( $page, 150 ) );
		}
		if ( $core ) {
			$out[] = '## صفحات کلیدی';
			$out[] = '';
			$out[] = '| صفحه | توضیح |';
			$out[] = '| --- | --- |';
			foreach ( array_slice( $core, 0, 12 ) as $item ) {
				$out[] = self::row( self::line( $item[0], 90 ), $item[1], $item[2] );
				$links++;
			}
			$out[] = '';
		}

		// 2) Services (CPT) — what the business actually sells.
		$services = get_posts( array( 'post_type' => 'zc_service', 'numberposts' => 12, 'post_status' => 'publish' ) );
		if ( $services ) {
			$out[] = '## خدمات';
			$out[] = '';
			$out[] = '| خدمت | توضیح |';
			$out[] = '| --- | --- |';
			foreach ( $services as $service ) {
				$out[] = self::row( self::line( get_the_title( $service ), 90 ), (string) get_permalink( $service ), self::short_excerpt( $service, 150 ) );
				$links++;
			}
			$out[] = '';
		}

		// 3) Case studies.
		$projects = get_posts( array( 'post_type' => 'zc_project', 'numberposts' => 8, 'post_status' => 'publish' ) );
		if ( $projects ) {
			$out[] = '## نمونه‌کارها';
			$out[] = '';
			$out[] = '| پروژه | توضیح |';
			$out[] = '| --- | --- |';
			foreach ( $projects as $project ) {
				$out[] = self::row( self::line( get_the_title( $project ), 90 ), (string) get_permalink( $project ), self::short_excerpt( $project, 150 ) );
				$links++;
			}
			$out[] = '';
		}

		// 4) Products (WooCommerce) — priced, citable facts.
		if ( function_exists( 'wc_get_products' ) && $links < self::MAX_LINKS ) {
			$products = wc_get_products( array( 'status' => 'publish', 'limit' => 8, 'orderby' => 'date', 'order' => 'DESC' ) );
			if ( $products ) {
				$out[] = '## محصولات';
				$out[] = '';
				$out[] = '| محصول | قیمت |';
				$out[] = '| --- | --- |';
				foreach ( $products as $product ) {
					$price = '' !== $product->get_price() ? wp_strip_all_tags( $product->get_price_html() ) : '—';
					$out[] = self::row( self::line( $product->get_name(), 90 ), (string) get_permalink( $product->get_id() ), self::line( $price, 60 ) );
					$links++;
				}
				$out[] = '';
			}
		}

		// 5) Latest articles — freshness matters for AI answers.
		$posts = get_posts( array( 'numberposts' => 8, 'post_status' => 'publish' ) );
		if ( $posts ) {
			$out[] = '## تازه‌ترین مقالات';
			$out[] = '';
			$out[] = '| مقاله | تاریخ به‌روزرسانی |';
			$out[] = '| --- | --- |';
			foreach ( $posts as $post ) {
				$out[] = self::row(
					self::line( get_the_title( $post ), 90 ),
					(string) get_permalink( $post ),
					(string) get_post_modified_time( 'Y-m-d', true, $post )
				);
				$links++;
			}
			$out[] = '';
		}

		// 6) Custom section pasted by the site owner.
		$extra = trim( (string) Store::get( 'ai_extra' ) );
		if ( '' !== $extra ) {
			$out[] = '## یادداشت‌های سایت';
			$out[] = '';
			$out[] = $extra;
			$out[] = '';
		}

		// 7) Policy + contact: the parts an agent needs to quote you correctly.
		$policy = array();
		$contact = (string) Store::get( 'ai_contact' );
		if ( '' !== $contact ) {
			$policy[] = '- تماس: ' . $contact;
		}
		$license = (string) Store::get( 'ai_license_url' );
		if ( '' !== $license ) {
			$policy[] = '- شرایط استفاده از محتوا: ' . $license;
		}
		$policy[] = '- نقل‌قول با ذکر منبع «' . $site . '» (' . home_url( '/' ) . ') مجاز است.';
		$policy[] = '- نقشهٔ سایت: ' . home_url( '/wp-sitemap.xml' );
		$out[] = '## شرایط استفاده';
		$out[] = '';
		$out   = array_merge( $out, $policy );
		$out[] = '';

		return implode( "\n", $out );
	}

	/** Expanded variant: adds the full text of the newest articles. */
	private static function build_full(): string {
		$out   = array( self::build() );
		$out[] = '';
		$out[] = '## متن کامل مقالات';
		foreach ( get_posts( array( 'numberposts' => 5, 'post_status' => 'publish' ) ) as $post ) {
			$body  = wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) );
			$body  = trim( preg_replace( '/\s+/u', ' ', $body ) ?? '' );
			$out[] = '';
			$out[] = '### ' . get_the_title( $post );
			$out[] = (string) get_permalink( $post );
			$out[] = '';
			$out[] = mb_substr( $body, 0, 6000 );
		}
		return implode( "\n", $out );
	}

	/** Key pages: the ones that are published and meaningful for an agent. */
	private static function page_candidates(): array {
		$candidates = array();
		$front      = (int) get_option( 'page_on_front' );
		$slugs      = array( 'services', 'projects-portfolio', 'shop', 'about-us', 'contact-us', 'pricing', 'blog' );

		foreach ( $slugs as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && 'publish' === $page->post_status && (int) $page->ID !== $front ) {
				$candidates[ $page->ID ] = $page;
			}
		}
		$shop = (int) get_option( 'woocommerce_shop_page_id' );
		if ( $shop && 'publish' === get_post_status( $shop ) && ! isset( $candidates[ $shop ] ) ) {
			$candidates[ $shop ] = get_post( $shop );
		}
		$blog = (int) get_option( 'page_for_posts' );
		if ( $blog && 'publish' === get_post_status( $blog ) && ! isset( $candidates[ $blog ] ) ) {
			$candidates[ $blog ] = get_post( $blog );
		}
		return array_values( array_filter( $candidates ) );
	}

	/** Short, single-line excerpt. */
	private static function short_excerpt( $post, int $limit ): string {
		$excerpt = (string) ( $post->post_excerpt ?? '' );
		if ( '' === trim( $excerpt ) ) {
			$excerpt = (string) ( $post->post_content ?? '' );
		}
		return self::line( $excerpt, $limit );
	}
}
