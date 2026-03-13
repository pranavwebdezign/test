<?php
namespace AIOSEO\Plugin\Pro\Redirects\ImportExport;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Pro\Redirects\Models;
use AIOSEO\Plugin\Pro\Redirects\Utils;

class YoastSeo extends Importer {
	/**
	 * A list of plugins to look for to import.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	public $plugins = [
		[
			'name'     => 'Yoast SEO Premium',
			'version'  => '14.0',
			'basename' => 'wordpress-seo-premium/wp-seo-premium.php',
			'slug'     => 'yoast-seo-premium'
		]
	];

	/**
	 * Import.
	 *
	 * @since 4.9.1
	 *
	 * @return void
	 */
	public function doImport() {
		$rules = get_option( 'wpseo-premium-redirects-base' );

		if ( ! is_array( $rules ) ) {
			return;
		}

		foreach ( $rules as $rule ) {
			if ( ! $this->validateStatusCode( $rule['type'] ) ) {
				continue;
			}

			$urlFrom = 'regex' === $rule['format'] ? $rule['origin'] : $this->leadingSlashIt( $rule['origin'] );
			$urlTo   = 0 === strpos( $rule['url'], 'http' ) || '/' === $rule['url'] ? $rule['url'] : $this->leadingSlashIt( $rule['url'] );
			if ( empty( $urlTo ) ) {
				$urlTo = '/';
			}

			// Codes higher than 400 don't have a target URL.
			if ( 400 <= $rule['type'] ) {
				$urlTo = '';
			}

			$redirect = Models\Redirect::getRedirectBySourceUrl( $urlFrom );
			$redirect->set( [
				'source_url'   => $urlFrom,
				'target_url'   => $urlTo,
				'type'         => $rule['type'],
				'query_param'  => json_decode( aioseo()->redirects->options->redirectDefaults->queryParam )->value,
				'group'        => 'manual',
				'regex'        => 'regex' === $rule['format'],
				'ignore_slash' => aioseo()->redirects->options->redirectDefaults->ignoreSlash,
				'ignore_case'  => aioseo()->redirects->options->redirectDefaults->ignoreCase,
				'enabled'      => true
			] );
			$redirect->save();
		}
	}
}