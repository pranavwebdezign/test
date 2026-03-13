<?php
namespace AIOSEO\Plugin\Addon\Eeat\Standalones;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the User Profile Tab standalone components.
 *
 * @since 1.0.0
 */
class UserProfileTab {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScript' ], 11 );
		add_action( 'profile_update', [ $this, 'updateAuthorSeoMetaData' ] );
	}

	/**
	 * Enqueues the script.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueueScript() {
		$screen = get_current_screen();
		if ( ! in_array( $screen->id, [ 'user-edit', 'profile' ], true ) ) {
			return;
		}

		global $user_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName
		if ( ! intval( $user_id ) ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName
			return;
		}

		if ( apply_filters( 'aioseo_user_profile_tab_disable', false, $user_id ) ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName
			return;
		}

		// Allow certain users without editing permissions to edit their Author SEO meta data.
		$allowedUserIds = apply_filters( 'aioseo_user_profile_tab_allowed_user_ids', [], $user_id ); // phpcs:ignore Squiz.NamingConventions.ValidVariableName

		$user = aioseo()->helpers->getUserData( $user_id ); // phpcs:ignore Squiz.NamingConventions.ValidVariableName
		if ( ! $user->has_cap( 'edit_posts' ) && ! in_array( $user_id, $allowedUserIds, true ) ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName
			return;
		}

		if ( version_compare( AIOSEO_EEAT_VERSION, aioseo()->addons->getMinimumVersion( 'aioseo-eeat' ), '<' ) ) {
			return;
		}

		aioseoEeat()->core->assets->load( 'src/vue/standalone/user-profile-tab/main.js', [], $this->getVueData(), 'aioseo' );

		wp_enqueue_media();
		wp_enqueue_editor();
		wp_enqueue_style( 'wp-edit-post' );
	}

	/**
	 * Returns the data Vue requires.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function getVueData() {
		global $user_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName
		if ( ! intval( $user_id ) ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName
			return [];
		}

		$vueData                                  = aioseo()->standalone->userProfileTab->getVueData();
		$vueData                                  = aioseoEeat()->helpers->getVueData( $vueData, 'search-appearance' );
		$vueData['userProfile']['authorMetaData'] = aioseoEeat()->helpers->getAuthorMetaData( $user_id ); // phpcs:ignore Squiz.NamingConventions.ValidVariableName

		if ( ! empty( $vueData['userProfile']['authorMetaData']['knowsAbout'] ) ) {
			$vueData['userProfile']['authorMetaData']['knowsAbout'] = wp_json_encode( $vueData['userProfile']['authorMetaData']['knowsAbout'] );
		}

		return $vueData;
	}

	/**
	 * Updates the author SEO meta data.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $userId The user ID.
	 * @return void
	 */
	public function updateAuthorSeoMetaData( $userId ) {
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-user_' . $userId ) ) {
			return;
		}

		if ( empty( $_POST['aioseo-author-seo-meta-data-input'] ) ) {
			return;
		}

		$data = json_decode( wp_unslash( $_POST['aioseo-author-seo-meta-data-input'] ), true );
		if ( empty( $data ) ) {
			return;
		}

		$authorMetaData = aioseo()->helpers->sanitize( $data, false, [ 'authorBio' ] );

		update_user_meta( $userId, 'aioseo_author_meta_data', $authorMetaData );
	}
}