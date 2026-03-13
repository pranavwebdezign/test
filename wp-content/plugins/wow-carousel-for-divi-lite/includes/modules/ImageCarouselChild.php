<?php
class WDCL_ImageCarouselChild extends Divi_Carousel_Free_Builder_Module
{

    public $slug                     = 'wdcl_image_carousel_child';
    public $vb_support               = 'on';
    public $type                     = 'child';
    public $child_title_var          = 'admin_title';
    public $child_title_fallback_var = 'title';

    public function init()
    {

        $this->name = esc_html__('Carousel Item', 'divi-carousel-free');

        $this->settings_modal_toggles = [
            'general'  => [
                'toggles' => [
                    'main_content' => [
                        'title' => esc_html__('Content', 'divi-carousel-free'),
                    ],
                ],
            ],

            'advanced' => [
                'toggles' => [
                    'image'   => [
                        'title' => esc_html__('Image', 'divi-carousel-free'),
                    ],
                    'overlay' => [
                        'title' => esc_html__('Overlay', 'divi-carousel-free'),
                    ],
                    'content' => [
                        'title' => esc_html__('Content', 'divi-carousel-free'),
                    ],
                    'texts'   => [
                        'title'             => esc_html__('Texts', 'divi-carousel-free'),
                        'tabbed_subtoggles' => true,
                        'sub_toggles'       => [
                            'title_tab'    => [
                                'name' => esc_html__('Title', 'divi-carousel-free'),
                            ],
                            'subtitle_tab' => [
                                'name' => esc_html__('Subtitle', 'divi-carousel-free'),
                            ],
                        ],
                    ],
                    'borders' => [
                        'title' => esc_html__('Border', 'divi-carousel-free'),
                    ],
                ],
            ],
        ];
    }

