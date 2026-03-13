<?php
namespace AIOSEO\Plugin\Pro\Redirects\ImportExport;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Pro\Redirects\Models;

/**
 * Imports the settings and meta data from other plugins.
 *
 * @since 4.9.1
 */
class Importer {
	/**
	 * A list of supported HTTP status codes.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	private $supportedStatusCodes = [
		301,
		302,
		303,
		304,
		307,
		308,
		400,
		401,
		403,
		404,
		410,
		418,
		451,
		500,
		501,
		502,
		503,
		504,
		505
	];

	/**
	 * The list of plugins.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	protected $plugins = [];

	/**
	 * The post action name.
	 *
	 * @since 4.9.1
	 *
	 * @param ImportExport $importer The main importer class.
	 */
	public function __construct( $importer ) {
		$plugins = $this->plugins;
		foreach ( $plugins as $key => $plugin ) {
			$plugins[ $key ]['class'] = $this;
		}
		$importer->addPlugins( $plugins );
	}

	/**
	 * Validate the status code.
	 *
	 * @since 4.9.1
	 *
	 * @param  mixed   $code The code to validate.
	 * @return boolean       True if the code validates.
	 */
	protected function validateStatusCode( $code ) {
		if ( is_string( $code ) ) {
			if ( ! preg_match( '/\A\d+\Z/', (string) $code, $matches ) ) {
				return false;
			}
			$code = (int) $code;
		}

		return in_array( $code, $this->supportedStatusCodes, true );
	}

	/**
	 * Import the rule.
	 *
	 * @since 4.9.1
	 *
	 * @param  array $rule The rule data.
	 * @return void
	 */
	public function importRule( $rule ) {
		if ( ! $this->validateStatusCode( $rule['type'] ) ) {
			return;
		}

		$url = $rule['source_url'];
		if ( ! empty( $rule['custom_rules'] ) ) {
			$customRules = ! is_string( $rule['custom_rules'] ) ? wp_json_encode( $rule['custom_rules'] ) : $rule['custom_rules'];
			$url        .= $customRules;
		}

		$redirect = Models\Redirect::getRedirectBySourceUrl( $url );
		$redirect->set( $rule );
		$redirect->save();

		// Save hits.
		if ( ! empty( $rule['hits'] ) ) {
			$redirect->setHits( (int) $rule['hits'] );
		}
	}

	/**
	 * Adds a leading slash on an url.
	 *
	 * @since 4.9.1
	 *
	 * @param  string $url The url string.
	 * @return string      The url with a leading slash.
	 */
	public function leadingSlashIt( $url ) {
		return '/' . ltrim( $url, '/' );
	}
}