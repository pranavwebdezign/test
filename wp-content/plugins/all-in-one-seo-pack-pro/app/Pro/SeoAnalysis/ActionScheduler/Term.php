<?php

namespace AIOSEO\Plugin\Pro\SeoAnalysis\ActionScheduler;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Pro\Models\Term as AioseoTerm;
use AIOSEO\Plugin\Pro\Models\Issue;

/**
 * Handles the action scheduler for Posts.
 *
 * @since 4.8.6
 */
class Term {
	/**
	 * The action.
	 *
	 * @since 4.8.6
	 *
	 * @var string
	 */
	protected $action = 'aioseo_seo_analysis_terms_scan';

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
		// If we're in idle mode (no terms to scan), unschedule and don't reschedule yet.
		if ( aioseo()->core->cache->get( 'as_seo_analysis_term_idle' ) ) {
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
	 * Handles the analysis for X terms and store the results.
	 *
	 * @since   4.8.6
	 * @version 4.9.4.2 Add runtime lock to prevent concurrent execution.
	 *
	 * @return void
	 */
	public function analyze() {
		// Runtime lock: Prevent concurrent execution of this action.
		$lockKey = 'as_seo_analysis_term_running';
		if ( aioseo()->core->cache->get( $lockKey ) ) {
			return;
		}

		// Set lock with a safety timeout in case the action fails mid-execution.
		aioseo()->core->cache->update( $lockKey, true, 2 * MINUTE_IN_SECONDS );

		$terms = $this->getEnqueuedTerms();
		if ( empty( $terms ) ) {
			// No terms to analyze - set idle cache. The schedule method on the next init will unschedule.
			aioseo()->core->cache->update( 'as_seo_analysis_term_idle', true, DAY_IN_SECONDS );
			aioseo()->core->cache->delete( $lockKey );

			return;
		}

		foreach ( $terms as $termId ) {
			Issue::deleteAll( $termId, 'term' );

			$scan                     = aioseo()->seoAnalysis->analyzeTerm( $termId );
			list( $basic, $advanced ) = array_values( $scan['results'] );

			$data = array_merge( $basic, $advanced );
			$term = get_term( $termId );
			if ( ! empty( $term->taxonomy ) ) {
				foreach ( $data as $issue ) {
					$model = new Issue( [
						'object_id'      => $termId,
						'object_type'    => 'term',
						'object_subtype' => $term->taxonomy,
						'code'           => @$issue->code,
						'metadata'       => @$issue->metadata
					] );

					$model->save();
				}
			}

			// Update the scan date.
			$obj = AioseoTerm::getTerm( $termId );
			$obj->seo_analyzer_scan_date = gmdate( 'Y-m-d H:i:s' );
			$obj->save();
		}

		aioseo()->core->cache->delete( $lockKey );
	}

	/**
	 * Get the terms that need to be analyzed.
	 *
	 * @since 4.8.6
	 *
	 * @return array Terms object
	 */
	private function getEnqueuedTerms() {
		$settings         = aioseo()->dynamicOptions->seoAnalysis->all();
		$publicTaxonomies = array_diff( aioseo()->helpers->getPublicTaxonomies( true ), [ 'product_attributes' ] );

		$taxonomies = $publicTaxonomies;
		if ( 1 !== (int) $settings['taxonomies']['all'] && ! empty( $settings['taxonomies']['included'] ) ) {
			$taxonomies = array_intersect( $taxonomies, $settings['taxonomies']['included'] );
		}

		$orderByCases = [];
		foreach ( $taxonomies as $value ) {
			$count          = count( $orderByCases ) + 1;
			$orderByCases[] = "WHEN tt.taxonomy = '$value' THEN $count";
		}

		$select  = [ 't.term_id' ];
		$orderBy = [];
		if ( ! empty( $orderByCases ) ) {
			$select[]  = '(CASE ' . implode( ' ', $orderByCases ) . ' END) as term_subtype_order';
			$orderBy[] = 'term_subtype_order';
		}

		$query = aioseo()->core->db->start( 'terms as t' )
			->select( implode( ', ', $select ) )
			->join( 'term_taxonomy as tt', 'tt.term_id = t.term_id' )
			->leftJoin( 'aioseo_terms as at', 'at.term_id = t.term_id' )
			->whereIn( 'tt.taxonomy', $publicTaxonomies )
			->where( 'at.seo_analyzer_scan_date', null );

		if ( ! empty( $orderBy ) ) {
			$query->orderBy( implode( ', ', $orderBy ) . ' ASC' );
		}

		$terms = $query
			->limit( $this->getPerRun() )
			->run()
			->result();

		// We just need the IDs.
		foreach ( $terms as &$term ) {
			$term = $term->term_id;
		}

		return $terms;
	}
}