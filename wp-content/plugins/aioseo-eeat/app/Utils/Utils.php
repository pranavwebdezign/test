<?php
namespace AIOSEO\Plugin\Addon\Eeat\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register util classes.
 *
 * @since 1.0.0
 */
class Utils {
	/**
	 * Blocks class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Blocks
	 */
	public $blocks = null;

	/**
	 * Tags class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Tags
	 */
	public $tags = null;

	/**
	 * Templates class instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Templates
	 */
	public $templates = null;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->blocks      = new Blocks();
		$this->tags        = new Tags();
		$this->templates   = new Templates();
	}
}