<?php
defined( 'ABSPATH' ) || exit;

/**
 * WBP_Addons_Admin — full Product Add-Ons replacement.
 * UI identical to woocommerce-product-addons plugin.
 */
class WBP_Addons_Admin {

    public static function init(): void {
        add_action( 'woocommerce_product_write_panel_tabs', [ __CLASS__, 'tab' ] );
        add_action( 'woocommerce_product_data_panels',      [ __CLASS__, 'panel' ] );
        add_action( 'woocommerce_process_product_meta',     [ __CLASS__, 'save' ], 1 );
        add_action( 'admin_enqueue_scripts',                [ __CLASS__, 'admin_assets' ] );

        add_filter( 'woocommerce_add_to_cart_validation',    [ __CLASS__, 'validate_cart_item' ], 10, 3 );
        add_filter( 'woocommerce_add_cart_item_data',        [ __CLASS__, 'add_cart_item_data' ], 10, 2 );
        add_filter( 'woocommerce_add_cart_item',             [ __CLASS__, 'apply_addon_prices' ], 20 );
        add_filter( 'woocommerce_get_cart_item_from_session',[ __CLASS__, 'cart_item_from_session' ], 20, 2 );
        add_filter( 'woocommerce_get_item_data',             [ __CLASS__, 'display_cart_item_data' ], 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'save_order_item_meta' ], 10, 3 );
    }

    /* ─── Assets ─────────────────────────────────────── */

    public static function admin_assets( string $hook ): void {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'product' ) return;

        wp_enqueue_style(
            'wbp-addons-admin',
            WBP_PLUGIN_URL . 'assets/css/addons-admin.css',
            [ 'woocommerce_admin_styles' ],
            WBP_VERSION
        );
        wp_enqueue_script( 'jquery-ui-sortable' );
    }

    /* ─── Tab ────────────────────────────────────────── */

    public static function tab(): void { ?>
        <li class="addons_tab product_addons">
            <a href="#wbp_product_addons_data">
                <span><?php esc_html_e( 'Add-ons', 'woo-booking-pro' ); ?></span>
            </a>
        </li>
    <?php }

    /* ─── Panel ──────────────────────────────────────── */

    public static function panel(): void {
        global $post;
        $product        = wc_get_product( $post );
        $exists         = (bool) $product->get_id();
        $product_addons = array_filter( (array) $product->get_meta( '_product_addons' ) );
        $exclude_global = $product->get_meta( '_product_addons_exclude_global' );
        $currency       = get_woocommerce_currency_symbol();
        ?>
        <div id="wbp_product_addons_data" class="panel woocommerce_options_panel wc-metaboxes-wrapper">

            <p class="toolbar wbp-toolbar-openclose" style="padding:10px 12px;display:none;">
                <a href="#" class="wbp-close-all"><?php esc_html_e( 'Close all', 'woo-booking-pro' ); ?></a>
                &nbsp;/&nbsp;
                <a href="#" class="wbp-expand-all"><?php esc_html_e( 'Expand all', 'woo-booking-pro' ); ?></a>
            </p>

            <div class="woocommerce_product_addons wc-metaboxes">
                <?php
                $loop = 0;
                foreach ( $product_addons as $addon ) {
                    self::render_addon( $loop, $addon, $currency );
                    $loop++;
                }
                ?>
            </div>

            <div class="toolbar" style="padding:10px 12px;overflow:hidden;">
                <button type="button" class="button wbp-add-addon">
                    <?php esc_html_e( 'New add-on', 'woo-booking-pro' ); ?>
                </button>
                <div style="float:right;">
                    <button type="button" class="button wbp-import-btn">
                        <?php esc_html_e( 'Import', 'woo-booking-pro' ); ?>
                    </button>
                    <button type="button" class="button wbp-export-btn">
                        <?php esc_html_e( 'Export', 'woo-booking-pro' ); ?>
                    </button>
                </div>
                <textarea name="export_product_addon" class="wbp-export-area" cols="20" rows="5"
                          style="width:100%;display:none;margin:10px 0 0"
                          readonly><?php echo esc_textarea( serialize( $product_addons ) ); ?></textarea>
                <textarea name="import_product_addon" class="wbp-import-area" cols="20" rows="5"
                          style="width:100%;display:none;margin:10px 0 0"
                          placeholder="<?php esc_attr_e( 'Paste exported add-on data here then save.', 'woo-booking-pro' ); ?>"></textarea>
            </div>

            <?php if ( $exists ) : ?>
            <div class="options_group">
                <p class="form-field">
                    <label for="wbp_exclude_global"><?php esc_html_e( 'Global Addon Exclusion', 'woo-booking-pro' ); ?></label>
                    <input id="wbp_exclude_global" name="_product_addons_exclude_global"
                           class="checkbox" type="checkbox" value="1" <?php checked( $exclude_global, 1 ); ?>>
                    <span class="description"><?php esc_html_e( 'Exclude this product from all Global Add-ons', 'woo-booking-pro' ); ?></span>
                </p>
            </div>
            <?php endif; ?>

        </div>

        <?php /* ── JS template: blank option row ── */ ?>
        <script type="text/html" id="wbp-tpl-option">
            <?php self::render_option( 'LOOPINDEX', [ 'label' => '', 'price' => '0.00', 'min' => '', 'max' => '' ] ); ?>
        </script>

        <?php /* ── JS template: blank addon group ── */ ?>
        <script type="text/html" id="wbp-tpl-addon">
            <?php self::render_addon( 'LOOPINDEX', [
                'name' => '', 'description' => '', 'type' => 'checkbox', 'required' => 0,
                'options' => [ [ 'label' => '', 'price' => '0.00', 'min' => '', 'max' => '' ] ],
            ], $currency ); ?>
        </script>

        <?php self::render_js(); ?>
        <?php
    }

    /* ─── Render one addon group ─────────────────────── */

    /**
     * @param int|string $loop  int for real groups, string 'LOOPINDEX' for JS template
     */
    private static function render_addon( $loop, array $addon, string $currency ): void {
        $name     = $addon['name']        ?? '';
        $desc     = $addon['description'] ?? '';
        $type     = $addon['type']        ?? 'checkbox';
        $required = $addon['required']    ?? 0;
        $options  = $addon['options']     ?? [];
        $li       = esc_attr( $loop );
        ?>
        <div class="woocommerce_product_addon wc-metabox closed">
            <h3>
                <button type="button" class="remove_addon button">
                    <?php esc_html_e( 'Remove', 'woo-booking-pro' ); ?>
                </button>
                <div class="handlediv" title="<?php esc_attr_e( 'Click to toggle', 'woo-booking-pro' ); ?>"></div>
                <strong>
                    <?php esc_html_e( 'Group', 'woo-booking-pro' ); ?>
                    <span class="group_name"><?php echo $name ? '"' . esc_html( $name ) . '"' : ''; ?></span>
                    &mdash;
                </strong>
                <select name="product_addon_type[<?php echo $li; ?>]" class="product_addon_type">
                    <option <?php selected( 'custom_price',           $type ); ?> value="custom_price"><?php esc_html_e( 'Additional custom price input', 'woo-booking-pro' ); ?></option>
                    <option <?php selected( 'input_multiplier',       $type ); ?> value="input_multiplier"><?php esc_html_e( 'Additional price multiplier', 'woo-booking-pro' ); ?></option>
                    <option <?php selected( 'checkbox',               $type ); ?> value="checkbox"><?php esc_html_e( 'Checkboxes', 'woo-booking-pro' ); ?></option>
                    <option <?php selected( 'custom_textarea',        $type ); ?> value="custom_textarea"><?php esc_html_e( 'Custom input (textarea)', 'woo-booking-pro' ); ?></option>
                    <optgroup label="<?php esc_attr_e( 'Custom input (text)', 'woo-booking-pro' ); ?>">
                        <option <?php selected( 'custom',                    $type ); ?> value="custom"><?php esc_html_e( 'Any text', 'woo-booking-pro' ); ?></option>
                        <option <?php selected( 'custom_email',              $type ); ?> value="custom_email"><?php esc_html_e( 'Email address', 'woo-booking-pro' ); ?></option>
                        <option <?php selected( 'custom_letters_only',       $type ); ?> value="custom_letters_only"><?php esc_html_e( 'Only letters', 'woo-booking-pro' ); ?></option>
                        <option <?php selected( 'custom_letters_or_digits',  $type ); ?> value="custom_letters_or_digits"><?php esc_html_e( 'Only letters and numbers', 'woo-booking-pro' ); ?></option>
                        <option <?php selected( 'custom_digits_only',        $type ); ?> value="custom_digits_only"><?php esc_html_e( 'Only numbers', 'woo-booking-pro' ); ?></option>
                    </optgroup>
                    <option <?php selected( 'radiobutton', $type ); ?> value="radiobutton"><?php esc_html_e( 'Radio buttons', 'woo-booking-pro' ); ?></option>
                    <option <?php selected( 'select',      $type ); ?> value="select"><?php esc_html_e( 'Select box', 'woo-booking-pro' ); ?></option>
                </select>
                <input type="hidden" name="product_addon_position[<?php echo $li; ?>]"
                       class="product_addon_position" value="<?php echo $li; ?>">
            </h3>
            <table cellpadding="0" cellspacing="0" class="wc-metabox-content">
                <tbody>
                    <tr>
                        <td class="addon_name" width="50%">
                            <label><?php esc_html_e( 'Name', 'woo-booking-pro' ); ?></label>
                            <input type="text"
                                   name="product_addon_name[<?php echo $li; ?>]"
                                   value="<?php echo esc_attr( $name ); ?>">
                        </td>
                        <td class="addon_required" width="50%">
                            <label><?php esc_html_e( 'Required fields?', 'woo-booking-pro' ); ?></label>
                            <input type="checkbox"
                                   name="product_addon_required[<?php echo $li; ?>]"
                                   value="1" <?php checked( $required, 1 ); ?>>
                        </td>
                    </tr>
                    <tr>
                        <td class="addon_description" colspan="2">
                            <label><?php esc_html_e( 'Description', 'woo-booking-pro' ); ?></label>
                            <textarea cols="20" rows="3"
                                      name="product_addon_description[<?php echo $li; ?>]"><?php echo esc_textarea( $desc ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td class="data" colspan="3">
                            <table cellspacing="0" cellpadding="0">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Label', 'woo-booking-pro' ); ?></th>
                                        <th class="price_column"><?php esc_html_e( 'Price', 'woo-booking-pro' ); ?></th>
                                        <th class="minmax_column"><span class="column-title"><?php esc_html_e( 'Min / Max', 'woo-booking-pro' ); ?></span></th>
                                        <th width="1%"></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <td colspan="4">
                                            <button type="button" class="add_addon_option button">
                                                <?php esc_html_e( 'New Option', 'woo-booking-pro' ); ?>
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    <?php foreach ( $options as $option ) self::render_option( $loop, $option ); ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /* ─── Render one option row ──────────────────────── */

    private static function render_option( $loop, array $option ): void {
        $li = esc_attr( $loop );
        ?>
        <tr>
            <td>
                <input type="text"
                       name="product_addon_option_label[<?php echo $li; ?>][]"
                       value="<?php echo esc_attr( $option['label'] ?? '' ); ?>"
                       placeholder="<?php esc_attr_e( 'Default Label', 'woo-booking-pro' ); ?>">
            </td>
            <td class="price_column">
                <input type="text"
                       name="product_addon_option_price[<?php echo $li; ?>][]"
                       value="<?php echo esc_attr( wc_format_localized_price( $option['price'] ?? 0 ) ); ?>"
                       placeholder="0.00" class="wc_input_price">
            </td>
            <td class="minmax_column">
                <input type="number"
                       name="product_addon_option_min[<?php echo $li; ?>][]"
                       value="<?php echo esc_attr( $option['min'] ?? '' ); ?>"
                       placeholder="Min" min="0" step="any">
                <input type="number"
                       name="product_addon_option_max[<?php echo $li; ?>][]"
                       value="<?php echo esc_attr( $option['max'] ?? '' ); ?>"
                       placeholder="Max" min="0" step="any">
            </td>
            <td class="actions" width="1%">
                <button type="button" class="remove_addon_option button">x</button>
            </td>
        </tr>
        <?php
    }

    /* ─── JS ─────────────────────────────────────────── */

    private static function render_js(): void { ?>
        <script type="text/javascript">
        jQuery(function($){

            var $panel   = $('#wbp_product_addons_data');
            var $list    = $panel.find('.woocommerce_product_addons');

            /* Read templates from <script type="text/html"> — safe, no PHP string escaping issues */
            var optionTpl = $('#wbp-tpl-option').html();
            var addonTpl  = $('#wbp-tpl-addon').html();

            /* ── Update header label when name changes ── */
            $panel.on('input change', '.addon_name input', function(){
                var val = $(this).val();
                $(this).closest('.woocommerce_product_addon').find('span.group_name')
                       .text( val ? '"' + val + '"' : '' );
            });

            /* ── Type dropdown → toggle columns ── */
            function applyTypeUI( $addon ) {
                var val = $addon.find('.product_addon_type').val();
                if ( ! val ) return;

                var textTypes = ['custom','custom_price','custom_textarea','input_multiplier',
                                 'custom_letters_only','custom_digits_only',
                                 'custom_letters_or_digits','custom_email'];

                if ( textTypes.indexOf(val) !== -1 ) {
                    $addon.find('td.minmax_column, th.minmax_column').show();
                } else {
                    $addon.find('td.minmax_column, th.minmax_column').hide();
                }

                $addon.find( val === 'custom_price'
                    ? 'td.price_column, th.price_column'
                    : 'td.price_column, th.price_column'
                )[ val === 'custom_price' ? 'hide' : 'show' ]();

                var titles = {
                    'custom_price':             '<?php echo esc_js( __( 'Min / max price',      'woo-booking-pro' ) ); ?>',
                    'input_multiplier':         '<?php echo esc_js( __( 'Min / max multiplier', 'woo-booking-pro' ) ); ?>',
                    'custom':                   '<?php echo esc_js( __( 'Min / max characters', 'woo-booking-pro' ) ); ?>',
                    'custom_textarea':          '<?php echo esc_js( __( 'Min / max characters', 'woo-booking-pro' ) ); ?>',
                    'custom_email':             '<?php echo esc_js( __( 'Min / max characters', 'woo-booking-pro' ) ); ?>',
                    'custom_letters_only':      '<?php echo esc_js( __( 'Min / max characters', 'woo-booking-pro' ) ); ?>',
                    'custom_digits_only':       '<?php echo esc_js( __( 'Min / max characters', 'woo-booking-pro' ) ); ?>',
                    'custom_letters_or_digits': '<?php echo esc_js( __( 'Min / max characters', 'woo-booking-pro' ) ); ?>'
                };
                var title = titles[val] || '<?php echo esc_js( __( 'Min / max', 'woo-booking-pro' ) ); ?>';
                $addon.find('th.minmax_column .column-title').text( title );

                /* disable remove-option btn if only 1 row */
                var $btns = $addon.find('button.remove_addon_option');
                $btns[ $btns.length < 2 ? 'attr' : 'removeAttr' ]('disabled', 'disabled');
            }

            $panel.on('change', 'select.product_addon_type', function(){
                applyTypeUI( $(this).closest('.woocommerce_product_addon') );
            });

            /* ── Close all / Expand all ──
               WooCommerce's own meta-boxes.js handles h3 click toggling for
               .wc-metabox elements — we must NOT add our own h3 handler or
               they will conflict and cancel each other. ── */
            $panel.on('click', '.wbp-close-all', function(e){
                e.preventDefault();
                $list.find('.woocommerce_product_addon').each(function(){
                    $(this).addClass('closed');
                    $(this).find('.wc-metabox-content').hide();
                });
            });
            $panel.on('click', '.wbp-expand-all', function(e){
                e.preventDefault();
                $list.find('.woocommerce_product_addon').each(function(){
                    $(this).removeClass('closed');
                    $(this).find('.wc-metabox-content').show();
                });
            });

            /* ── Add option row ── */
            $panel.on('click', '.add_addon_option', function(){
                var $addon = $(this).closest('.woocommerce_product_addon');
                var loop   = $addon.index('.woocommerce_product_addon');
                var html   = optionTpl.replace(/LOOPINDEX/g, loop);
                $addon.find('.data tbody').append( html );
                applyTypeUI( $addon );
                return false;
            });

            /* ── Remove option row ── */
            $panel.on('click', '.remove_addon_option', function(){
                if ( ! confirm('<?php echo esc_js( __( 'Are you sure you want delete this option?', 'woo-booking-pro' ) ); ?>') ) return false;
                var $addon = $(this).closest('.woocommerce_product_addon');
                $(this).closest('tr').remove();
                applyTypeUI( $addon );
                return false;
            });

            /* ── Add new addon group ── */
            $panel.on('click', '.wbp-add-addon', function(){
                var loop  = $list.find('.woocommerce_product_addon').length;
                var html  = addonTpl.replace(/LOOPINDEX/g, loop);
                $list.append( html );

                var $new = $list.find('.woocommerce_product_addon').last();
                $new.removeClass('closed').find('.wc-metabox-content').show();
                applyTypeUI( $new );
                initOptionSort( $new );

                if ( $list.find('.woocommerce_product_addon').length > 1 ) {
                    $panel.find('.wbp-toolbar-openclose').show();
                }
                return false;
            });

            /* ── Remove addon group ── */
            $panel.on('click', '.remove_addon', function(){
                if ( ! confirm('<?php echo esc_js( __( 'Are you sure you want remove this add-on?', 'woo-booking-pro' ) ); ?>') ) return false;
                $(this).closest('.woocommerce_product_addon').remove();
                reindex();
                if ( $list.find('.woocommerce_product_addon').length <= 1 ) {
                    $panel.find('.wbp-toolbar-openclose').hide();
                }
                return false;
            });

            /* ── Export ── */
            $panel.on('click', '.wbp-export-btn', function(){
                $panel.find('.wbp-import-area').hide();
                $panel.find('.wbp-export-area').slideToggle(500, function(){ $(this).select(); });
                return false;
            });

            /* ── Import ── */
            $panel.on('click', '.wbp-import-btn', function(){
                $panel.find('.wbp-export-area').hide();
                $panel.find('.wbp-import-area').slideToggle(500, function(){ $(this).val(''); });
                return false;
            });

            /* ── Sortable: drag groups by h3 handle ── */
            $list.sortable({
                items:             '.woocommerce_product_addon',
                cursor:            'move',
                axis:              'y',
                handle:            'h3',
                scrollSensitivity: 40,
                helper:  function(e, ui){ return ui; },
                start:   function(e, ui){ ui.item.css('border-style','dashed'); },
                stop:    function(e, ui){ ui.item.removeAttr('style'); reindex(); }
            });

            /* ── Sortable: drag option rows ── */
            function initOptionSort( $scope ) {
                $scope.find('.data table tbody').sortable({
                    items:  'tr', cursor: 'move', axis: 'y', scrollSensitivity: 40,
                    helper: function(e, ui){ ui.children().each(function(){ $(this).width($(this).width()); }); return ui; },
                    start:  function(e, ui){ ui.item.css('background-color','#f6f6f6'); },
                    stop:   function(e, ui){ ui.item.removeAttr('style'); }
                });
            }
            initOptionSort( $list );

            /* ── Reindex hidden position inputs ── */
            function reindex(){
                $list.find('.woocommerce_product_addon').each(function(i){
                    $(this).find('.product_addon_position').val(i);
                });
            }

            /* ── Init on load ── */
            $list.find('.woocommerce_product_addon').each(function(){
                applyTypeUI( $(this) );
            });

            if ( $list.find('.woocommerce_product_addon').length > 1 ) {
                $panel.find('.wbp-toolbar-openclose').show();
            }

        });
        </script>
        <?php
    }

    /* ─── Save ───────────────────────────────────────── */

    public static function save( int $post_id ): void {
        if ( ! isset( $_POST['product_addon_name'] ) ) return;

        $names      = array_values( (array) ( $_POST['product_addon_name']         ?? [] ) );
        $descs      = array_values( (array) ( $_POST['product_addon_description']  ?? [] ) );
        $types      = array_values( (array) ( $_POST['product_addon_type']         ?? [] ) );
        $positions  = array_values( (array) ( $_POST['product_addon_position']     ?? [] ) );
        $requireds  = (array) ( $_POST['product_addon_required']     ?? [] );
        $opt_labels = (array) ( $_POST['product_addon_option_label'] ?? [] );
        $opt_prices = (array) ( $_POST['product_addon_option_price'] ?? [] );
        $opt_mins   = (array) ( $_POST['product_addon_option_min']   ?? [] );
        $opt_maxs   = (array) ( $_POST['product_addon_option_max']   ?? [] );

        $text_types = [ 'custom','custom_email','custom_textarea','custom_price',
                        'custom_digits_only','custom_letters_only',
                        'custom_letters_or_digits','input_multiplier' ];

        $addons  = [];
        $counter = 0;

        for ( $i = 0, $n = count( $names ); $i < $n; $i++ ) {
            $name = sanitize_text_field( wp_unslash( $names[ $i ] ) );
            if ( $name === '' ) continue;

            $type = sanitize_key( $types[ $i ] ?? 'checkbox' );
            $counter++;

            $options = [];
            foreach ( (array) ( $opt_labels[ $i ] ?? [] ) as $j => $raw_label ) {
                $label = sanitize_text_field( wp_unslash( $raw_label ) );
                if ( $label === '' ) continue;
                $options[] = [
                    'label' => $label,
                    'price' => wc_format_decimal( sanitize_text_field( wp_unslash( $opt_prices[ $i ][ $j ] ?? 0 ) ) ),
                    'min'   => sanitize_text_field( wp_unslash( $opt_mins[ $i ][ $j ] ?? '' ) ),
                    'max'   => sanitize_text_field( wp_unslash( $opt_maxs[ $i ][ $j ] ?? '' ) ),
                ];
            }

            if ( empty( $options ) && ! in_array( $type, $text_types, true ) ) continue;
            if ( empty( $options ) ) $options[] = [ 'label' => $name, 'price' => 0, 'min' => '', 'max' => '' ];

            $addons[] = [
                'name'        => $name,
                'description' => wp_kses_post( wp_unslash( $descs[ $i ] ?? '' ) ),
                'type'        => $type,
                'required'    => isset( $requireds[ $i ] ) ? 1 : 0,
                'position'    => absint( $positions[ $i ] ?? $i ),
                'field-name'  => sanitize_title( $name ) . '-' . $counter,
                'options'     => $options,
            ];
        }

        usort( $addons, fn( $a, $b ) => $a['position'] <=> $b['position'] );

        $product = wc_get_product( $post_id );
        if ( $product ) {
            $product->update_meta_data( '_product_addons', $addons );
            $product->update_meta_data( '_product_addons_exclude_global',
                isset( $_POST['_product_addons_exclude_global'] ) ? 1 : 0 );
            $product->save();
        }
    }

    /* ─── Cart: validate ─────────────────────────────── */

    public static function validate_cart_item( bool $passed, int $product_id, int $qty ): bool {
        foreach ( self::get_product_addons( $product_id ) as $addon ) {
            if ( empty( $addon['required'] ) ) continue;
            $field = 'addon-' . sanitize_title( $addon['field-name'] ?? $addon['name'] );
            $value = $_POST[ $field ] ?? '';
            if ( $value === '' || $value === [] ) {
                wc_add_notice( sprintf( __( '"%s" is a required field.', 'woo-booking-pro' ), esc_html( $addon['name'] ) ), 'error' );
                return false;
            }
        }
        return $passed;
    }

    /* ─── Cart: store ────────────────────────────────── */

    public static function add_cart_item_data( array $meta, int $product_id ): array {
        $addons = self::get_product_addons( $product_id );
        if ( empty( $addons ) ) return $meta;
        if ( empty( $meta['addons'] ) ) $meta['addons'] = [];
        foreach ( $addons as $addon ) {
            $field = 'addon-' . sanitize_title( $addon['field-name'] ?? $addon['name'] );
            $raw   = $_POST[ $field ] ?? '';
            $value = is_array( $raw )
                ? array_map( 'sanitize_text_field', wp_unslash( $raw ) )
                : sanitize_text_field( wp_unslash( $raw ) );
            if ( $value === '' || $value === [] ) continue;
            $data = self::process_addon_value( $addon, $value );
            if ( ! empty( $data ) ) $meta['addons'] = array_merge( $meta['addons'], $data );
        }
        return $meta;
    }

    private static function process_addon_value( array $addon, $value ): array {
        $data = [];
        switch ( $addon['type'] ) {
            case 'radiobutton':
            case 'checkbox':
                $vals = is_array( $value ) ? $value : [ $value ];
                foreach ( $addon['options'] as $opt ) {
                    if ( in_array( sanitize_title( $opt['label'] ), array_map( 'sanitize_title', $vals ), true ) )
                        $data[] = [ 'name' => $addon['name'], 'value' => $opt['label'], 'price' => (float) ( $opt['price'] ?? 0 ) ];
                }
                break;
            case 'select':
                $loop = 0;
                foreach ( $addon['options'] as $opt ) {
                    $loop++;
                    if ( sanitize_title( $opt['label'] . '-' . $loop ) === $value ) {
                        $data[] = [ 'name' => $addon['name'], 'value' => $opt['label'], 'price' => (float) ( $opt['price'] ?? 0 ) ];
                        break;
                    }
                }
                break;
            case 'custom_price':
                $p = (float) sanitize_text_field( $value );
                if ( $p >= 0 ) $data[] = [ 'name' => $addon['name'], 'value' => $p, 'price' => $p, 'display' => strip_tags( wc_price( $p ) ) ];
                break;
            case 'input_multiplier':
                $qty  = absint( $value );
                $unit = (float) ( $addon['options'][0]['price'] ?? 0 );
                if ( $qty > 0 ) $data[] = [ 'name' => $addon['name'], 'value' => $qty, 'price' => $qty * $unit ];
                break;
            default:
                $data[] = [ 'name' => $addon['name'], 'value' => wp_kses_post( $value ), 'price' => (float) ( $addon['options'][0]['price'] ?? 0 ) ];
                break;
        }
        return $data;
    }

    /* ─── Cart: price, session, display ─────────────── */

    public static function apply_addon_prices( array $item ): array {
        if ( empty( $item['addons'] ) ) return $item;
        $price = (float) $item['data']->get_price( 'edit' );
        foreach ( $item['addons'] as $a ) { if ( ! empty( $a['price'] ) ) $price += (float) $a['price']; }
        $item['data']->set_price( $price );
        return $item;
    }

    public static function cart_item_from_session( array $item, array $values ): array {
        if ( ! empty( $values['addons'] ) ) { $item['addons'] = $values['addons']; $item = self::apply_addon_prices( $item ); }
        return $item;
    }

    public static function display_cart_item_data( array $item_data, array $cart_item ): array {
        if ( empty( $cart_item['addons'] ) ) return $item_data;
        foreach ( $cart_item['addons'] as $a ) {
            $name = $a['name'];
            if ( ! empty( $a['price'] ) && $a['price'] > 0 ) $name .= ' (+' . strip_tags( wc_price( $a['price'] ) ) . ')';
            $item_data[] = [ 'name' => $name, 'value' => $a['display'] ?? $a['value'], 'display' => '' ];
        }
        return $item_data;
    }

    /* ─── Order meta ─────────────────────────────────── */

    public static function save_order_item_meta( $item, $cart_item_key, array $values ): void {
        if ( empty( $values['addons'] ) ) return;
        foreach ( $values['addons'] as $a ) {
            $key = $a['name'];
            if ( ! empty( $a['price'] ) && $a['price'] > 0 ) $key .= ' (+' . strip_tags( wc_price( (float) $a['price'] ) ) . ')';
            $item->add_meta_data( $key, $a['value'] );
        }
    }

    /* ─── Helper: get addons for a product ──────────── */

    public static function get_product_addons( int $product_id ): array {
        $product = wc_get_product( $product_id );
        if ( ! $product ) return [];
        $addons = array_filter( (array) $product->get_meta( '_product_addons' ) );
        $c = 0;
        foreach ( $addons as &$a ) {
            $c++;
            if ( empty( $a['field-name'] ) ) $a['field-name'] = sanitize_title( $a['name'] ) . '-' . $c;
        }
        unset( $a );
        return $addons;
    }
}
