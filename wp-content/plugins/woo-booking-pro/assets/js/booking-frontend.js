/**
 * WooBooking Pro – Frontend Booking Flow v1.2
 * Zones & settings are read LAZILY at runtime from window.wbp_data
 * so the inline <script> injected by [woo_booking id="X"] always wins.
 */
(function ($) {
    'use strict';

    /* ─────────────────────────────────────────────
       Lazy data helpers – always read from window.wbp_data at call-time
       so the shortcode's inline <script> is never overwritten by wp_localize_script
    ───────────────────────────────────────────── */
    function getData()        { return window.wbp_data || {}; }
    function getZones()       { return getData().zones        || []; }
    function getCountries()   { return getData().countries    || {}; }
    function getAjaxUrl()     { return getData().ajaxurl      || (window.ajax_object ? window.ajax_object.ajaxurl : ''); }
    function getRestrictions(){ return getData().maps_restrictions || []; }
    function isMultiMode()    { return !!getData().multi_country; }
    function getDefCountry()  { return getData().default_country  || 'GB'; }

    /* ─────────────────────────────────────────────
       State
    ───────────────────────────────────────────── */
    var state = {
        country_iso      : '',   // set on init
        country_data     : {},
        postal_code      : '',
        raw_postcode     : '',
        zone             : null,
        surcharge_postal : 0,
        surcharge_svc    : 0,
        surcharge_total  : 0,
        final_amount     : 0,
        product_id       : null,
        pickup_date      : '',
        delivery_date    : '',
        order_notes      : '',
        accept_policy    : false,
        send_final_data  : [],
        service_title    : '',
        day_arr          : [],
        disable_dates    : [],
        min_delivery_days: 0,
    };

    /* ─────────────────────────────────────────────
       Google Maps autocomplete
       Uses google.maps.places.Autocomplete (legacy API — reliable, no web-component issues)
    ───────────────────────────────────────────── */
    var gmapsAutocomplete = null;
    var gmapsLegacy       = null;
    var mapsReady         = false;

    // Called once Maps API is loaded (either via callback or polling)
    function onMapsReady() {
        if ( mapsReady ) return;
        mapsReady = true;
        buildAutocomplete();
    }

    // Legacy callback (kept for backward compat)
    window.wbpInitAutocomplete = onMapsReady;

    // Poll for Maps API if callback was not used (loading=async)
    function waitForMaps() {
        if ( typeof google !== 'undefined' && google.maps && google.maps.places ) {
            onMapsReady();
        } else {
            setTimeout( waitForMaps, 200 );
        }
    }
    waitForMaps();

    function buildAutocomplete() {
        var inputEl = document.getElementById('wbp-autocomplete');
        if ( !inputEl ) return;

        // Remove any stray web components from previous attempts
        var stray = document.getElementById('wbp-place-autocomplete');
        if ( stray ) stray.remove();
        inputEl.style.display = '';  // always keep the real input visible

        if ( typeof google === 'undefined' || !google.maps || !google.maps.places || !google.maps.places.Autocomplete ) {
            return; // Maps not loaded yet, waitForMaps() will retry
        }

        var iso   = state.country_iso || getDefCountry();
        var cData = getCountries()[iso] || {};

        var restrictTo;
        if ( isMultiMode() ) {
            restrictTo = [ cData.maps_country || iso.toLowerCase() ];
        } else {
            var r = getRestrictions();
            restrictTo = r.length ? r : null;
        }

        // Destroy existing instance before creating a new one
        if ( gmapsLegacy ) {
            google.maps.event.clearInstanceListeners( gmapsLegacy );
            gmapsLegacy = null;
        }

        var opts = { types: ['geocode'] };
        if ( restrictTo ) opts.componentRestrictions = { country: restrictTo };

        gmapsLegacy = new google.maps.places.Autocomplete( inputEl, opts );
        gmapsLegacy.addListener('place_changed', function() {
            var place = gmapsLegacy.getPlace();
            state.postal_code  = '';
            state.raw_postcode = '';
            if ( !place || !place.address_components ) return;
            extractFromPlaceLegacy( place, inputEl.value );
        });
    }


    function extractFromPlaceLegacy( place, rawText ) {
        for ( var i = 0; i < place.address_components.length; i++ ) {
            var comp  = place.address_components[i];
            var types = comp.types || [];
            if ( types.indexOf('postal_code') !== -1 ) {
                state.raw_postcode = comp.short_name;
                state.postal_code  = comp.short_name.replace(/\s/g,'').toUpperCase();
            }
            if ( types.indexOf('postal_code_prefix') !== -1 && !state.postal_code ) {
                state.raw_postcode = comp.short_name;
                state.postal_code  = comp.short_name.replace(/\s/g,'').toUpperCase();
            }
        }
        if ( !state.postal_code ) {
            state.postal_code = extractPostcodeFromText(
                place.formatted_address || rawText, state.country_iso
            );
        }
        console.log('[WBP] Legacy Autocomplete → postal_code:', state.postal_code);
    }

    function extractPostcodeFromText( text, iso ) {
        text = text.toUpperCase().replace(/,/g,' ').replace(/\s+/g,' ').trim();
        if ( iso === 'GB' || !iso ) {
            // Full UK postcode e.g. N1P2HZ or N1P 2HZ
            var full = text.match( /\b([A-Z]{1,2}[0-9][0-9A-Z]?\s?[0-9][A-Z]{2})\b/ );
            if ( full ) return full[1].replace(/\s/g,'');
            // Outward/district code e.g. N1P, SW1A, E1
            var out = text.match( /\b([A-Z]{1,2}[0-9][0-9A-Z]?)\b/ );
            if ( out ) return out[1];
        }
        var words = text.split(' ');
        for ( var i = 0; i < words.length; i++ ) {
            if ( /[0-9]/.test(words[i]) && words[i].length >= 3 ) return words[i];
        }
        return text.split(' ')[0] || '';
    }

    /* ─────────────────────────────────────────────
       Country selector
    ───────────────────────────────────────────── */
    $(document).on('change', '#wbp-country-select', function () {
        var iso   = $(this).val();
        var cData = getCountries()[iso] || {};
        state.country_iso  = iso;
        state.country_data = cData;
        state.postal_code  = '';
        state.raw_postcode = '';

        var hint = cData.format_hint || '';
        $('#wbp-autocomplete').attr('placeholder', hint ? 'e.g. ' + hint : 'Enter your postcode…').val('');

        if ( hint ) {
            $('#wbp-country-info-bar')
                .html('<span>' + escHtml(cData.flag||'') + ' <strong>' + escHtml(cData.name||iso) + '</strong> — postcode format: <code>' + escHtml(hint) + '</code></span>')
                .show();
        } else {
            $('#wbp-country-info-bar').hide();
        }

        buildAutocomplete();
    });

    /* ─────────────────────────────────────────────
       Postcode validation — accepts outward/district codes too
    ───────────────────────────────────────────── */
    function validatePostcode( pc, iso ) {
        if ( !pc || pc.length < 2 ) return false;
        iso = (iso || 'GB').toUpperCase();
        if ( iso === 'GB' ) {
            // Full postcode: N1P2HZ  or  outward code: N1P, SW1A, E1
            return /^[A-Z]{1,2}[0-9][0-9A-Z]?([0-9][A-Z]{2})?$/i.test(pc);
        }
        var cData = getCountries()[iso] || {};
        var regexStr = cData.regex || '/^.+$/';
        try {
            var m = regexStr.match(/^\/(.+)\/([gimsuy]*)$/);
            if ( m ) return new RegExp(m[1], m[2]).test(pc);
            return new RegExp(regexStr).test(pc);
        } catch(e) {
            return pc.length >= 2;
        }
    }

    /* ─────────────────────────────────────────────
       Zone matching
    ───────────────────────────────────────────── */
    function normalise( raw ) { return raw.replace(/\s/g,'').toUpperCase(); }

    function findZone( rawPostcode, iso ) {
        var pc     = normalise(rawPostcode);
        var cData  = getCountries()[iso] || {};
        var maxPre = parseInt(cData.prefix_length, 10) || 4;

        // Also try with just the outward part (everything before first digit in inward)
        // e.g. N1P2HZ → try N1P first (outward), then N1, then N
        var toTry = [];

        // UK: extract outward code (letters+digits before space/inward)
        if ( (iso||'GB') === 'GB' ) {
            // Full postcode: outward is everything except last 3 chars
            if ( pc.length > 3 ) {
                toTry.push( pc.slice(0, pc.length - 3) ); // e.g. N1P2HZ → N1P, SW1A1AA → SW1A
            }
        }

        // Try progressively shorter prefixes up to maxPre
        for ( var len = Math.min(pc.length, maxPre); len >= 1; len-- ) {
            var p = pc.substring(0, len);
            if ( toTry.indexOf(p) === -1 ) toTry.push(p);
        }

        console.log('[WBP] findZone pc=' + pc + ' iso=' + iso + ' trying:', toTry);

        var zones = getZones();
        console.log('[WBP] Total zones loaded:', zones.length);

        for ( var t = 0; t < toTry.length; t++ ) {
            var prefix_str = toTry[t];
            for ( var i = 0; i < zones.length; i++ ) {
                var z = zones[i];
                var zCountry = (z.country || 'GB').toUpperCase();
                if ( zCountry !== iso.toUpperCase() ) continue;
                var arr = z.post_code_arr || [];
                for ( var j = 0; j < arr.length; j++ ) {
                    var zCode = normalise(arr[j]);
                    if ( zCode === prefix_str ) {
                        console.log('[WBP] Zone matched! prefix=' + prefix_str + ' zoneCode=' + zCode);
                        return z;
                    }
                }
            }
        }
        console.log('[WBP] No zone found for', pc, iso);
        return null;
    }

    /* ─────────────────────────────────────────────
       Section helpers
    ───────────────────────────────────────────── */
    function showSection( cls ) {
        $('.wbp-section').hide();
        $(cls).show();
        if ( $('.wbp-booking-wrapper').length ) {
            $('html,body').animate({ scrollTop: $('.wbp-booking-wrapper').offset().top - 80 }, 300);
        }
    }
    function showLoading() { $('.wbp-loading').show(); }
    function hideLoading() { $('.wbp-loading').hide(); }

    /* ─────────────────────────────────────────────
       STEP 1 → 2 : Postcode Next
    ───────────────────────────────────────────── */
    $(document).on('click', '#wbp-next-postcode', function () {
        $('.wbp-error').hide();
        $('.wbp-err-inline').remove();

        var inputVal = $('#wbp-autocomplete').val();
        inputVal = (inputVal || '').trim();
        if ( !inputVal ) {
            $('#wbp-error-postcode').text('Please enter your postcode.').show();
            return;
        }

        var iso = state.country_iso || getDefCountry();

        // Use Maps-extracted postcode if available, else extract from input text
        if ( !state.postal_code ) {
            state.postal_code = extractPostcodeFromText(inputVal, iso);
        }

        var pc = state.postal_code || normalise(inputVal);
        console.log('[WBP] Next clicked. iso=' + iso + ' pc=' + pc + ' zones=' + getZones().length);

        if ( !validatePostcode(pc, iso) ) {
            var cData = getCountries()[iso] || {};
            var hint  = cData.format_hint || '';
            $('#wbp-error-postcode')
                .text('Please enter a valid postcode' + (hint ? ' (e.g. ' + hint + ')' : '') + '.')
                .show();
            return;
        }

        var zone = findZone(pc, iso);
        if ( !zone ) {
            $('#wbp-error-postcode')
                .text("Sorry, we don't currently service your area (" + pc + "). Please try a nearby postcode.")
                .show();
            return;
        }

        // Store zone in state
        state.zone               = zone;
        state.surcharge_postal   = parseFloat(zone.surcharge) || 0;
        state.surcharge_total    = state.surcharge_postal;
        state.min_delivery_days  = parseInt(zone.minimum_delivery_days, 10) || 3;
        state.services           = zone.services || [];

        var dayMap = {Sunday:0,Monday:1,Tuesday:2,Wednesday:3,Thursday:4,Friday:5,Saturday:6};
        var days   = zone.pickup_and_deliver_day || [];
        state.day_arr = days.map(function(d){ return dayMap[d]; }).filter(function(d){ return d !== undefined; });
        state.disable_dates = (zone.disable_dates||[]).map(function(d){
            return typeof d==='object' ? (d.disable_date||'') : d;
        });

        var cData = getCountries()[iso] || {};
        if ( days.length ) {
            $('#wbp-delivery-info').html(
                '<div class="wbp-delivery-info-bar">' +
                escHtml(cData.flag||'') + ' We collect &amp; deliver on <strong>"' +
                escHtml(days.join(', ')) + '"</strong> in your area.</div>'
            );
        }

        // Load service categories
        $('#wbp-services-list').empty();
        showLoading();
        var loaded = 0, total = state.services.length;
        if ( !total ) {
            hideLoading();
            $('#wbp-error-postcode').text('No services are configured for your area yet.').show();
            return;
        }

        state.services.forEach(function(cat_id) {
            $.ajax({
                url: getAjaxUrl(), type:'POST', dataType:'json',
                data: { action:'ajax_action', cat_id:cat_id },
                success: function(res) {
                    loaded++;
                    if (res) {
                        var thumbHtml = res.thumb
                            ? '<div class="wbp-card-img"><img src="' + res.thumb + '" alt="' + escHtml(res.name) + '"></div>'
                            : '';
                        var descHtml = res.description
                            ? '<p>' + escHtml(res.description) + '</p>'
                            : '';
                        $('#wbp-services-list').append(
                            '<div class="wbp-card-option product-selection-tabs product-selection-tab-cat">' +
                            '<input type="radio" value="' + res.term_id + '" ' +
                            'data-surcharge="' + (res.surcharge||0) + '" ' +
                            'id="wbp-cat-' + res.term_id + '" ' +
                            'data-desc="' + escAttr(res.description||'') + '" ' +
                            'name="wbpPropertyType">' +
                            '<label for="wbp-cat-' + res.term_id + '">' +
                            thumbHtml +
                            '<h2>' + escHtml(res.name) + '</h2>' +
                            descHtml +
                            '</label></div>'
                        );
                        bindServiceDesc();
                    }
                    if (loaded >= total) hideLoading();
                },
                error: function(){ loaded++; if(loaded>=total) hideLoading(); }
            });
        });

        showSection('.wbp-section-services');
    });

    function bindServiceDesc() {
        $('input[name=wbpPropertyType]').off('click.wbp').on('click.wbp', function(){
            $('.wbp-service-desc').text($(this).attr('data-desc')||'');
        });
    }

    /* ─────────────────────────────────────────────
       STEP 2 → 3 : load products directly from service category
    ───────────────────────────────────────────── */
    $(document).on('click', '#wbp-next-service', function () {
        $('.wbp-error').hide();
        var $sel = $('input[name=wbpPropertyType]:checked');
        if (!$sel.length) { $('#wbp-error-service').show(); return; }

        var catId    = $sel.val();
        var catLabel = $sel.closest('.wbp-card-option').find('label h2').text().trim() ||
                       $sel.closest('.wbp-card-option').find('label').text().trim();
        var svcSur   = parseFloat($sel.attr('data-surcharge')) || 0;

        state.service_title   = catLabel;
        state.surcharge_svc   = svcSur;
        state.surcharge_total = state.surcharge_postal + svcSur;

        if (catLabel === 'Collection & Delivery') { showSection('.wbp-section-collection'); return; }
        if (catLabel === 'Postal Service')         { loadNationwide(catLabel); return; }

        // Load products directly (skip sub-category step)
        showLoading();
        $.ajax({
            url:getAjaxUrl(), type:'POST', dataType:'json',
            data:{ action:'ajax_action1', cat_child_name: catId },
            success:function(res){
                hideLoading();
                if (res && res.a) {
                    $('#wbp-product-selection').html(res.a);
                    $('#wbp-product-meta-data').html(res.b);
                    state.product_id = res.c;
                    recalcTotal();
                    showSection('.wbp-section-products');
                    initDatePickers();
                    bindProductClick('ajax_action5');
                } else {
                    $('#wbp-error-service').text('No products found for this service.').show();
                }
            },
            error:function(){ hideLoading(); }
        });
    });

    /* ─────────────────────────────────────────────
       Nationwide / Postal
    ───────────────────────────────────────────── */
    function loadNationwide(catName) {
        showLoading();
        $('#wbp-product-selection').hide();
        state.surcharge_total = 0;
        $.ajax({
            url:getAjaxUrl(), type:'POST', dataType:'json',
            data:{ action:'ajax_action10', cat_name_nation:catName },
            success:function(res){
                hideLoading();
                $('#wbp-nationwide-section').show();
                $('#wbp-product-selection-nation').html(res.a);
                $('#wbp-product-meta-data').html(res.b);
                state.product_id = res.c;
                $('#wbp-date-fields').hide();
                recalcTotal();
                showSection('.wbp-section-products');
                bindProductClick('ajax_action6');
            },
            error:function(){ hideLoading(); }
        });
    }

    /* ─────────────────────────────────────────────
       Product click → reload add-ons
    ───────────────────────────────────────────── */
    function bindProductClick(ajaxAction) {
        $(document).off('click.wbp-prod').on('click.wbp-prod', '.product-input', function(){
            var pid = $(this).val();
            state.product_id = pid;
            showLoading();
            $.ajax({
                url:getAjaxUrl(), type:'POST', dataType:'json',
                data:{ action:ajaxAction, product_id:pid },
                success:function(res){
                    hideLoading();
                    $('#wbp-product-meta-data').html(res.a);
                    state.product_id = res.b;
                    recalcTotal();
                },
                error:function(){ hideLoading(); }
            });
        });
    }

    /* ─────────────────────────────────────────────
       Order total recalculation
    ───────────────────────────────────────────── */
    function recalcTotal() {
        var arr = [], final_data = [], addons_sum = 0, base_sum = 0;
        var D = getData();
        var currency = (state.country_data && state.country_data.currency) || D.currency || '£';

        $('input[name="product-select"]:checked').each(function(){
            var pid=$(this).val(), price=parseFloat($(this).attr('data-amount'))||0, title=$(this).attr('data-title')||'';
            base_sum += price;
            var item = {id:pid,title:title,amount:price.toFixed(2),addons:[]};
            arr.push(item); final_data.push($.extend(true,{},item));
        });

        // Add-ons: radio/checkbox cards
        $('input.wbp-addon-input:checked').each(function(){
            var amount = parseFloat($(this).val())||0;
            var title  = $(this).attr('data-title')||'';
            var pid    = $(this).attr('pid'), oid = $(this).attr('id');
            addons_sum += amount;
            if (title) {
                var addon = {id:oid, title:title, amount:amount.toFixed(2)};
                [arr,final_data].forEach(function(a){
                    a.forEach(function(item){
                        if (String(item.id)===String(pid) && !item.addons.find(function(x){return x.id===oid;}))
                            item.addons.push(addon);
                    });
                });
            }
        });
        // Add-ons: select dropdowns
        $('select.wbp-addon-select').each(function(){
            var price = parseFloat($(this).find('option:selected').attr('data-price')) || 0;
            if (price > 0) addons_sum += price;
        });
        // Add-ons: price multipliers
        $('input.wbp-addon-multiplier').each(function(){
            var qty = parseInt($(this).val()) || 0;
            var unit = parseFloat($(this).attr('data-price')) || 0;
            if (qty > 0 && unit > 0) addons_sum += qty * unit;
        });
        // Add-ons: custom price inputs
        $('input.wbp-addon-customprice').each(function(){
            var p = parseFloat($(this).val()) || 0;
            if (p > 0) addons_sum += p;
        });

        var surcharge = parseFloat(state.surcharge_total)||0;
        var total     = base_sum + addons_sum + surcharge;
        state.final_amount    = total.toFixed(2);
        state.send_final_data = final_data;

        arr.push({title:'Collection &amp; Delivery Fee <span class="wbp-info-icon" title="Two-way trip/postal charge">ⓘ</span>',amount:surcharge.toFixed(2),addons:[]});
        final_data.push({title:'Service Charges',amount:surcharge.toFixed(2)});
        arr.push({title:'<strong>Total</strong>',amount:total.toFixed(2),addons:[]});
        final_data.push({title:'Total',amount:total.toFixed(2)});

        var html='';
        arr.forEach(function(a){
            html += '<tr><th>'+a.title+'</th><td>'+currency+' '+a.amount+'</td></tr>';
            (a.addons||[]).forEach(function(ad){
                html += '<tr><td><ul><li>'+escHtml(ad.title)+'</li></ul></td><td>'+currency+' '+ad.amount+'</td></tr>';
            });
        });
        $('#wbp-order-rows').html(html);

        var minOrder = parseFloat(D.min_order||0);
        if (minOrder>0 && total<minOrder) {
            $('#wbp-min-order-val').text(currency+minOrder.toFixed(2));
            $('#wbp-min-order-error').show();
        } else {
            $('#wbp-min-order-error').hide();
        }
    }

    $(document).on('click change',
        '#wbp-product-meta-data input, #wbp-product-selection input, #wbp-product-selection-nation input, .wbp-addon-input, .wbp-addon-select, .wbp-addon-multiplier, .wbp-addon-customprice',
        function(){ recalcTotal(); }
    );

    /* ─────────────────────────────────────────────
       Date pickers (Flatpickr)
    ───────────────────────────────────────────── */
    function initDatePickers() {
        var dayArr   = state.day_arr;
        var disabled = state.disable_dates;
        var minDays  = state.min_delivery_days || 3;

        function isDisabledDate(date){
            var d=('0'+date.getDate()).slice(-2), m=('0'+(date.getMonth()+1)).slice(-2), y=date.getFullYear();
            return disabled.indexOf(d+'/'+m+'/'+y) !== -1;
        }
        function dayOk(date){
            if (dayArr.length && dayArr.indexOf(date.getDay())===-1) return false;
            if (isDisabledDate(date)) return false;
            return true;
        }

        flatpickr('.collection-pickup',{
            dateFormat:'d/m/Y', minDate:'today',
            enable:[function(date){ return dayOk(date); }],
            locale:{firstDayOfWeek:1},
            onChange:function(selectedDates){
                if (!selectedDates.length) return;
                state.pickup_date = flatpickr.formatDate(selectedDates[0],'d/m/Y');
                var minDel = new Date(selectedDates[0]);
                minDel.setDate(minDel.getDate()+minDays);
                if (window._wbpDelFP) window._wbpDelFP.set('minDate',minDel);
            }
        });

        window._wbpDelFP = flatpickr('.collection-delivery',{
            dateFormat:'d/m/Y',
            enable:[function(date){ return dayOk(date); }],
            locale:{firstDayOfWeek:1},
            onChange:function(selectedDates){
                if (!selectedDates.length) return;
                state.delivery_date = flatpickr.formatDate(selectedDates[0],'d/m/Y');
            }
        });
    }

    /* ─────────────────────────────────────────────
       T&C / Add to Cart / Navigation
    ───────────────────────────────────────────── */
    $(document).on('change','#wbp-tandc',function(){ state.accept_policy=$(this).is(':checked'); });

    $(document).on('click','#wbp-add-to-cart',function(){
        $('.wbp-error').hide(); $('.wbp-err-inline').remove();
        var isPostal = (state.service_title==='Postal Service');
        if (!isPostal && !state.pickup_date)  { $('#wbp-pickup-date').closest('.wbp-input-icon-wrap').after('<span class="wbp-err-inline">Please select a collection date.</span>'); return; }
        if (!isPostal && !state.delivery_date){ $('#wbp-delivery-date').closest('.wbp-input-icon-wrap').after('<span class="wbp-err-inline">Please select a delivery date.</span>'); return; }
        if (!state.accept_policy) { $('#wbp-privacy-error').show(); return; }
        if (!state.product_id)    { alert('Please select a service first.'); return; }

        state.order_notes = $('#wbp-order-notes').val();
        showLoading();
        $.ajax({
            url:getAjaxUrl(), type:'POST', dataType:'json',
            data:{
                action:'ajax_action7',
                productid:state.product_id, price:state.final_amount,
                meta_data:state.send_final_data,
                pickupdate:state.pickup_date, deliverydate:state.delivery_date,
                order_notes:state.order_notes, country:state.country_iso,
            },
            success:function(url){ if(url) window.location.href=url; else hideLoading(); },
            error:function(){ hideLoading(); }
        });
    });

    $(document).on('click','#wbp-prev-service', function(){ showSection('.wbp-section-postcode'); $('#wbp-delivery-info').empty(); $('#wbp-services-list').empty(); });
    $(document).on('click','#wbp-prev-subcat',  function(){ showSection('.wbp-section-services'); });
    $(document).on('click','#wbp-prev-products',function(){
        showSection('.wbp-section-services');
    });

    /* ─────────────────────────────────────────────
       Utilities
    ───────────────────────────────────────────── */
    function escHtml(s){ return $('<div>').text(String(s||'')).html(); }
    function escAttr(s){ return String(s||'').replace(/"/g,'&quot;'); }

    /* ─────────────────────────────────────────────
       Init
    ───────────────────────────────────────────── */
    $(function(){
        hideLoading();

        // Set initial country from data
        state.country_iso  = getDefCountry();
        state.country_data = getCountries()[state.country_iso] || {};

        // Reset postal_code on manual edit (both legacy input and new element)
        $(document).on('input','#wbp-autocomplete',function(){
            state.postal_code  = '';
            state.raw_postcode = '';
        });

        if ( isMultiMode() ) {
            $('#wbp-country-select').trigger('change');
        } else {
            buildAutocomplete();
            var cData = getCountries()[state.country_iso] || {};
            if (cData.format_hint) {
                $('#wbp-country-info-bar')
                    .html('<span>'+escHtml(cData.flag||'')+' <strong>'+escHtml(cData.name||state.country_iso)+'</strong> — postcode format: <code>'+escHtml(cData.format_hint)+'</code></span>')
                    .show();
            }
        }

        console.log('[WBP] Init. Zones loaded:', getZones().length, 'Country:', state.country_iso);
    });

})(jQuery);
