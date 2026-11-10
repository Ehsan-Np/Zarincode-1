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
		add_filter( 'robots_txt', [ self::class, 'robots_txt' ], 10, 2 );
		// Robots directives go through the core API, never a second meta tag.
		add_filter( 'wp_robots', [ self::class, 'robots_filter' ], 10 );
		// A static front page otherwise gets the bare site name as its <title>.
		add_filter( 'document_title_parts', [ self::class, 'title_parts' ], 10 );
	}

	/** True when an SEO plugin already owns document metadata. */
	private static function owned(): bool {
		return \Zarincode\Theme\Compatibility\SEO::has_owner();
	}

	/** Pages that must stay out of the index: search results and 404s. */
	private static function noindex(): bool {
		return is_404() || is_search();
	}

	/** Canonical URL of the current request without pagination. */
	private static function self_url(): string {
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
			$link = get_post_type_archive_link( (string) get_query_var( 'post_type' ) );
			return $link ? (string) $link : home_url( '/' );
		}
		return home_url( '/' );
	}

	/**
	 * Document description: native excerpt first, then term description,
	 * finally the brand slogan. Trimmed to a search-snippet-safe length.
	 */
	/**
	 * The post id a request should take its metadata from.
	 *
	 * Normally the queried object. The WooCommerce shop is a *post type
	 * archive*, so it has no queried object id — but it is still a page the
	 * owner writes a title and description for, so it is resolved explicitly.
	 */
	private static function page_id(): int {
		if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'wc_get_page_id' ) ) {
			return (int) wc_get_page_id( 'shop' );
		}
		return is_singular() ? (int) get_queried_object_id() : 0;
	}

	private static function description(): string {
		// An editor (or the demo importer) can write an exact description per
		// page. It always wins: a hand-written sentence beats anything derived.
		$explicit_id = self::page_id();
		if ( $explicit_id ) {
			$explicit = trim( (string) get_post_meta( $explicit_id, '_zc_seo_description', true ) );
			if ( '' !== $explicit ) {
				return self::shorten( wp_strip_all_tags( $explicit ), 158 );
			}
		}
		$text = '';
		if ( is_singular() ) {
			// Strip shortcodes first: a page built from [zarincode_widget …] would
			// otherwise expose its own markup as the search snippet.
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
				$content = strip_shortcodes( (string) get_post_field( 'post_content', $id ) );
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
		// An Elementor page keeps its words inside `_elementor_data`, not in
		// post_content — without this every Elementor page fell back to the site
		// slogan and the whole site shipped the same 66-character description.
		if ( '' === trim( $text ) && is_singular() ) {
			$text = self::elementor_text( (int) get_queried_object_id() );
		}
		// Drop any shortcode residue ("[zarincode_widget …]") before trimming.
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $text ) ) ) ?? '' );
		if ( '' === $text ) {
			$text = (string) zarincode_get_option( 'brand_slogan', 'توسعه نرم‌افزار، طراحی سایت و سئوی فنی — استودیو زرین کد' );
		}
		// A description is a sentence, not a cut word: shorten at the last space.
		if ( 160 < mb_strlen( $text ) ) {
			$text = self::shorten( $text, 158 );
		}
		return $text;
	}

	/**
	 * Human text of an Elementor document, in document order.
	 *
	 * Reads `_elementor_data`, keeps the strings that are real copy (heading
	 * titles, editor content, box descriptions, button labels) and drops the
	 * settings that only look like text (CSS values, icon names, ids). Used as
	 * the last resort for a page whose copy never reaches `post_content`.
	 */
	private static function elementor_text( int $id ): string {
		$raw = get_post_meta( $id, '_elementor_data', true );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return '';
		}
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return '';
		}
		$keys  = array( 'title', 'editor', 'description', 'text', 'subtitle', 'heading', 'title_text', 'button_text', 'caption' );
		$parts = array();
		$walk  = static function ( $nodes ) use ( &$walk, &$parts, $keys ) {
			foreach ( (array) $nodes as $node ) {
				if ( ! is_array( $node ) ) {
					continue;
				}
				$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
				foreach ( $keys as $key ) {
					$value = $settings[ $key ] ?? null;
					if ( ! is_string( $value ) || '' === trim( $value ) ) {
						continue;
					}
					$value = trim( wp_strip_all_tags( $value ) );
					// Skip CSS-ish and placeholder-ish strings.
					if ( '' === $value || preg_match( '/^[a-z-]+:\s|^fa[srlbd]?\s|^\{\{/', $value ) ) {
						continue;
					}
					$parts[] = $value;
				}
				if ( ! empty( $node['elements'] ) ) {
					$walk( $node['elements'] );
				}
			}
		};
		$walk( $data );
		$parts = array_values( array_unique( array_filter( $parts, static function ( $v ) {
			return mb_strlen( (string) $v ) >= 24; // Labels like «شروع پروژه» are not a description.
		} ) ) );
		if ( ! $parts ) {
			return '';
		}
		// A description must read like a sentence. Prefer the first paragraph of
		// real prose, then the longest fragment, and only fall back to gluing
		// short labels together when the document has no prose at all.
		foreach ( $parts as $part ) {
			$len = mb_strlen( $part );
			if ( $len >= 90 && $len <= 260 && preg_match( '/[.!؟?]$/u', $part ) ) {
				return $part;
			}
		}
		usort( $parts, static fn( $a, $b ) => mb_strlen( (string) $b ) <=> mb_strlen( (string) $a ) );
		if ( mb_strlen( (string) $parts[0] ) >= 70 ) {
			return (string) $parts[0];
		}
		$out = '';
		foreach ( $parts as $part ) {
			$out = trim( $out . ' ' . $part );
			if ( mb_strlen( $out ) >= 110 ) {
				break;
			}
		}
		return $out;
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

		if ( ! is_404() ) {
			$desc = self::description();
			echo '<meta name="description" content="' . esc_attr( $desc ) . '" />' . "\n";
			echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
			echo '<meta property="og:locale" content="' . esc_attr( str_replace( '-', '_', (string) get_bloginfo( 'language' ) ) ?: 'fa_IR' ) . '" />' . "\n";
			$canonical = is_singular() ? (string) get_permalink() : self::self_url();
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

	/**
	 * Feeds noindex into WordPress' own robots tag instead of echoing a second
	 * `<meta name="robots">`. Two tags on one page is a documented crawl hazard:
	 * Google merges them, and a later plugin's `index` cannot cancel ours.
	 */
	public static function robots_filter( array $robots ): array {
		if ( self::owned() ) {
			return $robots;
		}
		if ( self::noindex() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}

	/**
	 * Front-page document title: «Site — tagline».
	 *
	 * WordPress prints only the site name for a static front page, which wastes
	 * the single strongest on-page ranking field. Composed through
	 * `document_title_parts` so the separator stays filterable, and skipped
	 * entirely when an SEO plugin owns metadata.
	 */
	public static function title_parts( array $parts ): array {
		if ( self::owned() || is_admin() ) {
			return $parts;
		}
		// A page can carry its own search title (`_zc_seo_title`): the demo
		// importer writes one for the front page, the shop, the blog and the
		// price list, and an editor can write one on any page.
		$explicit_id = self::page_id();
		if ( $explicit_id && ! is_paged() ) {
			$explicit = trim( (string) get_post_meta( $explicit_id, '_zc_seo_title', true ) );
			if ( '' !== $explicit ) {
				$parts['title'] = $explicit;
				unset( $parts['tagline'], $parts['site'] );
				return $parts;
			}
		}
		if ( ! is_front_page() || is_paged() ) {
			return $parts;
		}
		$tagline = trim( (string) get_bloginfo( 'description' ) );
		if ( '' === $tagline && function_exists( 'zarincode_get_option' ) ) {
			$tagline = trim( (string) zarincode_get_option( 'brand_slogan', '' ) );
		}
		if ( '' === $tagline ) {
			return $parts;
		}
		// Never cut a title mid-phrase: a «…» in a <title> is a truncated result in
		// the SERP. A long tagline is shortened at the last word that still fits
		// in 60 characters, and the site name stays complete either way.
		if ( 60 < mb_strlen( $tagline ) ) {
			$tagline = self::shorten( $tagline, 60 );
		}
		$parts['title']   = (string) get_bloginfo( 'name' );
		$parts['tagline'] = $tagline;
		return $parts;
	}

	/**
	 * Shortens a phrase to `$limit` characters at a word boundary.
	 *
	 * No ellipsis on purpose: search engines already truncate, and a hard
	 * «…» inside the title makes an intentional sentence look broken.
	 */
	public static function shorten( string $text, int $limit ): string {
		$text = trim( preg_replace( '/\s+/u', ' ', $text ) ?: '' );
		if ( mb_strlen( $text ) <= $limit ) {
			return $text;
		}
		$cut  = mb_substr( $text, 0, $limit );
		$last = mb_strrpos( $cut, ' ' );
		if ( $last && $last > 0 ) {
			$cut = mb_substr( $cut, 0, $last );
		}
		return rtrim( $cut, " \t\n\r\0\x0B\,،;؛:-–—" );
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
			'inLanguage'  => str_replace( '-', '_', (string) get_bloginfo( 'language' ) ) ?: 'fa_IR',
			'potentialAction' => [
				'@type'      => 'SearchAction',
				'target'     => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			],
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/** Advertise the core sitemap to crawlers via the virtual robots.txt. */
	public static function robots_txt( string $output, string $path ): string {
		unset( $path );
		$sitemap = home_url( '/wp-sitemap.xml' );
		if ( false !== strpos( $output, $sitemap ) ) {
			return $output;
		}
		return rtrim( $output, "\n" ) . "\nSitemap: $sitemap\n";
	}
}
