<?php
defined( 'ABSPATH' ) || exit;

class WBP_Frontend {

    const TEMPLATE_SLUG = 'wbp-services-template';

    public static function init(): void {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        add_filter( 'theme_page_templates', [ __CLASS__, 'register_template' ] );
        add_filter( 'template_include',     [ __CLASS__, 'load_template' ] );
        add_shortcode( 'woo_booking',       [ __CLASS__, 'shortcode' ] );
        add_action( 'vc_before_init',       [ __CLASS__, 'vc_register' ] );
        add_action( 'init',                 [ __CLASS__, 'register_gutenberg_block' ] );
    }

    // ── Script enqueue ────────────────────────────────────────────────
    public static function enqueue( $config_id = 0 ): void {
        $config_id = absint( $config_id );
        if ( $config_id === 0 && ! self::is_booking_page() ) return;

        $api_key      = WBP_Settings::get( 'google_maps_api_key', '' );
        $restrictions = WBP_Settings::get_maps_restrictions();

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style(  'jquery-ui-style',   'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css', [], '1.13.2' );
        wp_enqueue_style(  'flatpickr',         'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css', [], '4.6.13' );
        wp_enqueue_script( 'flatpickr',         'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js', ['jquery'], '4.6.13', true );
        wp_enqueue_style(  'font-awesome-5',    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4' );
        wp_enqueue_style(  'wbp-booking',       WBP_PLUGIN_URL . 'assets/css/booking-frontend.css', [], WBP_VERSION );

        // Dynamic CSS from admin color settings
        add_action( 'wp_head', [ __CLASS__, 'output_dynamic_css' ], 99 );

        if ( $api_key ) {
            // Use stable Google Maps Places API with callback so JS knows when it's ready
            wp_enqueue_script( 'google-maps-api',
                'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key)
                . '&libraries=places&callback=wbpInitAutocomplete',
                [], null, true );
        }

        wp_enqueue_script( 'wbp-booking', WBP_PLUGIN_URL . 'assets/js/booking-frontend.js',
            ['jquery','jquery-ui-datepicker','flatpickr'], WBP_VERSION, true );

        // Zones: config-specific or global
        $zones     = WBP_Config_CPT::get_zones_for( $config_id );
        $settings  = WBP_Settings::all();
        $countries = WBP_Settings::get_enabled_countries();

        $countries_js = [];
        foreach ( $countries as $iso => $c ) {
            $countries_js[$iso] = [
                'iso2'         => $c['iso2']         ?? $iso,
                'name'         => $c['name']         ?? $iso,
                'flag'         => $c['flag']         ?? '',
                'maps_country' => $c['maps_country'] ?? strtolower($iso),
                'format_hint'  => $c['format_hint']  ?? '',
                'regex'        => $c['regex']        ?? '/^.+$/',
                'prefix_length'=> intval($c['prefix_length'] ?? 4),
                'currency'     => $c['currency']     ?? $settings['currency_symbol'],
            ];
        }

        // Only localize basic settings here. Full zone data is injected inline by the shortcode.
        // This prevents wp_localize_script (which runs in wp_footer) from overwriting the
        // inline <script> that the shortcode already injected with the correct config zones.
        wp_localize_script( 'wbp-booking', 'wbp_data_base', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ] );
        wp_localize_script( 'wbp-booking', 'ajax_object', [ 'ajaxurl' => admin_url('admin-ajax.php') ] );
    }

