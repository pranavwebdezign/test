<?php
/**
 * Booking form partial – used by [woo_booking] shortcode and page template.
 */
defined( 'ABSPATH' ) || exit;

$settings         = WBP_Settings::all();
$currency         = $settings['currency_symbol']         ?? '£';
$step_labels      = $settings['step_labels']             ?? [ 'Enter your Postcode', 'See Service Availability', 'Customise your order', 'Summary + Details' ];
$step_icons       = $settings['step_icons']              ?? [];
$notes_title      = $settings['order_notes_title']       ?? 'Special Instructions';
$notes_ph         = $settings['order_notes_placeholder'] ?? 'Add any special instructions here…';
$terms_url        = $settings['terms_url']               ?? '/terms/';
$privacy_url      = $settings['privacy_url']             ?? '/privacy-policy/';
$multi_country    = ! empty( $settings['multi_country_mode'] );
$default_country  = $settings['default_country']         ?? 'GB';
$enabled_countries= WBP_Settings::get_enabled_countries();
$default_preset   = WBP_Settings::get_country_preset( $default_country );
?>

<div class="wbp-booking-wrapper" data-config-id="<?php echo esc_attr( $GLOBALS['wbp_current_config_id'] ?? 0 ); ?>">

    <!-- Loading spinner -->
    <div class="wbp-loading" style="display:none">
        <div class="wbp-spinner"><div></div></div>
    </div>

    <!-- ── STEP INDICATOR ─────────────────── -->
    <div class="wbp-step-indicators">
        <?php for ( $i = 0; $i < 4; $i++ ) : ?>
        <div class="wbp-step-indicator" id="wbp-step-ind-<?php echo $i; ?>">
            <div class="wbp-step-icon">
                <?php if ( ! empty( $step_icons[ $i ] ) ) : ?>
                    <img src="<?php echo esc_url( $step_icons[ $i ] ); ?>" alt="">
                <?php else : ?>
                    <span class="wbp-step-num"><?php echo $i + 1; ?></span>
                <?php endif; ?>
            </div>
            <div class="wbp-step-label"><p><?php echo wp_kses_post( $step_labels[ $i ] ); ?></p></div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- ════════════════════════════════════════
         SECTION 1 – Country + Postcode
    ════════════════════════════════════════ -->
    <div class="wbp-section wbp-section-postcode wbp-active-section">
        <div class="wbp-container">
            <div class="wbp-section-content">
                <div class="wbp-section-head">
                    <h2><?php esc_html_e( 'Let us do the running around for you', 'woo-booking-pro' ); ?></h2>
                    <p><?php esc_html_e( 'Enter your postcode to get started', 'woo-booking-pro' ); ?></p>
                </div>

                <?php if ( $multi_country && count( $enabled_countries ) > 1 ) : ?>
                <!-- Country selector (multi-country mode) -->
                <div class="wbp-country-selector" style="margin-bottom:16px">
                    <label for="wbp-country-select" class="wbp-field-label">
                        <strong><?php esc_html_e( 'Select your country', 'woo-booking-pro' ); ?></strong>
                    </label>
                    <div class="wbp-country-select-wrap">
                        <select id="wbp-country-select" class="wbp-country-select">
                            <?php foreach ( $enabled_countries as $iso => $country ) : ?>
                            <option value="<?php echo esc_attr( $iso ); ?>"
                                    data-flag="<?php echo esc_attr( $country['flag'] ?? '' ); ?>"
                                    data-maps="<?php echo esc_attr( $country['maps_country'] ?? strtolower($iso) ); ?>"
                                    data-hint="<?php echo esc_attr( $country['format_hint'] ?? '' ); ?>"
                                    data-prefix="<?php echo esc_attr( $country['prefix_length'] ?? 4 ); ?>"
                                    data-currency="<?php echo esc_attr( $country['currency'] ?? $currency ); ?>"
                                    data-regex="<?php echo esc_attr( $country['regex'] ?? '/^.+$/' ); ?>"
                                    <?php selected( $iso, $default_country ); ?>>
                                <?php echo esc_html( ( $country['flag'] ?? '' ) . ' ' . ( $country['name'] ?? $iso ) ); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php else : ?>
                <!-- Hidden input – single country mode -->
                <input type="hidden" id="wbp-country-select" value="<?php echo esc_attr( $default_country ); ?>"
                       data-flag="<?php echo esc_attr( $default_preset['flag'] ?? '' ); ?>"
                       data-maps="<?php echo esc_attr( $default_preset['maps_country'] ?? strtolower($default_country) ); ?>"
                       data-hint="<?php echo esc_attr( $default_preset['format_hint'] ?? '' ); ?>"
                       data-prefix="<?php echo esc_attr( $default_preset['prefix_length'] ?? 4 ); ?>"
                       data-currency="<?php echo esc_attr( $default_preset['currency'] ?? $currency ); ?>"
                       data-regex="<?php echo esc_attr( $default_preset['regex'] ?? '/^.+$/' ); ?>">
                <?php endif; ?>

                <!-- Country info bar (shown after selection) -->
                <div id="wbp-country-info-bar" class="wbp-country-info-bar" style="display:none"></div>

                <!-- Postcode field -->
                <div id="wbp-location-field" class="wbp-postcode-field-wrap">
                    <label for="wbp-autocomplete" class="screen-reader-text">
                        <?php esc_html_e( 'Enter postcode', 'woo-booking-pro' ); ?>
                    </label>
                    <div class="wbp-postcode-input-wrap">
                        <span class="wbp-postcode-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </span>
                        <input id="wbp-autocomplete"
                               class="wbp-postcode-input"
                               placeholder="<?php echo esc_attr( $default_preset['format_hint']
                                   ? sprintf( __( 'e.g. %s', 'woo-booking-pro' ), $default_preset['format_hint'] )
                                   : __( 'Start typing your postcode or address…', 'woo-booking-pro' ) ); ?>"
                               type="text"
                               autocomplete="off">
                    </div>
                </div>

                <div id="wbp-error-postcode" class="wbp-error" style="display:none">
                    <?php esc_html_e( 'Please enter a valid postcode.', 'woo-booking-pro' ); ?>
                </div>

                <div class="wbp-nav-row">
                    <button class="wbp-btn wbp-btn-next" id="wbp-next-postcode">
                        <?php esc_html_e( 'Next', 'woo-booking-pro' ); ?> →
                    </button>
                </div>
            </div>
        </div>
    </div><!-- /section 1 -->

    <!-- ════════════════════════════════════════
         SECTION 2 – Service Selection
    ════════════════════════════════════════ -->
    <div class="wbp-section wbp-section-services" style="display:none">
        <div class="wbp-container">
            <div class="wbp-section-content">
                <div class="wbp-section-head">
                    <h2><?php esc_html_e( 'Services available in your area', 'woo-booking-pro' ); ?></h2>
                    <p><?php esc_html_e( 'Please select what service you would like to book', 'woo-booking-pro' ); ?></p>
                </div>
                <div id="wbp-delivery-info"></div>
                <div class="wbp-radio-group" id="wbp-services-list"></div>
                <div class="wbp-service-desc"></div>
                <div id="wbp-error-service" class="wbp-error" style="display:none">
                    <?php esc_html_e( 'Please select a service.', 'woo-booking-pro' ); ?>
                </div>
            </div>
            <div class="wbp-nav-row">
                <button class="wbp-btn wbp-btn-prev" id="wbp-prev-service">← <?php esc_html_e( 'Previous', 'woo-booking-pro' ); ?></button>
                <button class="wbp-btn wbp-btn-next" id="wbp-next-service"><?php esc_html_e( 'Next', 'woo-booking-pro' ); ?> →</button>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         SECTION 2b – Collection info page
    ════════════════════════════════════════ -->
    <div class="wbp-section wbp-section-collection" style="display:none">
        <div class="wbp-container">
            <div class="wbp-section-content">
                <div class="wbp-section-head"><h2><?php esc_html_e( 'Delivering Convenience', 'woo-booking-pro' ); ?></h2></div>
                <div id="wbp-collection-content"></div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         SECTION 4 – Products + Add-ons + Dates + Summary
    ════════════════════════════════════════ -->
    <div class="wbp-section wbp-section-products" style="display:none">
        <div class="wbp-container">
            <div class="wbp-section-content">

                <div class="wbp-section-head">
                    <h2><?php esc_html_e( 'Your Garment / Service Type', 'woo-booking-pro' ); ?></h2>
                </div>

                <div class="wbp-product-selection-section">
                    <section class="wbp-product-selection" id="wbp-product-selection"></section>
                </div>
                <div class="wbp-product-selection-section" id="wbp-nationwide-section" style="display:none">
                    <section class="wbp-product-selection-full" id="wbp-product-selection-nation"></section>
                </div>

                <div id="wbp-product-meta-data"></div>

                <hr class="wbp-divider">

                <!-- Order Summary -->
                <div class="wbp-order-summary">
                    <h2><?php esc_html_e( 'Order Summary', 'woo-booking-pro' ); ?></h2>
                    <table class="wbp-order-table">
                        <thead><tr>
                            <th><?php esc_html_e( 'Product / Service', 'woo-booking-pro' ); ?></th>
                            <th><?php esc_html_e( 'Amount', 'woo-booking-pro' ); ?></th>
                        </tr></thead>
                        <tbody id="wbp-order-rows"></tbody>
                    </table>
                    <div id="wbp-min-order-error" class="wbp-error" style="display:none">
                        <?php printf( esc_html__( 'Minimum order amount is %s.', 'woo-booking-pro' ), '<span id="wbp-min-order-val"></span>' ); ?>
                    </div>
                </div>

                <hr class="wbp-divider">

                <!-- Dates -->
                <div class="wbp-booking-dates">
                    <h2><?php esc_html_e( 'Your Booking is almost complete!', 'woo-booking-pro' ); ?></h2>
                    <p><?php esc_html_e( 'Please fill out the information below.', 'woo-booking-pro' ); ?></p>

                    <div class="wbp-date-row" id="wbp-date-fields">
                        <div class="wbp-date-col">
                            <label for="wbp-pickup-date">
                                <strong><?php esc_html_e( 'Select collection date', 'woo-booking-pro' ); ?></strong>
                                <div class="wbp-input-icon-wrap">
                                    <i class="fas fa-calendar-alt wbp-icon"></i>
                                    <input type="text" class="wbp-date-input collection-pickup" id="wbp-pickup-date" placeholder="DD/MM/YYYY" readonly>
                                </div>
                            </label>
                        </div>
                        <div class="wbp-date-col">
                            <label for="wbp-delivery-date">
                                <strong><?php esc_html_e( 'Select desired delivery date', 'woo-booking-pro' ); ?></strong>
                                <div class="wbp-input-icon-wrap">
                                    <i class="fas fa-calendar-alt wbp-icon"></i>
                                    <input type="text" class="wbp-date-input collection-delivery" id="wbp-delivery-date" placeholder="DD/MM/YYYY" readonly>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="wbp-notes-area">
                        <label for="wbp-order-notes">
                            <strong><?php echo esc_html( $notes_title ); ?></strong>
                            <textarea name="wbp-order-notes" id="wbp-order-notes" class="wbp-notes-textarea" rows="4"
                                      placeholder="<?php echo esc_attr( $notes_ph ); ?>"></textarea>
                        </label>
                    </div>
                </div>

                <!-- T&C -->
                <div class="wbp-terms-row">
                    <label>
                        <input type="checkbox" id="wbp-tandc" name="wbp-tandc" value="accepted">
                        <?php printf(
                            wp_kses(
                                __( 'I have read and agree to <a href="%1$s" target="_blank">Privacy Policy</a> and <a href="%2$s" target="_blank">Terms &amp; Conditions</a>.', 'woo-booking-pro' ),
                                [ 'a' => [ 'href' => [], 'target' => [] ] ]
                            ),
                            esc_url( $privacy_url ),
                            esc_url( $terms_url )
                        ); ?>
                    </label>
                    <div id="wbp-privacy-error" class="wbp-error" style="display:none">
                        <?php esc_html_e( 'Please accept the privacy policy and terms.', 'woo-booking-pro' ); ?>
                    </div>
                </div>

                <div class="wbp-nav-row">
                    <button class="wbp-btn wbp-btn-prev" id="wbp-prev-products">← <?php esc_html_e( 'Previous', 'woo-booking-pro' ); ?></button>
                    <button class="wbp-btn wbp-btn-next" id="wbp-add-to-cart"><?php esc_html_e( 'Add to Cart →', 'woo-booking-pro' ); ?></button>
                </div>

            </div>
        </div>
    </div><!-- /section 4 -->

</div><!-- /.wbp-booking-wrapper -->
