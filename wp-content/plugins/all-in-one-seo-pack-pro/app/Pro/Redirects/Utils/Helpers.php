<?php
namespace AIOSEO\Plugin\Pro\Redirects\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Pro\Redirects\Models;

/**
 * Contains helper functions
 *
 * @since 4.9.1
 */
class Helpers {
	/**
	 * Gets the data for vue.
	 *
	 * @since 4.9.1
	 *
	 * @param  string $page The current page.
	 * @return array        An array of data.
	 */
	public function getVueData( $data = [], $page = null ) {
		if ( 'redirects' === $page ) {
			$data = $this->getRedirectsPageData( $data );
		}

		if ( aioseo()->helpers->isScreenPostList() ) {
			$data['options'] = aioseo()->redirects->options->all();
		}

		if (
			'post' === $page ||
			(
				! empty( $data['currentPost']['context'] ) &&
				'term' === $data['currentPost']['context']
			)
		) {
			$data = $this->getPostPageData( $data );
		}

		if ( ! empty( $data ) ) {
			$data['protectedPaths'] = Request::getProtectedPaths();
		}

		return $data;
	}

	/**
	 * Get posts page data.
	 *
	 * @since 4.9.1
	 *
	 * @param  array $data The data array.
	 * @return array       The modified data array.
	 */
	private function getPostPageData( $data ) {
		$data['options'] = aioseo()->redirects->options->all();
		$data['rows']    = array_values( aioseo()->redirects->redirect->getRedirects( $data['currentPost']['permalink'], 'all' ) );

		if ( ! empty( $data['currentPost']['postStatus'] ) && 'publish' !== $data['currentPost']['postStatus'] ) {
			$redirect = aioseo()->redirects->redirect->getRedirectByPostId( $data['currentPost']['id'] );
			if ( $redirect->exists() ) {
				$data['rows'] = [ $redirect ];
			}
		}

		$data['currentPost']['permalinkPath'] = WpUri::excludeHomeUrl( get_permalink( $data['currentPost']['id'] ) );
		if (
			! empty( $data['currentPost']['context'] ) &&
			'term' === $data['currentPost']['context']
		) {
			$data['currentPost']['permalinkPath'] = WpUri::getUrlPath( get_term_link( $data['currentPost']['id'] ) );
		}

		return $data;
	}

	/**
	 * Get redirects page data.
	 *
	 * @since 4.9.1
	 *
	 * @param  array $data The data array.
	 * @return array       The modified data array.
	 */
	private function getRedirectsPageData( $data ) {
		// Get the total number of results.
		$total     = aioseo()->core->db->start( 'aioseo_redirects' )->count();
		$total404  = Models\Redirects404Log::getTotals();
		$totalLogs = Models\RedirectsLog::getTotals();

		// Inject our vue data into this page.
		$wpUploadDir       = wp_upload_dir();
		$data = [
			'options'    => aioseo()->redirects->options->all(),
			'rows'       => array_values(
				aioseo()->core->db->start( 'aioseo_redirects' )
					->orderBy( 'id DESC' )
					->limit( aioseo()->settings->tablePagination['redirects'], 0 )
					->run()
					->models( 'AIOSEO\\Plugin\\Pro\\Redirects\\Models\\Redirect', null, true )
			),
			'logs404'    => Models\Redirects404Log::getFiltered(),
			'logs'       => Models\RedirectsLog::getFiltered(),
			'logSizes'   => [
				'logs404'      => aioseo()->helpers->convertFileSize( aioseo()->core->db->getTableSize( 'aioseo_redirects_404_logs' ) ),
				'redirectLogs' => aioseo()->helpers->convertFileSize( aioseo()->core->db->getTableSize( 'aioseo_redirects_logs' ) ),
			],
			'filters'    => [
				[
					'slug'   => 'all',
					'name'   => __( 'All', AIOSEO_PLUGIN_SHORT_NAME ), // phpcs:ignore
					'count'  => $total,
					'active' => true
				],
				[
					'slug'   => 'enabled',
					'name'   => __( 'Enabled', AIOSEO_PLUGIN_SHORT_NAME ), // phpcs:ignore
					'count'  => aioseo()->core->db->start( 'aioseo_redirects' )->where( 'enabled', 1 )->count(),
					'active' => false
				],
				[
					'slug'   => 'disabled',
					'name'   => __( 'Disabled', AIOSEO_PLUGIN_SHORT_NAME ), // phpcs:ignore
					'count'  => aioseo()->core->db->start( 'aioseo_redirects' )->where( 'enabled', 0 )->count(),
					'active' => false
				]
			],
			'totals'     => [
				'total404' => [
					'total' => $total404,
					'pages' => 0 < aioseo()->settings->tablePagination['redirect404Logs'] ? ceil( $total404 / aioseo()->settings->tablePagination['redirect404Logs'] ) : 1,
					'page'  => 1
				],
				'logs'     => [
					'total' => $totalLogs,
					'pages' => 0 < aioseo()->settings->tablePagination['redirectLogs'] ? ceil( $totalLogs / aioseo()->settings->tablePagination['redirectLogs'] ) : 1,
					'page'  => 1
				],
				'main'     => [
					'total' => $total,
					'pages' => 0 < aioseo()->settings->tablePagination['redirects'] ? ceil( $total / aioseo()->settings->tablePagination['redirects'] ) : 1,
					'page'  => 1
				]
			],
			'importers'  => aioseo()->redirects->importExport->plugins(),
			'server'     => [
				'redirectTest' => [
					'testing' => false,
					'failed'  => aioseo()->redirects->cache->get( 'server-redirects-failed' )
				],
				'filePath'     => $wpUploadDir['basedir'] . '/aioseo/redirects/.redirects',
			],
			'manualUrls' => []
		];

		// phpcs:disable HM.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['aioseo-manual-urls'] ) ) {
			$data['manualUrls'] = aioseo()->redirects->helpers->getManualRedirectUrls( sanitize_key( $_GET['aioseo-manual-urls'] ) ) ?: [];
		}
		// phpcs:enable