    public static function output_dynamic_css(): void {
        $s      = WBP_Settings::all();
        $primary       = $s['color_primary']      ?? '#2c7be5';
        $primary_text  = $s['color_primary_text'] ?? '#ffffff';
        $card_sel      = $s['color_card_selected']?? '#d4edda';
        $card_border   = $s['color_card_border']  ?? $primary;
        $step_bg       = $s['color_step_bg']      ?? $primary;
        $addon_check   = $s['color_addon_check']  ?? $primary;
        $radius        = absint($s['border_radius'] ?? 12) . 'px';
        $custom_css    = $s['custom_css'] ?? '';
        ?>
        <style id="wbp-dynamic-css">
        /* WooBooking Pro – Dynamic Styles */
        .wbp-btn, .wbp-btn-next, #wbp-add-to-cart {
            background-color: <?php echo esc_attr($primary); ?> !important;
            color: <?php echo esc_attr($primary_text); ?> !important;
        }
        .wbp-btn:hover { filter: brightness(1.1); }
        .wbp-step-num, .wbp-step-indicator .wbp-step-icon {
            background-color: <?php echo esc_attr($step_bg); ?> !important;
            color: <?php echo esc_attr($primary_text); ?> !important;
        }
        /* Card options – services, sub-categories, products */
        .wbp-card-option { border-radius: <?php echo esc_attr($radius); ?>; }
        .wbp-card-option input[type="radio"]:checked + label,
        .wbp-card-option input[type="checkbox"]:checked + label {
            background-color: <?php echo esc_attr($card_sel); ?> !important;
            border-color: <?php echo esc_attr($card_border); ?> !important;
        }
        .wbp-card-option label { border-radius: <?php echo esc_attr($radius); ?>; }
        .wbp-card-option label:hover { border-color: <?php echo esc_attr($primary); ?>; }
        /* Add-on checkboxes/radios */
        .wbp-addon-input:checked + .wbp-addon-label { border-color: <?php echo esc_attr($addon_check); ?>; background: <?php echo esc_attr($card_sel); ?>; }
        .wbp-addon-label { border-radius: <?php echo esc_attr($radius); ?>; }
        .wbp-addon-input:checked + .wbp-addon-label .wbp-addon-check {
            background: <?php echo esc_attr($addon_check); ?>;
            border-color: <?php echo esc_attr($addon_check); ?>;
        }
        .wbp-addon-price { color: <?php echo esc_attr($primary); ?>; }
        /* Info bar */
        .wbp-delivery-info-bar { border-left-color: <?php echo esc_attr($primary); ?>; }
        .wbp-country-info-bar  { border-color: <?php echo esc_attr($primary); ?>; background: <?php echo esc_attr($primary); ?>18; }
        <?php if ($custom_css): echo esc_html($custom_css); endif; ?>
        </style>
        <?php
    }

    public static function is_booking_page(): bool {
        global $post;
        if ( ! $post ) return false;
        if ( is_a($post,'WP_Post') && has_shortcode($post->post_content,'woo_booking') ) return true;
        $tpl = get_post_meta($post->ID,'_wp_page_template',true);
        if ( $tpl && str_contains($tpl,self::TEMPLATE_SLUG) ) return true;
        return false;
    }

    public static function register_template( array $tpls ): array {
        $tpls[self::TEMPLATE_SLUG.'.php'] = __('WooBooking – Services','woo-booking-pro');
        return $tpls;
    }

    public static function load_template( string $tpl ): string {
        global $post;
        if (!$post) return $tpl;
        if (get_post_meta($post->ID,'_wp_page_template',true) !== self::TEMPLATE_SLUG.'.php') return $tpl;
        $p = WBP_PLUGIN_DIR.'templates/'.self::TEMPLATE_SLUG.'.php';
        return file_exists($p) ? $p : $tpl;
    }

    // ── Shortcode [woo_booking] or [woo_booking id="33"] ─────────────
    public static function shortcode( $atts ): string {
        $atts      = shortcode_atts( ['id'=>0], (array)$atts, 'woo_booking' );
        $config_id = absint( $atts['id'] );

        // Ensure base assets are loaded
        if ( ! wp_script_is( 'wbp-booking', 'enqueued' ) ) {
            self::enqueue( $config_id );
        }

        // Always inject the correct zones for this config as an inline script.
        // This runs AFTER wp_head, so it overrides whatever wp_localize_script set earlier.
        $zones     = WBP_Config_CPT::get_zones_for( $config_id );
        $settings  = WBP_Settings::all();
        $countries = WBP_Settings::get_enabled_countries();

        $countries_js = [];
        foreach ( $countries as $iso => $c ) {
            $countries_js[$iso] = [
                'iso2'          => $c['iso2']          ?? $iso,
                'name'          => $c['name']          ?? $iso,
                'flag'          => $c['flag']          ?? '',
                'maps_country'  => $c['maps_country']  ?? strtolower($iso),
                'format_hint'   => $c['format_hint']   ?? '',
                'regex'         => $c['regex']         ?? '/^.+$/',
                'prefix_length' => intval( $c['prefix_length'] ?? 4 ),
                'currency'      => $c['currency']      ?? $settings['currency_symbol'],
            ];
        }

        $js_var  = $config_id ? 'wbp_data_' . $config_id : 'wbp_data';
        $payload = wp_json_encode( [
            'ajaxurl'          => admin_url( 'admin-ajax.php' ),
            'config_id'        => $config_id,
            'currency'         => $settings['currency_symbol'],
            'zones'            => $zones,
            'countries'        => $countries_js,
            'maps_restrictions'=> WBP_Settings::get_maps_restrictions(),
            'multi_country'    => (bool)( $settings['multi_country_mode'] ?? false ),
            'default_country'  => $settings['default_country'] ?? 'GB',
            'terms_url'        => $settings['terms_url'],
            'privacy_url'      => $settings['privacy_url'],
            'min_order'        => floatval( $settings['min_order_amount'] ?? 0 ),
            'notes_title'      => $settings['order_notes_title'],
            'notes_ph'         => $settings['order_notes_placeholder'],
        ] );

        // Also keep wbp_data in sync so the JS fallback path works too
        $inline  = '<script>';
        $inline .= 'window.' . esc_js( $js_var ) . ' = ' . $payload . ';';
        if ( $config_id ) {
            $inline .= 'window.wbp_data = window.' . esc_js( $js_var ) . ';'; // make default also point here
        }
        $inline .= '</script>';

        $GLOBALS['wbp_current_config_id'] = $config_id;

        ob_start();
        echo $inline;
        include WBP_PLUGIN_DIR . 'templates/booking-form.php';
        return ob_get_clean();
    }

    public static function vc_register(): void {
        if (!function_exists('vc_map')) return;
        vc_map([
            'name'        => 'WooBooking Form',
            'base'        => 'woo_booking',
            'description' => 'Multi-step WooCommerce booking form.',
            'category'    => 'Booking',
            'icon'        => 'dashicons-calendar-alt',
            'params'      => [[
                'type'        => 'textfield',
                'heading'     => 'Config ID',
                'param_name'  => 'id',
                'description' => 'Optional. Leave blank for global zones, or enter the Booking Config post ID.',
                'value'       => '',
            ]],
        ]);
    }

    public static function register_gutenberg_block(): void {
        if (!function_exists('register_block_type')) return;
        wp_register_script('wbp-block-editor', WBP_PLUGIN_URL.'assets/js/block-editor.js',
            ['wp-blocks','wp-element','wp-editor'], WBP_VERSION);
        register_block_type('woo-booking-pro/booking-form',[
            'editor_script'   => 'wbp-block-editor',
            'render_callback' => [__CLASS__,'shortcode'],
            'attributes'      => [
                'id' => ['type'=>'string','default'=>''],
            ],
        ]);
    }
}
