<?php
namespace AIOSEO\Plugin\Addon\Eeat\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to replace tag values with their data counterparts.
 *
 * @since 1.0.0
 */
class Tags {
	/**
	 * A list of contexts to separate tags.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $context = [
		'authorExcerpt' => [
			'author_bio',
			'author_first_name',
			'author_last_name',
			'author_name',
			'current_date',
			'current_day',
			'current_month',
			'current_year',
			'separator_sa',
			'site_title',
			'tagline'
		]
	];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		aioseo()->tags->addContext( $this->context );
	}
}