		return $data;
	}


	/**
	 * Generates a URL for adding manual redirects.
	 *
	 * @since 4.9.1
	 *
	 * @param  array  $urls An array of [url, target, type, slash, case, regex].
	 * @return string       The redirect link.
	 */
	public function manualRedirectUrl( $urls ) {
		// Transform a single url on an array of urls.
		if ( isset( $urls['url'] ) ) {
			$urls = [ $urls ];
		}

		// Cleanup urls.
		foreach ( $urls as &$url ) {
			$url['url'] = WpUri::excludeHomeUrl( $url['url'] );
		}

		$hash = md5( wp_json_encode( $urls ) );

		aioseo()->redirects->cache->update( 'manual-urls-' . $hash, $urls, HOUR_IN_SECONDS );

		return add_query_arg( 'aioseo-manual-urls', $hash, admin_url( 'admin.php?page=aioseo-redirects' ) );
	}

	/**
	 * Gets stored manual redirects.
	 *
	 * @since 4.9.1
	 *
	 * @param  string $hash The hash to search for.
	 * @return string       The stored manual redirects.
	 */
	public function getManualRedirectUrls( $hash ) {
		return aioseo()->redirects->cache->get( 'manual-urls-' . $hash );
	}

	/**
	 * Does a 404 redirect.
	 *
	 * @since 4.9.1
	 *
	 * @param  string $url The url to redirect to.
	 * @return void
	 */
	public function do404Redirect( $url, $redirectBy = 'AIOSEO 404' ) {
		if ( 'AIOSEO 404' !== $redirectBy ) {
			// Prefix redirect by.
			$redirectBy = ucwords( strtolower( str_replace( '-', ' ', $redirectBy ) ) );
			$redirectBy = 'AIOSEO 404 ' . $redirectBy;
		}

		// Safe redirect.
		wp_redirect( $url, 301, $redirectBy );
		die;
	}

	/**
	 * Takes an array of url parts and puts them together with proper slashes.
	 *
	 * @since 4.9.1
	 *
	 * @param  array  $parts URL parts in order.
	 * @return string        A built URL.
	 */
	public function makeUrl( $parts ) {
		if ( ! is_array( $parts ) ) {
			$parts = [ $parts ];
		}

		$url = implode( '', array_map( 'trailingslashit', $parts ) );

		// Make sure http(s) has '://'.
		$url = preg_replace( '@^(http(?:s|))(?:://|:/|:|)@', '$1://', (string) $url );

		// Fix duplicated forward slashes.
		return preg_replace( '/(?<!http:|https:)\/{2,}/', '/', (string) $url );
	}
}