<?php
defined( 'ABSPATH' ) || exit;

/**
 * WBP_WooCommerce – hooks into WooCommerce to:
 *   • apply the custom price set during add-to-cart
 *   • save booking metadata to orders
 *   • display booking info on the order admin page
 *   • keep products hidden from the shop/catalogue
 */
class WBP_WooCommerce {

    public static function init(): void {
        // Compatibility: fix Divi child theme session bug (wdm_user_custom_data_value undefined)
        add_action( 'woocommerce_cart_loaded_from_session', [ __CLASS__, 'fix_theme_session_compat' ], 1 );
        add_action( 'woocommerce_before_cart',              [ __CLASS__, 'fix_theme_session_compat' ], 1 );
        add_action( 'woocommerce_before_checkout_form',     [ __CLASS__, 'fix_theme_session_compat' ], 1 );
        add_action( 'wp',                                   [ __CLASS__, 'fix_theme_session_compat' ], 1 );

        // Apply custom price
        add_action( 'woocommerce_before_calculate_totals', [ __CLASS__, 'apply_custom_price' ] );

        // Redirect "Return to shop" back to the booking page
        add_filter( 'woocommerce_return_to_shop_redirect', [ __CLASS__, 'return_to_shop_url' ] );

        // Hide products from catalogue / search
        add_filter( 'woocommerce_product_is_visible',            '__return_false' );
        add_filter( 'woocommerce_register_post_type_product',    [ __CLASS__, 'hide_product_pages' ], 12 );
        add_filter( 'woocommerce_cart_item_permalink',           '__return_null' );

        // Save booking metadata on order
        add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'save_booking_line_item_meta' ], 10, 4 );

        // Display booking metadata in order admin
        add_action( 'woocommerce_after_order_itemmeta',           [ __CLASS__, 'display_booking_meta' ], 10, 3 );

        // Display in My Account order view
        add_filter( 'woocommerce_order_item_get_formatted_meta_data', [ __CLASS__, 'format_booking_meta' ], 10, 2 );

        // Product Add-ons: handled by WBP_Addons_Admin class (class-wbp-addons-admin.php)

        // Product category surcharge meta (term meta box)
        add_action( 'product_cat_add_form_fields',  [ __CLASS__, 'category_surcharge_field' ] );
        add_action( 'product_cat_edit_form_fields', [ __CLASS__, 'category_surcharge_field_edit' ] );
        add_action( 'created_product_cat',          [ __CLASS__, 'save_category_surcharge' ] );
        add_action( 'edited_product_cat',           [ __CLASS__, 'save_category_surcharge' ] );
    }

    // ──────────────────────────────────────────
    // Price & cart
    // ──────────────────────────────────────────

    public static function apply_custom_price( WC_Cart $cart ): void {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        foreach ( $cart->get_cart() as $item ) {
            if ( isset( $item['custom_price'] ) && $item['custom_price'] > 0 ) {
                $item['data']->set_price( floatval( $item['custom_price'] ) );
            }
        }
    }

    public static function return_to_shop_url(): string {
        $booking_page = get_option( 'wbp_booking_page_url', '/booking' );
        return $booking_page ?: '/booking';
    }

    public static function hide_product_pages( array $args ): array {
        $args['publicly_queryable'] = false;
        $args['public']             = false;
        return $args;
    }

    // ──────────────────────────────────────────
    // Order meta
    // ──────────────────────────────────────────

    public static function save_booking_line_item_meta( $item, $cart_item_key, $cart_item, $order ): void {
        // Booking dates & notes stored in session during AJAX
        $pickup_date   = WC()->session->get( 'wbp_pickup_date',   '' );
        $delivery_date = WC()->session->get( 'wbp_delivery_date', '' );
        $order_notes   = WC()->session->get( 'wbp_order_notes',   '' );
        $booking_meta  = WC()->session->get( 'wbp_booking_meta',  [] );
        $country_iso   = WC()->session->get( 'wbp_country',       '' );

        if ( $country_iso ) {
            $country_name = WBP_Settings::get_country_preset( $country_iso )['name'] ?? $country_iso;
            $item->add_meta_data( __( 'Country', 'woo-booking-pro' ), $country_name, true );
        }

        if ( $pickup_date ) {
            $item->add_meta_data( __( 'Collection Date', 'woo-booking-pro' ), $pickup_date, true );
        }
        if ( $delivery_date ) {
            $item->add_meta_data( __( 'Delivery Date', 'woo-booking-pro' ), $delivery_date, true );
        }
        if ( $order_notes ) {
            $item->add_meta_data( __( 'Special Instructions', 'woo-booking-pro' ), $order_notes, true );
        }

        // Summarise selected add-ons
        if ( is_array( $booking_meta ) ) {
            foreach ( $booking_meta as $row ) {
                $title  = sanitize_text_field( $row['title'] ?? '' );
                $amount = floatval( $row['amount'] ?? 0 );
                if ( $title ) {
                    $item->add_meta_data( $title, WBP_Settings::get( 'currency_symbol', '£' ) . number_format( $amount, 2 ), false );
                }
            }
        }

        // Clear session
        WC()->session->set( 'wbp_pickup_date',   '' );
        WC()->session->set( 'wbp_delivery_date', '' );
        WC()->session->set( 'wbp_order_notes',   '' );
        WC()->session->set( 'wbp_booking_meta',  [] );
        WC()->session->set( 'wbp_country',       '' );
    }

    public static function display_booking_meta( $item_id, $item, $product ): void {
        $meta_data = $item->get_formatted_meta_data();
        foreach ( $meta_data as $meta ) {
            echo '<p><strong>' . esc_html( $meta->display_key ) . ':</strong> '
               . wp_kses_post( $meta->display_value ) . '</p>';
        }
    }

    public static function format_booking_meta( array $formatted_meta, $item ): array {
        return $formatted_meta; // pass through; displayed automatically
    }


        // ──────────────────────────────────────────
    // Category surcharge field
    // ──────────────────────────────────────────

    public static function category_surcharge_field(): void {
        ?>
        <div class="form-field">
            <label for="wbp_surcharge"><?php esc_html_e( 'Service Surcharge', 'woo-booking-pro' ); ?></label>
            <input type="number" id="wbp_surcharge" name="wbp_surcharge" step="0.01" min="0" value="0">
            <p><?php esc_html_e( 'Extra fee added for booking this service category.', 'woo-booking-pro' ); ?></p>
        </div>
        <?php
    }

    /**
     * Divi child theme compatibility fix.
     *
     * The theme's wdm_add_user_custom_option_from_session_into_cart() calls
     * count() on $_SESSION['wdm_user_custom_data_value'] without checking if
     * the key exists first, causing:
     *   Warning: Undefined array key "wdm_user_custom_data_value"
     *   Fatal: count(): Argument must be Countable|array, null given
     *
     * We initialise the key to an empty array so count() gets a valid value.
     */
    public static function fix_theme_session_compat(): void {
        // PHP session
        if ( session_status() === PHP_SESSION_ACTIVE ) {
            if ( ! isset( $_SESSION['wdm_user_custom_data_value'] ) ) {
                $_SESSION['wdm_user_custom_data_value'] = [];
            }
        }

        // WooCommerce session
        if ( function_exists('WC') && WC()->session ) {
            $val = WC()->session->get('wdm_user_custom_data_value');
            if ( ! is_array($val) ) {
                WC()->session->set('wdm_user_custom_data_value', []);
            }
        }
    }

    public static function category_surcharge_field_edit( WP_Term $term ): void {
        $surcharge = get_term_meta( $term->term_id, 'wbp_surcharge', true );
        ?>
        <tr class="form-field">
            <th><label for="wbp_surcharge"><?php esc_html_e( 'Service Surcharge', 'woo-booking-pro' ); ?></label></th>
            <td>
                <input type="number" id="wbp_surcharge" name="wbp_surcharge" step="0.01" min="0" value="<?php echo esc_attr( $surcharge ); ?>">
                <p class="description"><?php esc_html_e( 'Extra fee for this service category.', 'woo-booking-pro' ); ?></p>
            </td>
        </tr>
        <?php
    }

    public static function save_category_surcharge( int $term_id ): void {
        if ( isset( $_POST['wbp_surcharge'] ) ) {
            update_term_meta( $term_id, 'wbp_surcharge', floatval( $_POST['wbp_surcharge'] ) );
        }
    }
}
