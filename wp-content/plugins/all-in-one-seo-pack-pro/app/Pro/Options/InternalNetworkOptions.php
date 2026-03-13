<?php
namespace AIOSEO\Plugin\Pro\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Options as CommonOptions;
use AIOSEO\Plugin\Pro\Traits;

/**
 * Class that holds all internal network options for AIOSEO.
 *
 * @since 4.2.5
 */
class InternalNetworkOptions extends CommonOptions\InternalNetworkOptions {
	use Traits\NetworkOptions;

	/**
	 * Defaults options for Pro.
	 *
	 * @since 4.2.5
	 *
	 * @var array
	 */
	private $proDefaults = [
		// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		'internal' => [
			'license' => [
				'expires'          => [ 'type' => 'number', 'default' => 0 ],
				'expired'          => [ 'type' => 'boolean', 'default' => false ],
				'invalid'          => [ 'type' => 'boolean', 'default' => false ],
				'disabled'         => [ 'type' => 'boolean', 'default' => false ],
				'connectionError'  => [ 'type' => 'boolean', 'default' => false ],
				'activationsError' => [ 'type' => 'boolean', 'default' => false ],
				'requestError'     => [ 'type' => 'boolean', 'default' => false ],
				'level'            => [ 'type' => 'string' ],
				'addons'           => [ 'type' => 'string', 'default' => '' ],
				'features'         => [ 'type' => 'string', 'default' => '' ],
				'counts'           => [ 'type' => 'string', 'default' => '' ],
				'upgradeUrl'       => [ 'type' => 'string', 'default' => '' ]
			],
			'ai'      => [
				'accessToken'         => [ 'type' => 'string', 'default' => '' ],
				'isTrialAccessToken'  => [ 'type' => 'boolean', 'default' => false ],
				'isManuallyConnected' => [ 'type' => 'boolean', 'default' => false ],
				'credits'             => [
					'total'     => [ 'type' => 'number', 'default' => 0 ],
					'remaining' => [ 'type' => 'number', 'default' => 0 ],
					'orders'    => [ 'type' => 'array', 'default' => [] ],
					'license'   => [
						'total'     => [ 'type' => 'number', 'default' => 0 ],
						'remaining' => [ 'type' => 'number', 'default' => 0 ],
						'expires'   => [ 'type' => 'number', 'default' => 0 ]
					]
				],
				'costPerFeature'      => [ 'type' => 'array', 'default' => [] ]
			]
		]
		// phpcs:enable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
	];
}