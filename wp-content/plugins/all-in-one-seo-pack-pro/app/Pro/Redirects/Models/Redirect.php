<?php
namespace AIOSEO\Plugin\Pro\Redirects\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Models as CommonModels;
use AIOSEO\Plugin\Pro\Redirects\Utils;

/**
 * The Redirects DB Model.
 *
 * @since 4.9.1
 */
class Redirect extends CommonModels\Model {
	/**
	 * The name of the table in the database, without the prefix.
	 *
	 * @since 4.9.1
	 *
	 * @var string
	 */
	protected $table = 'aioseo_redirects';

	/**
	 * Fields that should be numeric values.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	protected $integerFields = [ 'id', 'post_id', 'type', 'hits' ];

	/**
	 * Fields that should be boolean values.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	protected $booleanFields = [
		'regex',
		'ignore_slash',
		'ignore_case',
		'enabled'
	];

	/**
	 * Fields that should be appended when the object is transformed to a json.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	protected $appends = [
		'hits',
		'groupName',
		'postStatus'
	];

	/**
	 * Fields that should be encoded/decoded on save/get.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	protected $jsonFields = [ 'custom_rules' ];

	/**
	 * The amount of redirect hits.
	 *
	 * @since 4.9.1
	 *
	 * @var int
	 */
	protected $hits = 0;

	/**
	 * The group.
	 *
	 * @since 4.9.1
	 *
	 * @var string
	 */
	protected $group = '';

	/**
	 * The group name.
	 *
	 * @since 4.9.1
	 *
	 * @var string
	 */
	public $groupName = '';

	/**
	 * Post status.
	 *
	 * @since 4.9.1
	 *
	 * @var int|null
	 */
	public $post_id = null;

	/**
	 * Post status.
	 *
	 * @since 4.9.1
	 *
	 * @var string
	 */
	public $postStatus = '';

	/**
	 * Source url.
	 *
	 * @since 4.9.1
	 *
	 * @var string
	 */
	public $source_url = '';

	/**
	 * Class constructor.
	 *
	 * @since 4.9.1
	 *
	 * @param mixed $var This can be the primary key of the resource, or it could be an array of data to manufacture a resource without a database query.
	 */
	public function __construct( $var = null ) {
		parent::__construct( $var );

		// Add some additional data here.
		if ( isset( $this->id ) && empty( $this->hits ) ) {
			$this->hits = RedirectsHit::getRedirectHit( $this->id )->count;
		}

		if ( ! is_numeric( $this->hits ) ) {
			$this->hits = 0;
		}

		$this->groupName  = $this->getGroupName( $this->group );
		$this->postStatus = $this->post_id ? get_post_status( $this->post_id ) : '';

		if ( $this->post_id && 'publish' !== $this->postStatus ) {
			$this->source_url = '(' . __( 'source url set once post is published', AIOSEO_PLUGIN_SHORT_NAME ) . ')'; // phpcs:ignore
		}
	}

	/**
	 * Transforms data as needed.
	 *
	 * @since 4.9.1
	 *
	 * @param  array $data The data array to transform.
	 * @return array       The transformed data.
	 */
	protected function transform( $data, $set = false ) {
		$data = parent::transform( $data, $set );

		$isRegex = ! empty( $data['regex'] ) ? (bool) $data['regex'] : false;

		// Normalize source if it's not a regex.
		if ( ! empty( $data['source_url'] ) ) {
			$postId = $this->post_id;
			if ( ! empty( $data['post_id'] ) ) {
				$postId = $data['post_id'];
			}

			// If we're tying to a post that's not published let's give it a placeholder source.
			// This source will change when the post is published.
			if ( $postId && 'publish' !== get_post_status( $postId ) ) {
				$data['source_url'] = sha1( $postId );
			}

			if ( ! $isRegex ) {
				$data['source_url'] = Utils\Request::normalizeUrl( $data['source_url'] );
				$data['source_url'] = aioseo()->helpers->decodeUrl( $data['source_url'] );
			}

			// Create source hash.
			$data['source_url_hash'] = Utils\Request::getUrlHash( $data['source_url'] );
			if ( ! empty( $data['custom_rules'] ) ) {
				$customRules             = ! is_string( $data['custom_rules'] ) ? wp_json_encode( $data['custom_rules'] ) : $data['custom_rules'];
				$data['source_url_hash'] = Utils\Request::getUrlHash( $data['source_url'] . $customRules );
			}

			// Create source match and hash.
			$data['source_url_match']      = $isRegex ? 'regex' : Utils\Request::getMatchedUrl( $data['source_url'] );
			$data['source_url_match_hash'] = $isRegex ? Utils\Request::getRegexHash() : Utils\Request::getUrlHash( $data['source_url_match'] );
		}

		// Normalize target and create hash.
		if ( ! empty( $data['target_url'] ) ) {
			$data['target_url']      = Utils\Request::getTargetUrl( Utils\Request::normalizeUrl( $data['target_url'] ) );
			$data['target_url_hash'] = Utils\Request::getUrlHash( $data['target_url'] );
		}

		return $data;
	}

