<?php
class WDCL_ImageCarousel extends Divi_Carousel_Free_Builder_Module
{

    public $slug       = 'wdcl_image_carousel';
    public $vb_support = 'on';
    public $child_slug = 'wdcl_image_carousel_child';

    public function init()
    {

        $this->name      = esc_html__('Image Carousel', 'divi-carousel-free');

        $this->settings_modal_toggles = [
            'general'  => [
                'toggles' => [
                    'settings' => [
                        'title'             => esc_html__('Carousel Settings', 'divi-carousel-free'),
                        'tabbed_subtoggles' => true,
                        'sub_toggles'       => [
                            'general'  => [
                                'name' => esc_html__('General', 'divi-carousel-free'),
                            ],
                            'advanced' => [
                                'name' => esc_html__('Advanced', 'divi-carousel-free'),
                            ],
                        ],
                    ],
                ],
            ],

            'advanced' => [
                'toggles' => [
                    'carousel'   => [
                        'title' => esc_html__('Carousel', 'divi-carousel-free'),
                    ],
                    'arrow'      => [
                        'title'             => esc_html__('Navigation', 'divi-carousel-free'),
                        'tabbed_subtoggles' => true,
                        'sub_toggles'       => [
                            'arrow_common' => [
                                'name' => esc_html__('Common', 'divi-carousel-free'),
                            ],
                            'arrow_left'   => [
                                'name' => esc_html__('Left', 'divi-carousel-free'),
                            ],
                            'arrow_right'  => [
                                'name' => esc_html__('Right', 'divi-carousel-free'),
                            ],
                        ],
                    ],
                    'pagination' => [
                        'title'             => esc_html__('Pagination', 'divi-carousel-free'),
                        'tabbed_subtoggles' => true,
                        'sub_toggles'       => [
                            'pagi_common' => [
                                'name' => esc_html__('Common', 'divi-carousel-free'),
                            ],
                            'pagi_active' => [
                                'name' => esc_html__('Active', 'divi-carousel-free'),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function get_fields()
    {
        return Divi_Carousel_Free_Builder_Module::_get_carousel_option_fields('carousel', ['lightbox']);
    }

    public function get_advanced_fields_config()
    {
        $advanced_fields = [];

        return $advanced_fields;
    }

    public function render($attrs, $content, $render_slug)
    {

        $classes          = [];
        $content          = $this->props['content'];
        $is_center        = $this->props['is_center'];
        $center_mode_type = $this->props['center_mode_type'];
        $custom_cursor    = $this->props['custom_cursor'];
        $use_lightbox     = $this->props['use_lightbox'] === 'on' ? 'enabled' : 'disabled';
        $sliding_dir      = $this->props['sliding_dir'];

        $this->apply_css($render_slug);

        array_push($classes, "dcm-lightbox-{$use_lightbox}");

        if ('on' === $is_center) {
            array_push($classes, 'dcm-centered');
            array_push($classes, "dcm-centered--{$center_mode_type}");
        }

        if ('on' === $custom_cursor) {
            array_push($classes, 'dcm-cursor');
        }

        $output = sprintf(
            '<div dir="%4$s" class="dcm-container dcm-carousel-maker %3$s" %2$s>
                %1$s
            </div>',
            $content,
            $this->get_carousel_options_data(),
            join(' ', $classes),
            $sliding_dir
        );

        return $output;
    }

    public function apply_css($render_slug)
    {
        $this->get_carousel_css($render_slug);
    }
}

new WDCL_ImageCarousel();
