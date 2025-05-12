<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_Handler {

    /**
     * ثبت لاگ در جدول msr_reports
     */
    public static function add_log($post_id, $message) {
        global $wpdb;

        $table = $wpdb->prefix . 'msr_reports';
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT log FROM $table WHERE wp_post_id = %d", $post_id
        ));

        $log_data = $existing ? json_decode($existing, true) : [];
        $log_data[] = [
            'timestamp' => current_time('mysql'),
            'message'   => $message
        ];

        $wpdb->update($table, [
            'log' => json_encode($log_data)
        ], ['wp_post_id' => $post_id]);
    }

    /**
     * به‌روزرسانی وضعیت پست در جدول افزونه
     */
    public static function update_status($post_id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'msr_reports';
        $wpdb->update($table, [
            'status' => sanitize_text_field($status)
        ], ['wp_post_id' => $post_id]);
    }

    /**
     * بررسی وجود پست در جدول msr_reports
     */
    public static function post_exists_in_msr($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'msr_reports';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE wp_post_id = %d", $post_id
        ));
        return $exists > 0;
    }

    /**
     * گرفتن وضعیت فعلی پست
     */
    public static function get_status($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'msr_reports';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM $table WHERE wp_post_id = %d", $post_id
        ));
    }

}
