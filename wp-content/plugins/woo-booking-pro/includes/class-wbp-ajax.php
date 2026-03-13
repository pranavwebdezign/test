<?php
defined( 'ABSPATH' ) || exit;

class WBP_Ajax {

    public static function init(): void {
        $map = [
            'ajax_action'      => 'get_category',
            'ajax_action2'     => 'get_child_cats',
            'ajax_action1'     => 'get_products',
            'ajax_action5'     => 'get_product_meta',
            'ajax_action6'     => 'get_product_meta_new',
            'ajax_action7'     => 'add_to_cart',
            'ajax_action10'    => 'get_nationwide',
            'ajax_action13'    => 'get_other_service',
            'wbp_get_category'         => 'get_category',
            'wbp_get_child_cats'       => 'get_child_cats',
            'wbp_get_products'         => 'get_products',
            'wbp_get_product_meta'     => 'get_product_meta',
            'wbp_get_product_meta_new' => 'get_product_meta_new',
            'wbp_add_to_cart'          => 'add_to_cart',
            'wbp_get_nationwide'       => 'get_nationwide',
            'wbp_get_other_service'    => 'get_other_service',
        ];
        foreach ( $map as $action => $method ) {
            add_action( "wp_ajax_{$action}",        [ __CLASS__, $method ] );
            add_action( "wp_ajax_nopriv_{$action}", [ __CLASS__, $method ] );
        }
    }

    private static function send( $data ): void { wp_send_json( $data ); }

