<?php
namespace AIOSEO\Plugin\Addon\Eeat\Ui {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	use AIOSEO\Plugin\Common\Models as CommonModels;

	/**
	 * Handles the reviewer tooltip.
	 *
	 * @since 1.0.0
	 */
	class ReviewerTooltip extends Base {
		/**
		 * The slug of the block.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected $slug = 'reviewer-tooltip';

		/**
		 * The required feature.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected $requiredFeature = 'post-reviewer';

		/**
		 * The block attributes.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		protected $blockAttributes = [
			'showLabel'   => [
				'type'    => 'boolean',
				'default' => true
			],
			'showImage'   => [
				'type'    => 'boolean',
				'default' => true
			],
			'showTooltip' => [
				'type'    => 'boolean',
				'default' => true
			],
			'showBioLink' => [
				'type'    => 'boolean',
				'default' => true
			]
		];

		/**
		 * The shortcode attributes.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		protected $shortcodeAttributes = [
			'show-label'    => true,
			'show-image'    => true,
			'show-tooltip'  => true,
			'show-bio-link' => true
		];

		/**
		 * Renders the reviewer tooltip.
		 *
		 * @since 1.0.0
		 *
		 * @param  bool        $echo       Whether to echo or return the output.
		 * @param  array       $attributes The attributes.
		 * @return string|void             The output from the output buffering or nothing.
		 */
		public function render( $echo = true, $attributes = [] ) {
			if ( ! aioseo()->license->hasAddonFeature( 'aioseo-eeat', 'post-reviewer' ) ) {
				return;
			}

			$reviewerId            = 0;
			$showSampleDescription = false; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

			$post = get_post();
			if ( is_a( $post, 'WP_Post' ) ) {
				$aioseoPost = CommonModels\Post::getPost( $post->ID );
				if ( ! $aioseoPost->exists() ) {
					return;
				}

				$reviewerId = $aioseoPost->reviewed_by;
			}

			if ( ! is_singular() && ! $reviewerId ) {
				$reviewerId            = get_current_user_id();
				$showSampleDescription = true; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			}

			if ( ! $reviewerId ) {
				if ( ! is_admin() ) {
					return;
				}

				$errorMessage = __( 'You must first assign a reviewer, save the post and reload before this block can be rendered.', 'aioseo-eeat' );
				if ( $echo ) {
					esc_html_e( $errorMessage ); // phpcs:ignore AIOSEO.Wp.I18n

					return;
				}

				return $errorMessage;
			}

			$template = aioseoEeat()->utils->templates->locateTemplate( 'ReviewerTooltip.php' );
			if ( ! $template ) {
				return;
			}

			// Load the CSS here since we're not adding the reviewer tooltip as a block.
			aioseoEeat()->core->assets->enqueueCss( 'src/vue/standalone/blocks/reviewer-tooltip/global.scss' );

			$reviewerMetaData = aioseoEeat()->helpers->getAuthorMetaData( $reviewerId );

			$data = [
				'reviewerId'       => $reviewerId,
				'reviewerMetaData' => $reviewerMetaData,
				'reviewerName'     => get_the_author_meta( 'display_name', $reviewerId ),
				'reviewerImageUrl' => ! empty( $reviewerMetaData['authorImage'] ) ? $reviewerMetaData['authorImage'] : get_avatar_url( $reviewerId, [ 'size' => 300 ] ),
				'reviewerUrl'      => ! empty( $reviewerMetaData['authorCustomUrl'] ) ? $reviewerMetaData['authorCustomUrl'] : get_author_posts_url( $reviewerId ),
				'hasPublishedPost' => count_user_posts( $reviewerId ) > 0,
				'attributes'       => [
					'showLabel'   => $attributes['showLabel'] ?? true,
					'showImage'   => $attributes['showImage'] ?? true,
					'showTooltip' => $attributes['showTooltip'] ?? true,
					'showBioLink' => $attributes['showBioLink'] ?? true,
				],
				'labels'           => aioseoEeat()->ui->labels
			];

			$data = apply_filters( 'aioseo_eeat_reviewer_tooltip_data', $data );

			if ( $echo ) {
				require $template;

				return;
			}

			ob_start();
			require $template;

			return ob_get_clean();
		}
	}
}

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	if ( ! function_exists( 'aioseo_eeat_reviewer_tooltip' ) ) {
		/**
		 * Global function for reviewer tooltip output.
		 *
		 * @since 1.0.0
		 *
		 * @param  bool $showLabel   Whether to show the label.
		 * @param  bool $showImage   Whether to show the image.
		 * @param  bool $showTooltip Whether to show the tooltip.
		 * @param  bool $showBioLink Whether to show the "See Full Bio" link.
		 * @return void
		 */
		function aioseo_eeat_reviewer_tooltip( $showLabel = true, $showImage = true, $showTooltip = true, $showBioLink = true ) {
			$attributes = [
				'showLabel'   => $showLabel,
				'showImage'   => $showImage,
				'showTooltip' => $showTooltip,
				'showBioLink' => $showBioLink
			];

			@aioseoEeat()->ui->reviewerTooltip->render( true, $attributes );
		}
	}
}