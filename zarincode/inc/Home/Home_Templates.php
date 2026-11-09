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
			<h1 class="big"><?php echo self::e( $a['title_line1'] ?? '' ); ?> <span class="hl"><?php echo self::e( $a['title_highlight'] ?? '' ); ?></span>،<br><?php echo self::e( $a['title_line2'] ?? '' ); ?><br><span class="pop"><?php echo self::e( $a['title_pop'] ?? '' ); ?></span></h1>
			<p class="lead"><?php echo self::e( $a['lead'] ?? '' ); ?></p>
			<div class="hero-actions">
				<a class="btn btn-gold btn-lg" href="<?php echo self::url( $a['primary_url'] ?? '' ); ?>"><?php echo self::ico( 'rocket' ); ?> <?php echo self::e( $a['primary_text'] ?? '' ); ?></a>
				<a class="btn btn-white btn-lg" href="<?php echo self::url( $a['secondary_url'] ?? '' ); ?>"><?php echo self::e( $a['secondary_text'] ?? '' ); ?> ←</a>
				<a class="btn btn-peach btn-lg" href="<?php echo self::url( $a['tertiary_url'] ?? '' ); ?>"><?php echo self::ico( 'phone' ); ?> <?php echo self::e( $a['tertiary_text'] ?? '' ); ?></a>
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
			<div class="code-card">
				<div class="bar"><i></i><i></i><i></i></div>
				<pre><b>function</b> <em>grow</em>(business) {
  <s>const</s> site = <em>design</em>(business, <b>true</b>);
  <s>const</s> seo  = <em>rank</em>(site, <b>'first-page'</b>);
  <s>return</s> <em>sell</em>(site + seo);
}</pre>
			</div>
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
		$out = '';
		foreach ( $items as $it ) {
			$out .= self::e( $it ) . '<b>◆</b> ';
		}
		return '<div class="ticker"><span>' . $out . '</span></div>';
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
							?>
					<span class="plogo"><?php echo self::ico( (string) ( $p['icon'] ?? 'building' ) ); ?> <?php echo self::e( $p['name'] ?? '' ); ?></span>
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
		$title_html = self::e( $title ) . ' <span class="g">' . self::e( $high ) . '</span>' . ( $after !== '' ? ' ' . self::e( $after ) : '' );
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
						<a class="smore" href="<?php echo self::url( $it['link'] ?? '' ); ?>"><?php echo self::e( $it['link_text'] ?? 'جزئیات' ); ?> <span class="ar">←</span></a>
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
		<?php echo self::head( $a, (string) ( $a['link_text'] ?? '' ), (string) ( $a['link_url'] ?? '/shop/' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="grid4">
			<?php foreach ( $items as $i => $it ) : $cover = $covers[ $i % 4 ]; ?>
			<div class="pcard">
				<div class="pcover <?php echo esc_attr( $cover ); ?>">
					<span class="ico-tile"><?php echo self::ico( (string) ( $it['icon'] ?? 'cubes' ) ); ?></span>
					<?php if ( ! empty( $it['discount'] ) ) : ?><span class="badge"><?php echo self::e( $it['discount'] ); ?></span><?php endif; ?>
					<span class="kind"><?php echo self::e( $it['kind'] ?? '' ); ?></span>
				</div>
				<div class="pbody">
					<h4><?php echo self::e( $it['title'] ?? '' ); ?></h4>
					<?php if ( ! empty( $it['tags'] ) && is_array( $it['tags'] ) ) : ?>
					<div class="pchips"><?php foreach ( $it['tags'] as $t ) : ?><span><?php echo self::e( $t ); ?></span><?php endforeach; ?></div>
					<?php endif; ?>
					<div class="prow">
						<span>
							<?php if ( ! empty( $it['old'] ) ) : ?><span class="old"><?php echo self::e( $it['old'] ); ?></span><?php endif; ?>
							<span class="now"><?php echo self::e( $it['now'] ?? '' ); ?><?php if ( ! empty( $a['currency'] ) ) : ?> <small><?php echo self::e( $a['currency'] ); ?></small><?php endif; ?></span>
						</span>
						<a class="add <?php echo ! empty( $it['featured'] ) ? 'g' : ''; ?>" href="<?php echo self::url( $it['link'] ?? '' ); ?>"><?php echo self::e( $it['cta'] ?? ( $a['cta_text'] ?? '' ) ); ?></a>
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
		<?php echo self::head( $a, (string) ( $a['link_text'] ?? '' ), (string) ( $a['link_url'] ?? '/projects-portfolio/' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="grid3">
			<?php foreach ( $items as $i => $it ) : ?>
			<div class="jcard">
				<div class="jcover <?php echo esc_attr( $tones[ $i % 3 ] ); ?>">
					<span class="yr"><?php echo self::e( $it['year'] ?? '' ); ?></span>
					<div class="win"></div>
					<?php if ( ! empty( $it['kpi'] ) ) : ?><span class="kpi"><?php echo self::e( $it['kpi'] ); ?></span><?php endif; ?>
					<span class="ico-tile"><?php echo self::ico( (string) ( $it['icon'] ?? 'display' ) ); ?></span>
				</div>
				<div class="jbody">
					<h4><?php echo self::e( $it['title'] ?? '' ); ?></h4>
					<div class="jrow">
						<span class="cat <?php echo esc_attr( (string) ( $it['tone'] ?? 'c1' ) ); ?>"><?php echo self::e( $it['cat'] ?? '' ); ?></span>
						<a class="go" href="<?php echo self::url( $it['link'] ?? '' ); ?>"><?php echo self::e( $it['cta'] ?? ( $a['cta_text'] ?? '' ) ); ?> ←</a>
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
		<?php if ( $tabs ) : ?>
		<div class="chips">
			<?php foreach ( $tabs as $i => $t ) : ?>
			<span class="chip <?php echo 0 === $i ? 'on' : ''; ?>"><?php echo self::e( $t ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<div class="plans">
			<?php foreach ( $plans as $p ) : ?>
			<div class="plan <?php echo esc_attr( (string) ( $p['tone'] ?? 'p1' ) ); ?>">
				<?php if ( ! empty( $p['flag'] ) ) : ?><span class="flag"><?php echo self::e( $p['flag'] ); ?></span><?php endif; ?>
				<h4><?php echo self::e( $p['title'] ?? '' ); ?></h4>
				<div class="sub"><?php echo self::e( $p['delivery'] ?? '' ); ?></div>
				<div class="pprice"><span><?php echo self::e( $a['price_label'] ?? '' ); ?></span><b><?php echo self::e( $p['price'] ?? '' ); ?><?php if ( ! empty( $a['price_unit'] ) ) : ?> <small><?php echo self::e( $a['price_unit'] ); ?></small><?php endif; ?></b></div>
				<?php if ( ! empty( $p['features'] ) && is_array( $p['features'] ) ) : ?>
				<ul><?php foreach ( $p['features'] as $f ) : ?><li><?php echo self::e( $f ); ?></li><?php endforeach; ?></ul>
				<?php endif; ?>
				<a class="btn btn-ink" href="<?php echo self::url( $p['link'] ?? '' ); ?>"><?php echo self::e( $p['cta'] ?? 'مشاوره و ثبت سفارش' ); ?></a>
			</div>
			<?php endforeach; ?>
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
				<div class="stars" aria-label="<?php echo esc_attr( (string) ( $t['stars'] ?? 5 ) ); ?> از ۵"><?php echo wp_kses_post( self::stars( (int) ( $t['stars'] ?? 5 ) ) ); ?></div>
				<p><?php echo self::e( $t['text'] ?? '' ); ?></p>
				<div class="who">
					<span class="ava"><?php echo self::e( $t['initial'] ?? 'م' ); ?></span>
					<span><b><?php echo self::e( $t['name'] ?? '' ); ?></b><span><?php echo self::e( $t['role'] ?? '' ); ?></span></span>
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
		<?php echo self::head( $a, (string) ( $a['link_text'] ?? '' ), (string) ( $a['link_url'] ?? '/blog/' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="bgrid">
			<article class="featured">
				<div class="fcover">
					<div class="dots"></div>
					<?php if ( ! empty( $feat['seal'] ) ) : ?><span class="seal">✦ <?php echo self::e( $feat['seal'] ); ?></span><?php endif; ?>
					<span class="cat"><?php echo self::e( $feat['cat'] ?? '' ); ?></span>
					<span class="glyph"><?php echo self::ico( (string) ( $feat['icon'] ?? 'newspaper' ) ); ?></span>
					<div class="ttl"><?php echo self::e( $feat['title'] ?? '' ); ?></div>
				</div>
				<div class="fbody">
					<p><?php echo self::e( $feat['excerpt'] ?? '' ); ?></p>
					<a class="continue" href="<?php echo self::url( $feat['link'] ?? '' ); ?>"><?php echo self::e( $a['read_more'] ?? '' ); ?> <span class="ar">←</span></a>
					<div class="bmeta">
						<div class="who2"><span class="ava2">ض</span><span><b><?php echo self::e( $feat['author'] ?? '' ); ?></b><span><?php echo self::e( $feat['date'] ?? '' ); ?></span></span></div>
						<span class="timepill">🕐 <?php echo self::e( $feat['read'] ?? '' ); ?></span>
					</div>
				</div>
			</article>
			<div class="stack">
				<?php foreach ( $side as $i => $b ) : ?>
				<article class="scard <?php echo esc_attr( (string) ( $b['tone'] ?? 'sc1' ) ); ?>">
					<div class="scover2"><span class="g"><?php echo self::ico( (string) ( $b['icon'] ?? 'newspaper' ) ); ?></span><span class="pill"><?php echo self::e( $b['pill'] ?? '' ); ?></span></div>
					<div class="sbody2">
						<div class="tags">
							<?php foreach ( (array) ( $b['tags'] ?? array() ) as $k => $tag ) : ?>
							<span class="tag <?php echo esc_attr( array( 'a', 'b', 'c' )[ $k % 3 ] ); ?>"><?php echo self::e( $tag ); ?></span>
							<?php endforeach; ?>
						</div>
						<h3><?php echo self::e( $b['title'] ?? '' ); ?></h3>
						<p><?php echo self::e( $b['excerpt'] ?? '' ); ?></p>
						<div class="bmeta">
							<div class="who2"><span class="ava2" style="width:32px;height:32px;font-size:12px">ض</span><span><b><?php echo self::e( $b['author'] ?? '' ); ?></b><span><?php echo self::e( $b['date'] ?? '' ); ?></span></span></div>
							<span class="timepill" style="font-size:11.5px"><?php echo self::e( $b['read'] ?? '' ); ?></span>
						</div>
					</div>
				</article>
				<?php endforeach; ?>
			</div>
			<div class="side">
				<div class="popular">
					<div class="pop-head"><span class="ic"><?php echo self::ico( 'chart-simple' ); ?></span><span><b><?php echo self::e( $a['popular_title'] ?? '' ); ?></b><span><?php echo self::e( $a['popular_sub'] ?? '' ); ?></span></span></div>
					<?php foreach ( $pop as $i => $p ) : ?>
					<div class="pop-item"><span class="rank r<?php echo (int) ( $i + 1 ); ?>"><?php echo self::e( sprintf( '%02d', $i + 1 ) ); ?></span><div><h4><?php echo self::e( $p['title'] ?? '' ); ?></h4><div class="pm">◆ <?php echo self::e( $p['meta'] ?? '' ); ?></div></div></div>
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
				<a class="btn btn-ink btn-lg" href="<?php echo self::url( $a['primary_url'] ?? '' ); ?>"><?php echo self::e( $a['primary_text'] ?? '' ); ?> ←</a>
				<a class="btn btn-white btn-lg" href="<?php echo self::url( $a['secondary_url'] ?? '' ); ?>"><?php echo self::e( $a['secondary_text'] ?? '' ); ?></a>
			</div>
		</div>
	</div>
</section>
		<?php
		return (string) ob_get_clean();
	}

	/** Wrap the whole homepage body (used by front-page.php). */
	public static function wrap_open(): string {
		return '<div class="zc-hp" dir="rtl">';
	}

	public static function wrap_close(): string {
		return '</div>';
	}
}
