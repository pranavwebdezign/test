<?php

namespace AIOSEO\Plugin\Pro\SeoAnalysis\ActionScheduler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Models\Post as AioseoPost;
use AIOSEO\Plugin\Pro\Models\Issue;

/**
 * Handles the action scheduler for Posts.
 *
 * @since 4.8.6
 */
class Post {
	/**
	 * The action.
	 *
	 * @since 4.8.6
	 *
	 * @var string
	 */
	protected $action = 'aioseo_seo_analysis_posts_scan';

	/**
	 * The number of items to analyze per run.
	 *
	 * @since 4.8.6
	 *
	 * @var integer
	 */
	protected $perRun = 5;

	/**
	 * Class constructor.
	 *
	 * @since   4.8.6
	 * @version 4.9.4.2 Change admin_init to init to allow frontend scheduling.
	 */
	public function __construct() {
		if ( ! aioseo()->options->general->licenseKey ) {
			return;
		}

		add_action( $this->action, [ $this, 'analyze' ] );
		add_action( 'init', [ $this, 'schedule' ] );
	}

	/**
	 * Schedule actions.
	 *
	 * @since 4.8.6
	 *
	 * @return void
	 */
	public function schedule() {
		// If we're in idle mode (no posts to scan), unschedule and don't reschedule yet.
		if ( aioseo()->core->cache->get( 'as_seo_analysis_post_idle' ) ) {
			aioseo()->actionScheduler->unschedule( $this->action );

			return;
		}

		$inteval = apply_filters( 'aioseo_seo_analyzer_scan_interval', MINUTE_IN_SECONDS );
		aioseo()->actionScheduler->scheduleRecurrent( $this->action, 0, $inteval );
	}

	/**
	 * Get the number of items to analyze per page.
	 *
	 * @since 4.8.6
	 *
	 * @return int
	 */
	protected function getPerRun() {
		return (int) apply_filters( 'aioseo_seo_analyzer_scan_items_per_run', $this->perRun );
	}

	/**
	 * Handles the analysis for X posts and store the results.
	 *
	 * @since   4.8.6
	 * @version 4.9.4.2 Add runtime lock to prevent concurrent execution.
	 *
	 * @return void
	 */
	public function analyze() {
		// Runtime lock: Prevent concurrent execution of this action.
		$lockKey = 'as_seo_analysis_post_running';
		if ( aioseo()->core->cache->get( $lockKey ) ) {
			return;
		}

		// Set lock with a safety timeout in case the action fails mid-execution.
		aioseo()->core->cache->update( $lockKey, true, 2 * MINUTE_IN_SECONDS );

		$postsToAnalyze = $this->getEnqueuedPosts();
		if ( empty( $postsToAnalyze ) ) {
			// No posts to analyze - set idle cache. The schedule method on the next init will unschedule.
			aioseo()->core->cache->update( 'as_seo_analysis_post_idle', true, HOUR_IN_SECONDS );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		foreach ( $postsToAnalyze as $postId ) {
			Issue::deleteAll( $postId, 'post' );

			$scan                     = aioseo()->seoAnalysis->analyzePost( $postId );
			list( $basic, $advanced ) = array_values( $scan['results'] );

			$data     = array_merge( $basic, $advanced );
			$postType = get_post_type( $postId );
			foreach ( $data as $issue ) {
				$model = new Issue( [
					'object_id'      => $postId,
					'object_type'    => 'post',
					'object_subtype' => $postType,
					'code'           => $issue->code,
					'metadata'       => $issue->metadata
				] );

				$model->save();
			}

			// Update the scan date.
			$post = AioseoPost::getPost( $postId );
			$post->seo_analyzer_scan_date = gmdate( 'Y-m-d H:i:s' );
			$post->save();
		}

		aioseo()->core->cache->delete( $lockKey );
	}

	/**
	 * Get the posts that need to be analyzed.
	 *
	 * @since 4.8.6
	 *
	 * @return array Posts object
	 */
	private function getEnqueuedPosts() {
		$settings           = aioseo()->dynamicOptions->seoAnalysis->all();
		$publicPostTypes    = aioseo()->helpers->getScannablePostTypes();
		$publicPostStatuses = aioseo()->helpers->getPublicPostStatuses( true );

		$postTypes = $publicPostTypes;
		if ( 1 !== (int) $settings['postTypes']['all'] && ! empty( $settings['postTypes']['included'] ) ) {
			$postTypes = array_intersect( $postTypes, $settings['postTypes']['included'] );
		}

		$orderByCasesPostTypes = [];
		foreach ( $postTypes as $value ) {
			$count                   = count( $orderByCasesPostTypes ) + 1;
			$orderByCasesPostTypes[] = "WHEN p.post_type = '$value' THEN $count";
		}

		$postStatuses = aioseo()->helpers->getPublicPostStatuses( true );
		if ( 1 !== (int) $settings['postStatuses']['all'] && ! empty( $settings['postStatuses']['included'] ) ) {
			$postStatuses = array_intersect( $postStatuses, $settings['postStatuses']['included'] );
		}

		$orderByCasesPostStatuses = [];
		foreach ( $postStatuses as $value ) {
			$count                      = count( $orderByCasesPostStatuses ) + 1;
			$orderByCasesPostStatuses[] = "WHEN p.post_status = '$value' THEN $count";
		}

		$select  = [ 'p.ID' ];
		$orderBy = [];

		if ( ! empty( $orderByCasesPostTypes ) ) {
			$select[]  = '(CASE ' . implode( ' ', $orderByCasesPostTypes ) . ' END) as post_type_order';
			$orderBy[] = 'post_type_order';
		}

		if ( ! empty( $orderByCasesPostStatuses ) ) {
			$select[]  = '(CASE ' . implode( ' ', $orderByCasesPostStatuses ) . ' END) as post_status_order';
			$orderBy[] = 'post_status_order';
		}

		$query = aioseo()->core->db->start( 'posts as p' )
			->select( implode( ', ', $select ) )
			->leftJoin( 'aioseo_posts as ap', 'ap.post_id = p.ID' )
			->whereIn( 'p.post_type', $publicPostTypes )
			->whereIn( 'p.post_status', $publicPostStatuses )
			->where( 'ap.seo_analyzer_scan_date', null );

		if ( ! empty( $orderBy ) ) {
			$query->orderBy( implode( ', ', $orderBy ) . ' ASC' );
		}

		$posts = $query
			->limit( $this->getPerRun() )
			->run()
			->result();

		// We just need the IDs.
		foreach ( $posts as &$post ) {
			$post = $post->ID;
		}

		return $posts;
	}
}