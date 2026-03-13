<?php
namespace AIOSEO\Plugin\Addon\Eeat\Standalones;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Search Appearance standalone components.
 *
 * @since 1.0.0
 */
class SearchAppearance {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'load-all-in-one-seo_page_aioseo-search-appearance', [ $this, 'hooks' ], 11 );
	}

	/**
	 * Hooks into the AIOSEO page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hooks() {
		$currentScreen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		global $admin_page_hooks; // phpcs:ignore Squiz.NamingConventions.ValidVariableName
		// phpcs:disable Squiz.NamingConventions.ValidVariableName
		if ( ! is_object( $currentScreen ) || empty( $currentScreen->id ) || empty( $admin_page_hooks ) || 'all-in-one-seo_page_aioseo-search-appearance' !== $currentScreen->id ) {
			return;
		}
		// phpcs:enable Squiz.NamingConventions.ValidVariableName

		if ( version_compare( AIOSEO_EEAT_VERSION, aioseo()->addons->getMinimumVersion( 'aioseo-eeat' ), '<' ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ], 11 );
	}

	/**
	 * Enqueues the assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueueAssets() {
		aioseoEeat()->core->assets->load( 'src/vue/standalone/search-appearance/main.js', [], [], 'aioseo' );
	}
}