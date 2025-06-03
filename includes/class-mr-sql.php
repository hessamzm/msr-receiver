<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ایجاد جدول هنگام استارت افزونه
function msr_create_database_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // جدول گزارشات
    $table_reports = $wpdb->prefix . 'msr_reports';
    $sql1 = "CREATE TABLE IF NOT EXISTS $table_reports (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        wp_post_id BIGINT NOT NULL,
        received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(50) DEFAULT 'draft',
        from_ip VARCHAR(100),
        log TEXT
    ) $charset_collate;";

    // جدول تبلیغات دریافتی
    $table_ads = $wpdb->prefix . 'msr_ads_vaghaye';
    $sql2 = "CREATE TABLE IF NOT EXISTS $table_ads (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        media_url TEXT,
        media_type VARCHAR(20),
        alt_text TEXT,
        target_link TEXT,
        start_date DATETIME,
        end_date DATETIME,
        placements TEXT NULL,
        received_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    // جدول بک لینک‌ها
    $table_backlinks = $wpdb->prefix . 'msr_backlinks';
    $sql3 = "CREATE TABLE IF NOT EXISTS $table_backlinks (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        keyword VARCHAR(255) NOT NULL,
        url TEXT NOT NULL,
        start_date DATETIME DEFAULT NULL,
        end_date DATETIME DEFAULT NULL,
        placements TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
}


// حذف جداول هنگام حذف افزونه
function msr_remove_database_table() {
    global $wpdb;
    $table1 = $wpdb->prefix . 'msr_reports';
    $table2 = $wpdb->prefix . 'msr_ads_vaghaye';
    $table3 = $wpdb->prefix . 'msr_backlinks';

    $wpdb->query("DROP TABLE IF EXISTS $table1");
    $wpdb->query("DROP TABLE IF EXISTS $table2");
    $wpdb->query("DROP TABLE IF EXISTS $table3");
}