<?php
namespace AIOSEO\Plugin\Addon\Eeat\Standalones;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers our standalone components.
 *
 * @since 1.0.0
 */
class Standalones {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		new ReviewerSetting();
		new SearchAppearance();
		new UserProfileTab();
	}
}