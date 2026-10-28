<?php
/**
 * Native technical SEO metadata for pages no SEO plugin owns.
 *
 * Emits meta description, robots directives, archive canonicals, Open Graph,
 * Twitter cards and the front-page WebSite JSON-LD. Everything is suppressed
 * when Yoast/RankMath/AIOSEO is active so metadata is never duplicated.
 *
 * @package Zarincode
 */
namespace Zarincode\Theme\Core;
defined( 'ABSPATH' ) || exit;

/** Search-result metadata: description, canonical, robots, OG/Twitter, WebSite graph. */
final class Seo_Meta {

	/** Register emitters on wp_head plus the virtual robots.txt sitemap line. */
	public static function boot(): void {
		add_action( 'wp_head', [ self::class, 'meta' ], 5 );
		add_action( 'wp_head', [ self::class, 'website_schema' ], 45 );
		add_filter( 'wp_robots', [ self::class, 'robots' ] );
		add_action( 'wp_head', [ self::class, 'content_schema' ], 46 );
		add_filter( 'robots_txt', [ self::class, 'robots_txt' ], 10, 2 );
	}

	/** True when an SEO plugin already owns document metadata. */
	private static function owned(): bool {
		return \Zarincode\Theme\Compatibility\SEO::has_owner();
	}

	/** Search/error, protected and transactional pages must stay out of the index. */
	private static function noindex(): bool {
		return is_404() || is_search() || ( is_singular() && post_password_required() )
            || ( function_exists( 'is_cart' ) && is_cart() )
            || ( function_exists( 'is_checkout' ) && is_checkout() )
            || ( function_exists( 'is_account_page' ) && is_account_page() );
	}

	/** Self-referencing canonicals; never collapse archive page 2 onto page 1. */
	private static function self_url(): string {
        if ( is_paged() ) { return (string) get_pagenum_link( max( 1, (int) get_query_var( 'paged' ) ) ); }
        if ( is_home() && get_option( 'page_for_posts' ) ) { return (string) get_permalink( (int) get_option( 'page_for_posts' ) ); }
        if ( function_exists( 'is_shop' ) && is_shop() ) { return (string) get_permalink( wc_get_page_id( 'shop' ) ); }
        if ( is_day() ) { return get_day_link( (int) get_query_var( 'year' ), (int) get_query_var( 'monthnum' ), (int) get_query_var( 'day' ) ); }
        if ( is_month() ) { return get_month_link( (int) get_query_var( 'year' ), (int) get_query_var( 'monthnum' ) ); }
        if ( is_year() ) { return get_year_link( (int) get_query_var( 'year' ) ); }
		if ( is_singular() ) {
			return (string) get_permalink();
		}
		if ( is_category() || is_tag() || is_tax() ) {
			$link = get_term_link( get_queried_object() );
			return is_wp_error( $link ) ? home_url( '/' ) : (string) $link;
		}
		if ( is_author() ) {
			return (string) get_author_posts_url( (int) get_queried_object_id() );
		}
		if ( is_post_type_archive() ) {
			$link = get_post_type_archive_link( (string) ( is_array( get_query_var( 'post_type' ) ) ? ( array_values( get_query_var( 'post_type' ) )[0] ?? '' ) : get_query_var( 'post_type' ) ) );
			return $link ? (string) $link : home_url( '/' );
		}
		return home_url( '/' );
	}

