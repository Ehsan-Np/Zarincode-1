<?php
/**
 * The one place the theme talks about icons.
 *
 * Why this class exists
 * ---------------------
 * The theme used to print icon markup in three different dialects:
 *
 *   1. `<span class="fa">&#xf108;</span>`  — a bare base class plus a raw
 *      private-use code point (Home_Templates::ico()).
 *   2. `<i class="fas fa-circle-check">`  — a Font Awesome **5** name, still
 *      used by the Elementor models and the header/footer builder.
 *   3. `<i class="fa-solid fa-house-laptop">`  — Font Awesome 6 names that
 *      Font Awesome 7 renamed or dropped.
 *
 * Font Awesome 7 is the version the theme ships (assets/fontawesome, Free 7.0.0).
 * Two of those three dialects do not survive it:
 *
 *   - `.fa` on its own no longer carries a font-family in the Pro build and, in
 *     the theme, the Elementor typography layer (`body .elementor-widget-container
 *     :where(...):not([class*="fa-"]):not(i)`) forced Vazirmatn on it — so every
 *     homepage glyph rendered as a tofu box.
 *   - FA5 names (`check-circle`, `shield-alt`, `undo`, …) simply do not exist in
 *     the 7.x font, so they render as tofu too, no matter which family is set.
 *
 * The rule from now on: **no template prints a Font Awesome class or a code
 * point by hand.** They all go through Icons::render() / Icons::fa_class(), which
 * (a) emit the style-agnostic `fa fa-solid fa-<name>` markup that the shipped CSS
 * understands, and (b) translate every legacy FA5/FA6 name to its 7.x name.
 *
 * None of this adds a dependency: the names are the ones Font Awesome Free 7
 * actually contains (verified against metadata/icons.json of the shipped build).
 *
 * @package Zarincode\Theme\Core
 */

namespace Zarincode\Theme\Core;

defined( 'ABSPATH' ) || exit;

final class Icons {

	/** Font Awesome build shipped in assets/fontawesome. */
	public const VERSION = '7.0.0';

	/** Style class per Font Awesome style. */
	private const STYLES = array(
		'solid'   => 'fa-solid',
		'regular' => 'fa-regular',
		'brands'  => 'fa-brands',
	);

	/**
	 * Legacy name => Font Awesome 7 Free name.
	 *
	 * Everything on the left was used somewhere in the theme or the companion
	 * plugin and is either an FA5 name (renamed in 6.0) or an FA6 name that 7.x
	 * dropped. The right-hand side is verified to exist in the shipped build.
	 */
	private const RENAMES = array(
		'check-circle'      => 'circle-check',
		'check-circle-o'    => 'circle-check',
		'times-circle'      => 'circle-xmark',
		'circle-o-notch'    => 'circle-notch',
		'shield-alt'        => 'shield-halved',
		'shield-alt-o'      => 'shield-halved',
		'undo'              => 'rotate-left',
		'undo-alt'          => 'rotate-left',
		'redo'              => 'rotate-right',
		'shopping-bag'      => 'bag-shopping',
		'shopping-basket'   => 'basket-shopping',
		'balance-scale'     => 'scale-balanced',
		'globe-europe'      => 'earth-europe',
		'globe-americas'    => 'earth-americas',
		'file-alt'          => 'file-lines',
		'file-archive'      => 'file-zipper',
		'file-text'         => 'file-lines',
		'magic'             => 'wand-magic-sparkles',
		'sparkles'          => 'wand-magic-sparkles',
		'user-edit'         => 'user-pen',
		'search'            => 'magnifying-glass',
		'pencil-ruler'      => 'pen-ruler',
		'pencil-alt'        => 'pencil',
		'exchange-alt'      => 'right-left',
		'laptop-house'      => 'house-laptop',
		'arrow-up-left'     => 'arrow-turn-up',
		'bar-chart'         => 'chart-bar',
		'chart-simple'      => 'chart-line',
		'home'              => 'house',
		'cog'               => 'gear',
		'times'             => 'xmark',
		'map-marker-alt'    => 'location-dot',
		'external-link-alt' => 'arrow-up-right-from-square',
		'envelope-open-text'=> 'envelope-open-text',
		'mouse-pointer'     => 'arrow-pointer',
		'trash-alt'         => 'trash-can',
		'comment-alt'       => 'comment',
		'comments'          => 'comments',
		'bars'              => 'bars',
		'sign-out-alt'      => 'right-from-bracket',
		'sign-in-alt'       => 'right-to-bracket',
		'user-circle'       => 'circle-user',
		'question-circle'   => 'circle-question',
		'info-circle'       => 'circle-info',
		'exclamation-circle'=> 'circle-exclamation',
		'exclamation-triangle' => 'triangle-exclamation',
		'arrow-circle-left' => 'circle-arrow-left',
		'arrow-circle-right'=> 'circle-arrow-right',
		'arrow-alt-circle-right' => 'circle-right',
		'long-arrow-alt-left'    => 'arrow-left-long',
		'long-arrow-alt-right'   => 'arrow-right-long',
		'thumb-tack'        => 'thumbtack',
		'chart-pie'         => 'chart-pie',
		'analytics'         => 'chart-line',
		'code-branch'       => 'code-branch',
		'project-diagram'   => 'diagram-project',
		'sitemap'           => 'sitemap',
		'network-wired'     => 'network-wired',
		'tachometer-alt'    => 'gauge-high',
		'clipboard-check'   => 'clipboard-check',
		'clipboard-list'    => 'clipboard-list',
		'hand-point-right'  => 'hand-point-right',
		'hands-helping'     => 'handshake-angle',
		'handshake'         => 'handshake',
	);

