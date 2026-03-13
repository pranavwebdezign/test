<?php
namespace AIOSEO\Plugin\Addon\LinkAssistant\Suggestions;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\LinkAssistant\Models;

/**
 * Registers and executes the Link Suggestions scan.
 *
 * @since 1.0.0
 */
class Suggestions {
	/**
	 * The base URL for the Link Suggestions server.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $baseUrl = 'https://link-suggestions.aioseo.com/v2/';

	/**
	 * The action name of the action that starts a suggestion scan.
	 *
	 * @since 1.0.3
	 *
	 * @var string
	 */
	private $registerScanActionName = 'aioseo_link_assistant_register_suggestions_scan';

	/**
	 * The action name of the main suggestions scan.
	 *
	 * @since 1.0.3
	 *
	 * @var string
	 */
	private $scanActionName = 'aioseo_link_assistant_suggestions_scan';

	/**
	 * Data class instance.
	 *
	 * @since 1.0.11
	 *
	 * @var Data
	 */
	public $data = null;

	/**
	 * Class constructor.
	 *
	 * @since   1.0.0
	 * @version 1.1.12 Remove is_admin() check to allow frontend scheduling.
	 */
	public function __construct() {
		$this->data = new Data();

		if ( ! aioseo()->license->isActive() || aioseoLinkAssistant()->cache->get( 'teapot' ) ) {
			return;
		}

		add_action( $this->registerScanActionName, [ $this, 'registerScan' ] );
		add_action( $this->scanActionName, [ $this, 'scanPosts' ] );
		add_action( 'init', [ $this, 'scheduleRegisterScan' ] );
		add_action( 'init', [ $this, 'scheduleScan' ] );
	}

	/**
	 * Schedules the register scan as a recurring action.
	 *
	 * @since   1.0.3
	 * @version 1.1.12 Switch to recurring action with cache-based idle state.
	 *
	 * @return void
	 */
	public function scheduleRegisterScan() {
		// If we're in idle/backoff mode, unschedule and don't reschedule yet.
		if ( aioseo()->core->cache->get( 'as_la_suggestions_register_idle' ) ) {
			aioseo()->actionScheduler->unschedule( $this->registerScanActionName );

			return;
		}

		if ( aioseo()->actionScheduler->isScheduled( $this->registerScanActionName ) ) {
			return;
		}

		$scanInterval = apply_filters( 'aioseo_link_assistant_suggestions_register_interval', 60 );
		aioseo()->actionScheduler->scheduleRecurrent( $this->registerScanActionName, 10, $scanInterval );
	}

	/**
	 * Schedules the main suggestions scan as a recurring action.
	 *
	 * @since   1.0.3
	 * @version 1.1.12 Switch to recurring action with cache-based idle state.
	 *
	 * @return void
	 */
	public function scheduleScan() {
		// If we're in idle/backoff mode, unschedule and don't reschedule yet.
		if ( aioseo()->core->cache->get( 'as_la_suggestions_scan_idle' ) ) {
			aioseo()->actionScheduler->unschedule( $this->scanActionName );

			return;
		}

		if ( aioseo()->actionScheduler->isScheduled( $this->scanActionName ) ) {
			return;
		}

		$scanInterval = apply_filters( 'aioseo_link_assistant_suggestions_scan_interval', 60 );
		aioseo()->actionScheduler->scheduleRecurrent( $this->scanActionName, 10, $scanInterval );
	}

