<?php
namespace AIOSEO\Plugin\Addon\Eeat\Ui {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Base class for the UI element classes.
	 *
	 * @since 1.0.0
	 */
	class Base {
		/**
		 * The slug of the block.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected $slug = '';

		/**
		 * The required feature.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected $requiredFeature = '';

		/**
		 * The block attributes.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		protected $blockAttributes = [];

		/**
		 * The shortcode attributes.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		protected $shortcodeAttributes = [];

		/**
		 * The shortcode name.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private $shortcodeName = '';

		/**
		 * Whether the block is rendering.
		 *
		 * @since 1.2.6
		 *
		 * @var bool
		 */
		protected $isRenderingBlock = false;

		/**
		 * Class constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			if ( ! aioseo()->license->hasAddonFeature( 'aioseo-eeat', $this->requiredFeature ) ) {
				return;
			}

			add_action( 'init', [ $this, 'register' ] );
		}

		/**
		 * Registers the block.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function register() {
			aioseoEeat()->utils->blocks->registerBlock(
				$this->slug, [
					'attributes'      => $this->blockAttributes,
					'render_callback' => [ $this, 'renderBlock' ]
				]
			);

			$this->shortcodeName = 'aioseo_eeat_' . str_replace( '-', '_', $this->slug );
			add_shortcode( $this->shortcodeName, [ $this, 'renderShortcode' ] );
		}
		/**
		 * Renders the shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param  array  $attributes The shortcode attributes.
		 * @return string             The output from the output buffering.
		 */
		public function renderShortcode( $attributes ) {
			$attributes = shortcode_atts( $this->shortcodeAttributes, $attributes, $this->shortcodeName );

			// Loop over the attributes and convert string booleans to actual booleans.
			foreach ( $attributes as $key => $value ) {
				if ( 'true' === $value ) {
					$attributes[ $key ] = true;
				} elseif ( 'false' === $value ) {
					$attributes[ $key ] = false;
				}
			}

			// Convert the attribute names to camel case. We need to do this in order to be consistent with the block attribute names.
			$attributes = array_combine(
				array_map(
					function( $key ) {
						return lcfirst( str_replace( ' ', '', ucwords( str_replace( '-', ' ', $key ) ) ) );
					},
					array_keys( $attributes )
				),
				array_values( $attributes )
			);

			return @$this->render( false, $attributes );
		}

		/**
		 * Renders the block.
		 *
		 * @since 1.0.0
		 *
		 * @param  array  $blockAttributes The block attributes.
		 * @return string                  The output from the output buffering.
		 */
		public function renderBlock( $blockAttributes ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			if ( ! aioseo()->license->hasAddonFeature( 'aioseo-eeat', $this->requiredFeature ) ) {
				return '';
			}

			$this->isRenderingBlock = true;

			ob_start();

			@$this->render( true, $blockAttributes );

			$buffer = ob_get_clean();

			return $buffer;
		}
	}
}