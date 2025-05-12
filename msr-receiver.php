<?php
/**
 * Plugin Name: MSR Report Receiver
 * Description: افزونه دریافت رپورتاژ خبری برای سایت مقصد با پشتیبانی از JWT.
 * Version: 1.0.0
 * Plugin URI: https://github.com/hessamzm/MSR-RECEIVER
 * Author: hessamzmz
 * Author URI: https://github.com/hessamzm/MSR-RECEIVER
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: msr-receiver
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define('MSR_RECEIVER_VERSION', '1.0.0');
define('MSR_RECEIVER_PLUGIN_DIR', plugin_dir_path(__FILE__));

// فایل‌های کمکی
require_once MSR_RECEIVER_PLUGIN_DIR . 'includes/class-mr-jwt.php';
require_once MSR_RECEIVER_PLUGIN_DIR . 'includes/class-mr-endpoints.php';
require_once MSR_RECEIVER_PLUGIN_DIR . 'includes/class-mr-handler.php';
require_once MSR_RECEIVER_PLUGIN_DIR . 'includes/class-mr-sql.php';
require_once MSR_RECEIVER_PLUGIN_DIR . 'includes/class-mr-dashboard.php';

// اجرای API
add_action('rest_api_init', ['MR_Endpoints', 'register_routes']);

add_action('admin_menu', 'msr_register_admin_menu');
function msr_register_admin_menu() {
    add_menu_page(
        'MSR Receiver',              // عنوان صفحه
        'MSR رپورتاژ',               // نام در منو
        'manage_options',            // دسترسی
        'msr-receiver-dashboard',    // slug
        'msr_render_dashboard_page', // تابع نمایش
        'dashicons-media-text',      // آیکون
        26
    );

    add_submenu_page(
        'msr-receiver-dashboard',
        'تنظیمات افزونه',
        'تنظیمات',
        'manage_options',
        'msr-receiver-settings',
        'msr_render_settings_page'
    );
}
register_activation_hook(__FILE__, 'msr_send_jwt_to_main_site');
register_activation_hook(__FILE__, 'msr_create_database_table');