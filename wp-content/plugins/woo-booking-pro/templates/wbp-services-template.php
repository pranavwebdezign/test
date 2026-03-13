<?php
/**
 * Template Name: WooBooking – Services
 *
 * A standalone page template that renders the multi-step booking flow.
 * Compatible with Divi, WPBakery and Gutenberg – you may also use
 * the [woo_booking] shortcode inside any page builder instead.
 *
 * @package WooBookingPro
 */

defined( 'ABSPATH' ) || exit;

// Force assets to load even though WBP_Frontend::is_booking_page() detects via template slug
if ( ! wp_script_is( 'wbp-booking', 'enqueued' ) ) {
    WBP_Frontend::enqueue();
}

get_header();

$settings    = WBP_Settings::all();
$banner_url  = $settings['banner_image_url'] ?? '';
$step_labels = $settings['step_labels'] ?? [];
?>

<div id="wbp-main-content">

    <!-- ── Banner ──────────────────────────────── -->
    <div class="wbp-title-area" style="background: #000 <?php echo $banner_url ? 'url(' . esc_url( $banner_url ) . ') center center / cover no-repeat' : ''; ?>">
        <div class="wbp-page-banner-container">
            <div class="wbp-page-title">
                <h1><?php the_title(); ?></h1>
            </div>

            <!-- Step icons bar -->
            <div class="wbp-banner-steps">
                <?php
                $step_icons = $settings['step_icons'] ?? [];
                for ( $i = 0; $i < 4; $i++ ) :
                ?>
                <div class="wbp-banner-step-col" id="wbp-banner-step-<?php echo $i + 1; ?>">
                    <div class="wbp-banner-step-icon">
                        <?php if ( ! empty( $step_icons[ $i ] ) ) : ?>
                            <img src="<?php echo esc_url( $step_icons[ $i ] ); ?>" alt="">
                        <?php else : ?>
                            <span class="wbp-step-num-badge"><?php echo $i + 1; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="wbp-banner-step-label">
                        <p><strong><?php echo wp_kses_post( nl2br( $step_labels[ $i ] ?? '' ) ); ?></strong></p>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div><!-- /.wbp-title-area -->

    <!-- ── Booking Form ────────────────────────── -->
    <?php echo do_shortcode( '[woo_booking]' ); ?>

</div><!-- /#wbp-main-content -->

<?php get_footer(); ?>
