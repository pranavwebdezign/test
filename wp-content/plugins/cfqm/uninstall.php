<?php
/**
 * ChangeFluent Quote Manager — Uninstall
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Only clears plugin data — does NOT delete posts/media that the user created.
 *
 * Data removed:
 *   - Custom DB tables (cfqm_events, cfqm_tokens)
 *   - Plugin options (cfqm_settings, cfqm_db_version, cfqm_categories_seeded)
 *   - Scheduled cron events
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Remove custom tables
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cfqm_events" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cfqm_tokens" );

// Remove options
delete_option( 'cfqm_settings' );
delete_option( 'cfqm_db_version' );
delete_option( 'cfqm_categories_seeded' );

// Clear cron
wp_clear_scheduled_hook( 'cfqm_hourly' );

// Flush rewrite rules
flush_rewrite_rules();
