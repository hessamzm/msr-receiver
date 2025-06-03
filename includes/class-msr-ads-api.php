<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MSR_Ads_API {

    public static function init() {
        // این متد روی هوک rest_api_init اجرا می‌شود
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        // دریافت تبلیغ
        register_rest_route( 'msr/v1', '/ads/receive', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'receive_ad' ],
            'permission_callback' => '__return_true',
        ] );

        // حذف تبلیغ
        register_rest_route( 'msr/v1', '/ads/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ __CLASS__, 'delete_ad' ],
            'permission_callback' => '__return_true',
        ] );
        register_rest_route('msr/v1', '/ads/placements', [
    'methods'  => 'GET',
    'callback' => [__CLASS__, 'get_active_placements'],
    'permission_callback' => '__return_true',  // یا امنیت مورد نظر خودت
]);
    }

    public static function receive_ad( WP_REST_Request $request ) {
        global $wpdb;

        // 1. بررسی JWT
        $jwt = self::get_token_from_header( $request );
        if ( ! $jwt || ! MR_JWT::verify_token( $jwt ) ) {
            return new WP_Error( 'invalid_jwt', 'توکن JWT معتبر نیست.', [ 'status' => 403 ] );
        }

        // 2. پارامترها
        $media_url   = esc_url_raw( $request->get_param( 'media_url' ) );
        $media_type  = sanitize_key ( $request->get_param( 'media_type' ) );
        $alt_text    = sanitize_text_field( $request->get_param( 'alt_text' ) );
        $target_link = esc_url_raw( $request->get_param( 'target_link' ) );
        $start_date  = sanitize_text_field( $request->get_param( 'start_date' ) );
        $end_date    = sanitize_text_field( $request->get_param( 'end_date' ) );
        $site_ids    = $request->get_param( 'site_ids' );
        if ( ! is_array( $site_ids ) ) {
            return new WP_Error( 'invalid_sites', 'پارامتر site_ids باید آرایه باشد.', [ 'status' => 400 ] );
        }
        $placements = $request->get_param('placements'); // انتظار آرایه یا JSON

        if (!is_array($placements)) {
        // اگر رشته JSON دریافت شد، decode کن
         $decoded = json_decode($placements, true);
        if (json_last_error() === JSON_ERROR_NONE) {
        $placements = $decoded;
         } else {
        $placements = [];
        }
            }
        // 3. درج در جدول msr_ads
$inserted = $wpdb->insert(
    "{$wpdb->prefix}msr_ads_vaghaye",
    [
        'media_url'   => $media_url,
        'media_type'  => $media_type,
        'alt_text'    => $alt_text,
        'target_link' => $target_link,
        'start_date'  => $start_date ?: null,
        'end_date'    => $end_date   ?: null,
        'placements'  => json_encode($placements, JSON_UNESCAPED_UNICODE),
        'received_at' => current_time('mysql'),
    ],
    [ '%s','%s','%s','%s','%s','%s','%s','%s' ]
);

        if ( ! $inserted ) {
            return new WP_Error( 'db_insert_failed', 'خطا در ذخیره تبلیغ.', [ 'status' => 500 ] );
        }

        $ad_id = $wpdb->insert_id;

        // 4. ارتباط با سایت‌های مقصد
        foreach ( $site_ids as $sid ) {
            $wpdb->insert(
                "{$wpdb->prefix}msr_ads_vaghaye_sites",
                [ 'ad_id' => $ad_id, 'site_id' => intval($sid) ],
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

        // 1. بررسی JWT
        $jwt = self::get_token_from_header( $request );
        if ( ! $jwt || ! MR_JWT::verify_token( $jwt ) ) {
            return new WP_Error( 'invalid_jwt', 'توکن JWT معتبر نیست.', [ 'status' => 403 ] );
        }

        // 2. آیدی تبلیغ
        $ad_id = intval( $request->get_param( 'id' ) );
        if ( ! $ad_id ) {
            return new WP_Error( 'invalid_id', 'آیدی تبلیغ نامعتبر است.', [ 'status' => 400 ] );
        }

        // 3. حذف ارتباط ها و سپس خود تبلیغ
        $wpdb->delete( "{$wpdb->prefix}msr_ads_vaghaye_sites", [ 'ad_id' => $ad_id ], [ '%d' ] );
        $deleted = $wpdb->delete( "{$wpdb->prefix}msr_ads_vaghaye", [ 'id' => $ad_id ], [ '%d' ] );

        if ( ! $deleted ) {
            return new WP_Error( 'delete_failed', 'خطا در حذف تبلیغ.', [ 'status' => 500 ] );
        }

        return [ 'message' => "تبلیغ شماره {$ad_id} با موفقیت حذف شد." ];
    }

    private static function get_token_from_header( $request ) {
        $auth = $request->get_header( 'authorization' );
        if ( preg_match( '/Bearer\s(\S+)/', $auth, $m ) ) {
            return $m[1];
        }
        return false;
    }
    public static function get_active_placements(WP_REST_Request $request) {
    // فرض کنیم جایگاه‌ها به صورت ثابت یا داینامیک تعریف شده‌اند
    // نمونه جایگاه‌ها:
    $placements = [
        'homepage_banner',
        'homepage_sidebar',
        'post_sidebar',
        'post_bottom',
        'popup',
    ];

    return ['placements' => $placements];
}

}