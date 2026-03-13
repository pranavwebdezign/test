<?php
namespace AIOSEO\Plugin\Addon\Eeat\Ui {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	use AIOSEO\Plugin\Common\Traits;

	/**
	 * Handles the compact author bio block.
	 *
	 * @since 1.0.0
	 */
	class AuthorBioCompact extends Base {
		use Traits\SocialProfiles;

		/**
		 * Class constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_filter( 'the_content', [ $this, 'inject' ], 200 );

			add_action( 'aioseo_bricks_content_end', [ $this, 'injectPageBuilder' ] );
			add_action( 'aioseo_oxygen_content_end', [ $this, 'injectPageBuilder' ] );
		}

		/**
		 * Checks if the author bio injection should be enabled for the current post.
		 *
		 * @since 1.2.9
		 *
		 * @return bool Whether the author bio should be injected.
		 */
		private function shouldInject() {
			if ( ! is_singular() ) {
				return false;
			}

			$post = get_post();
			if (
				! is_a( $post, 'WP_Post' ) ||
				empty( $post->post_author ) ||
				in_array( $post->post_type, [ 'download', 'product', 'web-story' ], true ) ||
				! post_type_supports( $post->post_type, 'author' ) ||
				post_password_required()
			) {
				return false;
			}

			$authorMetaData = aioseoEeat()->helpers->getAuthorMetaData( $post->post_author );
			$enabled        = ! empty( $authorMetaData['enabled'] ) ? aioseoEeat()->options->eeat->settings->authorBioInjection : false;
			if ( ! empty( $authorMetaData['enabled'] ) && empty( $authorMetaData['injectAuthorBioDefault'] ) ) {
				$enabled = ! empty( $authorMetaData['injectAuthorBio'] );
			}

			if ( ! apply_filters( 'aioseo_eeat_author_bio_inject', $enabled, $post ) ) {
				return false;
			}

			$postTypes = aioseoEeat()->options->eeat->settings->postTypes->all();
			if (
				! $postTypes['all'] &&
				! in_array( $post->post_type, $postTypes['included'], true )
			) {
				return false;
			}

			// Load the CSS here since we're not adding the author bio as a block.
			if ( ! empty( $authorMetaData['enabled'] ) || $enabled ) {
				aioseoEeat()->core->assets->enqueueCss( 'src/vue/standalone/blocks/author-bio/global.scss' );
			}

			return true;
		}

		/**
		 * Injects the author bio block into the post content.
		 *
		 * @since 1.0.0
		 *
		 * @param  string $postContent The post content.
		 * @return string              The post content with the author bio appended.
		 */
		public function inject( $postContent ) {
			if ( ! $this->shouldInject() ) {
				return $postContent;
			}

			$postContent .= $this->render( false );

			return $postContent;
		}

		/**
		 * Injects the author bio block for page builders that bypass `the_content` filter.
		 *
		 * @since 1.2.9
		 *
		 * @return void
		 */
		public function injectPageBuilder() {
			if ( ! $this->shouldInject() ) {
				return;
			}

			$this->render();
		}

		/**
		 * Renders the author bio block.
		 *
		 * @since 1.0.0
		 *
		 * @param  bool        $echo       Whether to echo or return the output.
		 * @param  array       $attributes The attributes for the block.
		 * @return string|void             The output from the output buffering or nothing.
		 */
		public function render( $echo = true, $attributes = [] ) {
			if ( ! aioseo()->license->hasAddonFeature( 'aioseo-eeat', 'author-info' ) ) {
				return;
			}

			$template = aioseoEeat()->utils->templates->locateTemplate( 'AuthorBioCompact.php' );
			if ( ! $template ) {
				return;
			}

			// If there is no post, fall back to the ID of the current user. We need to do so in order to generate a sample in the Site Editor.
			$authorId              = 0;
			$showSampleDescription = false; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
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

			$authorMetaData = aioseoEeat()->helpers->getAuthorMetaData( $authorId );

			$data = [
				'authorId'       => $authorId,
				'authorMetaData' => $authorMetaData,
				'authorName'     => get_the_author_meta( 'display_name', $authorId ),
				'authorImageUrl' => ! empty( $authorMetaData['authorImage'] ) ? $authorMetaData['authorImage'] : get_avatar_url( $authorId, [ 'size' => 300 ] ),
				'authorUrl'      => ! empty( $authorMetaData['authorCustomUrl'] ) ? $authorMetaData['authorCustomUrl'] : get_author_posts_url( $authorId ),
				'socialUrls'     => $this->getUserProfiles( $authorId ),
				'socialIcons'    => aioseoEeat()->helpers->getSocialIcons(),
				'attributes'     => [
					'showBioLink' => isset( $attributes['showBioLink'] ) ? $attributes['showBioLink'] : true,
				],
				'labels'         => aioseoEeat()->ui->labels,
			];

			$data = apply_filters( 'aioseo_eeat_author_bio_data', $data );

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