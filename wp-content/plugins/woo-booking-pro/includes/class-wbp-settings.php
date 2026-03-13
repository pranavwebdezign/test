<?php
defined( 'ABSPATH' ) || exit;

/**
 * WBP_Settings – central options store.
 *
 * wp_options keys:
 *   wbp_settings  – general plugin settings (serialised array)
 *   wbp_zones     – postcode zones            (JSON)
 *   wbp_countries – country config rows       (JSON)
 */
class WBP_Settings {

    /** Built-in country presets */
    const COUNTRY_PRESETS = [
        'GB' => [ 'name'=>'United Kingdom',  'iso2'=>'GB', 'flag'=>'🇬🇧', 'regex'=>'/^[A-Z]{1,2}[0-9][0-9A-Z]?\s?[0-9][A-Z]{2}$/i', 'prefix_length'=>3,  'format_hint'=>'SW1A 1AA',      'currency'=>'£',    'maps_country'=>'gb' ],
        'US' => [ 'name'=>'United States',   'iso2'=>'US', 'flag'=>'🇺🇸', 'regex'=>'/^\d{5}(-\d{4})?$/',                              'prefix_length'=>5,  'format_hint'=>'90210',         'currency'=>'$',    'maps_country'=>'us' ],
        'CA' => [ 'name'=>'Canada',          'iso2'=>'CA', 'flag'=>'🇨🇦', 'regex'=>'/^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/i',                  'prefix_length'=>3,  'format_hint'=>'K1A 0A9',       'currency'=>'CA$',  'maps_country'=>'ca' ],
        'AU' => [ 'name'=>'Australia',       'iso2'=>'AU', 'flag'=>'🇦🇺', 'regex'=>'/^\d{4}$/',                                       'prefix_length'=>2,  'format_hint'=>'2000',          'currency'=>'A$',   'maps_country'=>'au' ],
        'NZ' => [ 'name'=>'New Zealand',     'iso2'=>'NZ', 'flag'=>'🇳🇿', 'regex'=>'/^\d{4}$/',                                       'prefix_length'=>2,  'format_hint'=>'1010',          'currency'=>'NZ$',  'maps_country'=>'nz' ],
        'DE' => [ 'name'=>'Germany',         'iso2'=>'DE', 'flag'=>'🇩🇪', 'regex'=>'/^\d{5}$/',                                       'prefix_length'=>5,  'format_hint'=>'10115',         'currency'=>'€',    'maps_country'=>'de' ],
        'FR' => [ 'name'=>'France',          'iso2'=>'FR', 'flag'=>'🇫🇷', 'regex'=>'/^\d{5}$/',                                       'prefix_length'=>5,  'format_hint'=>'75001',         'currency'=>'€',    'maps_country'=>'fr' ],
        'ES' => [ 'name'=>'Spain',           'iso2'=>'ES', 'flag'=>'🇪🇸', 'regex'=>'/^\d{5}$/',                                       'prefix_length'=>5,  'format_hint'=>'28001',         'currency'=>'€',    'maps_country'=>'es' ],
        'IT' => [ 'name'=>'Italy',           'iso2'=>'IT', 'flag'=>'🇮🇹', 'regex'=>'/^\d{5}$/',                                       'prefix_length'=>5,  'format_hint'=>'00118',         'currency'=>'€',    'maps_country'=>'it' ],
        'NL' => [ 'name'=>'Netherlands',     'iso2'=>'NL', 'flag'=>'🇳🇱', 'regex'=>'/^\d{4}\s?[A-Z]{2}$/i',                          'prefix_length'=>4,  'format_hint'=>'1234 AB',       'currency'=>'€',    'maps_country'=>'nl' ],
        'BE' => [ 'name'=>'Belgium',         'iso2'=>'BE', 'flag'=>'🇧🇪', 'regex'=>'/^\d{4}$/',                                       'prefix_length'=>4,  'format_hint'=>'1000',          'currency'=>'€',    'maps_country'=>'be' ],
        'CH' => [ 'name'=>'Switzerland',     'iso2'=>'CH', 'flag'=>'🇨🇭', 'regex'=>'/^\d{4}$/',                                       'prefix_length'=>4,  'format_hint'=>'8001',          'currency'=>'CHF',  'maps_country'=>'ch' ],
        'AT' => [ 'name'=>'Austria',         'iso2'=>'AT', 'flag'=>'🇦🇹', 'regex'=>'/^\d{4}$/',                                       'prefix_length'=>4,  'format_hint'=>'1010',          'currency'=>'€',    'maps_country'=>'at' ],
        'SE' => [ 'name'=>'Sweden',          'iso2'=>'SE', 'flag'=>'🇸🇪', 'regex'=>'/^\d{3}\s?\d{2}$/',                               'prefix_length'=>3,  'format_hint'=>'103 16',        'currency'=>'SEK',  'maps_country'=>'se' ],
        'NO' => [ 'name'=>'Norway',          'iso2'=>'NO', 'flag'=>'🇳🇴', 'regex'=>'/^\d{4}$/',                                       'prefix_length'=>4,  'format_hint'=>'0026',          'currency'=>'NOK',  'maps_country'=>'no' ],
        'DK' => [ 'name'=>'Denmark',         'iso2'=>'DK', 'flag'=>'🇩🇰', 'regex'=>'/^\d{4}$/',                                       'prefix_length'=>4,  'format_hint'=>'1000',          'currency'=>'DKK',  'maps_country'=>'dk' ],
        'IE' => [ 'name'=>'Ireland',         'iso2'=>'IE', 'flag'=>'🇮🇪', 'regex'=>'/^[A-Z]\d{2}\s?[A-Z0-9]{4}$/i',                 'prefix_length'=>3,  'format_hint'=>'D02 X285',      'currency'=>'€',    'maps_country'=>'ie' ],
        'IN' => [ 'name'=>'India',           'iso2'=>'IN', 'flag'=>'🇮🇳', 'regex'=>'/^\d{6}$/',                                       'prefix_length'=>3,  'format_hint'=>'110001',        'currency'=>'₹',    'maps_country'=>'in' ],
        'SG' => [ 'name'=>'Singapore',       'iso2'=>'SG', 'flag'=>'🇸🇬', 'regex'=>'/^\d{6}$/',                                       'prefix_length'=>2,  'format_hint'=>'018956',        'currency'=>'S$',   'maps_country'=>'sg' ],
        'AE' => [ 'name'=>'UAE',             'iso2'=>'AE', 'flag'=>'🇦🇪', 'regex'=>'/^\d{1,6}$/',                                     'prefix_length'=>5,  'format_hint'=>'00000',         'currency'=>'AED',  'maps_country'=>'ae' ],
        'ZA' => [ 'name'=>'South Africa',    'iso2'=>'ZA', 'flag'=>'🇿🇦', 'regex'=>'/^\d{4}$/',                                       'prefix_length'=>4,  'format_hint'=>'0001',          'currency'=>'R',    'maps_country'=>'za' ],
        'BR' => [ 'name'=>'Brazil',          'iso2'=>'BR', 'flag'=>'🇧🇷', 'regex'=>'/^\d{5}-?\d{3}$/',                                'prefix_length'=>5,  'format_hint'=>'01310-100',     'currency'=>'R$',   'maps_country'=>'br' ],
        'MX' => [ 'name'=>'Mexico',          'iso2'=>'MX', 'flag'=>'🇲🇽', 'regex'=>'/^\d{5}$/',                                       'prefix_length'=>5,  'format_hint'=>'06600',         'currency'=>'MX$',  'maps_country'=>'mx' ],
        'JP' => [ 'name'=>'Japan',           'iso2'=>'JP', 'flag'=>'🇯🇵', 'regex'=>'/^\d{3}-?\d{4}$/',                                'prefix_length'=>3,  'format_hint'=>'100-0001',      'currency'=>'¥',    'maps_country'=>'jp' ],
        'KR' => [ 'name'=>'South Korea',     'iso2'=>'KR', 'flag'=>'🇰🇷', 'regex'=>'/^\d{5}$/',                                       'prefix_length'=>5,  'format_hint'=>'03000',         'currency'=>'₩',    'maps_country'=>'kr' ],
        'HK' => [ 'name'=>'Hong Kong',       'iso2'=>'HK', 'flag'=>'🇭🇰', 'regex'=>'/^.+$/',                                          'prefix_length'=>2,  'format_hint'=>'(No postcode)', 'currency'=>'HK$',  'maps_country'=>'hk' ],
        'CUSTOM' => [ 'name'=>'Custom Country', 'iso2'=>'', 'flag'=>'🌍', 'regex'=>'/^.+$/', 'prefix_length'=>4, 'format_hint'=>'', 'currency'=>'', 'maps_country'=>'' ],
    ];

