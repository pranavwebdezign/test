<?php
namespace AIOSEO\Plugin\Addon\Eeat\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Updater class.
 *
 * @since 1.0.0
 */
class Updates {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		add_action( 'aioseo_run_updates', [ $this, 'runUpdates' ], 1000 );
		add_action( 'aioseo_run_updates', [ $this, 'updateLatestVersion' ], 3000 );
	}

	/**
	 * Runs our migrations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function runUpdates() {
		$lastActiveVersion = aioseoEeat()->internalOptions->internal->lastActiveVersion;
		// Don't run updates if the last active version is the same as the current version.
		if ( aioseoEeat()->version === $lastActiveVersion ) {
			return;
		}

		// Try to acquire the lock.
		if ( ! aioseo()->core->db->acquireLock( 'aioseo_eeat_run_updates_lock', 0 ) ) {
			// If we couldn't acquire the lock, exit early without doing anything.
			// This means another process is already running updates.
			return;
		}

		if ( version_compare( $lastActiveVersion, '1.0.0', '<' ) ) {
			$this->addReviewedByColumn();
			$this->migrateGlobalTopics();
		}
	}

	/**
	 * Updates the latest version after all migrations and updates have run.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function updateLatestVersion() {
		if ( aioseoEeat()->internalOptions->internal->lastActiveVersion === aioseoEeat()->version ) {
			return;
		}

		aioseoEeat()->internalOptions->internal->lastActiveVersion = aioseoEeat()->version;

		// Bust the DB cache so we can make sure that everything is fresh.
		aioseo()->core->db->bustCache();
	}

	/**
	 * Adds the reviewed by column to our posts table.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function addReviewedByColumn() {
		if ( aioseo()->core->db->columnExists( 'aioseo_posts', 'reviewed_by' ) ) {
			return;
		}

		$tableName = aioseo()->core->db->db->prefix . 'aioseo_posts';
		aioseo()->core->db->execute(
			"ALTER TABLE {$tableName}
			ADD reviewed_by bigint(20) unsigned DEFAULT NULL AFTER limit_modified_date"
		);

		// Reset the cache for the installed tables.
		aioseo()->core->cache->delete( 'db_schema' );
	}

	/**
	 * Migrates the global topics from JSON to an array for beta users.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function migrateGlobalTopics() {
		$globalKnowsAbout = aioseoEeat()->options->eeat->globalKnowsAbout;
		if ( empty( $globalKnowsAbout ) || is_array( $globalKnowsAbout ) ) {
			return;
		}

		$globalKnowsAbout = json_decode( $globalKnowsAbout, true );
		if ( ! is_array( $globalKnowsAbout ) ) {
			return;
		}

		aioseoEeat()->options->eeat->globalKnowsAbout = $globalKnowsAbout;
	}
}