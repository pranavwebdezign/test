<?php
namespace AIOSEO\Plugin\Pro\Redirects\ImportExport;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Pro\Redirects\Models;
use AIOSEO\Plugin\Pro\Redirects\Utils;

class Redirects301 extends Importer {
	/**
	 * A list of plugins to look for to import.
	 *
	 * @since 4.9.1
	 *
	 * @var array
	 */
	public $plugins = [
		[
			'name'     => '301 Redirects',
			'version'  => '2.67',
			'basename' => 'eps-301-redirects/eps-301-redirects.php',
			'slug'     => '301-redirects'
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
		if ( ! aioseo()->core->db->tableExists( 'redirects' ) ) {
			return;
		}

		$rules = aioseo()->core->db->start( 'redirects' )
			->run()
			->result();
		foreach ( $rules as $rule ) {
			if ( ! $this->validateStatusCode( $rule->status ) ) {
				continue;
			}

			if ( empty( $rule->url_to ) ) {
				$rule->url_to = '/';
			}

			if ( is_numeric( $rule->url_to ) ) {
				$rule->url_to = Utils\WpUri::getPostPath( $rule->url_to );
			}

			// Codes higher than 400 don't have a target URL.
			if ( 400 <= $rule->status ) {
				$rule->url_to = '';
			}

			$fromUrl    = $this->leadingSlashIt( $rule->url_from );
			$redirect   = Models\Redirect::getRedirectBySourceUrl( $fromUrl );
			$redirect->set( [
				'source_url'   => $fromUrl,
				'target_url'   => $rule->url_to,
				'type'         => $rule->status,
				'query_param'  => json_decode( aioseo()->redirects->options->redirectDefaults->queryParam )->value,
				'group'        => 'manual',
				'regex'        => false,
				'ignore_slash' => aioseo()->redirects->options->redirectDefaults->ignoreSlash,
				'ignore_case'  => aioseo()->redirects->options->redirectDefaults->ignoreCase,
				'enabled'      => true
			] );
			$redirect->save();

			// Save hits.
			if ( $rule->count ) {
				$redirect->setHits( (int) $rule->count );
			}
		}
	}
}