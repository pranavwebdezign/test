<?php
namespace AIOSEO\Plugin\Addon\Eeat\Standalones;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Reviewer Setting standalone components.
 *
 * @since 1.0.0
 */
class ReviewerSetting {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( apply_filters( 'aioseo_eeat_reviewer_disable', false ) ) {
			return;
		}

		if ( ! aioseo()->license->hasAddonFeature( 'aioseo-eeat', 'post-reviewer' ) ) {
			return;
		}

		add_action( 'rest_api_init', [ $this, 'registerRestHooks' ] );

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScript' ] );
		add_action( 'post_submitbox_misc_actions', [ $this, 'outputClassicEditorContainer' ] );
	}

	/**
	 * Register the REST API hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function registerRestHooks() {
		// Prevent REST API from dropping limit modified date value before updating the post.
		foreach ( aioseo()->helpers->getPublicPostTypes( true ) as $postType ) {
			add_filter( "rest_pre_insert_$postType", [ $this, 'addReviewedByValue' ], 10, 2 );
		}
	}

	/**
	 * Enqueues the script.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueueScript() {
		if ( ! $this->isAllowed() || ! aioseo()->helpers->isScreenBase( 'post' ) ) {
			return;
		}

		// Only enqueue this script if the post-settings-metabox is already enqueued.
		if ( wp_script_is( 'aioseo/js/src/vue/standalone/post-settings/main.js', 'enqueued' ) ) {
			aioseoEeat()->core->assets->load( 'src/vue/standalone/reviewer-setting/main.js', [], [], 'aioseo' );
		}
	}

	/**
	 * Add the container for the Classic Editor.
	 *
	 * @since 4.1.8
	 *
	 * @param  \WP_Post $post The post object.
	 * @return void
	 */
	public function outputClassicEditorContainer( $post ) {
		if ( ! $this->isAllowed( $post->post_type ) ) {
			return;
		}

		?>
		<div class="misc-pub-section">
			<div id="aioseo-reviewed-by"></div>
		</div>
		<?php
	}

	/**
	 * Check if the reviewer setting feature is supposed to be mounted.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $postType The current post type.
	 * @return bool             Whether the functionality is allowed.
	 */
	private function isAllowed( $postType = false ) {
		if ( empty( $postType ) ) {
			$postType = get_post_type();
		}

		if ( ! $this->isAllowedPostType( $postType ) ) {
			return false;
		}

		if ( ! aioseo()->access->hasCapability( 'aioseo_page_schema_settings' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the given post type is allowed to limit the modified date.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $postType The post type name.
	 * @return bool             Whether the post type is allowed.
	 */
	private function isAllowedPostType( $postType ) {
		$dynamicOptions = aioseo()->dynamicOptions->noConflict();
		$postTypes      = aioseo()->helpers->getPublicPostTypes( true );

		$postTypes = array_diff( $postTypes, [ 'download', 'product' ] );
		$postTypes = apply_filters( 'aioseo_reviewer_post_types', $postTypes );

		if ( ! in_array( $postType, $postTypes, true ) ) {
			return false;
		}

		if ( ! $dynamicOptions->searchAppearance->postTypes->has( $postType ) || ! $dynamicOptions->searchAppearance->postTypes->$postType->advanced->showMetaBox ) {
			return false;
		}

		if ( ! post_type_supports( $postType, 'author' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Adds the Reviewed by field to the post object to prevent it from being dropped.
	 *
	 * @since 1.0.0
	 *
	 * @param  Object          $preparedPost The post data.
	 * @param  WP_REST_Request $restRequest  The request.
	 * @return Object                        The modified post data.
	 */
	public function addReviewedByValue( $preparedPost, $restRequest = null ) {
		if ( 'PUT' !== $restRequest->get_method() ) {
			return $preparedPost;
		}

		$params = $restRequest->get_json_params();
		if ( empty( $params ) || ! isset( $params['aioseo_reviewed_by'] ) ) {
			return $preparedPost;
		}

		$preparedPost->aioseo_reviewed_by = $params['aioseo_reviewed_by'];

		return $preparedPost;
	}
}