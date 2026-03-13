<?php
namespace AIOSEO\Plugin\Addon\Eeat\Ui {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Registers our UI elements.
	 *
	 * @since 1.0.0
	 */
	class Ui {
		/**
		 * Author Bio class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var AuthorBio
		 */
		public $authorBio;

		/**
		 * Author Bio Compact class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var authorBioCompact
		 */
		public $authorBioCompact;

		/**
		 * Author Tooltip class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var AuthorTooltip
		 */
		public $authorTooltip;

		/**
		 * Reviewer Tooltip class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var ReviewerTooltip
		 */
		public $reviewerTooltip;

		/**
		 * The labels texts.
		 *
		 * @since 1.2.0
		 *
		 * @var array
		 */
		public $labels = [];

		/**
		 * Class constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->authorBio        = new AuthorBio();
			$this->authorBioCompact = new AuthorBioCompact();
			$this->authorTooltip    = new AuthorTooltip();
			$this->reviewerTooltip  = new ReviewerTooltip();

			add_action( 'init', [ $this, 'init' ] );
		}

		/**
		 * Sets the labels.
		 *
		 * @since 1.2.1
		 *
		 * @return void
		 */
		public function init() {
			$this->labels = apply_filters( 'aioseo_eeat_author_labels', [
				'seeFullBio'       => __( 'See Full Bio', 'aioseo-eeat' ),
				'alumniOf'         => __( 'Education:', 'aioseo-eeat' ),
				'writtenBy'        => __( 'Written By:', 'aioseo-eeat' ),
				'reviewedBy'       => __( 'Reviewed By:', 'aioseo-eeat' ),
				'authorImageAlt'   => __( 'author avatar', 'aioseo-eeat' ),
				'reviewerImageAlt' => __( 'reviewer avatar', 'aioseo-eeat' ),
				'socialsIconAlt'   => __( 'social network icon', 'aioseo-eeat' )
			] );
		}
	}
}

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	if ( ! function_exists( 'aioseo_eeat_author_bio' ) ) {
		/**
		 * Global function for the compact author bio block output.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		function aioseo_eeat_author_bio( $compact = true, $showBioLink = true ) {
			$attributes = [ 'showBioLink' => $showBioLink ];

			if ( $compact ) {
				aioseoEeat()->ui->authorBioCompact->render( true, $attributes );

				return;
			}

			aioseoEeat()->ui->authorBio->render( true, $attributes );
		}
	}
}