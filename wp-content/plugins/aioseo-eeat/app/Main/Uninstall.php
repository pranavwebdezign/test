<?php
namespace AIOSEO\Plugin\Addon\Eeat\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin deinstallation.
 *
 * @since 1.2.2
 */
class Uninstall {
	/**
	 * Removes all data.
	 *
	 * @since 1.2.2
	 *
	 * @param  bool $force Whether we should ignore the uninstall option or not. We ignore it when we reset all data via the Debug Panel.
	 * @return void
	 */
	public function dropData( $force = false ) {
		// Don't call `aioseo()->options` as it's not loaded during uninstall.
		$aioseoOptions = get_option( 'aioseo_options', '' );
		$aioseoOptions = json_decode( $aioseoOptions, true );

		// Confirm that user has decided to remove all data, otherwise stop.
		if (
			! $force &&
			empty( $aioseoOptions['advanced']['uninstall'] )
		) {
			return;
		}

		// Delete user meta.
		$this->deleteUserMeta();
	}

	/**
	 * Deletes all user meta.
	 *
	 * @since 1.2.2
	 *
	 * @return void
	 */
	private function deleteUserMeta() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", 'aioseo_%' ) );
	}
}