	/**
	 * Kicks off the initial scan for the link suggestions.
	 *
	 * @since   1.0.0
	 * @version 1.1.12 Use recurring action with runtime lock and idle state.
	 *
	 * @return void
	 */
	public function registerScan() {
		// If we there's a scan in progress, don't register a new one.
		$scanId = aioseoLinkAssistant()->internalOptions->internal->scanId;
		if ( ! empty( $scanId ) ) {
			return;
		}

		// Runtime lock: Prevent concurrent execution of this action.
		$lockKey = 'as_la_suggestions_register_running';
		if ( aioseo()->core->cache->get( $lockKey ) ) {
			return;
		}

		// Set lock with a safety timeout in case the action fails mid-execution.
		aioseo()->core->cache->update( $lockKey, true, 2 * MINUTE_IN_SECONDS );

		$arePostsToScan = $this->data->arePostsToScan();
		if ( ! $arePostsToScan ) {
			// No posts to scan - set idle. The schedule method on the next init will unschedule.
			aioseo()->core->cache->update( 'as_la_suggestions_register_idle', true, HOUR_IN_SECONDS );
			aioseoLinkAssistant()->cache->update( 'no_scan', true, 15 * MINUTE_IN_SECONDS );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		$posts = $this->data->getAllPosts();
		if ( empty( $posts ) ) {
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		$requestBody = array_merge(
			$this->data->getBaseData(),
			[
				'posts'                     => $posts,
				'cornerstoneContentPostIds' => $this->data->getCornerstoneContentPostIds(),
				'totals'                    => [
					'posts' => aioseoLinkAssistant()->helpers->getTotalScannablePosts()
				]
			]
		);

		$response     = $this->doPostRequest( 'suggestions/scan/start/', $requestBody );
		$responseCode = (int) wp_remote_retrieve_response_code( $response );

		if ( 401 === $responseCode ) {
			aioseo()->core->cache->update( 'as_la_suggestions_register_idle', true, DAY_IN_SECONDS + wp_rand( 60, 600 ) );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		if ( 418 === $responseCode ) {
			aioseoLinkAssistant()->cache->update( 'teapot', true, HOUR_IN_SECONDS + wp_rand( 60, 600 ) );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		$responseBody = json_decode( wp_remote_retrieve_body( $response ) );
		if ( 200 !== $responseCode || empty( $responseBody->success ) ) {
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		// Success: store scanId, clear scan idle so it picks up immediately, and idle the register.
		aioseoLinkAssistant()->internalOptions->internal->scanId = $responseBody->scanId;
		aioseo()->core->cache->delete( 'as_la_suggestions_scan_idle' );
		aioseo()->core->cache->update( 'as_la_suggestions_register_idle', true, 15 * MINUTE_IN_SECONDS );
		aioseo()->core->cache->delete( $lockKey );
	}

	/**
	 * Scans posts for link suggestions.
	 *
	 * @since   1.0.3
	 * @version 1.1.12 Use recurring action with runtime lock and idle state.
	 *
	 * @return void
	 */
	public function scanPosts() {
		// Runtime lock: Prevent concurrent execution of this action.
		$lockKey = 'as_la_suggestions_scan_running';
		if ( aioseo()->core->cache->get( $lockKey ) ) {
			return;
		}

		// Set lock with a safety timeout in case the action fails mid-execution.
		aioseo()->core->cache->update( $lockKey, true, 2 * MINUTE_IN_SECONDS );

		$scanId = aioseoLinkAssistant()->internalOptions->internal->scanId;
		if ( empty( $scanId ) ) {
			// No scan ID — nothing to do. Set idle so the schedule method unschedules this action.
			aioseo()->core->cache->update( 'as_la_suggestions_scan_idle', true, 15 * MINUTE_IN_SECONDS );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		$postsToScan = $this->data->getPostsToScan( true );
		if ( empty( $postsToScan ) ) {
			$this->endScan();

			// Scan complete - set idle cache. The schedule method on the next init will unschedule.
			aioseo()->core->cache->update( 'as_la_suggestions_scan_idle', true, 15 * MINUTE_IN_SECONDS );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		Models\Suggestion::deleteNonDismissedSuggestions( $postsToScan );

		$requestBody  = [ 'postsToScan' => $postsToScan ];
		$response     = $this->doPostRequest( "suggestions/scan/{$scanId}/", $requestBody );
		$responseCode = (int) wp_remote_retrieve_response_code( $response );

		if ( 401 === $responseCode ) {
			aioseo()->core->cache->update( 'as_la_suggestions_scan_idle', true, DAY_IN_SECONDS + wp_rand( 60, 600 ) );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		if ( 418 === $responseCode ) {
			aioseoLinkAssistant()->cache->update( 'teapot', true, HOUR_IN_SECONDS + wp_rand( 60, 600 ) );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		$responseBody = json_decode( wp_remote_retrieve_body( $response ) );
		if ( 200 !== $responseCode || empty( $responseBody->success ) ) {
			// If the JSON file with the scan data cannot be found on the server, wipe the scan ID so the scan restarts.
			if ( ! empty( $responseBody->error ) && 'missing-scan-data' === strtolower( $responseBody->error ) ) {
				aioseoLinkAssistant()->internalOptions->internal->scanId = '';
			}

			// Generic error: just return and let the next recurring tick retry.
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		if ( ! empty( $responseBody->scannedPostsWithSuggestions ) ) {
			$scannedPostIds = array_keys( (array) $responseBody->scannedPostsWithSuggestions );
			$this->markPostsAsScanned( $scannedPostIds );

			$this->data->parseSuggestions( $responseBody->scannedPostsWithSuggestions );
		}

		aioseo()->core->cache->delete( $lockKey );
	}

	/**
	 * Ends the scan and deletes the scan ID.
	 *
	 * @since 1.0.3
	 *
	 * @return void
	 */
	private function endScan() {
		$scanId = aioseoLinkAssistant()->internalOptions->internal->scanId;
		if ( empty( $scanId ) ) {
			return;
		}

		// Reset the scan ID.
		aioseoLinkAssistant()->internalOptions->internal->scanId = null;

		aioseo()->helpers->wpRemoteDelete( $this->getUrl() . "suggestions/scan/$scanId/", [
			'blocking'         => false,
			'aioseo_skip_lock' => true
		] );
	}

	/**
	 * Refreshes the link suggestions for the given post.
	 *
	 * @since 1.0.3
	 *
	 * @param  \WP_Post $postToScan The post that needs to be scanned.
	 * @return void
	 */
	public function refresh( $postToScan ) {
		if ( aioseoLinkAssistant()->cache->get( 'refresh_delay' ) ) {
			return;
		}

		$posts = $this->data->getAllPosts();
		if ( empty( $posts ) ) {
			return;
		}

		Models\Suggestion::deleteNonDismissedSuggestions( $postToScan );

		$postToScan->phrases = $this->data->getPhrases( $postToScan );
		unset( $postToScan->post_content );

		$requestBody = array_merge(
			$this->data->getBaseData(),
			[
				'cornerstoneContentPostIds' => $this->data->getCornerstoneContentPostIds(),
				'postToScan'                => $postToScan,
				'posts'                     => $posts
			]
		);

		$response     = $this->doPostRequest( 'suggestions/refresh/', $requestBody );
		$responseCode = (int) wp_remote_retrieve_response_code( $response );

		if ( 401 === $responseCode ) {
			aioseoLinkAssistant()->cache->update( 'refresh_delay', true, HOUR_IN_SECONDS + wp_rand( 60, 600 ) );

			return;
		}

		if ( 418 === $responseCode ) {
			aioseoLinkAssistant()->cache->update( 'teapot', true, HOUR_IN_SECONDS + wp_rand( 60, 600 ) );

			return;
		}

		$responseBody = json_decode( wp_remote_retrieve_body( $response ) );
		if (
			200 !== $responseCode ||
			empty( $responseBody->success ) ||
			empty( $responseBody->scannedPostsWithSuggestions )
		) {
			return false;
		}

		$this->markPostsAsScanned( $postToScan->ID );

		$this->data->parseSuggestions( $responseBody->scannedPostsWithSuggestions );
	}

	/**
	 * Marks the given posts as scanned.
	 *
	 * @since 1.0.3
	 *
	 * @param  array|int $scannedPostIds The posts that were scanned.
	 * @return void
	 */
	private function markPostsAsScanned( $scannedPostIds ) {
		if ( ! is_array( $scannedPostIds ) ) {
			$scannedPostIds = [ $scannedPostIds ];
		}

		$tableName          = aioseo()->core->db->prefix . 'aioseo_posts';
		$postIdPlaceholders = aioseo()->helpers->implodePlaceholders( $scannedPostIds, '%d' );

		aioseo()->core->db->execute(
			aioseo()->core->db->db->prepare(
				"UPDATE $tableName
				SET `link_suggestions_scan_date`=%s
				WHERE `post_id` IN ( $postIdPlaceholders )",
				array_merge(
					[ gmdate( 'Y-m-d H:i:s' ) ],
					$scannedPostIds
				)
			)
		);
	}

	/**
	 * Sends a POST request to the server.
	 *
	 * @since 1.0.3
	 *
	 * @param  string            $path        The path.
	 * @param  array             $requestBody The request body.
	 * @return \WP_REST_Response              The response.
	 */
	private function doPostRequest( $path, $requestBody = [] ) {
		$requestData = [
			'timeout' => 60
		];

		if ( ! empty( $requestBody ) ) {
			$requestData['body'] = wp_json_encode( $requestBody );
		}

		return aioseo()->helpers->wpRemotePost( $this->getUrl() . $path, $requestData );
	}

	/**
	 * Returns the URL for the Link Suggestions server.
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL.
	 */
	public function getUrl() {
		if ( defined( 'AIOSEO_LINK_SUGGESTIONS_URL' ) ) {
			return AIOSEO_LINK_SUGGESTIONS_URL;
		}

		return $this->baseUrl;
	}
}