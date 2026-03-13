<?php

namespace Divi_Carousel_Free;

defined('ABSPATH') || exit;

class Admin_Notice
{
    private $notice_key = 'dcf_bf_notice_dismissed';
    private $bf_launch_date;
    private $bf_end_date;
    private $cm_start_date;
    private $cm_end_date;

    public function __construct()
    {
        // Black Friday: November 27 - November 30, 2025
        $this->bf_launch_date = strtotime('2025-11-27');
        $this->bf_end_date = strtotime('2025-11-30 23:59:59');

        // Cyber Monday / Holiday Sale: December 1 - December 31, 2025
        $this->cm_start_date = strtotime('2025-12-01');
        $this->cm_end_date = strtotime('2025-12-31 23:59:59');

        add_action('admin_notices', [$this, 'display_sale_notice']);
        add_action('wp_ajax_dcf_dismiss_notice', [$this, 'dismiss_notice']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_notice_styles']);
    }

    public function should_show_notice()
    {
        // Don't show if user dismissed it
        if (get_user_meta(get_current_user_id(), $this->notice_key, true)) {
            return false;
        }

        // Check if we're in any sale period
        $now = current_time('timestamp');
        $in_black_friday = ($now >= $this->bf_launch_date && $now <= $this->bf_end_date);
        $in_cyber_monday = ($now >= $this->cm_start_date && $now <= $this->cm_end_date);

        if (!$in_black_friday && !$in_cyber_monday) {
            return false;
        }

        // Only show on plugin pages or dashboard
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }

        return true;
    }

    public function get_sale_config()
    {
        $now = current_time('timestamp');

        // Black Friday period
        if ($now >= $this->bf_launch_date && $now <= $this->bf_end_date) {
            $days_left = max(0, ceil(($this->bf_end_date - $now) / DAY_IN_SECONDS));
            return [
                'type' => 'black_friday',
                'badge' => '🎉 BLACK FRIDAY - 50% OFF',
                'title' => 'DiviStack Lifetime Pass',
                'original_price' => '$159',
                'price' => '$79.50',
                'sites' => '15 websites',
                'plugins' => '14 current + future plugins',
                'days_left' => $days_left,
            ];
        }

        // Cyber Monday / Holiday Sale period
        if ($now >= $this->cm_start_date && $now <= $this->cm_end_date) {
            $days_left = max(0, ceil(($this->cm_end_date - $now) / DAY_IN_SECONDS));
            return [
                'type' => 'cyber_monday',
                'badge' => '🎄 HOLIDAY SALE - 50% OFF',
                'title' => 'DiviStack Lifetime Pass',
                'original_price' => '$159',
                'price' => '$79.50',
                'sites' => '15 websites',
                'plugins' => '14 current + future plugins',
                'days_left' => $days_left,
            ];
        }

        return null;
    }

    public function display_sale_notice()
    {
        if (!$this->should_show_notice()) {
            return;
        }

        $config = $this->get_sale_config();
        if (!$config) {
            return;
        }

        $days_left = $config['days_left'];
?>
        <div class="notice dcf-bf-notice is-dismissible" data-notice="<?php echo esc_attr($this->notice_key); ?>">
            <div class="dcf-bf-notice-content">
                <div class="dcf-bf-left">
                    <div class="dcf-bf-badge-wrapper">
                        <span class="dcf-bf-badge"><?php echo esc_html($config['badge']); ?></span>
                    </div>
                    <div class="dcf-bf-info">
                        <h3 class="dcf-bf-title"><?php echo esc_html($config['title']); ?></h3>
                        <p class="dcf-bf-details">
                            <?php echo esc_html($config['plugins']); ?> • <?php echo esc_html($config['sites']); ?> • Lifetime updates & support
                        </p>
                    </div>
                </div>
                <div class="dcf-bf-right">
                    <div class="dcf-bf-price-box">
                        <span class="dcf-bf-original"><?php echo esc_html($config['original_price']); ?></span>
                        <span class="dcf-bf-price"><?php echo esc_html($config['price']); ?></span>
                        <?php if ($days_left > 0) : ?>
                            <span class="dcf-bf-timer"><?php echo $days_left; ?> day<?php echo $days_left > 1 ? 's' : ''; ?> left</span>
                        <?php else : ?>
                            <span class="dcf-bf-timer">Ends today!</span>
                        <?php endif; ?>
                    </div>
                    <a href="https://divistack.io/deal" target="_blank" class="dcf-bf-button">
                        Get 50% OFF →
                    </a>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $(document).on('click', '.dcf-bf-notice .notice-dismiss', function() {
                    var noticeKey = $(this).closest('.dcf-bf-notice').data('notice');
                    $.post(ajaxurl, {
                        action: 'dcf_dismiss_notice',
                        notice_key: noticeKey,
                        nonce: '<?php echo wp_create_nonce('dcf_dismiss_notice'); ?>'
                    });
                });
            });
        </script>