	/**
	 * Semantic shortcuts used by the templates ("sparkles" style keys).
	 *
	 * The homepage models address icons by a short semantic name so an editor can
	 * pick one from a Persian list. Each key maps to [style, Font Awesome name].
	 */
	private const SEMANTIC = array(
		'circle-check'      => array( 'solid', 'circle-check' ),
		'star'              => array( 'solid', 'star' ),
		'shield-halved'     => array( 'solid', 'shield-halved' ),
		'bolt'              => array( 'solid', 'bolt' ),
		'chart-line'        => array( 'solid', 'chart-line' ),
		'handshake'         => array( 'solid', 'handshake' ),
		'cart-shopping'     => array( 'solid', 'cart-shopping' ),
		'magnifying-glass'  => array( 'solid', 'magnifying-glass' ),
		'comments'          => array( 'solid', 'comments' ),
		'display'           => array( 'solid', 'display' ),
		'gears'             => array( 'solid', 'gears' ),
		'arrow-trend-up'    => array( 'solid', 'arrow-trend-up' ),
		'pen-nib'           => array( 'solid', 'pen-nib' ),
		'hashtag'           => array( 'solid', 'hashtag' ),
		'cubes'             => array( 'solid', 'cubes' ),
		'graduation-cap'    => array( 'solid', 'graduation-cap' ),
		'robot'             => array( 'solid', 'robot' ),
		'building'          => array( 'solid', 'building' ),
		'palette'           => array( 'solid', 'palette' ),
		'hospital'          => array( 'solid', 'hospital' ),
		'newspaper'         => array( 'solid', 'newspaper' ),
		'compass'           => array( 'solid', 'compass' ),
		'bullseye'          => array( 'solid', 'bullseye' ),
		'layer-group'       => array( 'solid', 'layer-group' ),
		'gift'              => array( 'solid', 'gift' ),
		'pen-ruler'         => array( 'solid', 'pen-ruler' ),
		'screwdriver-wrench'=> array( 'solid', 'screwdriver-wrench' ),
		'rocket'            => array( 'solid', 'rocket' ),
		'wordpress'         => array( 'brands', 'wordpress' ),
		'envelope'          => array( 'solid', 'envelope' ),
		'cloud'             => array( 'solid', 'cloud' ),
		'building-columns'  => array( 'solid', 'building-columns' ),
		'globe'             => array( 'solid', 'globe' ),
		'server'            => array( 'solid', 'server' ),
		'users'             => array( 'solid', 'users' ),
		'clock'             => array( 'solid', 'clock' ),
		'file-lines'        => array( 'solid', 'file-lines' ),
		'phone'             => array( 'solid', 'phone' ),
		'arrow-left'        => array( 'solid', 'arrow-left' ),
		'quote-right'       => array( 'solid', 'quote-right' ),
		'sparkles'          => array( 'solid', 'wand-magic-sparkles' ),
		'user'              => array( 'solid', 'user' ),
	);

	/** Semantic name => Font Awesome name (kept for the elementor pickers). */
	public static function semantic_map(): array {
		$out = array();
		foreach ( self::SEMANTIC as $key => $pair ) {
			$out[ $key ] = $pair[1];
		}
		return $out;
	}

	/** Style of a semantic icon ("solid" when unknown). */
	public static function semantic_style( string $key ): string {
		return self::SEMANTIC[ $key ][0] ?? 'solid';
	}

	/**
	 * Turns one legacy Font Awesome name into the name the shipped font has.
	 *
	 * Accepts "check-circle", "fa-circle-check" or "fas fa-circle-check" and
	 * returns the bare, current name ("circle-check").
	 */
	public static function fa_name( string $name ): string {
		$name = trim( $name );
		$name = (string) preg_replace( '/^(fa[srlbd]?|fa-solid|fa-regular|fa-brands)\s+/', '', $name );
		$name = (string) preg_replace( '/^fa-/', '', $name );
		$name = strtolower( $name );
		return self::RENAMES[ $name ] ?? $name;
	}