    public function get_fields()
    {

        $fields = [

            'photo'                 => [
                'label'              => esc_html__('Upload Image', 'divi-carousel-free'),
                'type'               => 'upload',
                'option_category'    => 'basic_option',
                'toggle_slug'        => 'main_content',
                'upload_button_text' => esc_attr__('Upload an image', 'divi-carousel-free'),
                'choose_text'        => esc_attr__('Choose an Image', 'divi-carousel-free'),
                'update_text'        => esc_attr__('Set As Image', 'divi-carousel-free'),
            ],

            'photo_alt'                 => [
                'label'           => esc_html__('Image Alt Text', 'divi-carousel-free'),
                'type'            => 'text',
                'toggle_slug'     => 'main_content',
            ],

            'title'                 => [
                'label'           => esc_html__('Title', 'divi-carousel-free'),
                'type'            => 'text',
                'toggle_slug'     => 'main_content',
                'dynamic_content' => 'text',
            ],

            'sub_title'             => [
                'label'           => esc_html__('Subtitle', 'divi-carousel-free'),
                'type'            => 'text',
                'toggle_slug'     => 'main_content',
                'dynamic_content' => 'text',
            ],

            'content_alignment'     => [
                'label'            => esc_html__('Content Text Alignment', 'divi-carousel-free'),
                'type'             => 'text_align',
                'option_category'  => 'layout',
                'options'          => et_builder_get_text_orientation_options(['justified']),
                'options_icon'     => 'module_align',
                'default_on_front' => 'left',
                'toggle_slug'      => 'content',
                'tab_slug'         => 'advanced',
            ],

            'content_width'         => [
                'label'           => esc_html__('Content Width', 'divi-carousel-free'),
                'type'            => 'range',
                'option_category' => 'basic_option',
                'default'         => '100%',
                'range_settings'  => [
                    'step' => 1,
                    'min'  => 0,
                    'max'  => 100,
                ],
                'tab_slug'        => 'advanced',
                'toggle_slug'     => 'content',
            ],

            'content_type'          => [
                'label'       => esc_html__('Content Type', 'divi-carousel-free'),
                'type'        => 'select',
                'toggle_slug' => 'content',
                'tab_slug'    => 'advanced',
                'default'     => 'absolute',
                'options'     => [
                    'normal'   => esc_html__('Normal', 'divi-carousel-free'),
                    'absolute' => esc_html__('Absolute', 'divi-carousel-free'),
                ],
            ],

            'content_pos_x'         => [
                'label'       => esc_html__('Content Horizontal Position', 'divi-carousel-free'),
                'type'        => 'select',
                'toggle_slug' => 'content',
                'tab_slug'    => 'advanced',
                'default'     => 'center',
                'options'     => [
                    'center'     => esc_html__('Center', 'divi-carousel-free'),
                    'flex-start' => esc_html__('Left', 'divi-carousel-free'),
                    'flex-end'   => esc_html__('Right', 'divi-carousel-free'),
                ],
                'show_if'     => [
                    'content_type' => 'absolute',
                ],
            ],

            'content_pos_y'         => [
                'label'       => esc_html__('Content Vertical Position', 'divi-carousel-free'),
                'type'        => 'select',
                'toggle_slug' => 'content',
                'tab_slug'    => 'advanced',
                'default'     => 'center',
                'options'     => [
                    'center'     => esc_html__('Center', 'divi-carousel-free'),
                    'flex-start' => esc_html__('Top', 'divi-carousel-free'),
                    'flex-end'   => esc_html__('Bottom', 'divi-carousel-free'),
                ],
                'show_if'     => [
                    'content_type' => 'absolute',
                ],
            ],

            'content_padding'       => [
                'label'          => esc_html__('Content Padding', 'divi-carousel-free'),
                'type'           => 'custom_padding',
                'tab_slug'       => 'advanced',
                'toggle_slug'    => 'content',
                'mobile_options' => true,
            ],

            // Image.
            'image_height'          => [
                'label'           => esc_html__('Image Height', 'divi-carousel-free'),
                'type'            => 'range',
                'option_category' => 'basic_option',
                'allowed_units'   => ['em', 'rem', 'px', 'cm', '%', 'mm', 'in', 'pt', 'pc', 'ex', 'vh', 'vw'],
                'default_unit'    => 'px',
                'default'         => 'auto',
                'range_settings'  => [
                    'min'  => 0,
                    'step' => 1,
                    'max'  => 1000,
                ],
                'toggle_slug'     => 'image',
                'tab_slug'        => 'advanced',
            ],

            'image_hover_animation' => [
                'label'       => esc_html__('Image Hover Animation', 'divi-carousel-free'),
                'type'        => 'select',
                'tab_slug'    => 'advanced',
                'toggle_slug' => 'image',
                'default'     => 'none',
                'options'     => [
                    'none'        => esc_html__('None', 'divi-carousel-free'),
                    'zoom-in'     => esc_html__('Zoom In', 'divi-carousel-free'),
                    'zoom-out'    => esc_html__('Zoom Out', 'divi-carousel-free'),
                    'pulse'       => esc_html__('Pulse', 'divi-carousel-free'),
                    'bounce'      => esc_html__('Bounce', 'divi-carousel-free'),
                    'flash'       => esc_html__('Flash', 'divi-carousel-free'),
                    'rubberBand'  => esc_html__('Rubber Band', 'divi-carousel-free'),
                    'shake'       => esc_html__('Shake', 'divi-carousel-free'),
                    'swing'       => esc_html__('Swing', 'divi-carousel-free'),
                    'tada'        => esc_html__('Tada', 'divi-carousel-free'),
                    'wobble'      => esc_html__('Wobble', 'divi-carousel-free'),
                    'jello'       => esc_html__('Jello', 'divi-carousel-free'),
                    'heartBeat'   => esc_html__('Heart Beat', 'divi-carousel-free'),
                    'bounceIn'    => esc_html__('Bounce In', 'divi-carousel-free'),
                    'fadeIn'      => esc_html__('Fade In', 'divi-carousel-free'),
                    'flip'        => esc_html__('Flip', 'divi-carousel-free'),
                    'liteSpeedIn' => esc_html__('Light Speed In', 'divi-carousel-free'),
                    'rotateIn'    => esc_html__('Rotate In', 'divi-carousel-free'),
                    'slideInUp'   => esc_html__('Slide In Up', 'divi-carousel-free'),
                    'slideInDown' => esc_html__('Slide In Down', 'divi-carousel-free'),
                ],
            ],

            // Text.
            'title_bottom_spacing'  => [
                'label'           => esc_html__('Title Spacing Bottom', 'divi-carousel-free'),
                'type'            => 'range',
                'default'         => '5px',
                'option_category' => 'basic_option',
                'allowed_units'   => ['px'],
                'default_unit'    => 'px',
                'range_settings'  => [
                    'min'  => 0,
                    'step' => 1,
                    'max'  => 100,
                ],
                'toggle_slug'     => 'texts',
                'tab_slug'        => 'advanced',
                'sub_toggle'      => 'title_tab',
            ],

        ];

        $label = [
            'admin_title' => [
                'label'       => esc_html__('Admin Label', 'divi-carousel-free'),
                'type'        => 'text',
                'description' => esc_html__('This will change the label of the item', 'divi-carousel-free'),
                'toggle_slug' => 'admin_label',
            ],
        ];

        $content = $this->_custom_advanced_background_fields('content', 'Content', 'advanced', 'content', ['color', 'gradient']);
        $overlay = $this->_get_overlay_option_fields('overlay', []);

        return array_merge($label, $fields, $content, $overlay);
    }

