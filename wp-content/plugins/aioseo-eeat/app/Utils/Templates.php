<?php
namespace AIOSEO\Plugin\Addon\Eeat\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Utils\Templates as CommonTemplates;

/**
 * Extends the templates clasa from the main plugin.
 *
 * @since 1.0.0
 */
class Templates extends CommonTemplates {
	/**
	 * This plugin absolute path.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $pluginPath = AIOSEO_EEAT_PATH;

	/**
	 * Paths were our template files are located.
	 *
	 * @since 1.0.0
	 *
	 * @var string List of paths to check.
	 */
	protected $paths = [
		'app/Views'
	];

	/**
	 * Subpath for theme usage.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $themeTemplateSubpath = 'eeat';
}