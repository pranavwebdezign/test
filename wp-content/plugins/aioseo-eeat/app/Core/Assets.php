<?php
namespace AIOSEO\Plugin\Addon\Eeat\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Traits;

/**
 * Handles the loading of our file assets.
 *
 * @since 1.0.0
 */
class Assets {
	use Traits\Assets;

	/**
	 * The script handle to use for asset enqueuing.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $scriptHandle = 'aioseo-eeat';

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Core $core The AIOSEO Core class.
	 */
	public function __construct( $core ) {
		$this->core         = $core;
		$this->version      = aioseoEeat()->version;
		$this->manifestFile = AIOSEO_EEAT_DIR . '/dist/Pro/manifest.php';
		$this->isDev        = aioseoEeat()->isDev;

		if ( $this->isDev ) {
			$this->domain = getenv( 'VITE_AIOSEO_EEAT_DOMAIN' );
			$this->port   = getenv( 'VITE_AIOSEO_EEAT_DEV_PORT' );
		}

		add_filter( 'script_loader_tag', [ $this, 'scriptLoaderTag' ], 10, 3 );
	}

	/**
	 * Returns the public URL base.
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL base.
	 */
	private function getPublicUrlBase() {
		return $this->shouldLoadDev() ? $this->getDevUrl() . 'dist/Pro/assets/' : $this->basePath();
	}

	/**
	 * Returns the base path URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string The base path URL.
	 */
	private function basePath() {
		return $this->normalizeAssetsHost( plugins_url( 'dist/Pro/assets/', AIOSEO_EEAT_FILE ) );
	}
}