    // ────────────────────────────────────────────────
    // Build add-on HTML – supports all WC Product Add-ons types
    // ────────────────────────────────────────────────
    public static function build_addons_html( int $product_id, string $prefix = '' ): string {
        // Use WBP_Addons_Admin::get_product_addons() so field-name keys are always set
        $addons = class_exists('WBP_Addons_Admin') ? WBP_Addons_Admin::get_product_addons( $product_id ) : [];
        if ( empty( $addons ) ) return '';

        $currency = WBP_Settings::get( 'currency_symbol', '£' );
        $html = '';
        $i    = 0;

        foreach ( $addons as $group ) {
            $i++;
            $name   = esc_html( $group['name']        ?? '' );
            $desc   = esc_html( $group['description'] ?? '' );
            $type   = $group['type'] ?? 'radiobutton';
            $opts   = $group['options'] ?? [];
            // Use addon- prefix + field-name so WBP_Addons_Admin::add_cart_item_data() can read it
            $field_name = sanitize_title( $group['field-name'] ?? $group['name'] ?? '' );
            $name_a     = 'addon-' . $field_name;

            $html .= '<div class="wbp-addon-group">';
            $html .= '<h3 class="wbp-addon-title">' . $name . '</h3>';
            if ( $desc ) $html .= '<p class="wbp-addon-desc">' . $desc . '</p>';

            if ( in_array( $type, [ 'radiobutton', 'checkbox', 'select' ], true ) ) {

                if ( $type === 'select' ) {
                    $html .= '<select name="' . esc_attr($name_a) . '" class="wbp-addon-select wbp-addon-input" data-pid="' . $product_id . '">';
                    $html .= '<option value="">-- Select --</option>';
                    foreach ( $opts as $opt ) {
                        $price   = floatval( $opt['price'] ?? 0 );
                        $label   = esc_html( $opt['label'] ?? '' );
                        $display = $label . ( $price > 0 ? ' (+ ' . $currency . number_format($price,2) . ')' : '' );
                        $opt_loop++;
                    $opt_val = sanitize_title( ($opt['label']??'') . '-' . $opt_loop );
                    $html .= '<option value="' . esc_attr($opt_val) . '" data-price="' . $price . '" data-title="' . esc_attr($opt['label']??'') . '">' . $display . '</option>';
                    }
                    $html .= '</select>';
                } else {
                    $input_type = ( $type === 'checkbox' ) ? 'checkbox' : 'radio';
                    $html .= '<div class="wbp-addon-options">';
                    $j = 0;
                    foreach ( $opts as $opt ) {
                        $j++;
                        $price   = floatval( $opt['price'] ?? 0 );
                        $label   = esc_html( $opt['label'] ?? '' );
                        $ctrl_id = 'wbp_addon_' . $prefix . $product_id . '_' . $i . '_' . $j;
                        $html .= '<div class="wbp-addon-option">';
                        $html .= '<input type="' . $input_type . '" id="' . $ctrl_id . '" '
                               . 'name="' . esc_attr($name_a) . '" '
                               . 'value="' . $price . '" '
                               . 'data-title="' . esc_attr($opt['label']??'') . '" '
                               . 'pid="' . $product_id . '" '
                               . 'class="wbp-addon-input">';
                        $html .= '<label for="' . $ctrl_id . '" class="wbp-addon-label">'
                               . '<span class="wbp-addon-check"></span>'
                               . '<span class="wbp-addon-name">' . $label . '</span>';
                        if ( $price > 0 ) $html .= '<span class="wbp-addon-price">+ ' . $currency . number_format($price,2) . '</span>';
                        $html .= '</label></div>';
                    }
                    $html .= '</div>';
                }

            } elseif ( in_array( $type, ['custom','custom_email','custom_digits_only','custom_letters_only','custom_letters_or_digits'], true ) ) {
                $itype = ( $type === 'custom_email' ) ? 'email' : 'text';
                $pat   = '';
                if ( $type === 'custom_digits_only'  ) $pat = ' pattern="[0-9]*"';
                if ( $type === 'custom_letters_only' ) $pat = ' pattern="[A-Za-z]*"';
                $min   = intval( $opts[0]['min'] ?? 0 );
                $max   = intval( $opts[0]['max'] ?? 0 );
                $html .= '<input type="' . $itype . '" name="' . esc_attr($name_a) . '" class="wbp-addon-text"'
                       . ( $min ? ' minlength="'.$min.'"' : '' )
                       . ( $max ? ' maxlength="'.$max.'"' : '' )
                       . $pat . '>';

            } elseif ( $type === 'custom_textarea' ) {
                $min   = intval( $opts[0]['min'] ?? 0 );
                $max   = intval( $opts[0]['max'] ?? 0 );
                $html .= '<textarea name="' . esc_attr($name_a) . '" class="wbp-addon-textarea" rows="3"'
                       . ( $min ? ' minlength="'.$min.'"' : '' )
                       . ( $max ? ' maxlength="'.$max.'"' : '' )
                       . '></textarea>';

            } elseif ( $type === 'custom_price' ) {
                $min   = floatval( $opts[0]['min'] ?? 0 );
                $max   = floatval( $opts[0]['max'] ?? 0 );
                $html .= '<div style="display:flex;align-items:center;gap:6px">'
                       . '<span>' . esc_html($currency) . '</span>'
                       . '<input type="number" name="' . esc_attr($name_a) . '" class="wbp-addon-customprice" step="0.01" min="' . ($min?:0) . '"' . ($max?' max="'.$max.'"':'') . '>'
                       . '</div>';

            } elseif ( $type === 'input_multiplier' ) {
                $price = floatval( $opts[0]['price'] ?? 0 );
                $min   = intval(   $opts[0]['min']   ?? 1 );
                $max   = intval(   $opts[0]['max']   ?? 0 );
                $html .= '<input type="number" name="' . esc_attr($name_a) . '" class="wbp-addon-multiplier" data-price="' . $price . '" value="' . $min . '" min="' . $min . '"' . ($max?' max="'.$max.'"':'') . '>';
                if ( $price > 0 ) $html .= '<span class="wbp-addon-price"> × ' . $currency . number_format($price,2) . ' each</span>';
            }

            $html .= '</div>'; // .wbp-addon-group
        }
        return $html;
    }

