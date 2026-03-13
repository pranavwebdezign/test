<?php
namespace AIOSEO\Plugin\Addon\Eeat\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Route class for the API.
 *
 * @since 1.0.0
 */
class Api {
	/**
	 * The REST routes.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $routes = [
		'POST' => [
			'eeat/authors' => [
				'callback' => [ 'Users', 'getAuthors', 'AIOSEO\\Plugin\\Addon\\Eeat\\Api' ],
				'access'   => [ 'aioseo_page_schema_settings' ]
			]
		]
	];

	/**
	 * Returns all routes that need to be registered.
	 *
	 * @since 1.0.0
	 *
	 * @return array The routes.
	 */
	public function getRoutes() {
		return $this->routes;
	}
}