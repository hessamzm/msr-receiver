<?php
// اطمینان از اینکه فایل مستقیماً اجرا نمی‌شود
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// حذف جدول اختصاصی افزونه
$table_name = $wpdb->prefix . 'msr_reports';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// در صورت نیاز: حذف گزینه‌هایی که ذخیره کرده‌ایم
// delete_option('msr_jwt_secret'); // اگر کلیدی در option ذخیره کرده‌ای

// در صورت نیاز: حذف فایل‌های موقتی یا داده‌های دیگر

// لاگ‌نویسی اختیاری (در صورت توسعه بیشتر)
error_log('MSR Receiver Plugin uninstalled and cleaned up.');
