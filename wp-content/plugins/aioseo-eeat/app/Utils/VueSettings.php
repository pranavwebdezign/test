<?php
namespace AIOSEO\Plugin\Addon\Eeat\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vue Settings for the user.
 *
 * @since 1.0.0
 */
class VueSettings {
	/**
	 * All the default settings.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $defaults = [
		'toggledCards' => [
			'authorSeoMetaData' => true,
			'authorSeoTopics'   => true,
			'authorSeoSettings' => true
		]
	];

	/**
	 * Adds some defaults to the dynamically generated defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function addDynamicDefaults() {
		return $this->defaults;
	}
}