	/**
	 * Document description: native excerpt first, then term description,
	 * finally the brand slogan. Trimmed to a search-snippet-safe length.
	 */
	private static function description(): string {
		$text = '';
		if ( is_singular() ) {
			$id   = (int) get_queried_object_id();
			$type = (string) get_post_type( $id );
			if ( 'product' === $type && function_exists( 'wc_get_product' ) ) {
				$product = wc_get_product( $id );
				$text    = $product ? (string) $product->get_short_description() : '';
			}
			if ( '' === trim( $text ) ) {
				$text = (string) get_post_field( 'post_excerpt', $id );
			}
			if ( '' === trim( $text ) ) {
				$content = (string) get_post_field( 'post_content', $id );
				if ( preg_match( '/<p[^>]*>(.+?)<\/p>/su', $content, $m ) ) {
					$text = $m[1];
				} else {
					$text = wp_strip_all_tags( $content );
				}
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			$text = $term && ! is_wp_error( $term ) ? (string) ( $term->description ?? '' ) : '';
		}
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $text ) ) ) ?? '' );
		if ( '' === $text ) {
			$text = (string) zarincode_get_option( 'brand_slogan', 'طراحی سایت، سئو، توسعه نرم‌افزار و تولید محتوای حرفه‌ای' );
		}
		if ( function_exists( 'mb_strlen' ) && 160 < mb_strlen( $text ) ) {
			$text = mb_substr( $text, 0, 157 ) . '…';
		}
		return $text;
	}

	/** Social/preview image: featured image, then site icon, then theme screenshot. */
	private static function image(): string {
		if ( is_singular() ) {
			$full = get_the_post_thumbnail_url( get_queried_object_id(), 'full' );
			if ( $full ) {
				return (string) $full;
			}
		}
		$icon = get_site_icon_url( 512 );
		if ( $icon ) {
			return (string) $icon;
		}
		return (string) get_theme_file_uri( 'screenshot.png' );
	}

	/** meta description, robots, archive canonical, Open Graph and Twitter cards. */
	public static function meta(): void {
		if ( self::owned() || is_embed() || is_admin() ) {
			return;
		}
		$noindex = self::noindex();
        // Never expose a protected page's excerpt in document metadata.
        if ( is_singular() && post_password_required() ) { return; }

		if ( ! is_404() ) {
			$desc = self::description();
			echo '<meta name="description" content="' . esc_attr( $desc ) . '" />' . "\n";
			echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
			echo '<meta property="og:locale" content="' . esc_attr( str_replace( '-', '_', (string) get_bloginfo( 'language' ) ) ?: 'fa_IR' ) . '" />' . "\n";
			$canonical = is_singular() ? (string) ( wp_get_canonical_url() ?: get_permalink() ) : self::self_url();
			if ( ! $noindex ) {
				// Core rel_canonical() covers singular requests only.
				if ( ! is_singular() ) {
					echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
				}
				$title = is_singular() ? wp_strip_all_tags( (string) get_the_title() ) : wp_get_document_title();
				$type  = is_singular( 'post' ) ? 'article' : ( is_singular( 'product' ) ? 'product' : 'website' );
				$image = self::image();
				echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
				echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
				echo '<meta property="og:type" content="' . esc_attr( $type ) . '" />' . "\n";
				echo '<meta property="og:url" content="' . esc_url( $canonical ) . '" />' . "\n";
				echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
				if ( 'article' === $type ) {
					echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '" />' . "\n";
					echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '" />' . "\n";
				}
				$card = $image ? 'summary_large_image' : 'summary';
				echo '<meta name="twitter:card" content="' . esc_attr( $card ) . '" />' . "\n";
				echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
				echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '" />' . "\n";
				if ( $image ) {
					echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
				}
			}
		}
	}

	/** Site-level WebSite graph on the front page when no plugin owns schema. */
	public static function website_schema(): void {
		if ( self::owned() || ! is_front_page() || is_paged() || is_embed() ) {
			return;
		}
		if ( ! zarincode_get_option( 'enable_schema_markup', true ) || ! \Zarincode\Theme\Compatibility\SEO::theme_schema_allowed() ) {
			return;
		}
		$data = [
			'@context'    => 'https://schema.org',
			'@type'       => 'WebSite',
			'@id'         => home_url( '/' ) . '#website',
			'url'         => home_url( '/' ),
			'name'        => get_bloginfo( 'name' ),
            'publisher'   => [ '@id' => home_url( '/' ) . '#organization' ],
			'inLanguage'  => (string) get_bloginfo( 'language' ) ?: 'fa-IR',

		];
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

    /** Merge with core's single robots tag instead of emitting a second conflicting tag. */
    public static function robots( array $robots ): array {
        if ( ! self::owned() && self::noindex() ) {
            unset( $robots['index'] );
            $robots['noindex'] = true;
        }
        return $robots;
    }

    /** Service/project entities use published content only, with no invented offers or ratings. */
    public static function content_schema(): void {
        if ( self::owned() || ! \Zarincode\Theme\Compatibility\SEO::theme_schema_allowed()
            || ! zarincode_get_option( 'enable_schema_markup', true ) || self::noindex()
            || ! is_singular( [ 'zc_service', 'zc_project' ] ) ) { return; }
        $id = (int) get_queried_object_id();
        $url = (string) get_permalink( $id );
        $service = 'zc_service' === get_post_type( $id );
        $data = [
            '@context' => 'https://schema.org', '@type' => $service ? 'Service' : 'CreativeWork',
            '@id' => $url . '#entity', 'url' => $url, 'name' => wp_strip_all_tags( get_the_title( $id ) ),
            'description' => self::description(), 'inLanguage' => get_bloginfo( 'language' ),
            'mainEntityOfPage' => $url,
            $service ? 'provider' : 'creator' => [ '@id' => home_url( '/' ) . '#organization' ],
        ];
        $image = get_the_post_thumbnail_url( $id, 'full' );
        if ( $image ) { $data['image'] = $image; }
        echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }

	/** Advertise the core sitemap to crawlers via the virtual robots.txt. */
	public static function robots_txt( string $output, bool $public ): string {
		if ( ! $public ) { return $output; }
        if ( self::owned() || ! get_option( 'blog_public' ) || ! apply_filters( 'wp_sitemaps_enabled', true ) ) { return $output; }
		$sitemap = home_url( '/wp-sitemap.xml' );
		if ( false !== strpos( $output, $sitemap ) ) {
			return $output;
		}
		return rtrim( $output, "\n" ) . "\nSitemap: $sitemap\n";
	}
}
