<?php
defined( 'ABSPATH' ) || exit;

/**
 * WBP_Admin – admin pages:
 *   1. General Settings   (API key, currency, URLs, labels, banner)
 *   2. Country Config     (enable/disable countries, postcode format overrides)
 *   3. Postcode Zones     (zone builder with per-country filter)
 */
class WBP_Admin {

    public static function init(): void {
        add_action( 'admin_menu',            [ __CLASS__, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        // AJAX save handlers (avoids admin-post.php redirect issues from 3rd-party notices)
        add_action( 'wp_ajax_wbp_save_general',   [ __CLASS__, 'save_general' ] );
        add_action( 'wp_ajax_wbp_save_countries', [ __CLASS__, 'save_countries' ] );
        add_action( 'wp_ajax_wbp_save_zones',     [ __CLASS__, 'save_zones' ] );
    }

    public static function add_menu(): void {
        add_menu_page(
            'WooBooking Pro', 'WooBooking', 'manage_options',
            'wbp-settings', [ __CLASS__, 'page_general' ],
            'dashicons-calendar-alt', 58
        );
        add_submenu_page( 'wbp-settings', 'General Settings',  'General Settings',  'manage_options', 'wbp-settings',   [ __CLASS__, 'page_general' ] );
        add_submenu_page( 'wbp-settings', 'Country Config',     'Country Config',    'manage_options', 'wbp-countries',  [ __CLASS__, 'page_countries' ] );
        add_submenu_page( 'wbp-settings', 'Postcode Zones',     'Postcode Zones (Global)', 'manage_options', 'wbp-zones', [ __CLASS__, 'page_zones' ] );
        // Note: "Booking Configs" submenu is added automatically by the CPT (show_in_menu => 'wbp-settings')
    }

    public static function enqueue( string $hook ): void {
        // Match by query var (most reliable) OR by hook name suffix
        $page      = sanitize_key( $_GET['page'] ?? '' );
        $our_pages = [ 'wbp-settings', 'wbp-countries', 'wbp-zones' ];
        $hook_ok   = ( strpos( $hook, 'wbp-settings' ) !== false
                    || strpos( $hook, 'wbp-countries' ) !== false
                    || strpos( $hook, 'wbp-zones' ) !== false );
        if ( ! in_array( $page, $our_pages, true ) && ! $hook_ok ) return;
        wp_enqueue_media();
        wp_enqueue_style(  'wbp-admin', WBP_PLUGIN_URL . 'assets/css/admin.css',  [], WBP_VERSION );
        wp_enqueue_script( 'wbp-admin', WBP_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery', 'media-upload' ], WBP_VERSION, true );
        wp_localize_script( 'wbp-admin', 'wbp_admin', [
            'presets'  => WBP_Settings::get_all_country_presets(),
            'ajaxurl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wbp_ajax_nonce' ),
        ] );
    }

    // ──────────────────────────────────────────
    // Page 1: General Settings
    // ──────────────────────────────────────────
    public static function page_general(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $s   = WBP_Settings::all();
        $msg = isset( $_GET['wbp_saved'] ) ? '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>' : '';
        ?>
        <div class="wrap wbp-wrap">
        <h1><span class="dashicons dashicons-calendar-alt"></span> WooBooking Pro – General Settings</h1>
        <?php echo $msg; ?>
        <form method="post" id="wbp-general-form" onsubmit="return false;">
            <table class="form-table wbp-form-table">

                <tr><th colspan="2"><h2 style="margin:0">🗺️ Google Maps</h2></th></tr>
                <tr>
                    <th><label for="wbp_gmaps">Google Maps API Key</label></th>
                    <td>
                        <input type="text" id="wbp_gmaps" name="google_maps_api_key" class="regular-text" value="<?php echo esc_attr($s['google_maps_api_key']); ?>" placeholder="AIzaSy…">
                        <p class="description">Enable <strong>Maps JavaScript API</strong> + <strong>Places API</strong> in Google Cloud Console. The autocomplete will automatically restrict to your enabled countries.</p>
                    </td>
                </tr>

                <tr><th colspan="2"><h2 style="margin:0">🌍 Country Mode</h2></th></tr>
                <tr>
                    <th><label>Booking Mode</label></th>
                    <td>
                        <label style="margin-right:20px">
                            <input type="radio" name="multi_country_mode" value="0" <?php checked( empty($s['multi_country_mode']) ); ?>>
                            Single country (postcode field, no country selector)
                        </label>
                        <label>
                            <input type="radio" name="multi_country_mode" value="1" <?php checked( !empty($s['multi_country_mode']) ); ?>>
                            Multi-country (shows country dropdown before postcode)
                        </label>
                        <p class="description">Configure which countries are active under <strong>Country Config</strong>.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="wbp_def_country">Default Country</label></th>
                    <td>
                        <select id="wbp_def_country" name="default_country">
                            <?php foreach ( WBP_Settings::get_all_country_presets() as $iso => $preset ) :
                                if ( $iso === 'CUSTOM' ) continue; ?>
                                <option value="<?php echo esc_attr($iso); ?>" <?php selected( $s['default_country'] ?? 'GB', $iso ); ?>>
                                    <?php echo esc_html( $preset['flag'] . ' ' . $preset['name'] . ' (' . $iso . ')' ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Used when single-country mode is active.</p>
                    </td>
                </tr>

                <tr><th colspan="2"><h2 style="margin:0">💰 Currency & Amounts</h2></th></tr>
                <tr>
                    <th><label for="wbp_currency">Default Currency Symbol</label></th>
                    <td><input type="text" id="wbp_currency" name="currency_symbol" class="small-text" value="<?php echo esc_attr($s['currency_symbol']); ?>">
                    <p class="description">Per-country currencies can be set in Country Config.</p></td>
                </tr>
                <tr>
                    <th><label for="wbp_min_order">Minimum Order Amount</label></th>
                    <td><input type="number" id="wbp_min_order" name="min_order_amount" class="small-text" step="0.01" min="0" value="<?php echo esc_attr($s['min_order_amount']??0); ?>">
                    <p class="description">Set to 0 to disable.</p></td>
                </tr>

                <tr><th colspan="2"><h2 style="margin:0">📄 Page URLs</h2></th></tr>
                <tr>
                    <th><label for="wbp_terms">Terms &amp; Conditions URL</label></th>
                    <td><input type="text" id="wbp_terms" name="terms_url" class="regular-text" value="<?php echo esc_attr($s['terms_url']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="wbp_privacy">Privacy Policy URL</label></th>
                    <td><input type="text" id="wbp_privacy" name="privacy_url" class="regular-text" value="<?php echo esc_attr($s['privacy_url']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="wbp_return_url">"Return to Shop" URL</label></th>
                    <td><input type="text" id="wbp_return_url" name="booking_page_url" class="regular-text" value="<?php echo esc_attr(get_option('wbp_booking_page_url','/booking')); ?>"></td>
                </tr>

                <tr><th colspan="2"><h2 style="margin:0">🖼️ Banner Image</h2></th></tr>
                <tr>
                    <th>Booking Page Banner</th>
                    <td>
                        <?php $banner = $s['banner_image_url']??''; ?>
                        <img id="wbp-banner-preview" src="<?php echo esc_url($banner); ?>" style="max-width:300px;display:<?php echo $banner?'block':'none';?>;margin-bottom:8px;border-radius:4px">
                        <input type="hidden" id="wbp_banner_url" name="banner_image_url" value="<?php echo esc_attr($banner); ?>">
                        <button type="button" id="wbp-upload-banner" class="button">Upload / Select Image</button>
                        <button type="button" id="wbp-remove-banner" class="button" style="<?php echo $banner?'':'display:none'; ?>">Remove</button>
                    </td>
                </tr>

                <tr><th colspan="2"><h2 style="margin:0">🎨 Design & Colors</h2></th></tr>
                <tr>
                    <th><label for="wbp_color_primary">Primary Color</label></th>
                    <td>
                        <input type="color" id="wbp_color_primary" name="color_primary" value="<?php echo esc_attr($s['color_primary']??'#2c7be5'); ?>">
                        <span style="color:#666;font-size:12px;margin-left:8px">Buttons, step circles, active card border</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="wbp_color_primary_text">Button Text Color</label></th>
                    <td><input type="color" id="wbp_color_primary_text" name="color_primary_text" value="<?php echo esc_attr($s['color_primary_text']??'#ffffff'); ?>"></td>
                </tr>
                <tr>
                    <th><label for="wbp_color_card_selected">Selected Card Background</label></th>
                    <td>
                        <input type="color" id="wbp_color_card_selected" name="color_card_selected" value="<?php echo esc_attr($s['color_card_selected']??'#d4edda'); ?>">
                        <span style="color:#666;font-size:12px;margin-left:8px">Background when a card option is selected</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="wbp_color_card_border">Card Border Color</label></th>
                    <td><input type="color" id="wbp_color_card_border" name="color_card_border" value="<?php echo esc_attr($s['color_card_border']??'#2c7be5'); ?>"></td>
                </tr>
                <tr>
                    <th><label for="wbp_color_step_bg">Step Circle Background</label></th>
                    <td><input type="color" id="wbp_color_step_bg" name="color_step_bg" value="<?php echo esc_attr($s['color_step_bg']??'#2c7be5'); ?>"></td>
                </tr>
                <tr>
                    <th><label for="wbp_color_addon_check">Add-on Checkbox/Radio Color</label></th>
                    <td><input type="color" id="wbp_color_addon_check" name="color_addon_check" value="<?php echo esc_attr($s['color_addon_check']??'#2c7be5'); ?>"></td>
                </tr>
                <tr>
                    <th><label for="wbp_border_radius">Card Border Radius (px)</label></th>
                    <td><input type="number" id="wbp_border_radius" name="border_radius" class="small-text" min="0" max="40" value="<?php echo esc_attr($s['border_radius']??12); ?>"></td>
                </tr>
                <tr>
                    <th><label for="wbp_custom_css">Custom CSS</label></th>
                    <td>
                        <textarea id="wbp_custom_css" name="custom_css" rows="6" class="widefat" style="font-family:monospace"><?php echo esc_textarea($s['custom_css']??''); ?></textarea>
                        <p class="description">Additional CSS applied to the booking form.</p>
                    </td>
                </tr>

                <tr><th colspan="2"><h2 style="margin:0">📝 Order Notes</h2></th></tr>
                <tr>
                    <th><label for="wbp_notes_title">Notes Field Title</label></th>
                    <td><input type="text" id="wbp_notes_title" name="order_notes_title" class="regular-text" value="<?php echo esc_attr($s['order_notes_title']);?>"></td>
                </tr>
                <tr>
                    <th><label for="wbp_notes_ph">Notes Placeholder</label></th>
                    <td><input type="text" id="wbp_notes_ph" name="order_notes_placeholder" class="regular-text" value="<?php echo esc_attr($s['order_notes_placeholder']);?>"></td>
                </tr>

            </table>
            <p class="submit">
                <button type="button" id="wbp-save-general" class="button button-primary button-large">
                    💾 Save Settings
                </button>
                <span id="wbp-general-msg" style="margin-left:12px;font-weight:600"></span>
            </p>
        </form>
        </div>
        <?php
    }

    public static function save_general(): void {
        // AJAX handler — return JSON, never redirect
        if ( ! check_ajax_referer( 'wbp_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid nonce' ); return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Forbidden' ); return;
        }

        // Merge over existing so fields not in this form are preserved
        $data = WBP_Settings::all();

        // Text fields
        foreach ( ['google_maps_api_key','currency_symbol','terms_url','privacy_url','order_notes_title','order_notes_placeholder','banner_image_url','default_country'] as $f ) {
            $data[ $f ] = sanitize_text_field( wp_unslash( $_POST[ $f ] ?? '' ) );
        }

        // Numeric
        $data['min_order_amount'] = floatval( $_POST['min_order_amount'] ?? 0 );
        $data['border_radius']    = absint( $_POST['border_radius'] ?? 12 );

        // Color fields
        foreach ( ['color_primary','color_primary_text','color_card_selected','color_card_border','color_step_bg','color_addon_check'] as $f ) {
            $val = sanitize_hex_color( wp_unslash( $_POST[ $f ] ?? '' ) );
            if ( $val ) $data[ $f ] = $val;
        }

        // Custom CSS
        $data['custom_css']         = wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ?? '' ) );
        $data['multi_country_mode'] = ( ( $_POST['multi_country_mode'] ?? '0' ) === '1' );

        WBP_Settings::save( $data );
        update_option( 'wbp_booking_page_url', esc_url_raw( wp_unslash( $_POST['booking_page_url'] ?? '/booking' ) ) );

        wp_send_json_success( 'Settings saved.' );
    }

    // ──────────────────────────────────────────
    // Page 2: Country Configuration
    // ──────────────────────────────────────────
    public static function page_countries(): void {
        if ( ! current_user_can('manage_options') ) return;
        $configured = WBP_Settings::get_configured_countries();
        $presets     = WBP_Settings::get_all_country_presets();
        $msg = isset( $_GET['wbp_saved'] ) ? '<div class="notice notice-success is-dismissible"><p>Countries saved.</p></div>' : '';
        ?>
        <div class="wrap wbp-wrap">
        <h1><span class="dashicons dashicons-location-alt"></span> WooBooking Pro – Country Configuration</h1>
        <?php echo $msg; ?>
        <p>Enable the countries your service operates in. Each country has its own postcode format, prefix length used for zone matching, and currency symbol. You can also add fully custom countries.</p>

        <form method="post" id="wbp-countries-form" onsubmit="return false;">
            <input type="hidden" id="wbp_countries_json" name="wbp_countries_json" value="<?php echo esc_attr( wp_json_encode($configured) ); ?>">

            <div id="wbp-countries-list"></div>

            <p style="margin-top:16px">
                <label for="wbp-add-preset-select"><strong>Add a Country:</strong></label>
                <select id="wbp-add-preset-select" style="margin:0 8px">
                    <option value="">— choose preset —</option>
                    <?php foreach ( $presets as $iso => $p ) : ?>
                        <option value="<?php echo esc_attr($iso); ?>"><?php echo esc_html( $p['flag'].' '.$p['name'].' ('.$iso.')' ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="wbp-add-country-btn" class="button button-primary">➕ Add Country</button>
            </p>

            <p class="submit">
                <button type="button" id="wbp-save-countries" class="button button-primary button-large">
                    💾 Save Country Config
                </button>
                <span id="wbp-countries-msg" style="margin-left:12px;font-weight:600"></span>
            </p>
        </form>
        </div>

        <script>
        (function($){
            var countries  = <?php echo wp_json_encode($configured); ?> || [];
            var presets    = wbp_admin.presets || {};

            function escHtml(s){ return $('<div>').text(String(s||'')).html(); }

            function renderAll(){
                var $list = $('#wbp-countries-list').empty();
                if(!countries.length){
                    $list.append('<p style="color:#999">No countries configured. Add one below.</p>');
                } else {
                    countries.forEach(function(c,ci){ $list.append(buildRow(c,ci)); });
                }
                syncJson();
            }

            function buildRow(c,ci){
                var iso = (c.iso2||'').toUpperCase();
                var preset = presets[iso] || presets['CUSTOM'] || {};
                var enabled = c.enabled ? 'checked' : '';
                var isCustom = (iso==='CUSTOM'||!presets[iso]);

                var $row = $('<div class="wbp-country-row postbox">');
                var flagName = escHtml((c.flag||preset.flag||'🌍')+' '+(c.name||preset.name||iso));

                $row.html(
                    '<div class="postbox-header"><h2 class="hndle" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">'+
                    '<label><input type="checkbox" class="wbp-c-enabled" '+enabled+' style="width:18px;height:18px;accent-color:#2c7be5"> Enabled</label>'+
                    '<span class="wbp-c-title" style="font-size:16px;font-weight:600">'+flagName+'</span>'+
                    '<button type="button" class="button wbp-remove-country" data-ci="'+ci+'" style="margin-left:auto">✕ Remove</button>'+
                    '</h2></div>'
                );

                var $body = $('<div class="inside" style="display:flex;flex-wrap:wrap;gap:20px;padding:16px">');

                // ISO code (editable only for custom)
                $body.append(
                    '<div class="wbp-c-field">'+
                    '<label><strong>Country ISO Code</strong><br>'+
                    '<input type="text" class="wbp-c-iso small-text" value="'+escHtml(c.iso2||iso)+'" '+(!isCustom?'readonly':'')+' placeholder="e.g. GB" maxlength="5" style="text-transform:uppercase">'+
                    '</label></div>'
                );

                // Country name
                $body.append(
                    '<div class="wbp-c-field" style="flex:2">'+
                    '<label><strong>Country Name</strong><br>'+
                    '<input type="text" class="wbp-c-name regular-text" value="'+escHtml(c.name||preset.name||'')+'" placeholder="e.g. United Kingdom">'+
                    '</label></div>'
                );

                // Flag emoji
                $body.append(
                    '<div class="wbp-c-field">'+
                    '<label><strong>Flag Emoji</strong><br>'+
                    '<input type="text" class="wbp-c-flag small-text" value="'+escHtml(c.flag||preset.flag||'🌍')+'" placeholder="🇬🇧" style="font-size:18px;width:60px">'+
                    '</label></div>'
                );

                // Google Maps restriction code
                $body.append(
                    '<div class="wbp-c-field">'+
                    '<label><strong>Google Maps Country Code</strong><br>'+
                    '<input type="text" class="wbp-c-maps small-text" value="'+escHtml(c.maps_country||preset.maps_country||'')+'" placeholder="gb" maxlength="3">'+
                    '<br><small style="color:#666">Lowercase ISO2 for Maps autocomplete restriction</small>'+
                    '</label></div>'
                );

                // Postcode format hint
                $body.append(
                    '<div class="wbp-c-field" style="flex:2">'+
                    '<label><strong>Postcode Format Example</strong><br>'+
                    '<input type="text" class="wbp-c-format regular-text" value="'+escHtml(c.format_hint||preset.format_hint||'')+'" placeholder="e.g. SW1A 1AA">'+
                    '<br><small style="color:#666">Shown to users as a placeholder hint</small>'+
                    '</label></div>'
                );

                // Postcode regex
                $body.append(
                    '<div class="wbp-c-field" style="flex:3">'+
                    '<label><strong>Postcode Validation Regex</strong><br>'+
                    '<input type="text" class="wbp-c-regex widefat" value="'+escHtml(c.regex||preset.regex||'/^.+$/')+'" placeholder="/^.+$/">'+
                    '<br><small style="color:#666">JavaScript regex. Use <code>/^.+$/</code> to accept anything.</small>'+
                    '</label></div>'
                );

                // Prefix length
                $body.append(
                    '<div class="wbp-c-field">'+
                    '<label><strong>Postcode Prefix Length</strong><br>'+
                    '<input type="number" class="wbp-c-prefix small-text" value="'+(c.prefix_length||preset.prefix_length||4)+'" min="1" max="10" step="1">'+
                    '<br><small style="color:#666">Chars used to match postcode zones<br>(e.g. 3 for "SW1" in "SW1A 1AA")</small>'+
                    '</label></div>'
                );

                // Currency
                $body.append(
                    '<div class="wbp-c-field">'+
                    '<label><strong>Currency Symbol</strong><br>'+
                    '<input type="text" class="wbp-c-currency small-text" value="'+escHtml(c.currency||preset.currency||'')+'" placeholder="£" style="width:60px">'+
                    '</label></div>'
                );

                $row.append($body);
                return $row;
            }

            function collectRow($row, ci){
                return {
                    iso2          : ($row.find('.wbp-c-iso').val()||'').toUpperCase(),
                    name          : $row.find('.wbp-c-name').val(),
                    flag          : $row.find('.wbp-c-flag').val(),
                    maps_country  : $row.find('.wbp-c-maps').val().toLowerCase(),
                    format_hint   : $row.find('.wbp-c-format').val(),
                    regex         : $row.find('.wbp-c-regex').val(),
                    prefix_length : parseInt($row.find('.wbp-c-prefix').val())||4,
                    currency      : $row.find('.wbp-c-currency').val(),
                    enabled       : $row.find('.wbp-c-enabled').is(':checked'),
                    preset        : ($row.find('.wbp-c-iso').val()||'').toUpperCase(),
                };
            }

            function syncJson(){
                var out = [];
                $('#wbp-countries-list .wbp-country-row').each(function(ci){
                    out.push(collectRow($(this), ci));
                });
                countries = out;
                $('#wbp_countries_json').val(JSON.stringify(countries));
            }

            // Add preset
            $(document).on('click','#wbp-add-country-btn',function(){
                var iso = $('#wbp-add-preset-select').val();
                if(!iso){ alert('Please select a country first.'); return; }
                var preset = presets[iso]||presets['CUSTOM'];
                countries.push({
                    iso2:iso, name:preset.name, flag:preset.flag,
                    maps_country:preset.maps_country, format_hint:preset.format_hint,
                    regex:preset.regex, prefix_length:preset.prefix_length,
                    currency:preset.currency, enabled:true, preset:iso
                });
                renderAll();
                $('#wbp-add-preset-select').val('');
            });

            // Remove
            $(document).on('click','.wbp-remove-country',function(){
                countries.splice($(this).data('ci'),1);
                renderAll();
            });

            $(document).on('input change','#wbp-countries-list input',function(){ syncJson(); });

            renderAll();
        })(jQuery);
        </script>
        <?php
    }

    public static function save_countries(): void {
        if ( ! check_ajax_referer( 'wbp_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid nonce' ); return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Forbidden' ); return;
        }

        $raw  = wp_unslash( $_POST['wbp_countries_json'] ?? '[]' );
        $rows = json_decode( $raw, true );
        if ( ! is_array( $rows ) ) $rows = [];

        $clean = [];
        foreach ( $rows as $row ) {
            $clean[] = [
                'iso2'          => strtoupper( sanitize_text_field( $row['iso2']         ?? '' ) ),
                'name'          => sanitize_text_field( $row['name']          ?? '' ),
                'flag'          => sanitize_text_field( $row['flag']          ?? '' ),
                'maps_country'  => strtolower( sanitize_text_field( $row['maps_country'] ?? '' ) ),
                'format_hint'   => sanitize_text_field( $row['format_hint']   ?? '' ),
                'regex'         => sanitize_text_field( $row['regex']         ?? '/^.+$/' ),
                'prefix_length' => absint( $row['prefix_length'] ?? 4 ),
                'currency'      => sanitize_text_field( $row['currency']      ?? '' ),
                'enabled'       => ! empty( $row['enabled'] ),
                'preset'        => strtoupper( sanitize_text_field( $row['preset'] ?? '' ) ),
            ];
        }

        WBP_Settings::save_countries( $clean );
        wp_send_json_success( 'Countries saved.' );
    }

    // ──────────────────────────────────────────
    // Page 3: Postcode Zones
    // ──────────────────────────────────────────
    public static function page_zones(): void {
        if ( ! current_user_can('manage_options') ) return;

        $zones    = WBP_Settings::get_zones();
        $countries = WBP_Settings::get_enabled_countries();
        $msg = isset($_GET['wbp_saved']) ? '<div class="notice notice-success is-dismissible"><p>Zones saved.</p></div>' : '';

        $product_cats = get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);
        $cat_options  = [];
        if ( !is_wp_error($product_cats) ) {
            foreach ($product_cats as $cat) {
                $cat_options[] = ['id'=>(string)$cat->term_id,'name'=>$cat->name];
            }
        }

        $days_of_week = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        ?>
        <div class="wrap wbp-wrap">
        <h1><span class="dashicons dashicons-location-alt"></span> WooBooking Pro – Postcode Zones</h1>
        <?php echo $msg; ?>

        <?php if (empty($countries)): ?>
        <div class="notice notice-warning"><p>⚠️ No countries are enabled yet. <a href="<?php echo admin_url('admin.php?page=wbp-countries'); ?>">Configure countries first →</a></p></div>
        <?php else: ?>
        <p>Each zone maps a set of postcodes to available services, collection days, and surcharges. Zones are <strong>country-specific</strong> — select the country for each zone so the correct postcode format is applied.</p>
        <div class="notice notice-info inline" style="margin:0 0 16px"><p>💡 <strong>Need different zones per page?</strong> Use <a href="<?php echo esc_url(admin_url('edit.php?post_type=wbp_config')); ?>">Booking Configs</a> to create named configs each with their own zone table, then use <code>[woo_booking id="POST_ID"]</code> in your page.</p></div>
        <?php endif; ?>

        <?php if (!empty($countries)): ?>
        <div id="wbp-zone-filter" style="margin-bottom:16px">
            <strong>Filter by country:</strong>
            <label style="margin:0 12px"><input type="radio" name="wbp_zone_filter" value="" checked> All</label>
            <?php foreach ($countries as $iso => $c): ?>
            <label style="margin-right:12px"><input type="radio" name="wbp_zone_filter" value="<?php echo esc_attr($iso);?>"> <?php echo esc_html($c['flag'].' '.$c['name']); ?></label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="post" id="wbp-zones-form" onsubmit="return false;">
            <input type="hidden" id="wbp_zones_json" name="wbp_zones_json" value="<?php echo esc_attr(wp_json_encode($zones)); ?>">

            <div id="wbp-zones-list"></div>

            <p>
                <?php if (!empty($countries)): ?>
                <label>Add zone for:
                <select id="wbp-zone-country-select">
                    <?php foreach ($countries as $iso=>$c): ?>
                    <option value="<?php echo esc_attr($iso);?>"><?php echo esc_html($c['flag'].' '.$c['name'].' ('.$iso.')');?></option>
                    <?php endforeach; ?>
                </select></label>
                <?php endif; ?>
                <button type="button" id="wbp-add-zone" class="button button-primary button-large" style="margin-left:8px">➕ Add Postcode Zone</button>
            </p>

            <p class="submit">
                <button type="button" id="wbp-save-zones" class="button button-primary button-large">
                    💾 Save All Zones
                </button>
                <span id="wbp-zones-msg" style="margin-left:12px;font-weight:600"></span>
            </p>
        </form>
        </div>

        <script>
        (function($){
            var zones    = <?php echo wp_json_encode($zones); ?> || [];
            var allCats  = <?php echo wp_json_encode($cat_options); ?>;
            var allDays  = <?php echo wp_json_encode($days_of_week); ?>;
            var countries= <?php echo wp_json_encode($countries); ?>;
            var presets  = wbp_admin.presets || {};

            function escHtml(s){ return $('<div>').text(String(s||'')).html(); }

            function getCountryData(iso){
                var c = countries[iso];
                if(c) return c;
                return presets[iso]||presets['CUSTOM']||{};
            }

            function renderZones(filterISO){
                var $list = $('#wbp-zones-list').empty();
                var any = false;
                zones.forEach(function(z,zi){
                    if(filterISO && z.country !== filterISO) return;
                    $list.append(buildZoneCard(z,zi));
                    any=true;
                });
                if(!any) $list.append('<p style="color:#999;font-style:italic">No zones'+(filterISO?' for this country':'')+'. Click "Add Postcode Zone" below.</p>');
                syncJson();
            }

            function buildZoneCard(z,zi){
                var iso  = z.country||'GB';
                var cData= getCountryData(iso);
                var flag = cData.flag||'🌍';
                var cname= cData.name||iso;
                var fmt  = cData.format_hint||'';
                var prefix= cData.prefix_length||4;

                var $card = $('<div class="wbp-zone-card postbox" data-zi="'+zi+'" data-country="'+escHtml(iso)+'">');

                // Header
                $card.append(
                    '<div class="postbox-header"><h2 class="hndle" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">'+
                    '<span class="wbp-zone-country-badge" style="background:#f0f4ff;border:1px solid #c0d0f0;border-radius:20px;padding:2px 10px;font-size:13px">'+escHtml(flag+' '+cname)+'</span>'+
                    '<input type="text" class="wbp-z-name regular-text" placeholder="Zone name" value="'+escHtml(z.name||'Zone '+(zi+1))+'" style="flex:1">'+
                    '<select class="wbp-z-country" style="width:auto">'+buildCountryOptions(iso)+'</select>'+
                    '<button type="button" class="button wbp-remove-zone" data-zi="'+zi+'" style="margin-left:auto">✕ Remove Zone</button>'+
                    '</h2></div>'
                );

                var $body = $('<div class="inside" style="padding:16px">');

                // Postcodes
                $body.append(
                    '<div style="margin-bottom:12px">'+
                    '<label><strong>Postcodes (comma-separated)</strong>'+
                    '<span style="color:#666;font-size:12px;margin-left:8px">— use the '+prefix+'-character prefix (e.g. "'+escHtml(fmt)+'" → prefix "'+escHtml(fmt.replace(/\s.*/,'').substring(0,prefix))+'")</span>'+
                    '</label>'+
                    '<textarea class="wbp-z-postcodes widefat" rows="2" placeholder="e.g. SW1, SW2, E1, E2, TW3">'+escHtml(z.post_code||'')+'</textarea>'+
                    '</div>'
                );

                // Surcharge + Min days (side by side)
                $body.append(
                    '<div style="display:flex;gap:24px;margin-bottom:12px">'+
                    '<label><strong>Surcharge ('+(cData.currency||'£')+')</strong><br><input type="number" class="wbp-z-surcharge small-text" step="0.01" min="0" value="'+(z.surcharge||0)+'"></label>'+
                    '<label><strong>Min Delivery Days</strong><br><input type="number" class="wbp-z-mindays small-text" step="1" min="0" value="'+(z.minimum_delivery_days||3)+'"></label>'+
                    '</div>'
                );

                // Services
                var svcHtml = '<div style="margin-bottom:12px"><strong>Services Available</strong><br>';
                allCats.forEach(function(cat){
                    var checked=(z.services||[]).indexOf(cat.id)!==-1?'checked':'';
                    svcHtml+='<label style="margin-right:12px"><input type="checkbox" class="wbp-z-svc" value="'+escHtml(cat.id)+'" '+checked+'> '+escHtml(cat.name)+'</label>';
                });
                svcHtml+='</div>';
                $body.append(svcHtml);

                // Pickup & Delivery days
                var daysHtml = '<div style="margin-bottom:12px"><strong>Pickup &amp; Delivery Days</strong><br>';
                allDays.forEach(function(day){
                    var checked=(z.pickup_and_deliver_day||[]).indexOf(day)!==-1?'checked':'';
                    daysHtml+='<label style="margin-right:12px"><input type="checkbox" class="wbp-z-day" value="'+day+'" '+checked+'> '+day+'</label>';
                });
                daysHtml+='</div>';
                $body.append(daysHtml);

                // Disabled Dates
                var disabledDates = (z.disable_dates||[]).map(function(d){ return typeof d==='object'?(d.disable_date||''):d; }).join('\n');
                $body.append(
                    '<div><strong>Disabled / Fully Booked Dates</strong> <em style="color:#888">(one per line, DD/MM/YYYY)</em><br>'+
                    '<textarea class="wbp-z-disabled-dates widefat" rows="3" placeholder="25/12/2025\n26/12/2025">'+escHtml(disabledDates)+'</textarea></div>'
                );

                $card.append($body);
                return $card;
            }

            function buildCountryOptions(selectedIso){
                var opts='';
                $.each(countries,function(iso,c){
                    var sel=(iso===selectedIso)?'selected':'';
                    opts+='<option value="'+escHtml(iso)+'" '+sel+'>'+escHtml((c.flag||'')+(c.name||iso))+' ('+escHtml(iso)+')</option>';
                });
                return opts;
            }

            function collectZone($card){
                var z={};
                z.name                  = $card.find('.wbp-z-name').val();
                z.country               = $card.find('.wbp-z-country').val()||'GB';
                z.post_code             = $card.find('.wbp-z-postcodes').val();
                z.surcharge             = $card.find('.wbp-z-surcharge').val();
                z.minimum_delivery_days = $card.find('.wbp-z-mindays').val();
                z.services=[]; $card.find('.wbp-z-svc:checked').each(function(){ z.services.push($(this).val()); });
                z.pickup_and_deliver_day=[]; $card.find('.wbp-z-day:checked').each(function(){ z.pickup_and_deliver_day.push($(this).val()); });
                var raw=$card.find('.wbp-z-disabled-dates').val().trim();
                z.disable_dates=raw?raw.split('\n').map(function(d){return{disable_date:d.trim()};}).filter(function(d){return d.disable_date;}):[]; 
                return z;
            }

            function syncJson(){
                var out=[];
                $('#wbp-zones-list .wbp-zone-card').each(function(){ out.push(collectZone($(this))); });
                zones=out;
                $('#wbp_zones_json').val(JSON.stringify(zones));
            }

            // Filter radio
            $(document).on('change','input[name=wbp_zone_filter]',function(){
                renderZones($(this).val());
            });

            // Add zone
            $(document).on('click','#wbp-add-zone',function(){
                var iso=$('#wbp-zone-country-select').val()||'GB';
                zones.push({name:'New Zone',country:iso,post_code:'',surcharge:0,minimum_delivery_days:3,services:[],pickup_and_deliver_day:[],disable_dates:[]});
                renderZones($('input[name=wbp_zone_filter]:checked').val());
            });

            // Remove zone
            $(document).on('click','.wbp-remove-zone',function(){
                zones.splice($(this).data('zi'),1);
                renderZones($('input[name=wbp_zone_filter]:checked').val());
            });

            // Country change → update badge
            $(document).on('change','.wbp-z-country',function(){
                var iso=$(this).val();
                var cData=getCountryData(iso);
                $(this).closest('.wbp-zone-card').find('.wbp-zone-country-badge').text((cData.flag||'')+(cData.name||iso));
                $(this).closest('.wbp-zone-card').data('country',iso);
                syncJson();
            });

            $(document).on('input change','#wbp-zones-list input,#wbp-zones-list textarea,#wbp-zones-list select',function(){
                syncJson();
            });

            renderZones();
        })(jQuery);
        </script>
        <?php
    }

    public static function save_zones(): void {
        if ( ! check_ajax_referer( 'wbp_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid nonce' ); return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Forbidden' ); return;
        }

        $raw   = wp_unslash( $_POST['wbp_zones_json'] ?? '[]' );
        $zones = json_decode( $raw, true );
        if ( ! is_array( $zones ) ) $zones = [];

        $days_valid = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $clean = [];
        foreach ( $zones as $zone ) {
            $disable_dates = [];
            foreach ( $zone['disable_dates'] ?? [] as $d ) {
                $val = is_array( $d ) ? ( $d['disable_date'] ?? '' ) : $d;
                $disable_dates[] = [ 'disable_date' => sanitize_text_field( $val ) ];
            }
            $services = array_map( 'strval', array_map( 'absint', $zone['services'] ?? [] ) );
            $days     = array_filter( $zone['pickup_and_deliver_day'] ?? [], fn($d) => in_array( $d, $days_valid, true ) );
            $clean[]  = [
                'name'                   => sanitize_text_field( $zone['name']    ?? '' ),
                'country'                => strtoupper( sanitize_text_field( $zone['country'] ?? 'GB' ) ),
                'post_code'              => sanitize_text_field( $zone['post_code'] ?? '' ),
                'surcharge'              => (string) floatval( $zone['surcharge']  ?? 0 ),
                'minimum_delivery_days'  => absint( $zone['minimum_delivery_days'] ?? 3 ),
                'services'               => array_values( $services ),
                'pickup_and_deliver_day' => array_values( $days ),
                'disable_dates'          => $disable_dates,
            ];
        }

        WBP_Settings::save_zones( $clean );
        wp_send_json_success( 'Zones saved.' );
    }
}
