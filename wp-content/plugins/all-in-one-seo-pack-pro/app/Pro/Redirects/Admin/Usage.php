<?php
namespace AIOSEO\Plugin\Pro\Redirects\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Description
 *
 * @since 4.9.1
 */
class Usage {
	/**
	 * Retrieves the data to send in the usage tracking.
	 *
	 * @since 4.9.1
	 *
	 * @return array An array of data to send.
	 */
	public function getData() {
		return [
			'options'         => aioseo()->redirects->options->all(),
			'internalOptions' => aioseo()->redirects->internalOptions->all()
		];
	}
}