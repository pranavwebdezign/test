<?php
namespace AIOSEO\Plugin\Pro\Social;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Social as CommonSocial;

/**
 * Handles our social meta.
 *
 * @since 4.0.0
 */
class Social extends CommonSocial\Social {
	/**
	 * Class constructor.
	 *
	 * @since   4.0.0
	 * @version 4.9.4.2 Fire OG cache bust directly instead of via Action Scheduler.
	 */
	public function __construct() {
		parent::__construct();

		$this->image = new Image();

		$this->facebook = new Facebook();
		$this->twitter  = new Twitter();

		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$this->output = new Output();

		add_action( 'edited_terms', [ $this, 'bustOgCacheTerm' ] );
	}

	/**
	 * Pings Facebook and asks them to bust the OG cache for a particular term.
	 * Uses a non-blocking request so it doesn't delay the current page load.
	 *
	 * @since   4.2.0
	 * @version 4.9.4.2 Fire directly instead of via Action Scheduler.
	 *
	 * @see https://developers.facebook.com/docs/sharing/opengraph/using-objects#update
	 *
	 * @param  int $termId The term ID.
	 * @return void
	 */
	public function bustOgCacheTerm( $termId ) {
		$term              = aioseo()->helpers->getTerm( $termId );
		$customAccessToken = apply_filters( 'aioseo_facebook_access_token', '' );

		if (
			! is_a( $term, 'WP_Term' ) ||
			( ! aioseo()->helpers->isSbCustomFacebookFeedActive() && ! $customAccessToken )
		) {
			return;
		}

		$permalink = get_term_link( $termId );
		$this->bustOgCacheHelper( $permalink );
	}
}