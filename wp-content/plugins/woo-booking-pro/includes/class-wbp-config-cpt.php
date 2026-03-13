<?php
defined( 'ABSPATH' ) || exit;

/**
 * WBP_Config_CPT
 * Registers the "wbp_config" custom post type.
 * Each post = one Booking Configuration with its own zone table.
 * Used via [woo_booking id="POST_ID"]
 */
class WBP_Config_CPT {

    const CPT = 'wbp_config';

    public static function init(): void {
        add_action( 'init',              [ __CLASS__, 'register_cpt' ] );
        add_action( 'add_meta_boxes',    [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post_' . self::CPT, [ __CLASS__, 'save_meta' ] );
        add_filter( 'manage_' . self::CPT . '_posts_columns',       [ __CLASS__, 'columns' ] );
        add_action( 'manage_' . self::CPT . '_posts_custom_column', [ __CLASS__, 'column_content' ], 10, 2 );
    }

    public static function register_cpt(): void {
        register_post_type( self::CPT, [
            'label'               => 'Booking Configs',
            'labels'              => [
                'name'          => 'Booking Configs',
                'singular_name' => 'Booking Config',
                'add_new_item'  => 'Add New Config',
                'edit_item'     => 'Edit Config',
                'new_item'      => 'New Config',
                'view_item'     => 'View Config',
                'search_items'  => 'Search Configs',
                'not_found'     => 'No configs found',
                'menu_name'     => 'Booking Configs ✦',
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'wbp-settings',
            'supports'            => [ 'title' ],
            'menu_icon'           => 'dashicons-list-view',
            'capability_type'     => 'post',
            'rewrite'             => false,
            'query_var'           => false,
        ] );
    }

    public static function add_meta_boxes(): void {
        add_meta_box(
            'wbp_config_zones',
            'Postcode Zone Table  <span style="font-weight:400;font-size:12px;color:#666">— use <code>[woo_booking id="' . get_the_ID() . '"]</code> on any page</span>',
            [ __CLASS__, 'render_zones_meta_box' ],
            self::CPT,
            'normal',
            'high'
        );
        add_meta_box(
            'wbp_config_shortcode',
            'Shortcode',
            [ __CLASS__, 'render_shortcode_box' ],
            self::CPT,
            'side',
            'high'
        );
    }

    public static function render_shortcode_box( WP_Post $post ): void {
        echo '<p style="font-size:13px;color:#666">Use this shortcode on any page, WPBakery element, or Divi code module:</p>';
        echo '<input type="text" readonly value="[woo_booking id=&quot;' . $post->ID . '&quot;]" class="widefat" onclick="this.select()" style="font-family:monospace;font-size:14px;font-weight:600;cursor:copy;background:#f0f4ff;border:1px solid #c0d0f0">';
        echo '<p style="margin-top:8px;font-size:12px;color:#888">Or use <code>[woo_booking]</code> (no ID) to load global zones from Postcode Zones page.</p>';
    }

    public static function render_zones_meta_box( WP_Post $post ): void {
        wp_nonce_field( 'wbp_config_save', 'wbp_config_nonce' );

        $zones_json  = get_post_meta( $post->ID, '_wbp_zones', true ) ?: '[]';
        $zones       = json_decode( $zones_json, true ) ?: [];

        $product_cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );
        $cat_options  = [];
        if ( ! is_wp_error( $product_cats ) ) {
            foreach ( $product_cats as $cat ) {
                $cat_options[] = [ 'id' => (string) $cat->term_id, 'name' => $cat->name ];
            }
        }

        $countries   = WBP_Settings::get_enabled_countries();
        $days_of_week = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];

        ?>
        <input type="hidden" id="wbp_config_zones_json" name="wbp_config_zones_json"
               value="<?php echo esc_attr( $zones_json ); ?>">

        <div class="wbp-zone-table-wrap">
            <table class="wbp-zone-table widefat" id="wbp-zone-table">
                <thead>
                    <tr>
                        <th style="width:32px">#</th>
                        <th>Country</th>
                        <th>Post Code</th>
                        <th>Pickup and Deliver Day</th>
                        <th>Services</th>
                        <th style="width:90px">Min Delivery Days</th>
                        <th style="width:90px">Surcharge</th>
                        <th>Disable Dates</th>
                        <th style="width:32px"></th>
                    </tr>
                </thead>
                <tbody id="wbp-zone-tbody">
                    <!-- Rows injected by JS -->
                </tbody>
            </table>

            <p style="margin-top:12px">
                <button type="button" id="wbp-add-zone-row" class="button button-primary">
                    ➕ Add Zone Row
                </button>
            </p>
        </div>

        <style>
        .wbp-zone-table { border-collapse:collapse; }
        .wbp-zone-table th { background:#f5f7fa; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:.4px; color:#444; padding:8px 10px; border:1px solid #ddd; }
        .wbp-zone-table td { padding:8px 10px; border:1px solid #ddd; vertical-align:top; }
        .wbp-zone-table tr:nth-child(even) td { background:#fafafa; }
        .wbp-zone-table tr:hover td { background:#f0f4ff; }
        /* Row number */
        .wbp-row-num { text-align:center; font-weight:700; color:#888; font-size:13px; }
        /* Post code input */
        .wbp-z-postcodes { width:100%; min-width:130px; resize:vertical; font-size:13px; }
        /* Day multi-select */
        .wbp-z-days { width:100%; min-width:140px; height:120px; font-size:13px; }
        /* Services tag input area */
        .wbp-services-wrap { min-width:160px; }
        .wbp-service-tags { display:flex; flex-wrap:wrap; gap:4px; margin-bottom:6px; min-height:28px; }
        .wbp-service-tag { background:#2c7be5; color:#fff; border-radius:3px; padding:2px 7px; font-size:12px; display:flex; align-items:center; gap:4px; }
        .wbp-service-tag .wbp-tag-remove { cursor:pointer; opacity:.8; font-size:14px; line-height:1; }
        .wbp-service-tag .wbp-tag-remove:hover { opacity:1; }
        .wbp-svc-select { width:100%; font-size:13px; }
        /* Number inputs */
        .wbp-z-mindays, .wbp-z-surcharge { width:70px; font-size:13px; }
        /* Disable dates repeater */
        .wbp-disable-dates-list { margin-bottom:6px; }
        .wbp-disable-date-row { display:flex; align-items:center; gap:4px; margin-bottom:4px; font-size:13px; }
        .wbp-disable-date-row input { width:90px; font-size:13px; }
        .wbp-disable-date-row .wbp-dd-num { width:16px; text-align:right; color:#aaa; font-size:11px; }
        .wbp-btn-add-date { font-size:12px; margin-top:4px; }
        /* Remove row */
        .wbp-remove-zone-row { background:none; border:none; cursor:pointer; color:#c00; font-size:18px; line-height:1; padding:2px 4px; }
        .wbp-remove-zone-row:hover { color:#900; }
        /* Country select */
        .wbp-z-country { width:100%; font-size:13px; min-width:100px; }
        </style>

        <script>
        (function($){
            var zones     = <?php echo wp_json_encode( $zones ); ?>;
            var allCats   = <?php echo wp_json_encode( $cat_options ); ?>;
            var allDays   = <?php echo wp_json_encode( $days_of_week ); ?>;
            var countries = <?php echo wp_json_encode( $countries ); ?>;

            function escHtml(s){ return $('<div>').text(String(s||'')).html(); }

            /* ── Build country <select> ── */
            function buildCountrySelect(sel){
                var opts='';
                $.each(countries, function(iso,c){
                    var s=(iso===sel)?'selected':'';
                    opts+='<option value="'+escHtml(iso)+'" '+s+'>'+escHtml((c.flag||'')+' '+(c.name||iso))+'</option>';
                });
                if(!opts) opts='<option value="GB">🇬🇧 United Kingdom</option>';
                return '<select class="wbp-z-country">'+opts+'</select>';
            }

            /* ── Build day <select multiple> ── */
            function buildDaySelect(selected){
                var opts='';
                allDays.forEach(function(d){
                    var s=(selected||[]).indexOf(d)!==-1?'selected':'';
                    opts+='<option value="'+d+'" '+s+'>'+d+'</option>';
                });
                return '<select class="wbp-z-days" multiple title="Hold Ctrl/Cmd to select multiple days">'+opts+'</select>';
            }

            /* ── Service tags ── */
            function buildServiceTags(row, selectedIds){
                var $wrap = $('<div class="wbp-services-wrap">');
                var $tags = $('<div class="wbp-service-tags">');

                // render current tags
                (selectedIds||[]).forEach(function(id){
                    var cat = allCats.find(function(c){return c.id===id;});
                    if(cat) $tags.append(makeTag(id, cat.name));
                });

                // dropdown to add
                var opts='<option value="">+ Add Service</option>';
                allCats.forEach(function(c){
                    opts+='<option value="'+escHtml(c.id)+'">'+escHtml(c.name)+'</option>';
                });
                var $sel = $('<select class="wbp-svc-select">'+opts+'</select>');

                $sel.on('change', function(){
                    var id=$(this).val(), name=$(this).find('option:selected').text();
                    if(!id) return;
                    // avoid duplicate
                    if($tags.find('[data-id="'+id+'"]').length){ $(this).val(''); return; }
                    $tags.append(makeTag(id,name));
                    $(this).val('');
                    syncJson();
                });

                $wrap.append($tags).append($sel);
                return $wrap;
            }

            function makeTag(id,name){
                return $('<span class="wbp-service-tag" data-id="'+escHtml(id)+'">')
                    .text(name)
                    .append($('<span class="wbp-tag-remove" title="Remove">×</span>').on('click',function(){
                        $(this).parent().remove();
                        syncJson();
                    }));
            }

            /* ── Disable dates repeater ── */
            function buildDisableDates(dates){
                var $wrap = $('<div class="wbp-disable-dates-cell">');
                var $list = $('<div class="wbp-disable-dates-list">');

                (dates||[]).forEach(function(d,i){
                    $list.append(makeDateRow(i+1, typeof d==='object'?(d.disable_date||''):d));
                });

                var $btn = $('<button type="button" class="button button-small wbp-btn-add-date">Add Row</button>');
                $btn.on('click', function(){
                    var n = $list.find('.wbp-disable-date-row').length+1;
                    $list.append(makeDateRow(n,''));
                    renumberDates($list);
                    syncJson();
                });

                $wrap.append($list).append($btn);
                return $wrap;
            }

            function makeDateRow(num, val){
                var $row = $('<div class="wbp-disable-date-row">');
                $row.append('<span class="wbp-dd-num">'+num+'</span>');
                $row.append($('<input type="text" placeholder="DD/MM/YYYY" maxlength="10">').val(val));
                $row.append($('<span style="cursor:pointer;color:#c00;font-size:15px;margin-left:2px" title="Remove">×</span>').on('click',function(){
                    $(this).parent().remove();
                    renumberDates($(this).closest('.wbp-disable-dates-list'));
                    syncJson();
                }));
                return $row;
            }

            function renumberDates($list){
                $list.find('.wbp-disable-date-row').each(function(i){
                    $(this).find('.wbp-dd-num').text(i+1);
                });
            }

            /* ── Build a full table row ── */
            function buildRow(z, ri){
                var $tr = $('<tr data-ri="'+ri+'">');

                // # col
                $tr.append($('<td class="wbp-row-num">').text(ri+1));

                // Country
                $tr.append($('<td>').html(buildCountrySelect(z.country||'GB')));

                // Postcodes
                $tr.append($('<td>').html('<textarea class="wbp-z-postcodes" rows="3" placeholder="AB1, AB2, N65">'+escHtml(z.post_code||'')+'</textarea>'));

                // Days
                $tr.append($('<td>').html(buildDaySelect(z.pickup_and_deliver_day)));

                // Services
                var svcTd = $('<td>');
                svcTd.append(buildServiceTags(ri, (z.services||[]).map(String)));
                $tr.append(svcTd);

                // Min days
                $tr.append($('<td>').html('<input type="number" class="wbp-z-mindays" min="0" step="1" value="'+(z.minimum_delivery_days||0)+'">'));

                // Surcharge
                $tr.append($('<td>').html('<input type="number" class="wbp-z-surcharge" min="0" step="0.01" value="'+(z.surcharge||0)+'">'));

                // Disable dates
                $tr.append($('<td>').append(buildDisableDates(z.disable_dates)));

                // Remove row
                var $rmv = $('<button type="button" class="wbp-remove-zone-row" title="Remove row">×</button>');
                $rmv.on('click', function(){
                    $(this).closest('tr').remove();
                    renumberRows();
                    syncJson();
                });
                $tr.append($('<td>').append($rmv));

                return $tr;
            }

            function renumberRows(){
                $('#wbp-zone-tbody tr').each(function(i){
                    $(this).attr('data-ri',i).find('.wbp-row-num').text(i+1);
                });
            }

            /* ── Render all rows ── */
            function renderAll(){
                var $tbody = $('#wbp-zone-tbody').empty();
                zones.forEach(function(z,i){ $tbody.append(buildRow(z,i)); });
                syncJson();
            }

            /* ── Collect all data → sync hidden input ── */
            function syncJson(){
                var out=[];
                $('#wbp-zone-tbody tr').each(function(){
                    var $tr=$(this);
                    var days=[];
                    $tr.find('.wbp-z-days option:selected').each(function(){ days.push($(this).val()); });
                    var svcs=[];
                    $tr.find('.wbp-service-tag').each(function(){ svcs.push($(this).data('id').toString()); });
                    var dates=[];
                    $tr.find('.wbp-disable-date-row input').each(function(){
                        var v=$(this).val().trim();
                        if(v) dates.push({disable_date:v});
                    });
                    out.push({
                        country              : $tr.find('.wbp-z-country').val()||'GB',
                        post_code            : $tr.find('.wbp-z-postcodes').val(),
                        pickup_and_deliver_day: days,
                        services             : svcs,
                        minimum_delivery_days: $tr.find('.wbp-z-mindays').val(),
                        surcharge            : $tr.find('.wbp-z-surcharge').val(),
                        disable_dates        : dates,
                    });
                });
                $('#wbp_config_zones_json').val(JSON.stringify(out));
            }

            /* ── Add row button ── */
            $(document).on('click','#wbp-add-zone-row', function(){
                var ri=$('#wbp-zone-tbody tr').length;
                var $row=buildRow({country:'GB',post_code:'',pickup_and_deliver_day:[],services:[],minimum_delivery_days:0,surcharge:0,disable_dates:[]},ri);
                $('#wbp-zone-tbody').append($row);
                syncJson();
            });

            /* ── Delegate input events → sync ── */
            $(document).on('input change','#wbp-zone-tbody input, #wbp-zone-tbody select, #wbp-zone-tbody textarea', function(){
                syncJson();
            });

            renderAll();
        })(jQuery);
        </script>
        <?php
    }

    public static function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['wbp_config_nonce'] )
            || ! wp_verify_nonce( $_POST['wbp_config_nonce'], 'wbp_config_save' )
            || ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
            || ! current_user_can( 'edit_post', $post_id )
        ) return;

        $raw   = wp_unslash( $_POST['wbp_config_zones_json'] ?? '[]' );
        $zones = json_decode( $raw, true );
        if ( ! is_array($zones) ) $zones = [];

        $days_valid = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $clean = [];
        foreach ( $zones as $z ) {
            $disable_dates = [];
            foreach ( $z['disable_dates'] ?? [] as $d ) {
                $val = is_array($d) ? ($d['disable_date']??'') : $d;
                if ($val) $disable_dates[] = ['disable_date' => sanitize_text_field($val)];
            }
            $services = array_map('strval', array_map('absint', $z['services'] ?? []));
            $days     = array_values( array_filter( $z['pickup_and_deliver_day'] ?? [],
                            fn($d) => in_array($d, $days_valid, true) ) );
            $clean[] = [
                'country'               => strtoupper( sanitize_text_field($z['country'] ?? 'GB') ),
                'post_code'             => sanitize_text_field($z['post_code'] ?? ''),
                'pickup_and_deliver_day'=> $days,
                'services'              => array_values($services),
                'minimum_delivery_days' => absint($z['minimum_delivery_days'] ?? 0),
                'surcharge'             => (string) floatval($z['surcharge'] ?? 0),
                'disable_dates'         => $disable_dates,
            ];
        }

        update_post_meta( $post_id, '_wbp_zones', wp_json_encode($clean) );
    }

    public static function columns( array $cols ): array {
        return [
            'cb'        => $cols['cb'],
            'title'     => 'Config Name',
            'shortcode' => 'Shortcode',
            'zones'     => 'Zones',
            'date'      => 'Date',
        ];
    }

    public static function column_content( string $col, int $post_id ): void {
        if ( $col === 'shortcode' ) {
            echo '<code>[woo_booking id="' . $post_id . '"]</code> <button class="button button-small" onclick="navigator.clipboard.writeText(\'[woo_booking id=&quot;' . $post_id . '&quot;]\');this.textContent=\'Copied!\';setTimeout(()=>this.textContent=\'Copy\',1500)">Copy</button>';
        }
        if ( $col === 'zones' ) {
            $zones = json_decode( get_post_meta($post_id,'_wbp_zones',true) ?: '[]', true );
            echo count((array)$zones) . ' zone(s)';
        }
    }

    /**
     * Get zones for a given config post ID, or fall back to global zones.
     */
    public static function get_zones_for( int $config_id ): array {
        if ( $config_id > 0 ) {
            $json  = get_post_meta( $config_id, '_wbp_zones', true );
            $zones = json_decode( $json ?: '[]', true );
            if ( is_array($zones) && ! empty($zones) ) {
                // normalise
                foreach ( $zones as &$z ) {
                    $z['post_code_arr'] = array_map('trim', explode(',', $z['post_code']??''));
                    $z['disable_dates'] = $z['disable_dates'] ?? [];
                    $z['services']      = $z['services']      ?? [];
                    $z['country']       = $z['country']       ?? 'GB';
                }
                unset($z);
                return $zones;
            }
        }
        // Fall back to global zones
        return WBP_Settings::get_zones();
    }
}
