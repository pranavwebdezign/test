<?php

class DiviCarouselMakerChild extends Divi_Carousel_Free_Builder_Module
{

	// Basic module properties
	public $name = 'Carousel Item';
	public $slug = 'divi_carousel_maker_child';
	public $vb_support = 'on'; // Visual Builder support
	public $type = 'child';
	public $child_title_var = 'title';
	public $settings_modal_toggles;
	public $props = [];

	/**
	 * Initialize the module
	 * Sets up module name and settings modal structure
	 */
	public function init()
	{
		$this->name = esc_html__($this->name, 'divi-carousel-free');

		$this->settings_modal_toggles = [
			'general' => [
				'toggles' => [
					'content' => esc_attr__('Divi Library', 'divi-carousel-free'),
				],
			],
		];
	}

	public function get_advanced_fields_config()
	{
		return [
			'text' => false,
			'text_shadow' => false,
			'fonts' => false
		];
	}

	public function get_fields()
	{
		return [
			'title' => [
				'label' => esc_html__('Admin Label', 'divi-carousel-free'),
				'type' => 'text',
				'toggle_slug' => 'content',
			],
			'library_id' => [
				'label' => esc_html__('Divi Library', 'divi-carousel-free'),
				'type' => 'select',
				'default' => '-1',
				'options' => divi_carousel_builder_library(),
				'toggle_slug' => 'content',
				'computed_affects' => [
					'__divi_layout',
				],
			],
			'__divi_layout' => [
				'type' => 'computed',
				'computed_callback' => [self::class, 'divi_layout_callback'],
				'computed_depends_on' => [
					'library_id',
				],
			],
		];
	}

	/**
	 * Callback to render Divi layout content
	 * 
	 * @param array $args Arguments containing library_id
	 * @return string Rendered layout content
	 */
	public static function divi_layout_callback($args = [])
	{
		$args = wp_parse_args($args, []);

		ob_start();

		if (!empty($args['library_id']) && '-1' !== $args['library_id']) {
			// Clean any existing module styles
			if (method_exists('ET_Builder_Element', 'clean_internal_modules_styles')) {
				ET_Builder_Element::clean_internal_modules_styles();
			}

			// Generate and render layout shortcode
			$shortcode = sprintf(
				'[et_pb_section global_module="%1$s"][/et_pb_section]',
				esc_attr($args['library_id'])
			);
			echo do_shortcode($shortcode);

			// Handle layout styles
			if (method_exists('ET_Builder_Element', 'get_style')) {
				$internal_style = ET_Builder_Element::get_style();
				ET_Builder_Element::clean_internal_modules_styles(false);

				if ($internal_style) {
					printf(
						'<style type="text/css" class="dcm-carousel-maker-%2$s">%1$s</style>',
						wp_kses_post($internal_style),
						esc_attr($args['library_id'])
					);
				}
			}
		} else {
			echo '<p>' . esc_html__('No Content available', 'divi-carousel-free') . '</p>';
		}

		return ob_get_clean();
	}

	/**
	 * Render the carousel item
	 * 
	 * @param array $attrs Module attributes
	 * @param string $content Module content
	 * @param string $render_slug Module render slug
	 * @return string Rendered HTML
	 */
	public function render($attrs, $content, $render_slug)
	{
		$library_id = isset($this->props['library_id']) ? $this->props['library_id'] : '';

		// Create shortcode for the library layout
		$shortcode = sprintf(
			'[et_pb_section global_module="%s"][/et_pb_section]',
			esc_attr($library_id)
		);

		// Update module classes
		if (method_exists($this, 'remove_classname')) {
			$this->remove_classname('et_pb_module');
		}

		if (method_exists($this, 'add_classname')) {
			$this->add_classname('wdc_et_pb_module');
		}

		// Return wrapped carousel item
		return sprintf(
			'<div class="dcm-libary-slider dcm-child-content-%2$s">%1$s</div>',
			do_shortcode($shortcode),
			esc_attr($library_id)
		);
	}
}

new DiviCarouselMakerChild();
