<?php
// اطمینان از اینکه فایل مستقیماً اجرا نمی‌شود
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// حذف جدول اختصاصی افزونه
$tables = [
    $wpdb->prefix . 'msr_reports',
    $wpdb->prefix . 'msr_ads_vaghaye',
    $wpdb->prefix . 'msr_backlinks'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// حذف گزینه‌های ذخیره‌شده (در صورت نیاز)
delete_option('msr_jwt_secret'); // اگر کلیدی در option ذخیره کرده‌ای

// لاگ‌نویسی اختیاری
error_log('MSR Receiver Plugin uninstalled and cleaned up.');