    private static array $defaults = [
        'google_maps_api_key'     => '',
        'currency_symbol'         => '£',
        'terms_url'               => '/terms/',
        'privacy_url'             => '/privacy-policy/',
        'order_notes_title'       => 'Special Instructions',
        'order_notes_placeholder' => 'Add any special instructions here…',
        'banner_image_url'        => '',
        'min_order_amount'        => 0,
        'booking_page_url'        => '/booking',
        'multi_country_mode'      => false,
        'default_country'         => 'GB',
        'step_labels'             => [ 'Enter your Postcode', 'See Service Availability', 'Customise your order', 'Summary + Details' ],
        'step_icons'              => [ '', '', '', '' ],
    ];

    public static function init(): void {}

    public static function get( string $key, $fallback = null ) {
        $opts = get_option( 'wbp_settings', [] );
        return $opts[ $key ] ?? self::$defaults[ $key ] ?? $fallback;
    }

    public static function all(): array {
        return array_merge( self::$defaults, get_option( 'wbp_settings', [] ) );
    }

    public static function save( array $data ): void {
        // Always merge over defaults so no key is ever missing from the option
        $merged = array_merge( self::$defaults, $data );
        update_option( 'wbp_settings', $merged );
    }

    // ──────────────────────────────────────────
    // Country helpers
    // ──────────────────────────────────────────

