<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_JWT {

    // کلید مخفی برای امضا
    private static function get_secret_key() {
        return defined('MSR_JWT_SECRET') ? MSR_JWT_SECRET : 'your-very-secret-key';
    }

    public static function build_site_jwt() {
        $payload = [
            'site_url' => get_site_url(),
            'activated_at' => time()
        ];
        return self::generate_token($payload);
    }

    public static function generate_token($payload, $exp_seconds = 3600) {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload['exp'] = time() + $exp_seconds;

        $base64UrlHeader = self::base64url_encode(json_encode($header));
        $base64UrlPayload = self::base64url_encode(json_encode($payload));

        $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", self::get_secret_key(), true);
        $base64UrlSignature = self::base64url_encode($signature);

        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }

    public static function verify_token($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        list($header64, $payload64, $signature64) = $parts;

        $expected_signature = self::base64url_encode(hash_hmac(
            'sha256',
            "$header64.$payload64",
            self::get_secret_key(),
            true
        ));

        if (!hash_equals($expected_signature, $signature64)) return false;

        $payload = json_decode(self::base64url_decode($payload64), true);
        if (!isset($payload['exp']) || time() > $payload['exp']) return false;

        return $payload;
    }

    public static function get_token_from_header() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) return false;

        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }

        return false;
    }

    public static function authorize_request() {
        $token = self::get_token_from_header();
        if (!$token) return false;

        $payload = self::verify_token($token);
        return $payload;
    }

    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64url_decode($data) {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
