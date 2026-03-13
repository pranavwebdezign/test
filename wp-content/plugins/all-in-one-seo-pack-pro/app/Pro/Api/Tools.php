<?php
namespace AIOSEO\Plugin\Pro\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Api as CommonApi;
use AIOSEO\Plugin\Pro\Redirects\Api as RedirectsApi;

/**
 * Route class for the API.
 *
 * @since 4.0.0
 */
class Tools extends CommonApi\Tools {
	/**
	 * Restore a settings backup.
	 *
	 * @since 4.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function restoreBackup( $request ) {
		$body   = $request->get_json_params();
		$siteId = ! empty( $body['siteId'] ) ? (int) $body['siteId'] : get_current_blog_id();

		// Ensure the user has access to the target site.
		if (
			is_multisite() &&
			(
				! is_user_member_of_blog( get_current_user_id(), $siteId ) &&
				! is_super_admin()
			)
		) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'You do not have permission to access this site.'
			], 403 );
		}

		aioseo()->helpers->switchToBlog( $siteId );

		$response = parent::restoreBackup( $request );

		$response->data['license'] = [
			'isActive'   => aioseo()->license->isActive(),
			'isExpired'  => aioseo()->license->isExpired(),
			'isDisabled' => aioseo()->license->isDisabled(),
			'isInvalid'  => aioseo()->license->isInvalid(),
			'expires'    => aioseo()->internalOptions->internal->license->expires
		];

		return $response;
	}

	/**
	 * Clear the passed in log.
	 *
	 * @since 4.1.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response The response.
	 */
	public static function clearLog( $request ) {
		$response = new \WP_REST_Response( [
			'success' => true,
			'logSize' => 0
		], 200 );

		$response = RedirectsApi\Tools::clearLog( $request, $response );

		return Api::addonsApi( $request, $response, '\\Api\\Tools', 'clearLog' );
	}

	/**
	 * Create a settings backup.
	 *
	 * @since 4.2.5
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function createBackup( $request ) {
		$body   = $request->get_json_params();
		$siteId = ! empty( $body['siteId'] ) ? (int) $body['siteId'] : get_current_blog_id();

		// Ensure the user has access to the target site.
		if (
			is_multisite() &&
			(
				! is_user_member_of_blog( get_current_user_id(), $siteId ) &&
				! is_super_admin()
			)
		) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'You do not have permission to access this site.'
			], 403 );
		}

		aioseo()->helpers->switchToBlog( $siteId );

		return parent::createBackup( $request );
	}

	/**
	 * Delete a settings backup.
	 *
	 * @since 4.2.5
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function deleteBackup( $request ) {
		$body   = $request->get_json_params();
		$siteId = ! empty( $body['siteId'] ) ? (int) $body['siteId'] : get_current_blog_id();

		// Ensure the user has access to the target site.
		if (
			is_multisite() &&
			(
				! is_user_member_of_blog( get_current_user_id(), $siteId ) &&
				! is_super_admin()
			)
		) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'You do not have permission to access this site.'
			], 403 );
		}

		aioseo()->helpers->switchToBlog( $siteId );

		return parent::deleteBackup( $request );
	}
}