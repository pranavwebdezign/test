<?php
namespace AIOSEO\Plugin\Addon\Eeat\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all user related endpoints.
 *
 * @since 1.0.0
 */
class Users {
	/**
	 * Returns a list of authors.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The request.
	 * @return \WP_REST_Response          The response.
	 */
	public static function getAuthors( $request ) {
		$params     = $request->get_json_params();
		$reviewerId = ! empty( $params['reviewerId'] ) ? intval( $params['reviewerId'] ) : '';
		$searchTerm = ! empty( $params['searchTerm'] ) ? sanitize_text_field( $params['searchTerm'] ) : '';
		$cacheKey      = 'aioseo_eeat_get_authors_result_' . $reviewerId;

		$cachedResults = empty( $searchTerm ) ? aioseo()->core->cache->get( $cacheKey ) : null;
		if ( $cachedResults ) {
			return new \WP_REST_Response( [
				'success' => true,
				'authors' => $cachedResults
			], 200 );
		}

		$allowedRoles = apply_filters( 'aioseo_eeat_reviewer_roles', [ 'author', 'editor', 'administrator', 'aioseo_manager', 'aioseo_editor', 'contributor', 'shop_manager' ] );

		$args = [
			'role__in' => $allowedRoles,
			'fields'   => [ 'ID', 'display_name', 'user_email' ],
			'orderby'  => 'display_name',
			'order'    => 'ASC',
			'number'   => 10
		];

		if ( $searchTerm ) {
			$args += [
				'search'     => '*' . $searchTerm . '*',
				'search_col' => [ 'ID', 'display_name', 'user_email' ],
			];
		}

		$args    = apply_filters( 'aioseo_eeat_reviewer_list_args', $args );
		$authors = get_users( $args );

		if ( $reviewerId ) {
			$reviewer = get_user_by( 'ID', $reviewerId );
			if ( is_a( $reviewer, 'WP_User' ) ) {
				array_unshift( $authors, [
					'ID'           => $reviewer->ID,
					'display_name' => $reviewer->display_name,
					'user_email'   => $reviewer->user_email
				] );
			}
		}

		if ( empty( $searchTerm ) ) {
			aioseo()->core->cache->update( $cacheKey, $authors, HOUR_IN_SECONDS );
		}

		return new \WP_REST_Response( [
			'success' => true,
			'authors' => $authors
		], 200 );
	}
}