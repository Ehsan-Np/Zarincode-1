<?php
/** Native comment relationships and Woo review integrity, independently of presentation.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Content;

defined( 'ABSPATH' ) || exit;
/** Owns persistent content independently of the active theme. */
final class Discussion_Service {
	/**
	 * Register content validation using WordPress hooks.
	 */
	public static function boot(): void {
		add_filter( 'preprocess_comment', array( self::class, 'prepare' ), -5 );
		if ( class_exists( 'WC_Comments' ) ) {
			remove_action( 'pre_comment_on_post', array( 'WC_Comments', 'validate_product_review_verified_owners' ) );
			add_action( 'pre_comment_on_post', array( self::class, 'verify_buyer' ) );
		}
	}
	/**
	 * Keep Woo verified-buyer validation except approved staff replies.
	 *
	 * @param int $post Target post ID.
	 */
	public static function verify_buyer( int $post ): void {
		if ( self::requested_parent( (int) $post, 'comment_parent' ) && self::staff() ) {
			return;
		}
		\WC_Comments::validate_product_review_verified_owners( $post );
	}
	/**
	 * Check the actual moderation or WooCommerce capability.
	 */
	public static function staff(): bool {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'moderate_comments' );}
	/**
	 * Resolve only approved public comments on the same post.
	 *
	 * @param int $id Comment ID.
	 * @param int $post Target post ID.
	 */
	public static function parent( int $id, int $post ): ?\WP_Comment {
		if ( ! $id ) {
			return null;
		}$c = get_comment( $id );
		return $c instanceof \WP_Comment && (int) $c->comment_post_ID === $post && '1' === $c->comment_approved && in_array( $c->comment_type, array( '', 'comment', 'review' ), true ) ? $c : null;
	}
	/**
	 * Read a parent selection without modifying any persistent data.
	 *
	 * @param int    $post Target post ID.
	 * @param string $key Public selection key.
	 */
	public static function requested_parent( int $post, string $key = 'replytocom' ): ?\WP_Comment {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Read-only public selection; native WordPress authorizes comment submission.
		$source = 'comment_parent' === $key ? $_POST : $_GET;
		$v      = $source[ $key ] ?? 0;
		if ( ! is_scalar( $v ) || ! ctype_digit( (string) $v ) ) {
			return null;
		}return self::parent( (int) $v, $post );
	}
	/**
	 * Bound traversal and detect historical parent cycles.
	 *
	 * @param \WP_Comment $comment Starting comment.
	 */
	public static function depth( \WP_Comment $comment ): int {
		$depth = 1;
		$seen  = array( $comment->comment_ID => true );
		while ( $comment->comment_parent && $depth < 11 ) {
			$parent = get_comment( $comment->comment_parent );
			if ( ! $parent instanceof \WP_Comment || isset( $seen[ $parent->comment_ID ] ) ) {
				break;
			}$seen[ $parent->comment_ID ] = true;
			++$depth;
			$comment = $parent;
		}return $depth;}
	/**
	 * Reject invalid parent relationships and prevent reply votes.
	 *
	 * @param array<string,mixed> $data WordPress comment submission.
	 * @return array<string,mixed>
	 */
	public static function prepare( array $data ): array {
		$raw = $data['comment_parent'] ?? 0;
		if ( ! $raw ) {
			return $data;
		}
		$post_id = is_numeric( $data['comment_post_ID'] ?? null ) ? (int) $data['comment_post_ID'] : 0;
		$parent  = is_scalar( $raw ) && ctype_digit( (string) $raw ) ? self::parent( (int) $raw, $post_id ) : null;
		$limit   = get_option( 'thread_comments_depth', 5 );
		$limit   = is_numeric( $limit ) ? min( 10, max( 1, (int) $limit ) ) : 5;
		if ( ! $parent || self::depth( $parent ) >= $limit ) {
			wp_die( 'دیدگاه مقصد معتبر نیست یا به بیشترین عمق پاسخ رسیده‌اید.', 'پاسخ نامعتبر', array( 'response' => 400 ) );
		}
		// A response discusses the parent; it is not another vote for the article/product.
		unset( $_POST['rating'], $_POST['zc_article_rating'] );
		return $data;
	}
}
