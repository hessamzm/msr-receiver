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
function msr_render_settings_page() {
    // ذخیره تنظیمات
    if (isset($_POST['msr_settings_nonce']) && wp_verify_nonce($_POST['msr_settings_nonce'], 'msr_settings')) {
        update_option('msr_default_author_id', intval($_POST['default_author']));
        echo '<div class="notice notice-success"><p>تنظیمات ذخیره شد.</p></div>';
    }

    // ارسال مجدد JWT
    if (isset($_POST['resend_jwt'])) {
        $jwt = MR_JWT::build_site_jwt();
        $response = wp_remote_post('https://web-coffee.ir/msr/jwt/resiver', [
            'headers' => ['Authorization' => 'Bearer ' . $jwt],
            'body'    => json_encode(['site_url' => get_site_url()]),
        ]);

        echo '<div class="notice notice-info"><p>JWT مجدداً ارسال شد.</p></div>';
    }

    $users = get_users(['fields' => ['ID', 'display_name']]);
    $current = get_option('msr_default_author_id', 1);

    echo '<div class="wrap"><h1>تنظیمات MSR</h1>';
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
    echo '</form></div>';
}
