<?php
namespace AIOSEO\Plugin\Pro\SearchStatistics\Stats;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\SearchStatistics\Api;

/**
 * The main statistics class.
 *
 * @since 4.3.0
 */
class Stats {
	/**
	 * The start date.
	 *
	 * @since 4.3.0
	 *
	 * @var string
	 */
	public $startDate = '';

	/**
	 * The end date.
	 *
	 * @since 4.3.0
	 *
	 * @var string
	 */
	public $endDate = '';

	/**
	 * The number of days to compare.
	 *
	 * @since 4.3.0
	 *
	 * @var int
	 */
	public $days = 0;

	/**
	 * The start date timestamp.
	 *
	 * @since 4.3.0
	 *
	 * @var int
	 */
	public $startTimestamp = '';

	/**
	 * The end date timestamp.
	 *
	 * @since 4.3.0
	 *
	 * @var int
	 */
	public $endTimestamp = '';

	/**
	 * The comparison start date.
	 *
	 * @since 4.3.0
	 *
	 * @var string
	 */
	public $compareStartDate = '';

	/**
	 * The comparison end date.
	 *
	 * @since 4.3.0
	 *
	 * @var string
	 */
	public $compareEndDate = '';

	/**
	 * Holds the instance of the Posts class.
	 *
	 * @since 4.3.0
	 *
	 * @var Posts
	 */
	public $posts = null;

	/**
	 * Holds the instance of the Keywords class.
	 *
	 * @since 4.3.0
	 *
	 * @var Keywords
	 */
	public $keywords = null;

	/**
	 * The latest available date Google has data for.
	 *
	 * @since 4.3.0
	 *
	 * @var string
	 */
	public $latestAvailableDate = '';

	/**
	 * Whether or not the site is unverified.
	 *
	 * @since 4.3.0
	 *
	 * @var bool
	 */
	public $unverifiedSite = false;

	/**
	 * The action name for updating the latest available date.
	 *
	 * @since 4.9.1
	 *
	 * @var string
	 */
	public $latestAvailableDateAction = 'aioseo_search_statistics_get_latest_available_date';

	/**
	 * Class constructor.
	 *
	 * @since 4.3.0
	 */
	public function __construct() {
		$this->posts    = new Posts();
		$this->keywords = new Keywords();

		add_action( 'admin_init', [ $this, 'setDefaultDateRange' ], 11 );

		add_action( 'init', [ $this, 'scheduleLatestAvailableDateAction' ] );
		add_action( $this->latestAvailableDateAction, [ $this, 'getLatestAvailableDate' ] );
	}