    public function get_advanced_fields_config()
    {

        $advanced_fields                = [];
        $advanced_fields['text']        = [];
        $advanced_fields['text_shadow'] = [];
        $advanced_fields['max_width']   = [];
        $advanced_fields['fonts']       = [];
        $advanced_fields['borders']     = [];

        $advanced_fields['fonts']['title'] = [
            'label'           => esc_html__('Title', 'divi-carousel-free'),
            'css'             => [
                'main'      => '%%order_class%% .dcm-image-title, .et-db #et-boc %%order_class%% .dcm-image-title',
                'important' => 'all',
            ],
            'tab_slug'        => 'advanced',
            'toggle_slug'     => 'texts',
            'sub_toggle'      => 'title_tab',
            'hide_text_align' => true,
            'line_height'     => [
                'range_settings' => [
                    'min'  => '1',
                    'max'  => '3',
                    'step' => '.1',
                ],
            ],
            'header_level'    => [
                'default' => 'h3',
            ],
        ];

        $advanced_fields['fonts']['subtitle'] = [
            'label'           => esc_html__('Subtitle', 'divi-carousel-free'),
            'css'             => [
                'main'      => '%%order_class%% .dcm-image-subtitle, .et-db #et-boc %%order_class%% .dcm-image-subtitle',
                'important' => 'all',
            ],
            'tab_slug'        => 'advanced',
            'toggle_slug'     => 'texts',
            'sub_toggle'      => 'subtitle_tab',
            'hide_text_align' => true,
            'line_height'     => [
                'range_settings' => [
                    'min'  => '1',
                    'max'  => '3',
                    'step' => '.1',
                ],
            ],
            'header_level'    => [
                'default' => 'h5',
            ],
        ];

        $advanced_fields['borders']['item'] = [
            'label_prefix' => esc_html__('Item', 'divi-carousel-free'),
            'css'          => [
                'main'      => '%%order_class%%',
                'important' => 'all',
            ],
            'tab_slug'     => 'advanced',
            'toggle_slug'  => 'borders',
        ];

        return $advanced_fields;
    }

    public function _render_figure()
    {
        $photo = esc_url($this->props['photo']);

        $processed_overlay_icon = esc_attr(et_pb_process_font_icon($this->props['overlay_icon']));
        $overlay_icon = !empty($processed_overlay_icon) ? esc_attr($processed_overlay_icon) : '';

        return sprintf(
            '<figure class="dcm-lightbox-ctrl">
				<div class="dcm-overlay" data-icon="%2$s"></div>
				<img class="dcm-main-img" data-mfp-src="%1$s" src="%1$s" alt="%3$s"/>
            </figure>',
            esc_url($photo),
            esc_attr($overlay_icon),
            esc_attr($this->props['photo_alt'])
        );
    }

    public function _render_title()
    {

        $title_text            = $this->props['title'];
        $title_level           = $this->props['title_level'];
        $processed_title_level = et_pb_process_header_level($title_level, 'h3');
        $processed_title_level = esc_html($processed_title_level);

        if (!empty($title_text)) {
            return sprintf('<%2$s class="dcm-image-title">%1$s</%2$s>', $title_text, $processed_title_level);
        }
    }

    public function _render_subTitle()
    {

        $sub_title                = $this->props['sub_title'];
        $subtitle_level           = $this->props['subtitle_level'];
        $processed_subtitle_level = et_pb_process_header_level($subtitle_level, 'h5');
        $processed_subtitle_level = esc_html($processed_subtitle_level);

        if (!empty($sub_title)) {
            return sprintf('<%2$s class="dcm-image-subtitle">%1$s</%2$s>', $sub_title, $processed_subtitle_level);
        }
    }

    public function _render_content()
    {

        if (empty($this->props['title']) && empty($this->props['sub_title'])) {
            return;
        }

        $content_type = $this->props['content_type'];

        if (empty($absolute)) {
            $content_type === 'absolute';
        }

        return sprintf(
            '<div class="content content--%3$s content--%4$s"><div class="content-inner"> %1$s %2$s </div></div>',
            $this->_render_title(),
            $this->_render_subTitle(),
            $this->props['content_alignment'],
            $content_type
        );
    }

