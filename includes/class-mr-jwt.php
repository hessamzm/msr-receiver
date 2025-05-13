<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_JWT {

    // کلید مخفی برای امضا
    private static function get_secret_key() {
        return defined('MSR_JWT_SECRET') ? MSR_JWT_SECRET : 'your-very-secret-key';
    }

    /**
     * تولید JWT با payload دلخواه (بدون exp)
     */
    public static function build_site_jwt() {
        $payload = [
            'site_url'     => get_site_url(),
            'activated_at' => time(),
            // سایر فیلدهای دلخواه را اینجا اضافه کنید
        ];
        return self::generate_token($payload);
    }

    /**
     * ساخت توکن (بدون افزودن exp)
     */
    public static function generate_token($payload) {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        // دیگر هیچ claim زمان‌بندی اضافه نمی‌شود

        $base64UrlHeader  = self::base64url_encode( wp_json_encode($header) );
        $base64UrlPayload = self::base64url_encode( wp_json_encode($payload) );

        $signature = hash_hmac(
            'sha256',
            "$base64UrlHeader.$base64UrlPayload",
            self::get_secret_key(),
            true
        );
        $base64UrlSignature = self::base64url_encode($signature);

        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }

    /**
     * اعتبارسنجی توکن (بدون بررسی exp)
     */
    public static function verify_token($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($header64, $payload64, $signature64) = $parts;

        // بررسی امضا
        $expected_signature = self::base64url_encode( hash_hmac(
            'sha256',
            "$header64.$payload64",
            self::get_secret_key(),
            true
        ) );
        if ( ! hash_equals($expected_signature, $signature64) ) {
            return false;
        }

        // بازگرداندن payload بدون چک exp
        $payload = json_decode( self::base64url_decode($payload64), true );
        return is_array($payload) ? $payload : false;
    }

    /**
     * استخراج توکن از هدر Authorization
     */
    public static function get_token_from_header() {
        // استفاده از $_SERVER برای سازگاری بیشتر:
        $auth = '';
        if ( isset($_SERVER['HTTP_AUTHORIZATION']) ) {
            $auth = trim( $_SERVER['HTTP_AUTHORIZATION'] );
        } elseif ( function_exists('getallheaders') ) {
            $headers = getallheaders();
            $auth = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        }

        if ( preg_match('/Bearer\s(\S+)/', $auth, $matches) ) {
            return $matches[1];
        }

        return false;
    }

    /**
     * تابع برای استفاده در permission_callback
     */
    public static function authorize_request() {
        $token = self::get_token_from_header();
        if ( ! $token ) {
            return false;
        }
        return self::verify_token($token);
    }

    private static function base64url_encode($data) {
        return rtrim( strtr( base64_encode($data), '+/', '-_' ), '=' );
    }

    private static function base64url_decode($data) {
        $pad = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode( strtr($data, '-_', '+/') );
    }
}
