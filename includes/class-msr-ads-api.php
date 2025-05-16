<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MSR_Ads_API {

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        register_rest_route( 'msr/v1', '/ads/receive', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'receive_ad' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'msr/v1', '/ads/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ __CLASS__, 'delete_ad' ],
            'permission_callback' => '__return_true',
        ] );
    }
    }

    public static function receive_ad( WP_REST_Request $request ) {
        global $wpdb;

        // 1. JWT از هدر
        $jwt = self::get_token_from_header( $request );
        if ( ! $jwt || ! MSR_JWT::verify_token( $jwt ) ) {
            return new WP_Error( 'invalid_jwt', 'توکن JWT معتبر نیست.', [ 'status' => 403 ] );
        }

        // 2. پارامترهای تبلیغ
        $media_url   = esc_url_raw( $request->get_param( 'media_url' ) );
        $media_type  = sanitize_key( $request->get_param( 'media_type' ) );
        $alt_text    = sanitize_text_field( $request->get_param( 'alt_text' ) );
        $target_link = esc_url_raw( $request->get_param( 'target_link' ) );
        $start_date  = sanitize_text_field( $request->get_param( 'start_date' ) );
        $end_date    = sanitize_text_field( $request->get_param( 'end_date' ) );
        $site_ids    = $request->get_param( 'site_ids' );
        if ( ! is_array( $site_ids ) ) {
            return new WP_Error( 'invalid_sites', 'پارامتر site_ids باید آرایه باشد.', [ 'status' => 400 ] );
        }

        // 3. درج تبلیغ در جدول اصلی msr_ads
        $result = $wpdb->insert(
            "{$wpdb->prefix}msr_ads",
            [
                'media_url'   => $media_url,
                'media_type'  => $media_type,
                'alt_text'    => $alt_text,
                'target_link' => $target_link,
                'start_date'  => $start_date ?: null,
                'end_date'    => $end_date   ?: null,
                'created_at'  => current_time( 'mysql' ),
            ],
            [ '%s','%s','%s','%s','%s','%s','%s' ]
        );

        if ( ! $result ) {
            return new WP_Error( 'db_insert_failed', 'خطا در ذخیره تبلیغ.', [ 'status' => 500 ] );
        }

        $ad_id = $wpdb->insert_id;

        // 4. ارتباط با سایت‌های مقصد
        foreach ( $site_ids as $sid ) {
            $sid = intval( $sid );
            $wpdb->insert(
                "{$wpdb->prefix}msr_ads_sites",
                [ 'ad_id' => $ad_id, 'site_id' => $sid ],
                [ '%d', '%d' ]
            );
        }

        return [
            'message' => 'تبلیغ با موفقیت دریافت و ذخیره شد.',
            'ad_id'   => $ad_id,
        ];
    }
        public static function delete_ad( WP_REST_Request $request ) {
        global $wpdb;
        // 1. JWT از هدر
        $jwt = self::get_token_from_header( $request );
        if ( ! $jwt || ! MSR_JWT::verify_token( $jwt ) ) {
            return new WP_Error( 'invalid_jwt', 'توکن JWT معتبر نیست.', [ 'status' => 403 ] );
        }

        // 2. آیدی تبلیغ از URL
        $ad_id = intval( $request->get_param( 'id' ) );
        if ( ! $ad_id ) {
            return new WP_Error( 'invalid_id', 'آیدی تبلیغ نامعتبر است.', [ 'status' => 400 ] );
        }

        $table_ads       = $wpdb->prefix . 'msr_ads';
        $table_ads_sites = $wpdb->prefix . 'msr_ads_sites';

        // 3. بررسی وجود تبلیغ
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_ads} WHERE id = %d",
            $ad_id
        ) );
        if ( ! $exists ) {
            return new WP_Error( 'not_found', 'تبلیغ مورد نظر یافت نشد.', [ 'status' => 404 ] );
        }

        // 4. حذف روابط سایت‌ها (اگر FK با CASCADE نیست)
        $wpdb->delete( $table_ads_sites, [ 'ad_id' => $ad_id ], [ '%d' ] );

        // 5. حذف خود تبلیغ
        $deleted = $wpdb->delete( $table_ads, [ 'id' => $ad_id ], [ '%d' ] );
        if ( ! $deleted ) {
            return new WP_Error( 'delete_failed', 'خطا در حذف تبلیغ.', [ 'status' => 500 ] );
        }

        return [ 'message' => "تبلیغ شماره {$ad_id} با موفقیت حذف شد." ];
    }

    private

    private static function get_token_from_header( $request ) {
        $auth = $request->get_header( 'authorization' );
        if ( preg_match( '/Bearer\s(\S+)/', $auth, $m ) ) {
            return $m[1];
        }
        return false;
    }
}

// بارگذاری و راه‌اندازی
add_action( 'plugins_loaded', function(){
    require_once MSR_SENDER_DIR . 'includes/class-msr-jwt.php';   // اگر کلاس JWT در فایلی دیگر است
    MSR_Ads_API::init();
});
