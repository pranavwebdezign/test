<?php

class WDCL_LogoCarousel extends Divi_Carousel_Free_Builder_Module
{

    public function init()
    {

        $this->name       = esc_html__('Logo Carousel', 'divi-carousel-free');
        $this->slug       = 'wdcl_logo_carousel';
        $this->vb_support = 'on';
        $this->child_slug = 'wdcl_logo_carousel_child';

        $this->settings_modal_toggles = [
            'general'  => [
                'toggles' => [
                    'settings'      => [
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
                    'logo_settings' => [
                        'title' => esc_html__('Logo Settings', 'divi-carousel-free'),
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

        $carousel_options = Divi_Carousel_Free_Builder_Module::_get_carousel_option_fields('carousel', []);

        $logo_options = [

            'logo_hover'  => [
                'label'       => esc_html__('Image Hover Animation', 'divi-carousel-free'),
                'type'        => 'select',
                'toggle_slug' => 'logo_settings',
                'default'     => 'zoom_in',
                'options'     => [
                    'no_hover'      => esc_html__('None', 'divi-carousel-free'),
                    'zoom_in'       => esc_html__('Zoom In', 'divi-carousel-free'),
                    'zoom_out'      => esc_html__('Zoom Out', 'divi-carousel-free'),
                    'fade'          => esc_html__('Fade', 'divi-carousel-free'),
                    'black_n_white' => esc_html__('Black and White', 'divi-carousel-free'),
                ],
            ],

            'logo_height' => [
                'label'           => esc_html__('Height', 'divi-carousel-free'),
                'type'            => 'range',
                'option_category' => 'basic_option',
                'default'         => 'auto',
                'default_unit'    => 'px',
                'range_settings'  => [
                    'step' => 1,
                    'min'  => 1,
                    'max'  => 1000,
                ],
                'toggle_slug'     => 'logo_settings',
                'mobile_options'  => true,
            ],

            'logo_width'  => [
                'label'           => esc_html__('Width', 'divi-carousel-free'),
                'type'            => 'range',
                'option_category' => 'basic_option',
                'default'         => 'auto',
                'default_unit'    => 'px',
                'range_settings'  => [
                    'step' => 1,
                    'min'  => 1,
                    'max'  => 1000,
                ],
                'toggle_slug'     => 'logo_settings',
                'mobile_options'  => true,
            ],
        ];

        return array_merge($carousel_options, $logo_options);
    }

    public function get_advanced_fields_config()
    {

        $advanced_fields = [];

        $advanced_fields['text']         = [];
        $advanced_fields['borders']      = [];
        $advanced_fields['text_shadow']  = [];
        $advanced_fields['link_options'] = [];
        $advanced_fields['fonts']        = [];

        return $advanced_fields;
    }

    public function render($attrs, $content, $render_slug)
    {

        // Props
        $content          = $this->props['content'];
        $logo_hover       = $this->props['logo_hover'];
        $is_center        = $this->props['is_center'];
        $center_mode_type = $this->props['center_mode_type'];
        $custom_cursor    = $this->props['custom_cursor'];
        $sliding_dir      = $this->props['sliding_dir'];

        // Render CSS
        $this->_render_css($render_slug);

        $classes = [];

        array_push($classes, $logo_hover);

        if ($is_center === 'on') {
            array_push($classes, 'dcm-centered');
            array_push($classes, "dcm-centered--{$center_mode_type}");
        }

        if ($custom_cursor === 'on') {
            array_push($classes, 'dcm-cursor');
        }

        $output = sprintf(
            '<div dir="%4$s" class="dcm-container dcm-logo-carousel %3$s" %2$s >
                %1$s
            </div>',
            $content,
            $this->get_carousel_options_data(),
            join(' ', $classes),
            $sliding_dir
        );

        return $output;
    }

    public function _render_logo_css($render_slug)
    {

        $logo_height                   = $this->props['logo_height'];
        $logo_height_tablet            = $this->props['logo_height_tablet'];
        $logo_height_phone             = $this->props['logo_height_phone'];
        $logo_height_last_edited       = $this->props['logo_height_last_edited'];
        $logo_height_responsive_status = et_pb_get_responsive_status($logo_height_last_edited);

        $logo_width                   = $this->props['logo_width'];
        $logo_width_tablet            = $this->props['logo_width_tablet'];
        $logo_width_phone             = $this->props['logo_width_phone'];
        $logo_width_last_edited       = $this->props['logo_width_last_edited'];
        $logo_width_responsive_status = et_pb_get_responsive_status($logo_width_last_edited);

        if ($logo_height !== 'auto') {

            \ET_Builder_Element::set_style(
                $render_slug,
                [
                    'selector'    => '%%order_class%% .dcm-logo-carousel-item',
                    'declaration' => sprintf('height: %1$s;display: flex; justify-content: center; align-items: center;', $logo_height),
                ]
            );

            if ($logo_height_tablet && $logo_height_responsive_status) {
                \ET_Builder_Element::set_style(
                    $render_slug,
                    [
                        'selector'    => '%%order_class%% .dcm-logo-carousel-item',
                        'declaration' => sprintf('height: %1$s;display: flex; justify-content: center; align-items: center; ', $logo_height_tablet),
                        'media_query' => ET_Builder_Element::get_media_query('max_width_980'),
                    ]
                );
            }

            if ($logo_height_phone && $logo_height_responsive_status) {
                \ET_Builder_Element::set_style(
                    $render_slug,
                    [
                        'selector'    => '%%order_class%% .dcm-logo-carousel-item',
                        'declaration' => sprintf('height: %1$s; display: flex; justify-content: center; align-items: center;`', $logo_height_phone),
                        'media_query' => ET_Builder_Element::get_media_query('max_width_767'),
                    ]
                );
            }
        }

        if ($logo_width !== 'auto') {

            \ET_Builder_Element::set_style(
                $render_slug,
                [
                    'selector'    => '%%order_class%% .dcm-logo-carousel-item img',
                    'declaration' => sprintf('width: %1$s;', $logo_width),
                ]
            );

            if ($logo_width_tablet && $logo_width_responsive_status) {
                \ET_Builder_Element::set_style(
                    $render_slug,
                    [
                        'selector'    => '%%order_class%% .dcm-logo-carousel-item img',
                        'declaration' => sprintf('width: %1$s;', $logo_width_tablet),
                        'media_query' => ET_Builder_Element::get_media_query('max_width_980'),
                    ]
                );
            }

            if ($logo_width_phone && $logo_width_responsive_status) {
                \ET_Builder_Element::set_style(
                    $render_slug,
                    [
                        'selector'    => '%%order_class%% .dcm-logo-carousel-item img',
                        'declaration' => sprintf('width: %1$s;`', $logo_width_phone),
                        'media_query' => ET_Builder_Element::get_media_query('max_width_767'),
                    ]
                );
            }
        }
    }

    public function _render_css($render_slug)
    {

        // Carousel CSS
        $this->get_carousel_css($render_slug);

        // Logo carousel
        $this->_render_logo_css($render_slug);
    }
}

new WDCL_LogoCarousel();
