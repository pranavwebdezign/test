<?php
namespace AIOSEO\Plugin\Pro\Redirects\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Route class for the API.
 *
 * @since 4.9.1
 */
class Settings {
	/**
	 * Import other plugin settings.
	 *
	 * @since 4.9.1
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function importPlugins( $request ) {
		$body     = $request->get_json_params();
		$plugins  = ! empty( $body['plugins'] ) ? $body['plugins'] : [];

		foreach ( $plugins as $plugin ) {
			aioseo()->redirects->importExport->startImport( $plugin['plugin'] );
		}

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Export settings.
	 *
	 * @since 4.9.1
	 *
	 * @param  \WP_REST_Request  $request  The REST Request.
	 * @param  \WP_REST_Response $response The parent response.
	 * @return \WP_REST_Response           The response.
	 */
	public static function exportSettings( $request, $response ) {
		if ( ! aioseo()->access->hasCapability( 'aioseo_redirects_settings' ) ) {
			return $response;
		}

		$body     = $request->get_json_params();
		$settings = ! empty( $body['settings'] ) ? $body['settings'] : [];

		if ( in_array( 'redirects', $settings, true ) ) {
			$response->data['settings']['settings']['redirects'] = aioseo()->redirects->options->all();
		}

		return $response;
	}

	/**
	 * Imports settings.
	 *
	 * @since 4.9.1
	 *
	 * @param  \WP_REST_Request  $request  The REST Request.
	 * @param  \WP_REST_Response $response The REST Request.
	 * @return \WP_REST_Response           The response.
	 */
	public static function importSettings( $request, $response ) {
		if ( ! aioseo()->access->hasCapability( 'aioseo_redirects_settings' ) ) {
			return $response;
		}

		$file = $request->get_file_params()['file'];
		if (
			empty( $file['tmp_name'] ) ||
			empty( $file['type'] ) ||
			'application/json' !== $file['type']
		) {
			return $response;
		}

		$contents = aioseo()->core->fs->getContents( $file['tmp_name'] );

		// Since this could be any file, we need to pretend like every variable here is missing.
		$contents = json_decode( $contents, true );
		if ( empty( $contents ) ) {
			return $response;
		}

		if ( isset( $contents['settings']['redirects'] ) ) {
			aioseo()->redirects->options->sanitizeAndSave( $contents['settings']['redirects'] );
		}

		return $response;
	}
}