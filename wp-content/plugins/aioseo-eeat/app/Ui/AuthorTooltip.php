<?php
namespace AIOSEO\Plugin\Addon\Eeat\Ui {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Handles the author tooltip.
	 *
	 * @since 1.0.0
	 */
	class AuthorTooltip extends Base {
		/**
		 * The slug of the block.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected $slug = 'author-tooltip';

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
			'postAuthor'  => [
				'type'    => 'number',
				'default' => 0
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
		 * Renders the author tooltip.
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

			$authorId              = 0;
			$showSampleDescription = false; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

			$post = get_post();
			if ( is_a( $post, 'WP_Post' ) && $post->post_author ) {
				$authorId = $post->post_author;
			}

			if ( ! is_singular() && ! $authorId ) {
				$authorId              = get_current_user_id();
				$showSampleDescription = true; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			}

			if ( ! $authorId ) {
				if ( ! is_admin() ) {
					return;
				}

				$errorMessage = __( 'You must first assign an author, save the post and reload before this block can be rendered.', 'aioseo-eeat' );
				if ( $echo ) {
					esc_html_e( $errorMessage ); // phpcs:ignore AIOSEO.Wp.I18n

					return;
				}

				return $errorMessage;
			}

			$template = aioseoEeat()->utils->templates->locateTemplate( 'AuthorTooltip.php' );
			if ( ! $template ) {
				return;
			}

			// Load the CSS here since we're not adding the author tooltip as a block.
			aioseoEeat()->core->assets->enqueueCss( 'src/vue/standalone/blocks/author-tooltip/global.scss' );

			$authorMetaData = aioseoEeat()->helpers->getAuthorMetaData( $authorId );

			$data = [
				'authorId'         => $authorId,
				'authorMetaData'   => $authorMetaData,
				'authorName'       => get_the_author_meta( 'display_name', $authorId ),
				'authorImageUrl'   => ! empty( $authorMetaData['authorImage'] ) ? $authorMetaData['authorImage'] : get_avatar_url( $authorId, [ 'size' => 300 ] ),
				'authorUrl'        => ! empty( $authorMetaData['authorCustomUrl'] ) ? $authorMetaData['authorCustomUrl'] : get_author_posts_url( $authorId ),
				'hasPublishedPost' => count_user_posts( $authorId ) > 0,
				'attributes'       => [
					'showLabel'   => $attributes['showLabel'] ?? true,
					'showImage'   => $attributes['showImage'] ?? true,
					'showTooltip' => $attributes['showTooltip'] ?? true,
					'showBioLink' => $attributes['showBioLink'] ?? true,
				],
				'labels'           => aioseoEeat()->ui->labels
			];

			$data = apply_filters( 'aioseo_eeat_author_tooltip_data', $data );

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

	if ( ! function_exists( 'aioseo_eeat_author_tooltip' ) ) {
		/**
		 * Global function for author tooltip output.
		 *
		 * @since 1.0.0
		 *
		 * @param  bool $showLabel   Whether to show the label.
		 * @param  bool $showImage   Whether to show the image.
		 * @param  bool $showTooltip Whether to show the tooltip.
		 * @param  bool $showBioLink Whether to show the "See Full Bio" link.
		 * @return void
		 */
		function aioseo_eeat_author_tooltip( $showLabel = true, $showImage = true, $showTooltip = true, $showBioLink = true ) {
			$attributes = [
				'showLabel'   => $showLabel,
				'showImage'   => $showImage,
				'showTooltip' => $showTooltip,
				'showBioLink' => $showBioLink
			];

			@aioseoEeat()->ui->authorTooltip->render( true, $attributes );
		}
	}
}