	/**
	 * Retrieve a list of filters for the table.
	 *
	 * @since 4.9.1
	 *
	 * @param  string $filter The filter to set active.
	 * @return array          An array of filters.
	 */
	public static function getFilters( $filter = 'all' ) {
		return [
			[
				'slug'   => 'all',
				'name'   => __( 'All', AIOSEO_PLUGIN_SHORT_NAME ), // phpcs:ignore
				'count'  => aioseo()->core->db->start( 'aioseo_redirects' )->count(),
				'active' => 'all' === $filter
			],
			[
				'slug'   => 'enabled',
				'name'   => __( 'Enabled', AIOSEO_PLUGIN_SHORT_NAME ), // phpcs:ignore
				'count'  => aioseo()->core->db->start( 'aioseo_redirects' )->where( 'enabled', 1 )->count(),
				'active' => 'enabled' === $filter
			],
			[
				'slug'   => 'disabled',
				'name'   => __( 'Disabled', AIOSEO_PLUGIN_SHORT_NAME ), // phpcs:ignore
				'count'  => aioseo()->core->db->start( 'aioseo_redirects' )->where( 'enabled', 0 )->count(),
				'active' => 'disabled' === $filter
			]
		];
	}

	/**
	 * Retrieves a pretty name for our built-in groups.
	 *
	 * @since 4.9.1
	 *
	 * @param  string $group The original group name.
	 * @return string        The pretty group name.
	 */
	private function getGroupName( $group ) {
		switch ( $group ) {
			case 'external':
				return __( 'External', AIOSEO_PLUGIN_SHORT_NAME ); // phpcs:ignore
			case 'modified':
				return __( 'Modified Post', AIOSEO_PLUGIN_SHORT_NAME ); // phpcs:ignore
			case '404':
				return $group;
			case 'imported':
				return __( 'Imported', AIOSEO_PLUGIN_SHORT_NAME ); // phpcs:ignore
			case 'manual':
			default:
				return __( 'Manual Redirect', AIOSEO_PLUGIN_SHORT_NAME ); // phpcs:ignore
		}
	}

	/**
	 * Lookup a redirect by source url.
	 *
	 * @since 4.9.1
	 *
	 * @param  string   $url       The source URL.
	 * @param  int|null $excludeId The ID to exclude.
	 * @return Redirect            The redirect object.
	 */
	public static function getRedirectBySourceUrl( $url, $excludeId = null ) {
		$query = aioseo()->core->db
			->start( 'aioseo_redirects' )
			->where( 'source_url_hash', Utils\Request::getUrlHash( Utils\Request::normalizeUrl( $url ) ) );

		if ( $excludeId ) {
			$query->where( 'id !=', $excludeId );
		}

		return $query->run()
			->model( 'AIOSEO\\Plugin\\Pro\\Redirects\\Models\\Redirect' );
	}

	/**
	 * Lookup a redirect by ID.
	 *
	 * @since 4.9.1
	 *
	 * @param  int      $redirectId The redirect ID.
	 * @return Redirect             The redirect object.
	 */
	public static function getRedirectById( $redirectId ) {
		return aioseo()->core->db
			->start( 'aioseo_redirects' )
			->where( 'id', $redirectId )
			->run()
			->model( 'AIOSEO\\Plugin\\Pro\\Redirects\\Models\\Redirect' );
	}

	/**
	 * Overrides the parent's save() function to clear the Redirects cache.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function save() {
		parent::save();
		aioseo()->redirects->cache->clearRedirects();
	}

	/**
	 * Overrides the parent's delete() function to clear the Redirects cache.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function delete() {
		parent::delete();
		aioseo()->redirects->cache->clearRedirects();
	}

	/**
	 * Adds a redirect hit.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function addHit() {
		$hit        = RedirectsHit::getRedirectHit( $this->id );
		$hit->count += 1;
		$hit->save();
	}

	/**
	 * Set hits on this redirect.
	 *
	 * @since 4.9.1
	 *
	 * @param  int  $hits Number of hits to set.
	 * @return void
	 */
	public function setHits( $hits ) {
		$hit        = RedirectsHit::getRedirectHit( $this->id );
		$hit->count = $hits;
		$hit->save();
	}

	/**
	 * Refreshes a redirect source if it's tied to a post id.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function refreshRedirectSource() {
		if ( empty( $this->post_id ) ) {
			return;
		}

		$this->set( [
			'source_url' => Utils\WpUri::getPostPath( $this->post_id )
		] );

		$this->save();
	}
}