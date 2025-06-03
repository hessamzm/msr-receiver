<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_Ads_Widget extends WP_Widget {

    private $placements = [
        'homepage_banner'   => 'بنر صفحه اصلی',
        'homepage_sidebar'  => 'سایدبار صفحه اصلی',
        'post_sidebar'      => 'سایدبار نوشته',
        'post_bottom'       => 'پایین نوشته',
        'popup'             => 'پاپ‌آپ',
    ];

    public function __construct() {
        parent::__construct(
            'mr_ads_widget',
            'MR Ads Widget',
            [ 'description' => 'نمایش تبلیغات MSR بر اساس بازهٔ زمانی و جایگاه' ]
        );

        // وقتی ابزارک فعال می‌شود (در تنظیمات وردپرس)، جایگاه فعال را به سایت مبدا ارسال کن
        add_action('widget_update_callback', [$this, 'send_active_placement_to_source'], 10, 3);
    }

    public function widget( $args, $instance ) {
        global $wpdb;
        echo $args['before_widget'];

        $placement = !empty($instance['placement']) ? $instance['placement'] : '';

        echo $args['before_title'] . 'تبلیغات' . $args['after_title'];

        if ( ! $placement ) {
            echo '<p>جایگاه تبلیغ مشخص نشده است.</p>';
            echo $args['after_widget'];
            return;
        }

        $now = current_time( 'mysql' );

        // گرفتن تبلیغات معتبر بر اساس جایگاه
        $table = $wpdb->prefix . 'msr_ads_vaghaye';
        $ads = $wpdb->get_results( $wpdb->prepare("
            SELECT * FROM $table
            WHERE ( start_date IS NULL OR start_date <= %s )
              AND ( end_date   IS NULL OR end_date   >= %s )
              AND JSON_CONTAINS(placements, %s)
            ORDER BY received_at DESC
            LIMIT 1
        ", $now, $now, json_encode($placement)) );

        if ( $ads ) {
            $ad = $ads[0];
            echo '<div class="mr-ads-widget" style="width:100%;max-width:100%;height:auto;margin:0 auto;">';

            if ( $ad->target_link ) {
                echo '<a href="' . esc_url( $ad->target_link ) . '" target="_blank">';
            }

            if ( $ad->media_type === 'video' ) {
                echo '<video controls style="max-width:100%;">';
                echo '<source src="' . esc_url( $ad->media_url ) . '" type="video/mp4">';
                echo 'ویدئوی شما پشتیبانی نمی‌شود.';
                echo '</video>';
            } else {
                echo '<img src="' . esc_url( $ad->media_url ) . '" alt="' . esc_attr( $ad->alt_text ) . '" style="max-width:100%;height:auto;">';
            }

            if ( $ad->target_link ) {
                echo '</a>';
            }

            echo '</div>';
        } else {
            echo '<p>تبلیغی برای نمایش وجود ندارد.</p>';
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $placement = !empty($instance['placement']) ? $instance['placement'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('placement'); ?>">جایگاه تبلیغ:</label>
            <select id="<?php echo $this->get_field_id('placement'); ?>" name="<?php echo $this->get_field_name('placement'); ?>" class="widefat">
                <option value="">-- انتخاب جایگاه --</option>
                <?php
                foreach ($this->placements as $key => $label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($key),
                        selected($placement, $key, false),
                        esc_html($label)
                    );
                }
                ?>
            </select>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = [];
        $instance['placement'] = (!empty($new_instance['placement'])) ? sanitize_text_field($new_instance['placement']) : '';

        // بعد از ذخیره، جایگاه فعال را به سایت مبدا اطلاع بده
        $this->send_active_placement_to_source($instance, $this, []);

        return $instance;
    }

    // ارسال جایگاه فعال به سایت مبدا (زمانی که ابزارک فعال یا بروزرسانی می‌شود)
    public function send_active_placement_to_source($instance, $widget, $args) {
        $placement = !empty($instance['placement']) ? $instance['placement'] : '';

        if (!$placement) return $instance;

        // آدرس API سایت مبدا (آدرس واقعی سایت مبدا را جایگزین کنید)
        $source_url = 'https://msr.vaghayenegar.ir/wp-json/msr/v1/ads/placements';

        // گرفتن توکن JWT برای احراز هویت
        $jwt = MR_JWT::build_site_jwt();

        $response = wp_remote_post($source_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $jwt,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(['placement' => $placement]),
            'timeout' => 5,
        ]);

        if (is_wp_error($response)) {
            error_log('Error sending active placement: ' . $response->get_error_message());
        } else {
            $code = wp_remote_retrieve_response_code($response);
            if ($code < 200 || $code >= 300) {
                $body = wp_remote_retrieve_body($response);
                error_log("Error sending active placement, HTTP code: {$code}, response: {$body}");
            }
        }

        return $instance;
    }
}

// ثبت ابزارک
add_action( 'widgets_init', function(){
    register_widget( 'MR_Ads_Widget' );
});

// تعریف شورتکد برای نمایش تبلیغ بر اساس جایگاه
function mr_ads_shortcode($atts) {
    global $wpdb;
    $atts = shortcode_atts([
        'placement' => '',
    ], $atts, 'mr_ads');

    if (!$atts['placement']) {
        return '<p>جایگاه تبلیغ مشخص نشده است.</p>';
    }

    $now = current_time('mysql');
    $table = $wpdb->prefix . 'msr_ads_vaghaye';

    $ads = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table
        WHERE (start_date IS NULL OR start_date <= %s)
          AND (end_date IS NULL OR end_date >= %s)
          AND JSON_CONTAINS(placements, %s)
        ORDER BY received_at DESC
        LIMIT 1
    ", $now, $now, json_encode($atts['placement'])));

    if ($ads) {
        $ad = $ads[0];
        $html = '<div class="mr-ads-widget" style="width:100%;max-width:100%;height:auto;margin:0 auto;">';

        if ($ad->target_link) {
            $html .= '<a href="' . esc_url($ad->target_link) . '" target="_blank">';
        }

        if ($ad->media_type === 'video') {
            $html .= '<video controls style="max-width:100%;">';
            $html .= '<source src="' . esc_url($ad->media_url) . '" type="video/mp4">';
            $html .= 'ویدئوی شما پشتیبانی نمی‌شود.';
            $html .= '</video>';
        } else {
            $html .= '<img src="' . esc_url($ad->media_url) . '" alt="' . esc_attr($ad->alt_text) . '" style="max-width:100%;height:auto;">';
        }

        if ($ad->target_link) {
            $html .= '</a>';
        }

        $html .= '</div>';

        return $html;
    } else {
        return '<p>تبلیغی برای نمایش وجود ندارد.</p>';
    }
}
add_shortcode('mr_ads', 'mr_ads_shortcode');
