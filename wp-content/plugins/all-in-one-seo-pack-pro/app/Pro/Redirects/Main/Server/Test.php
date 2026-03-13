<?php
namespace AIOSEO\Plugin\Pro\Redirects\Main\Server;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Models as CommonModels;

/**
 * Class for the server redirect test.
 *
 * @since 4.9.1
 */
class Test {
	/**
	 * The intercepted redirect URL to test with.
	 *
	 * @since 4.9.1
	 *
	 * @var string
	 */
	private $testInterceptedRedirect = 'aioseo-test-server-redirects-intercepted';

	/**
	 * The redirect URL to test with. Will be dynamically regenerated on each config rewrite.
	 *
	 * @since 4.9.1
	 *
	 * @var string
	 */
	private $testRedirect = 'aioseo-test-server-redirects';

	/**
	 * Redirect missing notification name.
	 *
	 * @since 4.9.1
	 *
	 * @var string
	 */
	private $notificationRedirectFailed = 'server-redirects-failed';

	/**
	 * Runs a redirect test in order to verify that server redirects are properly configured.
	 *
	 * @since 4.9.1
	 *
	 * @return boolean True if server redirects are working.
	 */
	public function runRedirectsTest() {
		$notification = CommonModels\Notification::getNotificationByName( $this->notificationRedirectFailed );

		if ( aioseo()->redirects->server->valid() && ! $this->redirectsTest() ) {
			aioseo()->redirects->cache->update( 'server-redirects-failed', true, 0 );
			if ( ! $notification->exists() ) {
				CommonModels\Notification::addNotification( [
					'slug'              => uniqid(),
					'addon'             => 'redirects',
					'notification_name' => $this->notificationRedirectFailed,
					'title'             => __( 'Server Redirects Missing', AIOSEO_PLUGIN_SHORT_NAME ), // phpcs:ignore
					'content'           => sprintf(
						// Translators: 1 - The plugin short name ("AIOSEO").
						__( 'Warning: %1$s was unable to detect server level redirects. This probably means the redirects have not been added properly. You may need to reload your server configuration or restart it in order for the redirects to take place.', AIOSEO_PLUGIN_SHORT_NAME ), // phpcs:ignore
						AIOSEO_PLUGIN_SHORT_NAME
					),
					'type'              => 'warning',
					'level'             => [ 'all' ],
					'button1_label'     => __( 'Learn More', AIOSEO_PLUGIN_SHORT_NAME ), // phpcs:ignore
					'button1_action'    => 'https://route#aioseo-redirects:settings',
					'start'             => gmdate( 'Y-m-d H:i:s' )
				] );
			}

			return false;
		}

		if ( $notification->exists() ) {
			$notification->delete();
		}

		aioseo()->redirects->cache->update( 'server-redirects-failed', false, 0 );

		return true;
	}

	/**
	 * Runs a redirect test in order to verify that server redirects are properly configured.
	 *
	 * @since 4.9.1
	 *
	 * @return boolean True if server redirects are working.
	 */
	private function redirectsTest() {
		$response = wp_remote_get( home_url( $this->getTestRedirect() ), [
			'redirection' => 0,
			'timeout'     => 15,
			'sslverify'   => false
		] );

		$code     = wp_remote_retrieve_response_code( $response );
		$location = wp_parse_url( wp_remote_retrieve_header( $response, 'location' ), PHP_URL_PATH );

		if ( 301 !== $code || '/' . $this->getTestInterceptedRedirect() !== $location ) {
			return false;
		}

		return true;
	}

	/**
	 * Return the test intercepted redirect.
	 *
	 * @since 4.9.1
	 *
	 * @return string The test intercepted redirect.
	 */
	public function getTestInterceptedRedirect() {
		return $this->testInterceptedRedirect;
	}

	/**
	 * Return the test redirect slug.
	 *
	 * @since 4.9.1
	 *
	 * @return string The test redirect.
	 */
	public function getTestRedirect() {
		$testRedirectSlug = aioseo()->redirects->cache->get( $this->testRedirect );
		if ( empty( $testRedirectSlug ) ) {
			$testRedirectSlug = $this->testRedirect . '-' . md5( time() );
			aioseo()->redirects->cache->update( $this->testRedirect, $testRedirectSlug, 0 );
		}

		return $testRedirectSlug;
	}

	/**
	 * Deletes the test redirect slug forcing a regeneration.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function regenerateTestRedirect() {
		aioseo()->redirects->cache->delete( $this->testRedirect );
	}
}