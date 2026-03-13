<?php
namespace AIOSEO\Plugin\Pro\Redirects\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Pro\Redirects\Utils;

/**
 * Main class to run our HTTP headers.
 *
 * @since 4.9.1
 */
class HttpHeaders {
	/**
	 * The license levels that can use full site redirects.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	private $licenseLevels = [ 'plus', 'pro', 'elite' ];

	/**
	 * Class constructor.
	 *
	 * @since 4.9.1
	 */
	public function __construct() {
		if ( ! aioseo()->license->hasCoreFeature( 'redirects', 'http-headers' ) ) {
			return;
		}

		// HTTP headers for the whole site.
		add_filter( 'wp_headers', [ $this, 'siteHttpHeaders' ], 50 );

		// HTTP headers for redirections.
		add_filter( 'wp_redirect', [ $this, 'redirectHttpHeaders' ], 0 );
	}

	/**
	 * Add configured HTTP Headers to all pages.
	 *
	 * @since 4.9.1
	 *
	 * @param  array $wpHeaders The WP headers.
	 * @return array            Our added headers.
	 */
	public function siteHttpHeaders( $wpHeaders ) {
		if ( ! $this->shouldRun() ) {
			return;
		}

		$httpHeaders = $this->getHttpHeaders( [ 'site' ] );

		foreach ( $httpHeaders as $header ) {
			$wpHeaders = array_merge( $wpHeaders, $this->makeHttpHeader( $header ) );
		}

		return $wpHeaders;
	}

	/**
	 * Add configured HTTP Headers to redirects.
	 *
	 * @since 4.9.1
	 *
	 * @param  string $url The target URL.
	 * @return string      The target URL.
	 */
	public function redirectHttpHeaders( $url ) {
		$headers = $this->getHttpHeaders( [ 'site', 'redirect' ] );
		if ( empty( $headers ) ) {
			return $url;
		}

		foreach ( $headers as $header ) {
			$header = $this->makeHttpHeader( $header );
			header( sprintf( '%s: %s', key( $header ), current( $header ) ) );
		}

		return $url;
	}

	/**
	 * Return a header string from a header object.
	 *
	 * @since 4.9.1
	 *
	 * @param  string|object $httpHeader The header object.
	 * @return array                     A http header array.
	 */
	public function makeHttpHeader( $httpHeader ) {
		$httpHeader  = is_string( $httpHeader ) ? json_decode( $httpHeader ) : $httpHeader;
		$headerValue = ( is_array( $httpHeader->value ) ? implode( ',', $httpHeader->value ) : $httpHeader->value );

		// Custom header.
		if ( 'custom' === $httpHeader->header ) {
			$httpHeader->header = $httpHeader->customHeader;
		}

		// Custom value.
		if ( ! empty( $httpHeader->customValue ) ) {
			$headerValue = preg_replace( '/\[[a-zA-Z0-9_-]+\]/', $httpHeader->customValue, (string) $headerValue );
		}

		return [ trim( $httpHeader->header ) => trim( $headerValue ) ];
	}

	/**
	 * Get the configured http headers optionally filtered by type.
	 *
	 * @since 4.9.1
	 *
	 * @param  array $types Types to filter (site, redirect).
	 * @return array        HTTP header array.
	 */
	public function getHttpHeaders( $types = [] ) {
		$headers = aioseo()->redirects->options->fullSite->httpHeaders;
		if ( empty( $headers ) ) {
			return [];
		}

		foreach ( $headers as $key => &$httpHeader ) {
			$httpHeader = json_decode( $httpHeader );

			// Let's make sure the headers are usable or it may crash Apache.
			if (
				! $httpHeader ||
				empty( $httpHeader->header ) ||
				( empty( $httpHeader->value ) && '0' !== $httpHeader->value ) || // @phpstan-ignore-line
				(
					! empty( $types ) &&
					! empty( $httpHeader->location ) &&
					! in_array( $httpHeader->location, $types, true )
				)
			) {
				unset( $headers[ $key ] );
			}
		}

		return $headers;
	}

	/**
	 * Returns if we should run a redirection.
	 *
	 * @since 4.9.1
	 *
	 * @return bool Should run.
	 */
	private function shouldRun() {
		if (
			is_admin() ||
			aioseo()->helpers->isDoingWpCli() ||
			aioseo()->helpers->isAjaxCronRestRequest() ||
			Utils\Request::isProtectedPath() ||
			aioseo()->redirects->redirect->isPageBuilderPreviewRequest()
		) {
			return false;
		}

		return true;
	}
}