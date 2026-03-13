<?php
namespace AIOSEO\Plugin\Pro\Redirects\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Admin class.
 *
 * @since 4.9.1
 */
class Admin {
	/**
	 * Class constructor.
	 *
	 * @since 4.9.1
	 */
	public function __construct() {
		add_action( 'current_screen', [ $this, 'loadAddRedirectModal' ] );
	}

	/**
	 * Loads needed admin scripts.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function loadAddRedirectModal() {
		if ( ! is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( aioseo()->helpers->isScreenPostList() || aioseo()->helpers->isScreenPostEdit() ) {
			aioseo()->core->assets->load( 'src/vue/standalone/redirects/add-redirect/main.js', [], aioseo()->helpers->getVueData() );
		}
	}
}