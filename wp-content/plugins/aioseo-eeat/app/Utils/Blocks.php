<?php
namespace AIOSEO\Plugin\Addon\Eeat\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Utils\Blocks as CommonBlocks;

/**
 * Block related helper methods.
 *
 * @since 1.0.0
 */
class Blocks extends CommonBlocks {
	/**
	 * The block slugs.
	 *
	 * @since 1.2.6
	 *
	 * @var array
	 */
	private $blockSlugs = [];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initializes our blocks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'registerBlockEditorAssets' ] );
	}

	/**
	 * Registers a given block.
	 *
	 * @since 1.0.0
	 *
	 * @param  string               $slug The block name.
	 * @param  array                $args A list of block arguments.
	 * @return \WP_Block_Type|false       The registered block on success, or false on failure.
	 */
	public function registerBlock( $slug = '', $args = [] ) {
		$slugWithPrefix = $slug;
		if ( ! strpos( $slugWithPrefix, '/' ) ) {
			$slugWithPrefix = 'aioseo/' . $slug;
		}

		if ( ! $this->isBlockEditorActive() ) {
			return false;
		}

		// Check if the block requires a minimum WP version.
		global $wp_version; // phpcs:ignore Squiz.NamingConventions.ValidVariableName
		if ( ! empty( $args['wp_min_version'] ) && version_compare( $wp_version, $args['wp_min_version'], '>' ) ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName
			return false;
		}

		// Checking whether block is registered to ensure it isn't registered twice.
		if ( $this->isRegistered( $slugWithPrefix ) ) {
			return false;
		}

		// Store the block slugs so we can enqueue the global & editor assets later on.
		// We can't do this here because the built-in functions from WP will throw notices due to things running too soon.
		$this->blockSlugs[] = $slug;

		// Check if the block has global or editor assets. If so, we'll need to enqueue them later on.
		$editorScript    = "src/vue/standalone/blocks/{$slug}/editor-script.js";
		$hasEditorScript = ! empty( aioseoEeat()->core->assets->getAssetManifestItem( $editorScript ) );

		$editorStyle     = "src/vue/standalone/blocks/{$slug}/editor.scss";
		$hasEditorStyle  = ! empty( aioseoEeat()->core->assets->getAssetManifestItem( $editorStyle ) );

		$globalStyle     = "src/vue/standalone/blocks/{$slug}/global.scss";
		$hasGlobalStyle  = ! empty( aioseoEeat()->core->assets->getAssetManifestItem( $globalStyle ) );

		// Register global CSS before registering the block type (WordPress 5.8+ requirement)
		if ( $hasGlobalStyle ) {
			aioseoEeat()->core->assets->registerCss( $globalStyle );
		}

		$defaults = [
			'attributes'      => [],
			'editor_script'   => $hasEditorScript ? aioseoEeat()->core->assets->jsHandle( $editorScript ) : '',
			'editor_style'    => $hasEditorStyle ? aioseoEeat()->core->assets->cssHandle( $editorStyle ) : '',
			'render_callback' => null,
			'style'           => $hasGlobalStyle ? aioseoEeat()->core->assets->cssHandle( $globalStyle ) : '',
			'supports'        => []
		];

		$args = wp_parse_args( $args, $defaults );

		return register_block_type( $slugWithPrefix, $args );
	}

	/**
	 * Registers Block Editor assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function registerBlockEditorAssets() {
		// TODO: Get rid of these dependencies. We should import them into the blocks.
		$dependencies = [
			'wp-annotations',
			'wp-block-editor',
			'wp-blocks',
			'wp-components',
			'wp-element',
			'wp-i18n',
			'wp-data',
			'wp-url',
			'wp-polyfill'
		];

		aioseoEeat()->core->assets->loadCss( 'src/vue/standalone/blocks/main.jsx' ); // Loads all JS attached CSS files (e.g. sidebar options) for all blocks.

		foreach ( $this->blockSlugs as $slug ) {
			aioseoEeat()->core->assets->enqueueJs( "src/vue/standalone/blocks/{$slug}/main.jsx", $dependencies );

			// Note: Since these files load conditionally, these need to be added to the vite.config as standalone entries.
			// TODO: Refactor this to use the block.json file (if possible - might conflict with hashes) in the future when 5.8.0 is the min. supported version.
			$editorScript    = "src/vue/standalone/blocks/{$slug}/editor-script.js";
			$hasEditorScript = ! empty( aioseoEeat()->core->assets->getAssetManifestItem( $editorScript ) );
			if ( $hasEditorScript ) {
				aioseoEeat()->core->assets->enqueueJs( $editorScript );
			}

			$editorStyle    = "src/vue/standalone/blocks/{$slug}/editor.scss";
			$hasEditorStyle = ! empty( aioseoEeat()->core->assets->getAssetManifestItem( $editorStyle ) );
			if ( $hasEditorStyle ) {
				aioseoEeat()->core->assets->registerCss( $editorStyle );
			}
		}
	}
}