<?php
    }

    public function dismiss_notice()
    {
        if (!isset($_POST['action']) || $_POST['action'] !== 'dcf_dismiss_notice') {
            return;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dcf_dismiss_notice')) {
            return;
        }

        if (!isset($_POST['notice_key'])) {
            return;
        }

        update_user_meta(get_current_user_id(), sanitize_text_field($_POST['notice_key']), true);
        wp_send_json_success();
    }

    public function enqueue_notice_styles()
    {
        if (!$this->should_show_notice()) {
            return;
        }

        wp_add_inline_style('wp-admin', '
            .dcf-bf-notice {
                position: relative;
                border: none;
                border-left: 4px solid #5733FF;
                background: linear-gradient(to right, #f0f9ff, #ffffff);
                padding: 0 !important;
                margin: 16px 0;
                border-radius: 0;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            
            .dcf-bf-notice-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
                padding: 16px 50px 16px 20px;
            }
            
            .dcf-bf-left {
                display: flex;
                align-items: center;
                gap: 16px;
                flex: 1;
            }
            
            .dcf-bf-badge-wrapper {
                flex-shrink: 0;
            }
            
            .dcf-bf-badge {
                display: inline-block;
                padding: 4px 10px;
                background: #5733FF;
                color: #fff;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.5px;
                text-transform: uppercase;
                border-radius: 4px;
            }
            
            .dcf-bf-info {
                flex: 1;
            }
            
            .dcf-bf-title {
                font-size: 15px;
                font-weight: 600;
                color: #0f172a;
                margin: 0 0 3px 0;
            }
            
            .dcf-bf-details {
                font-size: 13px;
                color: #64748b;
                margin: 0;
            }
            
            .dcf-bf-right {
                display: flex;
                align-items: center;
                gap: 16px;
                flex-shrink: 0;
            }
            
            .dcf-bf-price-box {
                text-align: right;
            }
            
            .dcf-bf-original {
                display: block;
                text-decoration: line-through;
                color: #94a3b8;
                font-size: 12px;
                line-height: 1.2;
            }
            
            .dcf-bf-price {
                display: block;
                color: #5733FF;
                font-size: 24px;
                font-weight: 700;
                line-height: 1.2;
                margin-bottom: 2px;
            }
            
            .dcf-bf-timer {
                display: block;
                font-size: 11px;
                font-weight: 600;
                color: #dc2626;
            }
            
            .dcf-bf-button {
                display: inline-flex;
                align-items: center;
                padding: 11px 22px;
                background: #5733FF;
                color: #fff;
                text-decoration: none;
                font-weight: 600;
                font-size: 14px;
                border-radius: 6px;
                transition: all 0.2s ease;
                white-space: nowrap;
            }
            
            .dcf-bf-button:hover {
                background: #1d4ed8;
                color: #fff;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            }
            
            .dcf-bf-notice .notice-dismiss {
                top: 50%;
                transform: translateY(-50%);
                color: #cbd5e1;
                right: 12px;
            }
            
            .dcf-bf-notice .notice-dismiss:hover {
                color: #64748b;
            }
            
            .dcf-bf-notice .notice-dismiss:before {
                color: currentColor;
            }
            
            @media screen and (max-width: 1200px) {
                .dcf-bf-notice-content {
                    padding: 14px 40px 14px 16px;
                }
                
                .dcf-bf-left {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 8px;
                }
                
                .dcf-bf-right {
                    flex-direction: column;
                    align-items: flex-end;
                    gap: 10px;
                }
            }
            
            @media screen and (max-width: 782px) {
                .dcf-bf-notice-content {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 12px;
                    padding: 14px 16px;
                }
                
                .dcf-bf-right {
                    flex-direction: row;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .dcf-bf-price-box {
                    text-align: left;
                }
                
                .dcf-bf-button {
                    flex: 1;
                    justify-content: center;
                }
                
                .dcf-bf-notice .notice-dismiss {
                    top: 10px;
                    right: 10px;
                    transform: none;
                }
            }
        ');
    }
}
