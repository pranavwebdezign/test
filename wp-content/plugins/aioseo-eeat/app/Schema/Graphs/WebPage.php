<?php
namespace AIOSEO\Plugin\Addon\Eeat\Schema\Graphs;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Models as CommonModels;
use AIOSEO\Plugin\Common\Schema\Graphs\WebPage as CoreWebPage;

/**
 * Adds additional schema for the WebPage graph.
 *
 * @since 1.0.0
 */
class WebPage {
	/**
	 * Returns the additional WebPage graph data.
	 *
	 * @since 1.0.0
	 *
	 * @param  int   $postId The user ID.
	 * @param  array $data   The graph data.
	 * @return array         The modified graph data.
	 */
	public function getAdditionalGraphData( $postId, $data ) {
		$postId = (int) $postId;
		if ( ! $postId ) {
			return $data;
		}

		$nonSupportedGraphs = [
			'CollectionPage',
			'PersonAuthor',
			'ProfilePage',
			'SearchResultsPage'
		];
		if ( in_array( $data['@type'], $nonSupportedGraphs, true ) ) {
			return $data;
		}

		if ( ! aioseo()->license->hasAddonFeature( 'aioseo-eeat', 'author-info' ) ) {
			return $data;
		}

		$post = get_post( $postId );
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return $data;
		}

		if ( ! aioseo()->license->hasAddonFeature( 'aioseo-eeat', 'post-reviewer' ) ) {
			return $data;
		}

		$thePost    = CommonModels\Post::getPost( $postId );
		$reviewerId = (int) $thePost->reviewed_by;
		if ( empty( $reviewerId ) || (int) $post->post_author === $reviewerId ) {
			return $data;
		}

		$authorMetaData = aioseoEeat()->helpers->getAuthorMetaData( $reviewerId );
		if (
			empty( $authorMetaData ) ||
			( isset( $authorMetaData['enabled'] ) && ! $authorMetaData['enabled'] )
		) {
			return $data;
		}

		$personAuthor       = new CoreWebPage\PersonAuthor();
		$data['reviewedBy'] = $personAuthor->get( $reviewerId );

		return $data;
	}
}