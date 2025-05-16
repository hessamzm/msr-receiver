<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// ایجاد جدول هنگام استارت افزونه
function msr_create_database_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'msr_reports';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        wp_post_id BIGINT NOT NULL,
        received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(50) DEFAULT 'draft',
        from_ip VARCHAR(100),
        log TEXT
    ) $charset_collate;";
     // جدول تبلیغات دریافتی
    $table_ads = $wpdb->prefix . 'msr_ads';
    $sql = "CREATE TABLE IF NOT EXISTS $table_ads (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        media_url TEXT,
        media_type VARCHAR(20),
        alt_text TEXT,
        target_link TEXT,
        start_date DATETIME,
        end_date DATETIME,
        received_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// حذف جدول هنگام حذف افزونه
register_uninstall_hook(__FILE__, 'msr_remove_database_table');
function msr_remove_database_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'msr_reports';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