    private static function product_image( int $id ): string {
        $img = get_the_post_thumbnail_url( $id, 'medium' );
        if ( $img ) return $img;
        $gallery = get_post_meta( $id, '_product_image_gallery', true );
        if ( $gallery ) {
            $ids = explode( ',', $gallery );
            $url = wp_get_attachment_url( (int) $ids[0] );
            if ( $url ) return $url;
        }
        return WC_PLACEHOLDER_IMG_SRC;
    }

    public static function get_category(): void {
        $cat_id = absint( $_POST['cat_id'] ?? 0 );
        $term   = get_term_by( 'id', $cat_id, 'product_cat' );
        if ( !$term ) { wp_send_json_error('Invalid category'); }
        $thumb_id  = get_term_meta( $cat_id, 'thumbnail_id', true );
        $thumb_url = $thumb_id ? wp_get_attachment_url( $thumb_id ) : '';
        self::send([
            'term_id'     => $term->term_id,
            'name'        => $term->name,
            'description' => $term->description,
            'surcharge'   => floatval( get_term_meta( $cat_id, 'wbp_surcharge', true ) ),
            'thumb'       => $thumb_url,
        ]);
    }

    public static function get_child_cats(): void {
        $cat_id   = absint( $_POST['cat_name'] ?? 0 );
        $children = get_term_children( $cat_id, 'product_cat' );
        if ( is_wp_error($children) || empty($children) ) { self::send(false); return; }
        $html = '';
        foreach ( $children as $child_id ) {
            $term = get_term_by( 'id', $child_id, 'product_cat' );
            if ( !$term ) continue;
            $thumb_id  = get_term_meta( $child_id, 'thumbnail_id', true );
            $thumb_url = $thumb_id ? wp_get_attachment_url($thumb_id) : '';
            $html .= '<div class="wbp-card-option product-selection-tabs product-selection-tab-cat">'
                   . '<input type="radio" id="control1_' . $term->term_id . '" '
                   . 'name="select-1-1" id_value="' . $term->term_id . '" '
                   . 'value="' . esc_attr($term->name) . '">'
                   . '<label for="control1_' . $term->term_id . '">';
            if ( $thumb_url ) $html .= '<div class="wbp-card-img"><img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($term->name) . '"></div>';
            $html .= '<h2>' . esc_html($term->name) . '</h2>'
                   . '<p>'  . wp_kses_post($term->description) . '</p>'
                   . '</label></div>';
        }
        self::send($html);
    }

    public static function get_products(): void {
        $cat_id = absint( $_POST['cat_child_name'] ?? 0 );
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'tax_query'      => [[ 'taxonomy'=>'product_cat','field'=>'term_id','terms'=>$cat_id ]],
        ];
        $loop     = new WP_Query($args);
        $design   = '';
        $first_id = 0;
        $currency = WBP_Settings::get('currency_symbol','£');

        while ( $loop->have_posts() ) {
            $loop->the_post();
            global $product;
            $id    = get_the_ID();
            if (!$first_id) $first_id = $id;
            $title = get_the_title();
            $short = wp_trim_words( wp_strip_all_tags($product->get_short_description()), 20 );
            $price = $product->get_price();
            $img   = self::product_image($id);

            $design .= '<div class="wbp-card-option product-selection-tabs product-selection-tabs-product">'
                     . '<input type="radio" id="control_' . $id . '" class="product-input" '
                     . 'name="product-select" value="' . $id . '" '
                     . 'data-amount="' . esc_attr($price) . '" '
                     . 'data-title="'  . esc_attr($title) . '">'
                     . '<label for="control_' . $id . '">'
                     . '<div class="wbp-card-img"><img src="' . esc_url($img) . '" alt="' . esc_attr($title) . '"></div>'
                     . '<h2>' . esc_html($title) . '</h2>'
                     . '<p>'  . esc_html($short)  . '</p>'
                     . '<p class="wbp-price-tag">' . $currency . esc_html($price) . '</p>'
                     . '</label></div>';
        }
        wp_reset_postdata();

