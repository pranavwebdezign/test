<?php
/**
 * Plugin Name: AIOSEO - Author SEO (E-E-A-T)
 * Plugin URI:  https://aioseo.com
 * Description: Adds E-E-A-T support for authors to All in One SEO.
 * Author:      All in One SEO Team
 * Author URI:  https://aioseo.com
 * Version:     1.2.9
 * Text Domain: aioseo-eeat
 * Domain Path: languages
 *
 * @since     1.0.0
 * @author    All in One SEO
 * @package   AIOSEO\Plugin\Addon\Eeat
 * @copyright Copyright © 2025, All in One SEO
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AIOSEO_EEAT_FILE', __FILE__ );
define( 'AIOSEO_EEAT_DIR', __DIR__ );
define( 'AIOSEO_EEAT_PATH', plugin_dir_path( AIOSEO_EEAT_FILE ) );
define( 'AIOSEO_EEAT_URL', plugin_dir_url( AIOSEO_EEAT_FILE ) );

// Require our translation downloader.
require_once __DIR__ . '/extend/translations.php';

add_action( 'init', 'aioseo_eeat_translations' );
function aioseo_eeat_translations() {
	$translations = new AIOSEOTranslations(
		'plugin',
		'aioseo-eeat',
		'https://aioseo.com/aioseo-plugin/aioseo-eeat/packages.json'
	);
	$translations->init();

	// @NOTE: The slugs here need to stay as aioseo-addon.
	$addonTranslations = new AIOSEOTranslations(
		'plugin',
		'aioseo-addon',
		'https://aioseo.com/aioseo-plugin/aioseo-addon/packages.json'
	);
	$addonTranslations->init();
}

require_once __DIR__ . '/extend/init.php';

if ( aioseoAddonIsDisabled( 'aioseo-eeat' ) ) {
	return;
}

new AIOSEOExtend( 'AIOSEO - Author SEO (E-E-A-T)', 'aioseo_eeat_load', AIOSEO_EEAT_FILE, '4.9.0' );

/**
 * Function to load the addon.
 *
 * @since 1.0.0
 *
 * @return void
 */
function aioseo_eeat_load() {
	$levels = aioseo()->addons->getAddonLevels( 'aioseo-eeat' );
	$extend = new AIOSEOExtend( 'AIOSEO - Author SEO (E-E-A-T)', '', AIOSEO_EEAT_FILE, '4.9.0', $levels );

	$addon = aioseo()->addons->getAddon( 'aioseo-eeat' );
	if ( ! $addon->hasMinimumVersion ) {
		$extend->requiresUpdate();

		return;
	}

	if ( ! aioseo()->pro ) {
		return $extend->requiresPro();
	}

	// We don't want to return if the plan is only expired.
	if ( aioseo()->license->isExpired() ) {
		$extend->requiresUnexpiredLicense();
		$extend->disableNotices = true;
	}

	if ( aioseo()->license->isInvalid() || aioseo()->license->isDisabled() ) {
		return $extend->requiresActiveLicense();
	}

	if ( ! aioseo()->license->isAddonAllowed( 'aioseo-eeat' ) ) {
		return $extend->requiresPlanLevel();
	}

	require_once __DIR__ . '/app/Eeat.php';

	aioseoEeat();
}