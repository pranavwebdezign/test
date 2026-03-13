<?php
namespace AIOSEO\Plugin\Addon\Eeat\Ui {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	use AIOSEO\Plugin\Common\Traits;

	/**
	 * Handles the author archive bio block.
	 *
	 * @since 1.0.0
	 */
	class AuthorBio extends Base {
		use Traits\SocialProfiles;

		/**
		 * The slug of the block.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected $slug = 'author-bio';

		/**
		 * The required feature.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected $requiredFeature = 'author-info';

		/**
		 * The block attributes.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		protected $blockAttributes = [
			'compact'     => [
				'type'    => 'boolean',
				'default' => true
			],
			'showBioLink' => [
				'type'    => 'boolean',
				'default' => true
			]
		];

		/**
		 * Renders the block.
		 *
		 * @since 1.0.0
		 *
		 * @param  array  $blockAttributes The block attributes.
		 * @return string                  The output from the output buffering.
		 */
		public function renderBlock( $blockAttributes ) {
			ob_start();

			if ( ! empty( $blockAttributes['compact'] ) ) {
				aioseoEeat()->ui->authorBioCompact->render( true, $blockAttributes );
			} else {
				$this->render( true, $blockAttributes );
			}

			$buffer = ob_get_clean();

			return $buffer;
		}

		/**
		 * Renders the shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param  array  $attributes The shortcode attributes.
		 * @return void
		 */
		public function renderShortcode( $attributes = [] ) {
			$attributes = shortcode_atts( [
				'compact'       => true,
				'show-bio-link' => true
			], $attributes, 'aioseo_eeat_author_bio' );

			if ( ! empty( $attributes['compact'] ) && 'false' === $attributes['compact'] ) {
				return $this->render( false, $attributes );
			}

			$attributes = [
				'compact'     => $attributes['compact'],
				'showBioLink' => filter_var( $attributes['show-bio-link'], FILTER_VALIDATE_BOOLEAN )
			];

			return aioseoEeat()->ui->authorBioCompact->render( false, $attributes );
		}

		/**
		 * Renders the author archive bio.
		 *
		 * @since 1.0.0
		 *
		 * @param  bool        $echo       Whether to echo or return the output.
		 * @param  array       $attributes The attributes.
		 * @return string|void             The output from the output buffering or nothing.
		 */
		public function render( $echo = true, $attributes = [] ) {
			if ( ! aioseo()->license->hasAddonFeature( 'aioseo-eeat', 'author-archive-bio' ) ) {
				return;
			}

			$template = aioseoEeat()->utils->templates->locateTemplate( 'AuthorBio.php' );
			if ( ! $template ) {
				return;
			}

			$authorId              = 0;
			$showSampleDescription = false; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			if ( is_author() ) {
				$authorId = get_queried_object_id();
			}

			$post = get_post();
			if ( is_singular() && is_a( $post, 'WP_Post' ) ) {
				$authorId = $post->post_author;
			}

			if ( ! $authorId ) {
				if ( is_a( $post, 'WP_Post' ) ) {
					$authorId = $post->post_author;
				} else {
					$authorId              = get_current_user_id();
					$showSampleDescription = true; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
				}
			}

			if ( ! $authorId ) {
				return;
			}

			$authorMetaData = aioseoEeat()->helpers->getAuthorMetaData( $authorId );

			$data = [
				'authorId'       => $authorId,
				'authorMetaData' => $authorMetaData,
				'authorName'     => get_the_author_meta( 'display_name', $authorId ),
				'authorImageUrl' => ! empty( $authorMetaData['authorImage'] ) ? $authorMetaData['authorImage'] : get_avatar_url( $authorId, [ 'size' => 300 ] ),
				'socialUrls'     => $this->getUserProfiles( $authorId ),
				'socialIcons'    => aioseoEeat()->helpers->getSocialIcons(),
				'attributes'     => [
					'showBioLink' => isset( $attributes['showBioLink'] ) ? $attributes['showBioLink'] : true
				],
				'labels'         => aioseoEeat()->ui->labels
			];

			$data = apply_filters( 'aioseo_eeat_author_archive_bio_data', $data );

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