    public function render($attrs, $content, $render_slug)
    {
        $content_pos_x                      = $this->props['content_pos_x'];
        $content_pos_y                      = $this->props['content_pos_y'];
        $content_type                       = $this->props['content_type'];
        $content_width                      = $this->props['content_width'];
        $title_bottom_spacing               = $this->props['title_bottom_spacing'];
        $image_hover_animation              = $this->props['image_hover_animation'];
        $image_height                       = $this->props['image_height'];
        $content_padding                    = $this->props['content_padding'];
        $content_padding_tablet             = $this->props['content_padding_tablet'];
        $content_padding_phone              = $this->props['content_padding_phone'];
        $content_padding_last_edited        = $this->props['content_padding_last_edited'];
        $content_padding_responsive_status  = et_pb_get_responsive_status($content_padding_last_edited);

        if ($content_type === 'absolute') {

            if (empty($content_padding)) {
                $content_padding = '10px|20px|10px|20px';
            }
        } else {

            if (empty($content_padding)) {
                $content_padding = '20px|0|0|0';
            }
        }

        // image Height
        if ($image_height !== 'auto') {

            ET_Builder_Element::set_style(
                $render_slug,
                [
                    'selector'    => '%%order_class%% .dcm-carousel-item figure',
                    'declaration' => sprintf('height: %1$s;', $image_height),
                ]
            );

            ET_Builder_Element::set_style(
                $render_slug,
                [
                    'selector'    => '%%order_class%% .dcm-carousel-item figure img',
                    'declaration' => 'height: 100%; object-fit: cover; width:100%;',
                ]
            );
        }

        // Texts
        ET_Builder_Element::set_style(
            $render_slug,
            [
                'selector'    => '%%order_class%% .dcm-carousel-item h3, .et-db #et-boc %%order_class%% .dcm-carousel-item h3',
                'declaration' => sprintf('padding-bottom: %1$s;', $title_bottom_spacing),
            ]
        );

        // Content
        if ($content_type === 'absolute') {
            ET_Builder_Element::set_style(
                $render_slug,
                [
                    'selector'    => '%%order_class%% .content--absolute',
                    'declaration' => sprintf(
                        'align-items: %1$s; justify-content: %2$s;',
                        $content_pos_x,
                        $content_pos_y
                    ),
                ]
            );
        }

        ET_Builder_Element::set_style(
            $render_slug,
            [
                'selector'    => '%%order_class%% .dcm-carousel-item .content .content-inner',
                'declaration' => sprintf(
                    'width: %1$s; %2$s',
                    $content_width,
                    Divi_Carousel_Free_Builder_Module::_process_padding($content_padding, false)
                ),
            ]
        );

        if ($content_padding_tablet && $content_padding_responsive_status) :

            ET_Builder_Element::set_style(
                $render_slug,
                [
                    'selector'    => '%%order_class%% .dcm-carousel-item .content .content-inner',
                    'media_query' => ET_Builder_Element::get_media_query('max_width_980'),
                    'declaration' => Divi_Carousel_Free_Builder_Module::_process_padding($content_padding_tablet, false),
                ]
            );
        endif;

        if ($content_padding_phone && $content_padding_responsive_status) :

            ET_Builder_Element::set_style(
                $render_slug,
                [
                    'selector'    => '%%order_class%% .dcm-carousel-item .content .content-inner',
                    'media_query' => ET_Builder_Element::get_media_query('max_width_767'),
                    'declaration' => Divi_Carousel_Free_Builder_Module::_process_padding($content_padding_phone, false),
                ]
            );
        endif;

        // Content background
        $this->_get_custom_bg_style($render_slug, 'content', '%%order_class%% .dcm-carousel-item .content .content-inner', '%%order_class%% .dcm-carousel-item .content .content-inner:hover');

        // Overlay Styles
        $this->_get_overlay_style($render_slug);

        // Module classnames
        $this->remove_classname('et_pb_module');
        $this->add_classname('wdc_et_pb_module');

        return sprintf(
            '<div class="dcm-carousel-item dcm-image-swap dcm-hover--%3$s">
				%1$s %2$s
			</div>',
            $this->_render_figure(),
            $this->_render_content(),
            $image_hover_animation
        );
    }
}

new WDCL_ImageCarouselChild();
