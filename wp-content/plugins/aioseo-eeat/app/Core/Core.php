<?php
namespace AIOSEO\Plugin\Addon\Eeat\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads core classes.
 *
 * @since 1.0.0
 */
class Core {
	/**
	 * Assets class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Assets
	 */
	public $assets;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets( $this );
	}
}