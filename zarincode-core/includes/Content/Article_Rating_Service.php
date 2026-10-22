<?php
/** WordPress integration for optional comment-rating persistence.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Content;

defined( 'ABSPATH' ) || exit;

/** Validation cannot be moved into a visual editor or a presentation template. */
final class Article_Rating_Service {
	/** Register only when the optional rating feature is enabled. */
	public static function boot(): void {
		add_filter( 'preprocess_comment', array( self::class, 'validate' ) );
		add_action( 'comment_post', array( self::class, 'capture' ) );
	}

	/**
	 * Read and verify an optional submitted rating using the existing stable nonce contract.
	 *
	 * @param int $post_id Target article.
	 * @return int|null Valid rating, -1 for invalid input, or null for no submission.
	 */
	private static function submitted( int $post_id ): ?int {
		if ( ! isset( $_POST['zc_article_rating'] ) ) {
			return null;
		}
		if ( ! isset( $_POST['zc_rating_nonce'] ) ) {
			return -1;
		}
		if ( ! is_string( $_POST['zc_article_rating'] ) || ! is_string( $_POST['zc_rating_nonce'] ) ) {
			return -1;
		}
		$rating = sanitize_text_field( wp_unslash( $_POST['zc_article_rating'] ) );
		$nonce  = sanitize_text_field( is_string( $_POST['zc_rating_nonce'] ) ? wp_unslash( $_POST['zc_rating_nonce'] ) : '' );
		if ( ! preg_match( '/^[1-5]$/D', (string) $rating ) ) {
			return -1;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( $nonce ), 'zc_article_rating_' . $post_id ) ) {
			return -1;
		}
		return (int) $rating;
	}

	/**
	 * Reject a forged rating before WordPress inserts the comment.
	 *
	 * @param array<string,mixed> $data WordPress comment data.
	 * @return array<string,mixed>
	 */
	public static function validate( array $data ): array {
		$raw_id  = $data['comment_post_ID'] ?? 0;
		$post_id = is_numeric( $raw_id ) ? absint( $raw_id ) : 0;
		if ( 'post' === get_post_type( $post_id ) && -1 === self::submitted( $post_id ) ) {
			wp_die( esc_html__( 'The rating or submission session is invalid. Refresh the page and try again.', 'zarincode-core' ), '', array( 'response' => 400 ) );
		}
		return $data;
	}

	/**
	 * Store at most one vote per comment. Existing metadata is never rewritten.
	 *
	 * @param int $comment_id New comment.
	 */
	public static function capture( int $comment_id ): void {
		if ( $comment_id < 1 ) {
			return;
		}
		$comment = get_comment( $comment_id );
		if ( ! $comment instanceof \WP_Comment || 'post' !== get_post_type( (int) $comment->comment_post_ID ) ) {
			return;
		}
		$rating = self::submitted( (int) $comment->comment_post_ID );
		if ( null !== $rating && $rating >= 1 ) {
			add_comment_meta( $comment_id, 'zc_article_rating', $rating, true );
		}
	}
}