	/**
	 * Schedules the latest available date action.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function scheduleLatestAvailableDateAction() {
		// Unschedule the job if not connected.
		if ( ! aioseo()->searchStatistics->api->auth->isConnected() ) {
			aioseo()->actionScheduler->unschedule( $this->latestAvailableDateAction );

			return;
		}
		if ( ! aioseo()->actionScheduler->isScheduled( $this->latestAvailableDateAction ) ) {
			aioseo()->actionScheduler->scheduleRecurrent( $this->latestAvailableDateAction, DAY_IN_SECONDS, DAY_IN_SECONDS, [] );

			// Run it right now if the action wasn't scheduled since it means the user just connected their site.
			$this->getLatestAvailableDate();
		}
	}

	/**
	 * Sets the latest available date.
	 *
	 * @since   4.3.0
	 * @version 4.9.4.2 Add runtime lock; remove conflicting scheduleSingle (daily recurring handles retry).
	 *
	 * @return void
	 */
	public function getLatestAvailableDate() {
		// Runtime lock: Prevent concurrent execution of this action.
		$lockKey = 'as_latest_date_running';
		if ( aioseo()->core->cache->get( $lockKey ) ) {
			return;
		}

		// Set lock with a safety timeout in case the action fails mid-execution.
		aioseo()->core->cache->update( $lockKey, true, 2 * MINUTE_IN_SECONDS );

		// Bail if not connected.
		$authedSite = aioseo()->searchStatistics->api->auth->getAuthedSite();
		if ( ! $authedSite || ! aioseo()->searchStatistics->api->auth->isConnected() ) {
			$this->latestAvailableDate = date( 'Y-m-d', strtotime( '-2 days' ) );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		$hash                = sha1( $authedSite );
		$cacheKey            = "aioseo_search_statistics_latest_date_{$hash}";
		$latestAvailableDate = aioseo()->core->cache->get( $cacheKey );
		if ( $latestAvailableDate ) {
			$this->latestAvailableDate = date( 'Y-m-d', strtotime( $latestAvailableDate ) );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		$api      = new Api\Request( 'google-search-console/newest-date/', [], 'GET' );
		$response = $api->request();
		if ( is_wp_error( $response ) || ! empty( $response['error'] ) || empty( $response['data']['date'] ) ) {
			if (
				is_array( $response ) &&
				! empty( $response['data']['message'] ) &&
				preg_match( '/403 Forbidden/i', (string) $response['data']['message'] )
			) {
				$this->unverifiedSite      = true;
				$this->latestAvailableDate = date( 'Y-m-d', strtotime( '-2 days' ) );
				aioseo()->core->cache->delete( $lockKey );

				return;
			}

			$this->latestAvailableDate = date( 'Y-m-d', strtotime( '-2 days' ) );
			aioseo()->core->cache->update( $cacheKey, $this->latestAvailableDate, DAY_IN_SECONDS );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		$this->latestAvailableDate = date( 'Y-m-d', strtotime( $response['data']['date'] ) );

		aioseo()->core->cache->update( $cacheKey, $this->latestAvailableDate, DAY_IN_SECONDS );
		aioseo()->core->cache->delete( $lockKey );
	}

	/**
	 * Sets the default date range.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function setDefaultDateRange() {
		$baseTimestamp = strtotime( '-2 days' );
		if ( ! empty( $this->latestAvailableDate ) ) {
			$baseTimestamp = strtotime( $this->latestAvailableDate );
		}

		switch ( aioseo()->internalOptions->searchStatistics->rolling ) {
			case 'last7Days':
				$startDate = gmdate( 'Y-m-d', strtotime( '-6 days', $baseTimestamp ) );
				break;
			case 'last3Months':
				$startDate = gmdate( 'Y-m-d', strtotime( '-89 days', $baseTimestamp ) );
				break;
			case 'last6Months':
				$startDate = gmdate( 'Y-m-d', strtotime( '-179 days', $baseTimestamp ) );
				break;
			case 'last28Days':
			default:
				$startDate = gmdate( 'Y-m-d', strtotime( '-27 days', $baseTimestamp ) );
				break;
		}

		$endDate = gmdate( 'Y-m-d', $baseTimestamp );

		$this->setDateRange( $startDate, $endDate );
	}

	/**
	 * Updates the date range.
	 *
	 * @since 4.3.0
	 *
	 * @param  string $startDate The start date.
	 * @param  string $endDate   The end date.
	 * @return void
	 */
	public function setDateRange( $startDate, $endDate ) {
		$this->startDate = $startDate;
		$this->endDate   = $endDate;

		// Timestamp.
		$this->startTimestamp = strtotime( $startDate );
		$this->endTimestamp   = strtotime( $endDate );

		// Compare date.
		$this->days       = ceil( abs( $this->endTimestamp - $this->startTimestamp ) / DAY_IN_SECONDS ) + 1;
		$compareEndDate   = $this->startTimestamp - DAY_IN_SECONDS;
		$compareStartDate = $compareEndDate - ( $this->days * DAY_IN_SECONDS );

		$this->compareStartDate = date( 'Y-m-d', $compareStartDate );
		$this->compareEndDate   = date( 'Y-m-d', $compareEndDate );
	}

	/**
	 * Returns the current date range.
	 *
	 * @since 4.3.0
	 *
	 * @return array The current date range.
	 */
	public function getDateRange() {
		return [
			'start'        => $this->startDate,
			'end'          => $this->endDate,
			'compareStart' => $this->compareStartDate,
			'compareEnd'   => $this->compareEndDate
		];
	}
}