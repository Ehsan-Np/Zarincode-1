<?php
/** Repository for moderated article ratings.
 *
 * @package ZarincodeCore
 */

namespace Zarincode\Runtime260\Content;

defined( 'ABSPATH' ) || exit;

/** Owns persistent rating data independently of the active theme. */
final class Article_Rating_Repository {
	/**
	 * Aggregate one canonical valid vote per approved public comment.
	 *
	 * Historical duplicate metadata must not multiply a comment's influence.
	 *
	 * @param int $post_id Article ID.
	 * @return array{count:int,average:float}
	 */
	public static function aggregate( int $post_id ): array {
		if ( $post_id < 1 ) {
			return array(
				'count'   => 0,
				'average' => 0.0,
			);
		}
		/**
		 * WordPress database adapter.
		 *
		 * @var \wpdb $wpdb
		 */
		global $wpdb;
		// WordPress has no aggregate API with canonical-per-comment metadata semantics.
		// No persistent cache: approval, edits and deletion must be reflected immediately.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared read-only aggregate in its repository; deliberately avoids stale moderation data.
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS votes, AVG(CAST(m.meta_value AS DECIMAL(3,1))) AS score
                FROM (
                    SELECT r.comment_id, MIN(r.meta_id) AS canonical_id
                    FROM %i r
                    INNER JOIN %i c ON c.comment_ID = r.comment_id
                    WHERE c.comment_post_ID = %d AND c.comment_approved = '1'
                    AND c.comment_parent = 0 AND c.comment_type IN ('','comment')
                    AND r.meta_key = 'zc_article_rating' AND r.meta_value IN ('1','2','3','4','5')
                    GROUP BY r.comment_id
                ) v INNER JOIN %i m ON m.meta_id = v.canonical_id",
				$wpdb->commentmeta,
				$wpdb->comments,
				$post_id,
				$wpdb->commentmeta
			),
			ARRAY_A
		);
		$row   = is_array( $row ) ? $row : array();
		$count = $row['votes'] ?? 0;
		$score = $row['score'] ?? 0;
		return array(
			'count'   => is_numeric( $count ) ? (int) $count : 0,
			'average' => is_numeric( $score ) ? round( (float) $score, 1 ) : 0.0,
		);
	}
}
