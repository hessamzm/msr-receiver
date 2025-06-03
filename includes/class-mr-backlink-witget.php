<?php
if (!defined('ABSPATH')) exit;

class MSR_Backlink_Widget extends WP_Widget {

    private $placements = [
        'homepage_sidebar'  => 'سایدبار صفحه اصلی',
        'post_sidebar'      => 'سایدبار نوشته',
        'footer'            => 'پایین سایت بخش فوتر',
    ];

    public function __construct() {
        parent::__construct(
            'msr_backlink_widget',
            'MSR Backlink Widget',
            ['description' => 'نمایش بک لینک‌های MSR بر اساس جایگاه و بازه زمانی']
        );
    }

    public function widget($args, $instance) {
        global $wpdb;

        $placement = !empty($instance['placement']) ? $instance['placement'] : '';

        echo $args['before_widget'];

        if (!$placement) {
            echo '<p>جایگاه بک لینک مشخص نشده است.</p>';
            echo $args['after_widget'];
            return;
        }

        // ارسال اطلاع جایگاه به سایت مبدا (ارسال هنگام نمایش)
        msr_send_active_placement_to_main_site($placement);

        $now = current_time('mysql');
        $table = $wpdb->prefix . 'msr_backlinks';

        $backlinks = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table
            WHERE (start_date IS NULL OR start_date <= %s)
              AND (end_date IS NULL OR end_date >= %s)
              AND JSON_CONTAINS(placements, %s)
            ORDER BY created_at DESC
        ", $now, $now, json_encode($placement)));

        if ($backlinks) {
            echo '<ul class="msr-backlink-list">';
            foreach ($backlinks as $link) {
                echo '<li><a href="' . esc_url($link->url) . '" target="_blank">' . esc_html($link->keyword) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>بک لینکی برای نمایش وجود ندارد.</p>';
        }

        echo $args['after_widget'];
    }

    public function form($instance) {
        $placement = !empty($instance['placement']) ? $instance['placement'] : '';

        // ارسال اطلاع جایگاه به سایت مبدا (ارسال هنگام بارگذاری فرم)
        if ($placement) {
            msr_send_active_placement_to_main_site($placement);
        }

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('placement'); ?>">جایگاه بک لینک:</label>
            <select id="<?php echo $this->get_field_id('placement'); ?>" name="<?php echo $this->get_field_name('placement'); ?>" class="widefat">
                <option value="">-- انتخاب جایگاه --</option>
                <?php foreach ($this->placements as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($placement, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['placement'] = (!empty($new_instance['placement'])) ? sanitize_text_field($new_instance['placement']) : '';

        // ارسال اطلاع جایگاه به سایت مبدا (ارسال هنگام به‌روزرسانی تنظیمات)
        if ($instance['placement']) {
            msr_send_active_placement_to_main_site($instance['placement']);
        }

        return $instance;
    }
}

/**
 * ارسال جایگاه فعال به سایت مبدا
 * 
 * @param string $placement جایگاه فعال
 */
function msr_send_active_placement_to_main_site($placement) {
    if (!class_exists('MR_JWT')) {
        error_log('[MSR] کلاس MR_JWT یافت نشد. ارسال جایگاه انجام نشد.');
        return;
    }

    $jwt = MR_JWT::build_site_jwt();
    $site_url = get_site_url();

    // آدرس سایت مبدا
    $main_site_url = 'https://msr.vaghayenegar.ir/wp-json/msr/v1/placements/backlink';

    $response = wp_remote_post($main_site_url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'site_url' => $site_url,
            'placement' => $placement,
            'timestamp' => current_time('mysql'),
        ]),
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
        error_log('[MSR] خطا در ارسال جایگاه به سایت مبدا: ' . $response->get_error_message());
    } else {
        $code = wp_remote_retrieve_response_code($response);
        error_log("[MSR] ارسال جایگاه $placement به سایت مبدا با کد پاسخ $code انجام شد.");
    }
}

// ثبت ابزارک
add_action('widgets_init', function() {
    register_widget('MSR_Backlink_Widget');
});