        $design_meta = $first_id ? self::build_addons_html($first_id) : '';
        self::send([ 'a'=>$design, 'b'=>$design_meta, 'c'=>$first_id, 'd'=>'' ]);
    }

    public static function get_product_meta(): void {
        $pid = absint($_POST['product_id'] ?? 0);
        self::send([ 'a'=> self::build_addons_html($pid), 'b'=>$pid, 'c'=>'' ]);
    }

    public static function get_product_meta_new(): void {
        $pid = absint($_POST['product_id'] ?? 0);
        self::send([ 'a'=> self::build_addons_html($pid,'n'), 'b'=>$pid, 'c'=>'' ]);
    }

    public static function add_to_cart(): void {
        $product_id    = absint( $_POST['productid']   ?? 0 );
        $price         = floatval( $_POST['price']     ?? 0 );
        $meta_data     = $_POST['meta_data']            ?? [];
        $pickup_date   = sanitize_text_field( $_POST['pickupdate']   ?? '' );
        $delivery_date = sanitize_text_field( $_POST['deliverydate'] ?? '' );
        $order_notes   = sanitize_textarea_field( $_POST['order_notes'] ?? '' );
        $country_iso   = strtoupper( sanitize_text_field( $_POST['country'] ?? '' ) );

        if ( !$product_id || !wc_get_product($product_id) ) { wp_send_json_error('Invalid product'); }

        WC()->session->set('wbp_booking_meta',  $meta_data);
        WC()->session->set('wbp_pickup_date',   $pickup_date);
        WC()->session->set('wbp_delivery_date', $delivery_date);
        WC()->session->set('wbp_order_notes',   $order_notes);
        WC()->session->set('wbp_country',       $country_iso);

        WC()->cart->add_to_cart($product_id, 1, 0, [], ['custom_price'=>$price]);
        self::send(wc_get_cart_url());
    }

    public static function get_nationwide(): void {
        $cat_name = sanitize_text_field($_POST['cat_name_nation'] ?? '');
        $args = [
            'post_type'=>'product','posts_per_page'=>-1,
            'post_status'=>'publish','product_cat'=>$cat_name,
        ];
        $loop     = new WP_Query($args);
        $html     = '';
        $first_id = 0;
        $currency = WBP_Settings::get('currency_symbol','£');

        while ($loop->have_posts()) {
            $loop->the_post();
            global $product;
            $id    = get_the_ID();
            if (!$first_id) $first_id = $id;
            $title = get_the_title();
            $price = $product->get_price();

            $html .= '<div class="wbp-addon-option wbp-nationwide-item">'
                   . '<input type="checkbox" id="control_' . $id . '" class="product-input wbp-addon-input" '
                   . 'name="product-select" value="' . $id . '" '
                   . 'data-amount="' . esc_attr($price) . '" '
                   . 'data-title="'  . esc_attr($title) . '">'
                   . '<label for="control_' . $id . '" class="wbp-addon-label">'
                   . '<span class="wbp-addon-check"></span>'
                   . '<span class="wbp-addon-name">' . esc_html($title) . '</span>'
                   . '<span class="wbp-addon-price">' . $currency . esc_html($price) . '</span>'
                   . '</label></div>';
        }
        wp_reset_postdata();

        $addons_html = $first_id ? self::build_addons_html($first_id) : '';
        self::send(['a'=>$html,'b'=>$addons_html,'c'=>$first_id]);
    }

    public static function get_other_service(): void {
        $post_id = absint($_POST['cat_name_nation'] ?? 0);
        if (!$post_id) { wp_send_json_error('Invalid post'); }
        self::send([
            'title'   => get_the_title($post_id),
            'content' => apply_filters('the_content', get_post($post_id)->post_content ?? ''),
        ]);
    }
}
