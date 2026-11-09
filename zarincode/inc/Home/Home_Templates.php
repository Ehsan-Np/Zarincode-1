<?php
/**
 * Homepage section templates — "cheerful + formal" design (v4.6.0).
 *
 * Every section is a plain PHP template that returns HTML using the scoped
 * .zc-hp design system (assets/css/home-cheerful.css). The Elementor widgets
 * in zarincode-core/includes/Widgets/Home_Widgets.php feed these templates, so
 * every string and repeater row is editable from the Elementor panel while the
 * visual language stays identical to the approved design reference.
 *
 * @package Zarincode\Theme\Home
 */

namespace Zarincode\Theme\Home;

defined( 'ABSPATH' ) || exit;

final class Home_Templates {

	/** Cached content model (json). */
	private static function content(): array {
		static $data = null;
		if ( null === $data ) {
			$file = __DIR__ . '/home-content.json';
			$json = is_readable( $file ) ? (string) file_get_contents( $file ) : '';
			$data = $json ? ( (array) json_decode( $json, true ) ) : array();
		}
		return $data;
	}

	public static function defaults( string $section ): array {
		$c = self::content();
		return isset( $c[ $section ] ) && is_array( $c[ $section ] ) ? $c[ $section ] : array();
	}

	/* ---------------------------------------------------------------- utils */

	private static function e( $v ): string {
		return esc_html( (string) $v );
	}

	private static function url( $v ): string {
		return esc_url( (string) ( $v ?: '#' ) );
	}

	/**
	 * A light index of the site's own published titles (services, products,
	 * projects, posts), built once per request with a fixed number of queries.
	 * Used by the ticker so every term can point at the real page that talks
	 * about it instead of a generic search.
	 *
	 * @return array<int, array{title:string, norm:string, url:string}>
	 */
	private static function content_index(): array {
		static $index = null;
		if ( null !== $index ) {
			return $index;
		}

		$index = array();
		$types = array( 'zc_service', 'product', 'zc_project', 'post' );
		foreach ( $types as $type ) {
			if ( ! post_type_exists( $type ) ) {
				continue;
			}
			$ids = get_posts( array(
				'post_type'        => $type,
				'post_status'      => 'publish',
				'numberposts'      => 60,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			) );
			foreach ( (array) $ids as $id ) {
				$title = (string) get_the_title( $id );
				$norm  = self::norm( $title );
				if ( '' === $norm ) {
					continue;
				}
				$url = (string) get_permalink( $id );
				if ( '' !== $url ) {
					$index[] = array( 'title' => $title, 'norm' => $norm, 'url' => $url );
				}
			}
		}

		return $index;
	}

	/** Normalises a label for comparison (ZWNJ, punctuation, case, spacing). */
	private static function norm( string $text ): string {
		$text = str_replace( array( "\xE2\x80\x8C", "\u{200c}", '-', '_', '\u{200f}', '\u{200e}' ), ' ', $text );
		$text = preg_replace( '/[^\p{L}\p{N}]+/u', '', $text );
		return mb_strtolower( (string) $text );
	}

	/**
	 * Best real page for a free-text label (used by the ticker): exact title
	 * first, then the longest title that contains / is contained by the label.
	 * Returns '' when the site has nothing to point at.
	 */
	private static function url_for_label( string $label ): string {
		$norm = self::norm( $label );
		if ( '' === $norm ) {
			return '';
		}

		$best     = '';
		$best_len = 0;
		foreach ( self::content_index() as $row ) {
			if ( $row['norm'] === $norm ) {
				return $row['url'];
			}
			$len = mb_strlen( $row['norm'] );
			if ( $len < 4 ) {
				continue;
			}
			if ( ( false !== mb_strpos( $row['norm'], $norm ) || false !== mb_strpos( $norm, $row['norm'] ) ) && $len > $best_len ) {
				$best     = $row['url'];
				$best_len = $len;
			}
		}

		return $best;
	}