    /** All built-in presets keyed by ISO2. */
    public static function get_all_country_presets(): array {
        return self::COUNTRY_PRESETS;
    }

    /**
     * Return enabled country rows merged with their preset.
     * Returns array keyed by ISO2.
     */
    public static function get_enabled_countries(): array {
        $rows = self::get_configured_countries();
        if ( empty( $rows ) ) {
            return [ 'GB' => array_merge( self::COUNTRY_PRESETS['GB'], [ 'enabled' => true ] ) ];
        }
        $result = [];
        foreach ( $rows as $row ) {
            if ( empty( $row['enabled'] ) ) continue;
            $iso    = strtoupper( $row['iso2'] ?? $row['preset'] ?? '' );
            $preset = self::COUNTRY_PRESETS[ $iso ] ?? self::COUNTRY_PRESETS['CUSTOM'];
            // Admin can override regex, prefix_length, currency
            $result[ $iso ] = array_merge( $preset, $row );
        }
        return $result;
    }

    /** Return raw configured country rows (enabled or not). */
    public static function get_configured_countries(): array {
        $stored = get_option( 'wbp_countries', '[]' );
        $rows   = json_decode( $stored, true );
        return is_array( $rows ) ? $rows : [];
    }

    public static function save_countries( array $countries ): void {
        update_option( 'wbp_countries', wp_json_encode( $countries ) );
    }

    /** ISO2 array of Google Maps restriction codes for enabled countries. */
    public static function get_maps_restrictions(): array {
        $codes = [];
        foreach ( self::get_enabled_countries() as $c ) {
            $mc = $c['maps_country'] ?? strtolower( $c['iso2'] ?? '' );
            if ( $mc ) $codes[] = $mc;
        }
        return array_values( array_unique( $codes ) );
    }

    /** Single preset by ISO2. */
    public static function get_country_preset( string $iso2 ): array {
        return self::COUNTRY_PRESETS[ strtoupper( $iso2 ) ] ?? self::COUNTRY_PRESETS['CUSTOM'];
    }

    // ──────────────────────────────────────────
    // Postcode Zone helpers
    // ──────────────────────────────────────────

    public static function get_zones(): array {
        $zones = json_decode( get_option( 'wbp_zones', '[]' ), true );
        if ( ! is_array( $zones ) ) return [];
        foreach ( $zones as &$z ) {
            $z['post_code_arr'] = array_map( 'trim', explode( ',', $z['post_code'] ?? '' ) );
            $z['disable_dates'] = $z['disable_dates'] ?? [];
            $z['services']      = $z['services']      ?? [];
            $z['country']       = $z['country']       ?? 'GB';
        }
        unset( $z );
        return $zones;
    }

    public static function save_zones( array $zones ): void {
        update_option( 'wbp_zones', wp_json_encode( $zones ) );
    }
}
