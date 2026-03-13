<?php
namespace AIOSEO\Plugin\Pro\Redirects\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Models as CommonModels;

/**
 * The Redirects DB Model.
 *
 * @since 4.9.1
 */
class RedirectsLog extends CommonModels\Model {
	/**
	 * The name of the table in the database, without the prefix.
	 *
	 * @since 4.9.1
	 *
	 * @var string
	 */
	protected $table = 'aioseo_redirects_logs';

	/**
	 * Fields that should be hidden when serialized.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	protected $hidden = [ 'id' ];

	/**
	 * Fields that should be json encoded on save and decoded on get.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	protected $jsonFields = [ 'request_data' ];

	/**
	 * Return filtered logs.
	 *
	 * @since 4.9.1
	 *
	 * @param  array $args Arguments for the function.
	 * @return array       The DB results.
	 */
	public static function getFiltered( $args = [] ) {
		$args = wp_parse_args( $args, [
			'limit'    => aioseo()->settings->tablePagination['redirectLogs'],
			'offset'   => 0,
			'orderBy'  => 'last_accessed',
			'orderDir' => 'DESC',
			'search'   => '',
			'return'   => ''
		] );

		$logs = aioseo()->core->db->start( 'aioseo_redirects_logs as `l1`' )
			->select( 'l1.id, l1.url, l2.hits, l1.created as last_accessed, l1.request_data, l1.ip, l1.redirect_id' )
			->join(
				'(SELECT MAX(id) as id, count(*) as hits FROM ' . aioseo()->core->db->db->prefix . 'aioseo_redirects_logs GROUP BY `url_hash`) as `l2`',
				'`l2`.`id` = `l1`.`id`',
				'',
				true
			)
			->orderBy( $args['orderBy'] . ' ' . $args['orderDir'] )
			->limit( $args['limit'], $args['offset'] );

		if ( ! empty( $args['search'] ) ) {
			$logs->whereRaw( $args['search'] );
		}

		$logResults = $logs->run()->result();
		if ( ! empty( $logResults ) ) {
			// Collect all url_hashes for batch query
			$urlHashes = [];
			foreach ( $logResults as $log ) {
				$urlHashes[] = sha1( $log->url );
			}

			// Single query to get all referrers for all URLs
			$allReferrers = aioseo()->core->db->start( 'aioseo_redirects_logs' )
				->select( 'url_hash, referrer' )
				->whereIn( 'url_hash', $urlHashes )
				->where( 'referrer !=', '' )
				->groupBy( 'url_hash, referrer' )
				->run()
				->result();

			// Group referrers by url_hash
			$referrersByHash = [];
			foreach ( $allReferrers as $referrer ) {
				$referrersByHash[ $referrer->url_hash ][] = $referrer->referrer;
			}

			// Assign referrers to each log
			foreach ( $logResults as &$log ) {
				$urlHash = sha1( $log->url );
				$log->referrers = isset( $referrersByHash[ $urlHash ] ) ? $referrersByHash[ $urlHash ] : [];
			}
		}

		return $logResults;
	}

	/**
	 * Return log totals.
	 *
	 * @since 4.9.1
	 *
	 * @param  string $search The search string.
	 * @return int            The total count.
	 */
	public static function getTotals( $search = '' ) {
		// If no search, we can just count distinct url_hash directly - much faster
		if ( empty( $search ) ) {
			return aioseo()->core->db->start( 'aioseo_redirects_logs' )
				->countDistinct( 'url_hash' );
		}

		// For searches, we need to apply the search filter first, then count distinct
		$query = aioseo()->core->db->start( 'aioseo_redirects_logs' );
		$query->whereRaw( $search );

		return $query->countDistinct( 'url_hash' );
	}
}