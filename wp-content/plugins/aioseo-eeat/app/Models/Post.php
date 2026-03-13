<?php
namespace AIOSEO\Plugin\Addon\Eeat\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads core classes.
 *
 * @since 1.0.0
 */
class Post {
	/**
	 * Sanitizes the post data and sets it (or the default value) to the Post object.
	 *
	 * @since 1.0.0
	 *
	 * @param  int   $postId  The post ID.
	 * @param  Post  $thePost The Post object.
	 * @param  array $data    The data.
	 * @return Post           The Post object with data set.
	 */
	public function sanitizeAndSetDefaults( $postId, $thePost, $data ) {
		$thePost->reviewed_by = isset( $data['reviewed_by'] ) ? (int) sanitize_text_field( $data['reviewed_by'] ) : null;

		return $thePost;
	}
}