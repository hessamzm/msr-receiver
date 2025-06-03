<?php 
if ( ! defined( 'ABSPATH' ) ) exit;
function msr_render_dashboard_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'msr_reports';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY received_at DESC LIMIT 50");

    echo '<div class="wrap"><h1>داشبورد رپورتاژها</h1>';
    echo '<table class="widefat"><thead><tr><th>عنوان</th><th>زمان انتشار</th><th>لینک</th></tr></thead><tbody>';

    foreach ($results as $row) {
        $post = get_post($row->wp_post_id);
        if ($post) {
            echo '<tr>';
            echo '<td>' . esc_html($post->post_title) . '</td>';
            echo '<td>' . esc_html($row->received_at) . '</td>';
            echo '<td><a href="' . esc_url(get_permalink($post->ID)) . '" target="_blank">مشاهده</a></td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table></div>';
}

function msr_send_jwt_to_main_site() {
    if (!class_exists('MR_JWT')) return;

    $jwt = MR_JWT::build_site_jwt();

    $response = wp_remote_post('https://msr.vaghayenegar.ir/wp-json/msr/v1/jwt/resiver', [
        'method'  => 'POST',
        'headers' => [
            'Authorization' => 'Bearer ' . $jwt,
            'Content-Type'  => 'application/json'
        ],
        'body'    => json_encode([
            'site_name' => get_bloginfo('name'),
            'site_url'  => get_site_url()
        ]),
        'timeout' => 10
    ]);

    if (is_wp_error($response)) {
        error_log('[MSR] JWT ارسال نشد: ' . $response->get_error_message());
    } else {
        error_log('[MSR] JWT ارسال شد: ' . wp_remote_retrieve_response_code($response));
    }
}
function msr_render_settings_page() {
    // ذخیره تنظیمات
    if (isset($_POST['msr_settings_nonce']) && wp_verify_nonce($_POST['msr_settings_nonce'], 'msr_settings')) {
        update_option('msr_default_author_id', intval($_POST['default_author']));
        echo '<div class="notice notice-success"><p>تنظیمات ذخیره شد.</p></div>';
    }
    if (isset($_POST['resend_jwt'])) {
        msr_send_jwt_to_main_site();
        echo '<div class="notice notice-info"><p>JWT مجدداً ارسال شد.</p></div>';
    }

    $users = get_users(['fields' => ['ID', 'display_name']]);
    $current = get_option('msr_default_author_id', 1);

    // استایل برای ستون چپ و بخش اصلی
    echo '<style>
    .msr-settings-wrapper {
        display: flex;
        gap: 20px;
        margin-top: 20px;
    }
    .msr-settings-sidebar {
        flex: 0 0 300px;
        background: #f9f9f9;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow-y: auto;
        max-height: 80vh;
    }
    .msr-settings-main {
        flex: 1 1 auto;
    }
    .msr-settings-sidebar h2, .msr-settings-sidebar h3 {
        margin-top: 20px;
    }
    pre {
        background:#f7f7f7;
        padding:10px;
        border-radius:5px;
        overflow-x:auto;
    }
    </style>';

    echo '<div class="wrap"><h1>تنظیمات MSR</h1>';

    echo '<div class="msr-settings-wrapper">';

    // ستون چپ (سایدبار)
    echo '<div class="msr-settings-sidebar">';

    // راهنمای تبلیغات
    echo '<h2>راهنمای استفاده از تبلیغات</h2>';
    echo '<p>برای نمایش تبلیغات در بخش‌های مختلف سایت  می‌توانید از شورت‌کد زیر استفاده کنید:</p>';
    echo '<pre>[mr_ads placement="homepage_banner"]</pre>';
    echo '<p>پارامتر <code>placement</code> مشخص‌کننده جایگاه تبلیغ است. جایگاه‌های معتبر عبارتند از:</p>';
    echo '<ul>
        <li><code>homepage_banner</code> - بنر صفحه اصلی</li>
        <li><code>homepage_sidebar</code> - سایدبار صفحه اصلی</li>
        <li><code>post_sidebar</code> - سایدبار نوشته</li>
        <li><code>post_bottom</code> - پایین نوشته</li>
        <li><code>popup</code> - پاپ‌آپ</li>
    </ul>';

    echo '<h3>نمونه کد PHP برای تبلیغات</h3>';
    echo '<pre>';
    $ad_placements = [
        'homepage_banner',
        'homepage_sidebar',
        'post_sidebar',
        'post_bottom',
        'popup',
    ];
    foreach ($ad_placements as $placement) {
        echo htmlspecialchars('<?php echo do_shortcode(\'[mr_ads placement="' . $placement . '"]\'); ?>') . "\n";
    }
    echo '</pre>';

    // راهنمای بک لینک
    echo '<hr>';
    echo '<h2>راهنمای استفاده از بک لینک‌ها</h2>';
    echo '<p>برای نمایش بک لینک‌ها در بخش‌های مختلف سایت  می‌توانید از شورت‌کد زیر استفاده کنید:</p>';
    echo '<pre>[msr_backlink placement="homepage_sidebar"]</pre>';
    echo '<p>پارامتر <code>placement</code> مشخص‌کننده جایگاه بک لینک است. جایگاه‌های معتبر عبارتند از:</p>';
    echo '<ul>
        <li><code>homepage_sidebar</code> - سایدبار صفحه اصلی</li>
        <li><code>post_sidebar</code> - سایدبار نوشته</li>
        <li><code>footer</code> - پایین سایت بخش فوتر</li>
    </ul>';

    echo '<h3>نمونه کد PHP برای بک لینک‌ها</h3>';
    echo '<pre>';
    $backlink_placements = [
        'homepage_sidebar',
        'post_sidebar',
        'footer',
    ];
    foreach ($backlink_placements as $placement) {
        echo htmlspecialchars('<?php echo do_shortcode(\'[msr_backlink placement="' . $placement . '"]\'); ?>') . "\n";
    }
    echo '</pre>';

    echo '</div>'; // پایان سایدبار

    // بخش اصلی تنظیمات
    echo '<div class="msr-settings-main">';
    echo '<form method="post">';
    wp_nonce_field('msr_settings', 'msr_settings_nonce');

    echo '<table class="form-table"><tr><th>نویسنده پیش‌فرض</th><td>';
    echo '<select name="default_author">';
    foreach ($users as $user) {
        $selected = ($user->ID == $current) ? 'selected' : '';
        echo '<option value="' . $user->ID . '" ' . $selected . '>' . esc_html($user->display_name) . '</option>';
    }
    echo '</select></td></tr></table>';

    submit_button('ذخیره تنظیمات');
    echo '</form>';

    echo '<form method="post" style="margin-top:30px">';
    submit_button('ارسال مجدد JWT', 'secondary', 'resend_jwt');
    echo '</form>';

    echo '</div>'; // پایان بخش اصلی

    echo '</div>'; // پایان wrapper

    echo '</div>'; // پایان wrap
}
