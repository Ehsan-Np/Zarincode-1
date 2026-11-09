<?php
/**
 * Search-engine indexing for the front end: verification tags, robots
 * directives, robots.txt groups, sitemap advertising, entity/article/
 * breadcrumb JSON-LD and IndexNow (v4.8.0).
 *
 * Performance contract (the whole point of this file):
 *   • nothing here runs on a normal page view except a handful of string
 *     comparisons and, when the owner typed something, a couple of echoes;
 *   • zero extra database queries — settings arrive with the autoloaded option,
 *     the IndexNow key is read from the same array;
 *   • no files are written; `/llms.txt` and `/{key}.txt` are virtual routes, so
 *     no `.htaccess` rewrite and no disk I/O on publish;
 *   • IndexNow submissions are queued with `wp_schedule_single_event()`, so
 *     publishing a post never waits on an HTTP request;
 *   • everything is suppressed when Yoast/RankMath/AIOSEO owns metadata, so no
 *     tag is ever emitted twice.
 *
 * @package Zarincode\Runtime260\Seo
 */

namespace Zarincode\Runtime260\Seo;

defined( 'ABSPATH' ) || exit;

final class Indexing {

	public const QUERY_VAR = 'zarincode_indexnow';
	public const CRON_HOOK = 'zarincode_indexnow_ping';
	private const ENDPOINT  = 'https://api.indexnow.org/indexnow';

	/** Bots that produce citations / answers — always allowed unless policy = block. */
	public const RETRIEVAL_BOTS = array(
		'OAI-SearchBot',
		'ChatGPT-User',
		'PerplexityBot',
		'Perplexity-User',
		'Claude-SearchBot',
		'Claude-User',
		'Google-Extended',
	);

	/** Crawlers that only collect training data — blocked when policy = nofeed. */
	public const TRAINING_BOTS = array(
		'GPTBot',
		'ClaudeBot',
		'CCBot',
		'Applebot-Extended',
		'Meta-ExternalAgent',
		'Bytespider',
		'Amazonbot',
		'cohere-ai',
		'ImagesiftBot',
		'Diffbot',
	);

	/** True when another SEO plugin owns document metadata. */
	public static function owned(): bool {
		return class_exists( '\Zarincode\Theme\Compatibility\SEO' )
			? \Zarincode\Theme\Compatibility\SEO::has_owner()
			: false;
	}

	/** Registers hooks. Called once from Bootstrap. */
	public static function boot(): void {
		add_action( 'init', array( __CLASS__, 'routes' ) );
		add_action( 'template_redirect', array( __CLASS__, 'serve_indexnow_key' ), 0 );

		add_action( 'wp_head', array( __CLASS__, 'head' ), 1 );
		add_filter( 'wp_robots', array( __CLASS__, 'wp_robots' ), 20 );
		add_filter( 'robots_txt', array( __CLASS__, 'robots_txt' ), 12, 2 );

		add_action( self::CRON_HOOK, array( __CLASS__, 'ping' ), 10, 1 );
		add_action( 'transition_post_status', array( __CLASS__, 'on_transition' ), 10, 3 );
	}

	// ---------------------------------------------------------------------
	// Head
	// ---------------------------------------------------------------------

	/** Emits verification tags and any admin-provided indexing code. */
	public static function head(): void {
		if ( ! Store::on( 'enabled' ) || is_admin() || is_embed() ) {
			return;
		}
		if ( self::owned() ) {
			return;
		}

		$map = array(
			'search_console' => 'google-site-verification',
			'bing'           => 'msvalidate.01',
			'yandex'         => 'yandex-verification',
			'baidu'          => 'baidu-site-verification',
			'pinterest'      => 'p:domain_verify',
		);
		foreach ( $map as $field => $name ) {
			$value = self::verification_value( (string) Store::get( $field ) );
			if ( '' !== $value ) {
				echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( $value ) . '" />' . "\n";
			}
		}

		$extra = (string) Store::get( 'extra_head' );
		if ( '' !== trim( $extra ) ) {
			echo $extra . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- sanitised on save.
		}

		if ( Store::on( 'ai_sections' ) ) {
			self::breadcrumb_schema();
			self::article_schema();
			self::entity_schema();
		}
	}