	/** Full class list for one icon: "fa fa-solid fa-circle-check". */
	public static function fa_class( string $name, string $style = 'solid' ): string {
		$fa_name    = self::fa_name( $name );
		$style      = self::STYLES[ $style ] ?? self::STYLES['solid'];
		// `fa` stays in the list on purpose: it is the size/line-box base class in
		// Font Awesome 6 and 7, and the project CSS keys a few layout rules off it.
		return trim( 'fa ' . $style . ' fa-' . $fa_name );
	}

	/**
	 * The only icon markup the templates are allowed to print.
	 *
	 * @param string $name   Semantic key ("sparkles") or a Font Awesome name.
	 * @param array  $args   {
	 *     @type string $class Extra classes for the element.
	 *     @type string $style solid|regular|brands (ignored for semantic keys).
	 *     @type string $label Accessible label. Empty = decorative (aria-hidden).
	 *     @type string $tag   span|i (default span).
	 * }
	 */
	public static function render( string $name, array $args = array() ): string {
		$name = trim( $name );
		if ( '' === $name ) {
			return '';
		}
		$style = isset( self::SEMANTIC[ $name ] ) ? self::SEMANTIC[ $name ][0] : ( $args['style'] ?? 'solid' );
		$fa    = isset( self::SEMANTIC[ $name ] ) ? self::SEMANTIC[ $name ][1] : $name;
		$tag   = (string) ( $args['tag'] ?? 'span' );
		$tag   = in_array( $tag, array( 'span', 'i' ), true ) ? $tag : 'span';

		$class = self::fa_class( $fa, $style );
		if ( ! empty( $args['class'] ) ) {
			$class .= ' ' . trim( (string) $args['class'] );
		}
		$label = (string) ( $args['label'] ?? '' );

		$attrs = ' class="' . esc_attr( $class ) . '"';
		$attrs .= ' data-zc-icon="' . esc_attr( $fa ) . '"';
		if ( '' === $label ) {
			$attrs .= ' aria-hidden="true"';
		} else {
			$attrs .= ' role="img" aria-label="' . esc_attr( $label ) . '"';
		}

		return '<' . $tag . $attrs . '></' . $tag . '>';
	}

	/**
	 * Rewrites a Font Awesome class list written by an older version.
	 *
	 * Used by the header/footer builder and the Elementor sanitizer so a
	 * document stored as `fas fa-circle-check` renders with `circle-check` after
	 * an upgrade, without touching the saved data.
	 */
	public static function normalize_classes( string $classes ): string {
		$tokens = preg_split( '/\s+/', trim( $classes ) ) ?: array();
		$style  = 'solid';
		$out    = array( 'fa' );
		foreach ( $tokens as $token ) {
			switch ( $token ) {
				case 'fas':
				case 'fa-solid':
				case 'fa':
					$style = 'solid';
					break;
				case 'far':
				case 'fa-regular':
					$style = 'regular';
					break;
				case 'fab':
				case 'fa-brands':
					$style = 'brands';
					break;
				default:
					if ( 0 === strpos( $token, 'fa-' ) ) {
						$out[] = 'fa-' . self::fa_name( $token );
					}
			}
		}
		$out[] = self::STYLES[ $style ];
		return implode( ' ', array_unique( $out ) );
	}

	/**
	 * Elementor icon control value (`['value' => 'fas fa-x', 'library' => 'fa-solid']`)
	 * normalized to the shipped font. Replaces the old FA5 aliasing pass.
	 */
	public static function normalize_icon_value( $icon ): array {
		if ( is_string( $icon ) ) {
			$icon = array( 'value' => $icon, 'library' => 'fa-solid' );
		}
		if ( ! is_array( $icon ) || empty( $icon['value'] ) || ! is_string( $icon['value'] ) ) {
			return array( 'value' => '', 'library' => '' );
		}
		$value   = trim( $icon['value'] );
		$library = (string) ( $icon['library'] ?? 'fa-solid' );
		if ( 'svg' === $library || 0 === strpos( $library, 'e-' ) ) {
			return $icon; // Uploaded SVG and Elementor's own icons are untouched.
		}
		if ( ! preg_match( '/\bfa[-srlbd]?\b|\bfa-(solid|regular|brands)\b/', $value ) && 0 !== strpos( $value, 'fa-' ) ) {
			$value = 'fas ' . $value;
		}
		return array( 'value' => self::normalize_classes( $value ), 'library' => 'fa-solid' );
	}
}