	/**
	 * Resolves a tag/category label to real site content: the term archive when
	 * that term exists, otherwise the site search for the phrase.
	 *
	 * @param string $name Term name as displayed.
	 * @param string $taxonomy Taxonomy to try (product_tag, category, …).
	 */
	private static function term_or_search_url( string $name, string $taxonomy = 'category' ): string {
		$name = trim( $name );
		if ( '' === $name ) {
			return '';
		}
		if ( taxonomy_exists( $taxonomy ) ) {
			$term = get_term_by( 'name', $name, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					return (string) $link;
				}
			}
		}
		return home_url( '/?s=' . rawurlencode( $name ) );
	}

	/** First non-empty link wins; empty values never degrade into "#" for CTAs. */
	private static function url_or( $value, string $fallback = '#' ): string {
		$value = is_string( $value ) ? trim( $value ) : '';
		return esc_url( '' !== $value ? $value : $fallback );
	}

	/**
	 * Font Awesome solid glyphs used across the homepage (short name => code point).
	 * Shared with the Elementor icon pickers via icon_choices().
	 */
	public static function icon_map(): array {
		return array(
			'circle-check' => 'f058', 'star' => 'f005', 'shield-halved' => 'f3ed', 'bolt' => 'f0e7',
			'chart-line' => 'f201', 'handshake' => 'f2b5', 'cart-shopping' => 'f07a', 'magnifying-glass' => 'f002',
			'comments' => 'f086', 'display' => 'f108', 'gears' => 'f085', 'arrow-trend-up' => 'e098',
			'pen-nib' => 'f5ad', 'hashtag' => 'f292', 'cubes' => 'f1b3', 'graduation-cap' => 'f19d',
			'robot' => 'f544', 'building' => 'f1ad', 'palette' => 'f53f', 'hospital' => 'f0f8',
			'newspaper' => 'f1ea', 'compass' => 'f14e', 'bullseye' => 'f140', 'layer-group' => 'f5fd',
			'gift' => 'f06b', 'pen-ruler' => 'f5ae', 'screwdriver-wrench' => 'f7d9', 'rocket' => 'f135',
			'wordpress' => 'f19a', 'envelope' => 'f0e0', 'cloud' => 'f0c2', 'building-columns' => 'f19c',
			'globe' => 'f0ac', 'server' => 'f233', 'chart-simple' => 'f201', 'users' => 'f0c0',
			'clock' => 'f017', 'file-lines' => 'f15c', 'phone' => 'f095', 'arrow-left' => 'f060',
			'quote-right' => 'f10e', 'sparkles' => 'f890',
		);
	}

	/** Persian labels for the icon picker, keyed by the same short names. */
	public static function icon_labels(): array {
		return array(
			'circle-check' => 'تأیید', 'star' => 'ستاره', 'shield-halved' => 'سپر و امنیت', 'bolt' => 'سرعت',
			'chart-line' => 'نمودار رشد', 'handshake' => 'همکاری', 'cart-shopping' => 'سبد خرید',
			'magnifying-glass' => 'ذره‌بین و جست‌وجو', 'comments' => 'گفت‌وگو', 'display' => 'نمایشگر و وب‌سایت',
			'gears' => 'چرخ‌دنده و فنی', 'arrow-trend-up' => 'روند صعودی', 'pen-nib' => 'قلم طراحی',
			'hashtag' => 'هشتگ', 'cubes' => 'محصول و بسته', 'graduation-cap' => 'آموزش',
			'robot' => 'هوش مصنوعی', 'building' => 'ساختمان', 'palette' => 'پالت رنگ', 'hospital' => 'درمان',
			'newspaper' => 'مقاله', 'compass' => 'قطب‌نما و راهبرد', 'bullseye' => 'هدف',
			'layer-group' => 'لایه‌ها', 'gift' => 'هدیه', 'pen-ruler' => 'طراحی و اندازه',
			'screwdriver-wrench' => 'ابزار فنی', 'rocket' => 'شروع و رشد', 'wordpress' => 'وردپرس',
			'envelope' => 'ایمیل', 'cloud' => 'ابر', 'building-columns' => 'سازمان',
			'globe' => 'وب و جهان', 'server' => 'سرور', 'chart-simple' => 'نمودار ساده',
			'users' => 'کاربران', 'clock' => 'ساعت و زمان', 'file-lines' => 'سند و مستندات',
			'phone' => 'تلفن', 'arrow-left' => 'فلش', 'quote-right' => 'نقل‌قول', 'sparkles' => 'درخشش',
		);
	}

	/** Options for an Elementor SELECT2 icon control. */
	public static function icon_choices(): array {
		$choices = array( '' => '— بدون آیکون —' );
		foreach ( self::icon_labels() as $name => $label ) {
			$choices[ $name ] = $label . ' — ' . $name;
		}
		return $choices;
	}

	/** Font Awesome solid glyph from a short name (display => f108). */
	private static function ico( string $name ): string {
		if ( '' === $name ) {
			return '';
		}
		$map  = self::icon_map();
		$code = $map[ $name ] ?? 'f111';
		return '<span class="fa" aria-hidden="true">&#x' . $code . ';</span>';
	}

	/**
	 * Reads a list from the settings, falling back to the content model.
	 *
	 * An empty array in $a means "the editor removed every row" and renders
	 * nothing — that is why array_key_exists() is used instead of !empty().
	 */
	private static function list_of( array $a, array $d, string $key ): array {
		if ( array_key_exists( $key, $a ) && is_array( $a[ $key ] ) ) {
			return array_values( $a[ $key ] );
		}
		return isset( $d[ $key ] ) && is_array( $d[ $key ] ) ? array_values( $d[ $key ] ) : array();
	}

	private static function stars( int $n ): string {
		return str_repeat( '<span aria-hidden="true">★</span>', max( 0, min( 5, $n ) ) );
	}

	/* ---------------------------------------------------------------- HERO */

	public static function hero( array $s ): string {
		$d = self::defaults( 'hero' );
		$a = array_merge( $d, $s );
		$badges = self::list_of( $a, $d, 'badges' );
		$trust  = self::list_of( $a, $d, 'trust' );
		$floats = self::list_of( $a, $d, 'floats' );
		ob_start();
		?>
<div class="hero">
	<div class="wrap hero-grid">
		<div>
			<div class="stickers">
				<?php foreach ( $badges as $i => $b ) : ?>
				<span class="sticker st<?php echo (int) ( $i + 1 ); ?>"><span class="em"><?php echo self::ico( (string) ( $b['icon'] ?? 'circle-check' ) ); ?></span> <?php echo self::e( $b['text'] ?? '' ); ?></span>
				<?php endforeach; ?>
			</div>
			<?php
			$line1 = trim( (string) ( $a['title_line1'] ?? '' ) );
			$high  = trim( (string) ( $a['title_highlight'] ?? '' ) );
			$line2 = trim( (string) ( $a['title_line2'] ?? '' ) );
			$pop   = trim( (string) ( $a['title_pop'] ?? '' ) );
			?>
			<h1 class="big"><?php
				echo self::e( $line1 );
				if ( '' !== $high ) :
					?> <span class="hl"><?php echo self::e( $high ); ?></span><?php
					// The comma belongs to the sentence, not to the design: only print
					// it when the highlight is followed by more text.
					if ( '' !== $line2 ) {
						echo '،';
					}
				endif;
				if ( '' !== $line2 ) :
					?><br><?php echo self::e( $line2 ); ?><?php
				endif;
				if ( '' !== $pop ) :
					?><br><span class="pop"><?php echo self::e( $pop ); ?></span><?php
				endif;
			?></h1>
			<p class="lead"><?php echo self::e( $a['lead'] ?? '' ); ?></p>
			<div class="hero-actions">
				<a class="btn btn-gold btn-lg" href="<?php echo self::url_or( $a['primary_url'] ?? '', '/contact-us/' ); ?>"><?php echo self::ico( 'rocket' ); ?> <?php echo self::e( $a['primary_text'] ?? '' ); ?></a>
				<a class="btn btn-white btn-lg" href="<?php echo self::url_or( $a['secondary_url'] ?? '', '/projects-portfolio/' ); ?>"><?php echo self::e( $a['secondary_text'] ?? '' ); ?> ←</a>
				<a class="btn btn-peach btn-lg" href="<?php echo self::url_or( $a['tertiary_url'] ?? '', '/contact-us/' ); ?>"><?php echo self::ico( 'phone' ); ?> <?php echo self::e( $a['tertiary_text'] ?? '' ); ?></a>
			</div>
			<div class="trust">
				<?php foreach ( $trust as $i => $t ) : ?>
				<div class="tcard tc<?php echo (int) ( $i + 1 ); ?>"><span class="ti"><?php echo self::ico( (string) ( $t['icon'] ?? 'chart-line' ) ); ?></span><span><b><?php echo self::e( $t['value'] ?? '' ); ?></b><span><?php echo self::e( $t['label'] ?? '' ); ?></span></span></div>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="collage">
			<div class="blob1"></div>
			<div class="blob2"></div>
			<?php
			// The code card is editable content, not decoration: the widget stores a
			// function signature plus one row per line, so a site can show its own
			// stack instead of the demo snippet.
			$code_rows = self::list_of( $a, $d, 'code_rows' );
			?>
			<?php if ( $code_rows ) : ?>
			<div class="code-card">
				<div class="bar"><i></i><i></i><i></i></div>
				<pre><b>function</b> <em><?php echo self::e( $a['code_fn'] ?? 'grow' ); ?></em>(<?php echo self::e( $a['code_arg'] ?? 'business' ); ?>) {
<?php foreach ( $code_rows as $row ) : ?>  <s><?php echo self::e( $row['left'] ?? '' ); ?></s> <em><?php echo self::e( $row['right'] ?? '' ); ?></em>
<?php endforeach; ?></pre>
			</div>
			<?php endif; ?>
			<div class="score"><b><?php echo self::e( $a['score_value'] ?? '' ); ?></b><span><?php echo self::e( $a['score_label'] ?? '' ); ?></span></div>
			<?php foreach ( $floats as $i => $f ) : ?>
			<div class="floaty fl<?php echo (int) ( $i + 1 ); ?>"><span class="fi"><?php echo self::ico( (string) ( $f['icon'] ?? 'cart-shopping' ) ); ?></span> <?php echo self::e( $f['text'] ?? '' ); ?></div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
		<?php
		return (string) ob_get_clean();
	}

	/* ------------------------------------------------------------ TICKER */

	public static function ticker( array $s ): string {
		$items = array_key_exists( 'items', $s ) && is_array( $s['items'] )
			? $s['items']
			: (array) ( self::defaults( 'hero' )['ticker'] ?? array() );
		if ( ! $items ) {
			return '';
		}

		// Each item: a plain string (text only) or [ 'text' => …, 'link' => … ].
		// Without an explicit link the term still resolves to real site content
		// through the WordPress search archive, so nothing in the bar is a dead end.
		$build = static function () use ( $items ): string {
			$out = '';
			foreach ( $items as $it ) {
				$is_map = is_array( $it );
				$text   = trim( (string) ( $is_map ? ( $it['text'] ?? '' ) : $it ) );
				if ( '' === $text ) {
					continue;
				}
				$link = $is_map ? trim( (string) ( $it['link'] ?? '' ) ) : '';
				if ( '' === $link ) {
					$link = self::url_for_label( $text ); // A real service / product / article.
				}
				$href = '' !== $link ? $link : home_url( '/?s=' . rawurlencode( $text ) );
				$out .= '<span><a class="titem" href="' . esc_url( $href ) . '">' . self::e( $text ) . '</a><b aria-hidden="true">◆</b></span>';
			}
			return $out;
		};

		$track = $build();
		if ( '' === $track ) {
			return '';
		}

		return '<div class="ticker" data-zc-ticker>'
			. '<button type="button" class="tpause" aria-label="توقف / ادامهٔ حرکت نوار" aria-pressed="false"><span aria-hidden="true">❙❙</span></button>'
			. '<div class="ttrack">' . $track . '</div>'
			. '<div class="ttrack" aria-hidden="true">' . $track . '</div>'
			. '</div>';
	}

	/* ---------------------------------------------------------- PARTNERS */

	public static function partners( array $s ): string {
		$d = self::defaults( 'partners' );
		$a = array_merge( $d, $s );
		$items = self::list_of( $a, $d, 'items' );
		ob_start();
		?>
<div class="clients">
	<div class="wrap">
		<div class="cbox2 partners">
			<div class="phead">
				<span class="lab"><?php echo self::ico( 'users' ); ?> <?php echo self::e( $a['label'] ?? '' ); ?></span>
				<?php if ( ! empty( $a['count'] ) ) : ?><span class="pn"><?php echo self::e( $a['count'] ); ?></span><?php endif; ?>
			</div>
			<div class="pview">
				<div class="ptrack">
					<?php
					// track is duplicated so the CSS marquee loops seamlessly.
					for ( $loop = 0; $loop < 2; $loop++ ) :
						foreach ( $items as $p ) :
							$plink = trim( (string) ( $p['link'] ?? '' ) );
							?>
					<?php if ( '' !== $plink ) : ?>
					<a class="plogo" href="<?php echo esc_url( self::url_or( $plink, '/' ) ); ?>" title="<?php echo esc_attr( (string) ( $p['name'] ?? '' ) ); ?>"><?php echo self::ico( (string) ( $p['icon'] ?? 'building' ) ); ?> <?php echo self::e( $p['name'] ?? '' ); ?></a>
					<?php else : ?>
					<span class="plogo"><?php echo self::ico( (string) ( $p['icon'] ?? 'building' ) ); ?> <?php echo self::e( $p['name'] ?? '' ); ?></span>
					<?php endif; ?>
							<?php
						endforeach;
					endfor;
					?>
				</div>
			</div>
			<?php if ( ! empty( $a['note'] ) ) : ?><span class="note2"><?php echo self::e( $a['note'] ); ?></span><?php endif; ?>
		</div>
	</div>
</div>
		<?php
		return (string) ob_get_clean();
	}

	/* ---------------------------------------------------------- SECTION HEAD */

	private static function head( array $a, string $more_text = '', string $more_url = '', bool $center = false ): string {
		$badge = (string) ( $a['badge'] ?? '' );
		$title = (string) ( $a['title'] ?? '' );
		$high  = (string) ( $a['title_highlight'] ?? '' );
		$after = (string) ( $a['title_after'] ?? '' );
		$desc  = (string) ( $a['desc'] ?? '' );
		$title_html = self::e( $title );
		if ( '' !== trim( $high ) ) {
			$title_html .= ' <span class="g">' . self::e( $high ) . '</span>';
		}
		if ( '' !== trim( $after ) ) {
			$title_html .= ' ' . self::e( $after );
		}
		ob_start();
		if ( $center ) :
			?>
	<div class="sec-head">
		<?php if ( $badge !== '' ) : ?><span class="k"><?php echo self::ico( 'sparkles' ); ?> <?php echo self::e( $badge ); ?></span><?php endif; ?>
		<h2 class="t"><?php echo wp_kses_post( $title_html ); ?></h2>
		<?php if ( $desc !== '' ) : ?><p><?php echo self::e( $desc ); ?></p><?php endif; ?>
	</div>
			<?php
		else :
			?>
	<div class="head-row">
		<div>
			<?php if ( $badge !== '' ) : ?><span class="k"><?php echo self::ico( 'sparkles' ); ?> <?php echo self::e( $badge ); ?></span><?php endif; ?>
			<h2 class="t"><?php echo wp_kses_post( $title_html ); ?></h2>
			<?php if ( $desc !== '' ) : ?><p><?php echo self::e( $desc ); ?></p><?php endif; ?>
		</div>
		<?php if ( $more_text !== '' ) : ?><a class="link" href="<?php echo self::url( $more_url ); ?>"><?php echo self::e( $more_text ); ?> <span class="ar">←</span></a><?php endif; ?>
	</div>
			<?php
		endif;
		return (string) ob_get_clean();
	}

	/* ---------------------------------------------------------- SERVICES */

	public static function services( array $s ): string {
		$d = self::defaults( 'services' );
		$a = array_merge( $d, $s );
		$items = self::list_of( $a, $d, 'items' );
		$tones = array( 'sc-a', 'sc-b', 'sc-c', 'sc-d', 'sc-e', 'sc-f' );
		ob_start();
		?>
<section>
	<div class="wrap">
		<?php echo self::head( $a, '', '', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="grid3">
			<?php foreach ( $items as $i => $it ) : $tone = $tones[ $i % 6 ]; ?>
			<div class="svc">
				<div class="scover <?php echo esc_attr( $tone ); ?>">
					<span class="ico-tile"><?php echo self::ico( (string) ( $it['icon'] ?? 'display' ) ); ?></span>
					<span class="num"><?php echo self::e( $it['no'] ?? sprintf( '%02d', $i + 1 ) ); ?></span>
					<span class="en"><?php echo self::e( $it['en'] ?? '' ); ?></span>
				</div>
				<div class="sbody">
					<h3><?php echo self::e( $it['title'] ?? '' ); ?></h3>
					<p><?php echo self::e( $it['desc'] ?? '' ); ?></p>
					<?php if ( ! empty( $it['meta'] ) && is_array( $it['meta'] ) ) : ?>
					<div class="schips">
						<?php foreach ( $it['meta'] as $m ) : ?>
						<span><?php echo self::ico( (string) ( $m['icon'] ?? 'clock' ) ); ?> <?php echo self::e( $m['text'] ?? '' ); ?></span>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
					<div class="sfoot">
						<span class="pricetag"><?php echo self::e( $it['price'] ?? '' ); ?></span>
						<a class="smore" href="<?php echo self::url_or( $it['link'] ?? '', '/services/' ); ?>"><?php echo self::e( $it['link_text'] ?? 'جزئیات' ); ?> <span class="ar">←</span></a>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
		<?php
		return (string) ob_get_clean();
	}

	/* ---------------------------------------------------------- PRODUCTS */

	public static function products( array $s ): string {
		$d = self::defaults( 'products' );
		$a = array_merge( $d, $s );
		$items = self::list_of( $a, $d, 'items' );
		$covers = array( 'pc1', 'pc2', 'pc3', 'pc4' );
		ob_start();
		?>
<section class="pt-tight">
	<div class="wrap">
		<?php echo self::head( $a, (string) ( $a['link_text'] ?? '' ), (string) ( $a['link_url'] ?? '' ) ?: '/shop/' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="grid4">
			<?php foreach ( $items as $i => $it ) : $cover = $covers[ $i % 4 ]; ?>
			<div class="pcard">
				<div class="pcover <?php echo esc_attr( $cover ); ?>">
					<?php if ( ! empty( $it['image'] ) ) : ?>
					<img class="pimg" src="<?php echo esc_url( (string) $it['image'] ); ?>" alt="<?php echo esc_attr( (string) ( $it['title'] ?? '' ) ); ?>" loading="lazy" decoding="async">
					<?php endif; ?>
					<span class="ico-tile"><?php echo self::ico( (string) ( $it['icon'] ?? 'cubes' ) ); ?></span>
					<?php if ( ! empty( $it['discount'] ) ) : ?><span class="badge"><?php echo self::e( $it['discount'] ); ?></span><?php endif; ?>
					<span class="kind"><?php echo self::e( $it['kind'] ?? '' ); ?></span>
				</div>
				<div class="pbody">
					<h4><?php echo self::e( $it['title'] ?? '' ); ?></h4>
					<?php if ( ! empty( $it['tags'] ) && is_array( $it['tags'] ) ) : ?>
					<div class="pchips"><?php foreach ( $it['tags'] as $t ) : ?><a href="<?php echo esc_url( self::term_or_search_url( (string) $t, 'product_tag' ) ); ?>"><?php echo self::e( $t ); ?></a><?php endforeach; ?></div>
					<?php endif; ?>
					<div class="prow">
						<span>
							<?php if ( ! empty( $it['old'] ) ) : ?><span class="old"><?php echo self::e( $it['old'] ); ?></span><?php endif; ?>
							<span class="now"><?php echo self::e( $it['now'] ?? '' ); ?><?php if ( ! empty( $a['currency'] ) ) : ?> <small><?php echo self::e( $a['currency'] ); ?></small><?php endif; ?></span>
						</span>
						<a class="add <?php echo ! empty( $it['featured'] ) ? 'g' : ''; ?>" href="<?php echo self::url_or( $it['link'] ?? '', '/shop/' ); ?>"><?php echo self::e( $it['cta'] ?? ( $a['cta_text'] ?? '' ) ); ?></a>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
		<?php
		return (string) ob_get_clean();
	}

	/* ---------------------------------------------------------- PORTFOLIO */

	public static function portfolio( array $s ): string {
		$d = self::defaults( 'portfolio' );
		$a = array_merge( $d, $s );
		$items = self::list_of( $a, $d, 'items' );
		$tones = array( 'jc1', 'jc2', 'jc3' );
		ob_start();
		?>
<section class="tint-cream">
	<div class="wrap">
		<?php echo self::head( $a, (string) ( $a['link_text'] ?? '' ), (string) ( $a['link_url'] ?? '' ) ?: '/projects-portfolio/' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="grid3">
			<?php foreach ( $items as $i => $it ) : ?>
			<div class="jcard">
				<div class="jcover <?php echo esc_attr( $tones[ $i % 3 ] ); ?>">
					<?php if ( ! empty( $it['image'] ) ) : ?>
					<img class="jimg" src="<?php echo esc_url( (string) $it['image'] ); ?>" alt="<?php echo esc_attr( (string) ( $it['title'] ?? '' ) ); ?>" loading="lazy" decoding="async">
					<?php endif; ?>
					<span class="yr"><?php echo self::e( $it['year'] ?? '' ); ?></span>
					<div class="win"></div>
					<?php if ( ! empty( $it['kpi'] ) ) : ?><span class="kpi"><?php echo self::e( $it['kpi'] ); ?></span><?php endif; ?>
					<span class="ico-tile"><?php echo self::ico( (string) ( $it['icon'] ?? 'display' ) ); ?></span>
				</div>
				<div class="jbody">
					<h4><?php echo self::e( $it['title'] ?? '' ); ?></h4>
					<div class="jrow">
						<?php if ( '' !== trim( (string) ( $it['cat'] ?? '' ) ) ) : ?><span class="cat <?php echo esc_attr( (string) ( $it['tone'] ?? 'c1' ) ); ?>"><?php echo self::e( $it['cat'] ); ?></span><?php endif; ?>
						<a class="go" href="<?php echo self::url_or( $it['link'] ?? '', '/projects-portfolio/' ); ?>"><?php echo self::e( $it['cta'] ?? ( $a['cta_text'] ?? '' ) ); ?> ←</a>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
		<?php
		return (string) ob_get_clean();
	}

	/* ---------------------------------------------------------------- WHY */

	public static function why( array $s ): string {
		$d = self::defaults( 'why' );
		$a = array_merge( $d, $s );
		$items = self::list_of( $a, $d, 'items' );
		ob_start();
		?>
<section>
	<div class="wrap">
		<?php echo self::head( $a, '', '', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="bento">
			<?php
			foreach ( $items as $i => $it ) :
				$tone = 'b' . ( ( $i % 8 ) + 1 );
				?>
			<div class="bcard <?php echo esc_attr( $tone ); ?>">
				<span class="no"><?php echo self::e( sprintf( '%02d', $i + 1 ) ); ?></span>
				<span class="em"><?php echo self::ico( (string) ( $it['icon'] ?? 'compass' ) ); ?></span>
				<h4><?php echo self::e( $it['title'] ?? '' ); ?></h4>
				<p><?php echo self::e( $it['text'] ?? '' ); ?></p>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
		<?php
		return (string) ob_get_clean();
	}

	/* -------------------------------------------------------------- STEPS */

	public static function steps( array $s ): string {
		$d = self::defaults( 'steps' );
		$a = array_merge( $d, $s );
		$items = self::list_of( $a, $d, 'items' );
		ob_start();
		?>
<section class="tint-gold">
	<div class="wrap">
		<?php echo self::head( $a, '', '', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="steps">
			<?php foreach ( $items as $it ) : ?>
			<div class="step">
				<span class="sic"><?php echo self::ico( (string) ( $it['icon'] ?? 'bullseye' ) ); ?></span>
				<b><?php echo self::e( $it['title'] ?? '' ); ?></b>
				<p><?php echo self::e( $it['text'] ?? '' ); ?></p>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
		<?php
		return (string) ob_get_clean();
	}

	/* ------------------------------------------------------------ PRICING */

	public static function pricing( array $s ): string {
		$d = self::defaults( 'pricing' );
		$a = array_merge( $d, $s );
		$plans  = self::list_of( $a, $d, 'plans' );
		$tabs   = self::list_of( $a, $d, 'tabs' );
		$matrix = array_key_exists( 'matrix', $a ) && is_array( $a['matrix'] ) ? $a['matrix'] : ( $d['matrix'] ?? array() );
		ob_start();
		?>
<section>
	<div class="wrap">
		<?php echo self::head( $a, '', '', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php
		// A plan may declare which track (tab) it belongs to. When none does, the
		// cards stay in one panel and the tabs stay decorative — never broken.
		$has_track = false;
		foreach ( $plans as $p ) {
			if ( '' !== trim( (string) ( $p['track'] ?? '' ) ) ) {
				$has_track = true;
				break;
			}
		}

		$groups = array();
		if ( $has_track ) {
			foreach ( $tabs as $t ) {
				$groups[ (string) $t ] = array();
			}
			$first = (string) ( $tabs[0] ?? '' );
			foreach ( $plans as $p ) {
				$key = trim( (string) ( $p['track'] ?? '' ) );
				if ( '' === $key || ! isset( $groups[ $key ] ) ) {
					$key = $first;
				}
				$groups[ $key ][] = $p;
			}
		} else {
			$groups[''] = $plans;
		}
		$groups = array_filter( $groups );

		$card = static function ( array $p ) use ( $a ): string {
			ob_start();
			?>
			<div class="plan <?php echo esc_attr( (string) ( $p['tone'] ?? 'p1' ) ); ?>">
				<?php if ( ! empty( $p['flag'] ) ) : ?><span class="flag"><?php echo self::e( $p['flag'] ); ?></span><?php endif; ?>
				<h4><?php echo self::e( $p['title'] ?? '' ); ?></h4>
				<div class="sub"><?php echo self::e( $p['delivery'] ?? '' ); ?></div>
				<div class="pprice"><span><?php echo self::e( $a['price_label'] ?? '' ); ?></span><b><?php echo self::e( $p['price'] ?? '' ); ?><?php if ( ! empty( $a['price_unit'] ) ) : ?> <small><?php echo self::e( $a['price_unit'] ); ?></small><?php endif; ?></b></div>
				<?php if ( ! empty( $p['features'] ) && is_array( $p['features'] ) ) : ?>
				<ul><?php foreach ( $p['features'] as $f ) : ?><li><?php echo self::e( $f ); ?></li><?php endforeach; ?></ul>
				<?php endif; ?>
				<a class="btn btn-ink" href="<?php echo self::url_or( $p['link'] ?? '', '/contact-us/' ); ?>"><?php echo self::e( $p['cta'] ?? 'مشاوره و ثبت سفارش' ); ?></a>
			</div>
			<?php
			return (string) ob_get_clean();
		};
		?>
		<?php if ( $has_track && count( $groups ) > 1 ) : ?>
		<div class="chips" role="tablist" aria-label="مسیرهای تعرفه">
			<?php $first = true; foreach ( array_keys( $groups ) as $i => $track ) : ?>
			<button type="button" class="chip <?php echo $first ? 'on' : ''; ?>" role="tab"
				id="zc-plan-tab-<?php echo (int) $i; ?>" aria-controls="zc-plan-panel-<?php echo (int) $i; ?>"
				aria-selected="<?php echo $first ? 'true' : 'false'; ?>" tabindex="<?php echo $first ? '0' : '-1'; ?>"
				data-zc-track="<?php echo (int) $i; ?>"><?php echo self::e( $track ); ?></button>
			<?php $first = false; endforeach; ?>
		</div>
		<?php endif; ?>
		<div class="plans"<?php echo ( $has_track && count( $groups ) > 1 ) ? ' data-zc-plans' : ''; ?>>
			<?php $first = true; foreach ( array_values( $groups ) as $i => $group ) : ?>
			<div class="plan-panel"<?php echo ( $has_track && count( $groups ) > 1 ) ? ' role="tabpanel" id="zc-plan-panel-' . (int) $i . '" aria-labelledby="zc-plan-tab-' . (int) $i . '"' : ''; ?><?php echo $first ? '' : ' hidden'; ?>>
				<?php foreach ( $group as $p ) : echo $card( $p ); endforeach; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<?php $first = false; endforeach; ?>
		</div>
		<?php if ( ! empty( $matrix['rows'] ) ) : ?>
		<div class="matrix">
			<table>
				<thead><tr>
					<?php foreach ( (array) ( $matrix['head'] ?? array() ) as $h ) : ?><th><?php echo self::e( $h ); ?></th><?php endforeach; ?>
				</tr></thead>
				<tbody>
					<?php foreach ( (array) $matrix['rows'] as $row ) : ?>
					<tr><?php foreach ( (array) $row as $i => $cell ) : ?><td class="<?php echo 0 === $i ? '' : 'yes'; ?>"><?php echo self::e( $cell ); ?></td><?php endforeach; ?></tr>
					<?php endforeach; ?>
				</tbody>
				<?php if ( ! empty( $matrix['foot'] ) ) : ?>
				<tfoot><tr><?php foreach ( (array) $matrix['foot'] as $c ) : ?><td><?php echo self::e( $c ); ?></td><?php endforeach; ?></tr></tfoot>
				<?php endif; ?>
			</table>
		</div>
		<?php endif; ?>
	</div>
</section>
		<?php
		return (string) ob_get_clean();
	}

	/* ------------------------------------------------------- TESTIMONIALS */

	public static function testimonials( array $s ): string {
		$d = self::defaults( 'testimonials' );
		$a = array_merge( $d, $s );
		$items = self::list_of( $a, $d, 'items' );
		$tones = array( 'tt1', 'tt2', 'tt3' );
		ob_start();
		?>
<section class="tint-violet">
	<div class="wrap">
		<?php echo self::head( $a, '', '', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="tgrid">
			<?php foreach ( $items as $i => $t ) : ?>
			<div class="tst <?php echo esc_attr( $tones[ $i % 3 ] ); ?>">
				<span class="qm">”</span>
				<?php $rating = max( 0, min( 5, (int) ( $t['stars'] ?? 5 ) ) ); ?>
				<?php if ( $rating > 0 ) : ?>
				<div class="stars" aria-label="<?php echo esc_attr( (string) $rating ); ?> از ۵"><?php echo wp_kses_post( self::stars( $rating ) ); ?></div>
				<?php endif; ?>
				<p><?php echo self::e( $t['text'] ?? '' ); ?></p>
				<div class="who">
					<?php $ava_link = (string) ( $t['link'] ?? '' ); ?>
					<?php if ( ! empty( $t['photo'] ) ) : ?>
					<span class="ava has-photo">
						<img src="<?php echo esc_url( (string) $t['photo'] ); ?>" alt="<?php echo esc_attr( (string) ( $t['name'] ?? '' ) ); ?>" width="48" height="48" loading="lazy" decoding="async">
					</span>
					<?php else : ?>
					<span class="ava"><?php echo self::e( $t['initial'] ?? 'م' ); ?></span>
					<?php endif; ?>
					<span><b><?php echo self::e( $t['name'] ?? '' ); ?></b><span><?php echo self::e( $t['role'] ?? '' ); ?></span></span>
					<?php if ( '' !== $ava_link ) : ?>
					<a class="tlink" href="<?php echo esc_url( self::url_or( $ava_link, '/' ) ); ?>" aria-label="<?php echo esc_attr( 'مشاهدهٔ ' . (string) ( $t['name'] ?? '' ) ); ?>">←</a>
					<?php endif; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
		<?php
		return (string) ob_get_clean();
	}

	/* --------------------------------------------------------------- BLOG */

	public static function blog( array $s ): string {
		$d = self::defaults( 'blog' );
		$a = array_merge( $d, $s );
		$feat = array_merge( (array) ( $d['featured'] ?? array() ), (array) ( $a['featured'] ?? array() ) );
		$side = self::list_of( $a, $d, 'side' );
		$pop  = self::list_of( $a, $d, 'popular' );
		$news = array_merge( (array) ( $d['newsletter'] ?? array() ), (array) ( $a['newsletter'] ?? array() ) );
		ob_start();
		?>
<section>
	<div class="wrap">
		<?php echo self::head( $a, (string) ( $a['link_text'] ?? '' ), (string) ( $a['link_url'] ?? '' ) ?: '/blog/' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="bgrid">
			<article class="featured">
				<div class="fcover">
					<?php if ( ! empty( $feat['image'] ) ) : ?>
					<img class="fimg" src="<?php echo esc_url( (string) $feat['image'] ); ?>" alt="<?php echo esc_attr( (string) ( $feat['title'] ?? '' ) ); ?>" loading="lazy" decoding="async">
					<?php endif; ?>
					<div class="dots"></div>
					<?php if ( ! empty( $feat['seal'] ) ) : ?><span class="seal">✦ <?php echo self::e( $feat['seal'] ); ?></span><?php endif; ?>
					<span class="cat"><?php echo self::e( $feat['cat'] ?? '' ); ?></span>
					<span class="glyph"><?php echo self::ico( (string) ( $feat['icon'] ?? 'newspaper' ) ); ?></span>
					<div class="ttl"><?php echo self::e( $feat['title'] ?? '' ); ?></div>
				</div>
				<div class="fbody">
					<p><?php echo self::e( $feat['excerpt'] ?? '' ); ?></p>
					<a class="continue" href="<?php echo self::url_or( $feat['link'] ?? '', '/blog/' ); ?>"><?php echo self::e( $a['read_more'] ?? '' ); ?> <span class="ar">←</span></a>
					<div class="bmeta">
						<div class="who2"><span class="ava2"><?php echo self::e( mb_substr( (string) ( $feat['author'] ?? 'ز' ), 0, 1 ) ); ?></span><span><b><?php echo self::e( $feat['author'] ?? '' ); ?></b><span><?php echo self::e( $feat['date'] ?? '' ); ?></span></span></div>
						<span class="timepill">🕐 <?php echo self::e( $feat['read'] ?? '' ); ?></span>
					</div>
				</div>
			</article>
			<div class="stack">
				<?php foreach ( $side as $i => $b ) : ?>
				<article class="scard <?php echo esc_attr( (string) ( $b['tone'] ?? 'sc1' ) ); ?>">
					<div class="scover2">
					<?php if ( ! empty( $b['image'] ) ) : ?>
					<img class="simg" src="<?php echo esc_url( (string) $b['image'] ); ?>" alt="<?php echo esc_attr( (string) ( $b['title'] ?? '' ) ); ?>" loading="lazy" decoding="async">
					<?php endif; ?>
					<span class="g"><?php echo self::ico( (string) ( $b['icon'] ?? 'newspaper' ) ); ?></span><span class="pill"><?php echo self::e( $b['pill'] ?? '' ); ?></span>
				</div>
					<div class="sbody2">
						<div class="tags">
							<?php foreach ( (array) ( $b['tags'] ?? array() ) as $k => $tag ) : ?>
							<a class="tag <?php echo esc_attr( array( 'a', 'b', 'c' )[ $k % 3 ] ); ?>" href="<?php echo esc_url( self::term_or_search_url( (string) $tag, 'category' ) ); ?>"><?php echo self::e( $tag ); ?></a>
							<?php endforeach; ?>
						</div>
						<h3><?php $side_link = (string) ( $b['link'] ?? '' ); ?><?php if ( '' !== $side_link ) : ?><a href="<?php echo esc_url( self::url_or( $side_link, '/blog/' ) ); ?>"><?php echo self::e( $b['title'] ?? '' ); ?></a><?php else : ?><?php echo self::e( $b['title'] ?? '' ); ?><?php endif; ?></h3>
						<p><?php echo self::e( $b['excerpt'] ?? '' ); ?></p>
						<div class="bmeta">
							<div class="who2"><span class="ava2" style="width:32px;height:32px;font-size:12px"><?php echo self::e( mb_substr( (string) ( $b['author'] ?? 'ز' ), 0, 1 ) ); ?></span><span><b><?php echo self::e( $b['author'] ?? '' ); ?></b><span><?php echo self::e( $b['date'] ?? '' ); ?></span></span></div>
							<span class="timepill" style="font-size:11.5px"><?php echo self::e( $b['read'] ?? '' ); ?></span>
						</div>
					</div>
				</article>
				<?php endforeach; ?>
			</div>
			<div class="side">
				<div class="popular">
					<div class="pop-head"><span class="ic"><?php echo self::ico( 'chart-simple' ); ?></span><span><b><?php echo self::e( $a['popular_title'] ?? '' ); ?></b><span><?php echo self::e( $a['popular_sub'] ?? '' ); ?></span></span></div>
					<?php foreach ( $pop as $i => $p ) : $pop_link = (string) ( $p['link'] ?? '' ); ?>
					<<?php echo '' !== $pop_link ? 'a' : 'div'; ?><?php echo '' !== $pop_link ? ' href="' . esc_url( self::url_or( $pop_link, '/blog/' ) ) . '"' : ''; ?> class="pop-item"><span class="rank r<?php echo (int) ( $i + 1 ); ?>"><?php echo self::e( sprintf( '%02d', $i + 1 ) ); ?></span><div><h4><?php echo self::e( $p['title'] ?? '' ); ?></h4><div class="pm">◆ <?php echo self::e( $p['meta'] ?? '' ); ?></div></div></<?php echo '' !== $pop_link ? 'a' : 'div'; ?>>
					<?php endforeach; ?>
				</div>
				<div class="news">
					<b>✦ <?php echo self::e( $news['title'] ?? '' ); ?></b>
					<span><?php echo self::e( $news['desc'] ?? '' ); ?></span>
					<form class="row" method="post" action="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>">
						<input type="email" name="zc_news_email" required placeholder="<?php echo esc_attr( (string) ( $news['placeholder'] ?? '' ) ); ?>" aria-label="ایمیل خبرنامه">
						<button type="submit"><?php echo self::e( $news['button'] ?? 'عضویت' ); ?></button>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>
		<?php
		return (string) ob_get_clean();
	}

	/* -------------------------------------------------------------- PROMO */

	public static function promo( array $s ): string {
		$d = self::defaults( 'promo' );
		$a = array_merge( $d, $s );
		ob_start();
		?>
<section class="pt-tight">
	<div class="wrap">
		<div class="promo">
			<span class="k">✦ <?php echo self::e( $a['badge'] ?? '' ); ?></span>
			<h2><?php echo self::e( $a['title'] ?? '' ); ?></h2>
			<p><?php echo self::e( $a['text'] ?? '' ); ?></p>
			<div class="row">
				<a class="btn btn-ink btn-lg" href="<?php echo self::url_or( $a['primary_url'] ?? '', '/contact-us/' ); ?>"><?php echo self::e( $a['primary_text'] ?? '' ); ?> ←</a>
				<a class="btn btn-white btn-lg" href="<?php echo self::url_or( $a['secondary_url'] ?? '', '/pricing/' ); ?>"><?php echo self::e( $a['secondary_text'] ?? '' ); ?></a>
			</div>
		</div>
	</div>
</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Wraps the whole section body (used by front-page.php and every widget).
	 *
	 * @param bool $flat Drops the soft gradient tints so a user-chosen section
	 *                   background colour is not covered by them.
	 */
	public static function wrap_open( bool $flat = false ): string {
		return '<div class="zc-hp' . ( $flat ? ' is-flat' : '' ) . '" dir="rtl">';
	}

	public static function wrap_close(): string {
		return '</div>';
	}
}