	/**
	 * Accepts either the bare token or the whole `<meta …>` tag pasted from the
	 * webmaster console — the most common copy/paste mistake.
	 */
	public static function verification_value( string $raw ): string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}
		if ( str_contains( $raw, '<' ) && preg_match( '/content\s*=\s*["\']([^"\']+)["\']/i', $raw, $m ) ) {
			return trim( $m[1] );
		}
		return $raw;
	}

	// ---------------------------------------------------------------------
	// Robots directives
	// ---------------------------------------------------------------------

	/** Adds max-image-preview / max-snippet and honours the noindex switches. */
	public static function wp_robots( array $robots ): array {
		if ( ! Store::on( 'enabled' ) || self::owned() ) {
			return $robots;
		}
		$preview = (string) Store::get( 'max_image_preview', 'large' );
		if ( 'large' !== $preview ) {
			$robots['max-image-preview'] = $preview;
		}
		$snippet = (string) Store::get( 'max_snippet', '-1' );
		if ( '-1' !== $snippet && '' !== $snippet ) {
			$robots['max-snippet'] = $snippet;
		}
		if ( self::should_noindex() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}

	/** Archive/utility views the owner may want out of the index. */
	public static function should_noindex(): bool {
		if ( is_404() || is_search() ) {
			return true;
		}
		if ( Store::on( 'noindex_media' ) && is_attachment() ) {
			return true;
		}
		if ( Store::on( 'noindex_date' ) && is_date() ) {
			return true;
		}
		if ( Store::on( 'noindex_author' ) && is_author() ) {
			return true;
		}
		if ( Store::on( 'noindex_tags' ) && is_tag() ) {
			return true;
		}
		if ( Store::on( 'noindex_search' ) && is_search() ) {
			return true;
		}
		// Paginated archives: keep them crawlable but out of the index to avoid
		// near-duplicate listing pages (pages 2..n still hold links).
		if ( Store::on( 'noindex_paged_404' ) && is_paged() && is_home() ) {
			return true;
		}
		return false;
	}

	// ---------------------------------------------------------------------
	// robots.txt
	// ---------------------------------------------------------------------

	/** Publishes the per-bot groups plus the sitemap line. */
	public static function robots_txt( string $output, string $path ): string {
		unset( $path );
		if ( ! Store::on( 'enabled' ) ) {
			return $output;
		}

		$policy = (string) Store::get( 'ai_bot_policy', 'all' );
		$lines  = array();

		if ( 'block' === $policy ) {
			$lines[] = '# AI crawlers — intentionally blocked (policy: block)';
			$lines[] = self::group( array_merge( self::RETRIEVAL_BOTS, self::TRAINING_BOTS ), 'Disallow: /' );
		} elseif ( 'nofeed' === $policy ) {
			$lines[] = '# AI retrieval crawlers — allowed, they produce citations';
			$lines[] = self::group( self::RETRIEVAL_BOTS, 'Allow: /' );
			$lines[] = '# AI training crawlers — opted out (policy: nofeed)';
			$lines[] = self::group( self::TRAINING_BOTS, 'Disallow: /' );
		} else {
			$lines[] = '# AI crawlers — all allowed: maximum visibility in AI answers';
			$lines[] = self::group( array_merge( self::RETRIEVAL_BOTS, self::TRAINING_BOTS ), 'Allow: /' );
		}

		$lines[] = '# Crawl-delay is not honoured by Google/Bing; omitted on purpose.';

		$out = rtrim( $output, "\n" ) . "\n\n" . implode( "\n", $lines ) . "\n";

		$owner_rules = trim( (string) Store::get( 'robots_extra' ) );
		if ( '' !== $owner_rules ) {
			$out .= "\n# Custom rules\n" . $owner_rules . "\n";
		}

		if ( Store::on( 'sitemap' ) ) {
			$sitemap = home_url( '/wp-sitemap.xml' );
			if ( false === strpos( $out, $sitemap ) ) {
				$out .= "\nSitemap: " . $sitemap . "\n";
			}
		}
		return $out;
	}

	/** One robots.txt group for several user agents. */
	private static function group( array $agents, string $rule ): string {
		$block = array();
		foreach ( $agents as $agent ) {
			$block[] = 'User-agent: ' . $agent;
		}
		$block[] = $rule;
		return implode( "\n", $block );
	}

	// ---------------------------------------------------------------------
	// Structured data
	// ---------------------------------------------------------------------

	/** Organization node — only when the theme's own entity schema is off. */
	private static function entity_schema(): void {
		if ( ! Store::on( 'org_schema' ) ) {
			return;
		}
		if ( self::theme_prints_entity() ) {
			return;
		}

		$logo = (string) Store::get( 'org_logo' );
		if ( '' === $logo && function_exists( 'get_site_icon_url' ) ) {
			$logo = (string) get_site_icon_url( 512 );
		}
		$data = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'@id'      => home_url( '/' ) . '#organization',
			'name'     => get_bloginfo( 'name' ),
			'url'      => home_url( '/' ),
		);
		if ( '' !== $logo ) {
			$data['logo'] = array( '@type' => 'ImageObject', 'url' => $logo );
		}
		$desc = trim( (string) Store::get( 'ai_summary' ) );
		if ( '' !== $desc ) {
			$data['description'] = $desc;
		}
		$same = array_values( array_filter( preg_split( '/[\r\n]+/', (string) Store::get( 'org_same_as' ) ) ?: array() ) );
		if ( $same ) {
			$data['sameAs'] = $same;
		}
		self::print_json( $data );
	}

	/** True when the theme emits its own Organization/BlogPosting graph. */
	private static function theme_prints_entity(): bool {
		return function_exists( 'zarincode_get_option' )
			&& zarincode_get_option( 'enable_schema_markup', true )
			&& class_exists( '\Zarincode\Theme\Compatibility\SEO' )
			&& \Zarincode\Theme\Compatibility\SEO::theme_schema_allowed();
	}

	/** BreadcrumbList for singular pages and archives with real ancestors. */
	private static function breadcrumb_schema(): void {
		if ( ! Store::on( 'breadcrumb_schema' ) || is_front_page() || is_404() ) {
			return;
		}
		$items = array( array( get_bloginfo( 'name' ), home_url( '/' ) ) );

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof \WP_Post ) {
				$ancestors = array_reverse( get_post_ancestors( $post ) );
				foreach ( $ancestors as $ancestor ) {
					$items[] = array( get_the_title( $ancestor ), (string) get_permalink( $ancestor ) );
				}
				$items[] = array( get_the_title( $post ), (string) get_permalink( $post ) );
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			$link = $term ? get_term_link( $term ) : '';
			if ( $term && ! is_wp_error( $link ) ) {
				$items[] = array( (string) $term->name, (string) $link );
			}
		} elseif ( is_post_type_archive() ) {
			$type = (string) get_query_var( 'post_type' );
			$link = get_post_type_archive_link( $type );
			if ( $link ) {
				$items[] = array( (string) post_type_archive_title( '', false ), (string) $link );
			}
		} elseif ( is_home() ) {
			$blog = (int) get_option( 'page_for_posts' );
			$items[] = array( $blog ? get_the_title( $blog ) : 'بلاگ', $blog ? (string) get_permalink( $blog ) : home_url( '/' ) );
		}

		if ( count( $items ) < 2 ) {
			return;
		}
		$list = array();
		foreach ( $items as $i => $item ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => wp_strip_all_tags( $item[0] ),
				'item'     => $item[1],
			);
		}
		self::print_json(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $list,
			)
		);
	}

	/** BlogPosting for single articles — the shape AI answers quote. */
	private static function article_schema(): void {
		if ( ! Store::on( 'article_schema' ) || ! is_singular( 'post' ) ) {
			return;
		}
		// The theme already prints a richer BlogPosting graph (author URL, publisher
		// logo, optional aggregateRating) on single posts: never emit a second one.
		if ( self::theme_prints_entity() && in_array( 'Zarincode\Theme\Core\Article_Ratings', get_declared_classes(), true ) ) {
			return;
		}
		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		$data = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'BlogPosting',
			'@id'              => (string) get_permalink( $post ) . '#article',
			'mainEntityOfPage' => array( '@type' => 'WebPage', '@id' => (string) get_permalink( $post ) ),
			'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
			'datePublished'    => get_post_time( 'c', true, $post ),
			'dateModified'     => get_post_modified_time( 'c', true, $post ),
			'inLanguage'       => str_replace( '-', '_', (string) get_bloginfo( 'language' ) ) ?: 'fa_IR',
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ) ?: get_bloginfo( 'name' ),
			),
			'publisher'        => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			),
		);
		$image = get_the_post_thumbnail_url( $post, 'full' );
		if ( $image ) {
			$data['image'] = $image;
		}
		$desc = (string) get_post_field( 'post_excerpt', $post );
		if ( '' === trim( $desc ) ) {
			$desc = wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 40, '' );
		}
		if ( '' !== trim( $desc ) ) {
			$data['description'] = wp_strip_all_tags( $desc );
		}
		self::print_json( $data );
	}

	/** Shared JSON-LD printer. */
	private static function print_json( array $data ): void {
		echo '<script type="application/ld+json">'
			. wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			. '</script>' . "\n";
	}

	// ---------------------------------------------------------------------
	// IndexNow
	// ---------------------------------------------------------------------

	/** Rewrite rules for the key file (no real file is created). */
	public static function routes(): void {
		$key = (string) Store::get( 'indexnow_key' );
		if ( Store::on( 'indexnow' ) && '' !== $key ) {
			add_rewrite_rule( '^' . preg_quote( $key, '/' ) . '\.txt$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
		}
		add_rewrite_tag( '%' . self::QUERY_VAR . '%', '1' );
	}

	/** Serves `/{key}.txt` = the key, exactly as the protocol requires. */
	public static function serve_indexnow_key(): void {
		$key = (string) Store::get( 'indexnow_key' );
		if ( '' === $key || ! Store::on( 'indexnow' ) ) {
			return;
		}
		$request = (string) parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
		$hit     = '1' === (string) get_query_var( self::QUERY_VAR )
			|| trailingslashit( $request ) === '/' . $key . '.txt';

		if ( ! $hit ) {
			return;
		}
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Cache-Control: public, max-age=86400' );
		header( 'X-Content-Type-Options: nosniff' );
		echo $key; // phpcs:ignore WordPress.Security.EscapeOutput -- plain key, sanitised on save.
		exit;
	}

	/** Generates a protocol-compliant key on demand (16 random bytes → 32 chars). */
	public static function generate_key(): string {
		return bin2hex( random_bytes( 16 ) );
	}

	/** Queues a submission instead of blocking the request that published. */
	public static function on_transition( string $new_status, string $old_status, $post ): void {
		if ( 'publish' !== $new_status || $old_status === $new_status ) {
			return;
		}
		if ( ! Store::on( 'indexnow' ) || '' === (string) Store::get( 'indexnow_key' ) ) {
			return;
		}
		if ( ! $post instanceof \WP_Post || wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, array( 'post', 'page', 'zc_service', 'zc_project', 'product' ), true ) ) {
			return;
		}
		$urls = array( (string) get_permalink( $post ) );
		if ( is_post_type_hierarchical( $post->post_type ) ) {
			$parent = (int) $post->post_parent;
			while ( $parent ) {
				$urls[] = (string) get_permalink( $parent );
				$parent = (int) get_post_field( 'post_parent', $parent );
			}
		}
		$urls[] = home_url( '/' );
		$urls   = array_values( array_unique( array_filter( $urls ) ) );

		wp_schedule_single_event( time() + 60, self::CRON_HOOK, array( $urls ) );
	}

	/**
	 * Sends the queued URLs. 200/202 = accepted (not an indexing verdict).
	 * One retry, then the response is stored for the admin screen.
	 */
	public static function ping( $urls ): void {
		$urls = is_array( $urls ) ? array_filter( array_map( 'strval', $urls ) ) : array();
		$key  = (string) Store::get( 'indexnow_key' );
		if ( ! $urls || '' === $key || ! Store::on( 'indexnow' ) ) {
			return;
		}

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout'     => 10,
				'blocking'    => true,
				'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'        => wp_json_encode(
					array(
						'host'        => wp_parse_url( home_url(), PHP_URL_HOST ),
						'key'         => $key,
						'keyLocation' => home_url( '/' . $key . '.txt' ),
						'urlList'     => array_slice( array_values( $urls ), 0, 10000 ),
					)
				),
			)
		);

		$code    = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		$history = (array) get_option( 'zarincode_indexnow_log', array() );
		array_unshift(
			$history,
			array(
				'time'  => time(),
				'code'  => $code,
				'count' => count( $urls ),
				'note'  => is_wp_error( $response ) ? $response->get_error_message() : self::code_note( $code ),
			)
		);
		update_option( 'zarincode_indexnow_log', array_slice( $history, 0, 20 ), false );

		if ( 0 === $code ) {
			wp_schedule_single_event( time() + 900, self::CRON_HOOK, array( array_values( $urls ) ) );
		}
	}

	/** Human explanation of an IndexNow response code. */
	public static function code_note( int $code ): string {
		switch ( $code ) {
			case 200:
				return 'پذیرفته شد (۲۰۰) — URL دریافت شد.';
			case 202:
				return 'پذیرفته شد (۲۰۲) — کلید در حال اعتبارسنجی است.';
			case 400:
				return 'درخواست نامعتبر (۴۰۰) — قالب JSON یا URL مشکل دارد.';
			case 403:
				return 'کلید رد شد (۴۰۳) — فایل کلید در دسترس نیست یا با هاست نمی‌خواند.';
			case 422:
				return 'URL یا هاست نامعتبر (۴۲۲) — هاست باید با دامنهٔ سایت یکی باشد.';
			case 429:
				return 'محدودیت نرخ (۴۲۹) — بعداً دوباره تلاش می‌شود.';
			case 0:
				return 'اتصال برقرار نشد — زمان‌بندی برای تلاش مجدد انجام شد.';
			default:
				return 'پاسخ غیرمنتظره (' . $code . ')';
		}
	}

	/** Submits a URL list immediately (used by the admin button). */
	public static function submit_now( array $urls ): array {
		self::ping( $urls );
		$log = (array) get_option( 'zarincode_indexnow_log', array() );
		return $log[0] ?? array( 'code' => 0, 'note' => 'نتیجه‌ای ثبت نشد.' );
	}
}
