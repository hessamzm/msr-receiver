<?php
if (!defined('ABSPATH')) exit;

class MSR_Backlink_API {

 public static function init() {
    self::register_routes();
}

    public static function register_routes() {
        register_rest_route('msr/v1', '/backlink', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'create_backlink'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('msr/v1', '/backlink/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [__CLASS__, 'update_backlink'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('msr/v1', '/backlink/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'delete_backlink'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('msr/v1', '/backlink', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_backlinks'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function create_backlink(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'msr_backlinks';

        $keyword = sanitize_text_field($request->get_param('keyword'));
        $url = esc_url_raw($request->get_param('url'));
        $start_date = sanitize_text_field($request->get_param('start_date'));
        $end_date = sanitize_text_field($request->get_param('end_date'));
        $placements = $request->get_param('placements');

        if (!is_array($placements)) {
            $decoded = json_decode($placements, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $placements = $decoded;
            } else {
                $placements = [];
            }
        }

        if (!$keyword || !$url) {
            return new WP_Error('missing_fields', 'کلمه کلیدی و لینک اجباری هستند.', ['status' => 400]);
        }

        $inserted = $wpdb->insert($table, [
            'keyword' => $keyword,
            'url' => $url,
            'start_date' => $start_date ?: null,
            'end_date' => $end_date ?: null,
            'placements' => json_encode($placements, JSON_UNESCAPED_UNICODE),
            'created_at' => current_time('mysql'),
        ], ['%s','%s','%s','%s','%s','%s']);

        if (!$inserted) {
            return new WP_Error('db_error', 'خطا در ذخیره بک لینک.', ['status' => 500]);
        }

        return ['message' => 'بک لینک با موفقیت ذخیره شد.', 'id' => $wpdb->insert_id];
    }

    public static function update_backlink(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'msr_backlinks';

        $id = intval($request->get_param('id'));
        if (!$id) return new WP_Error('invalid_id', 'شناسه معتبر نیست.', ['status' => 400]);

        $keyword = sanitize_text_field($request->get_param('keyword'));
        $url = esc_url_raw($request->get_param('url'));
        $start_date = sanitize_text_field($request->get_param('start_date'));
        $end_date = sanitize_text_field($request->get_param('end_date'));
        $placements = $request->get_param('placements');

        if (!is_array($placements)) {
            $decoded = json_decode($placements, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $placements = $decoded;
            } else {
                $placements = [];
            }
        }

        $update_data = [
            'keyword' => $keyword,
            'url' => $url,
            'start_date' => $start_date ?: null,
            'end_date' => $end_date ?: null,
            'placements' => json_encode($placements, JSON_UNESCAPED_UNICODE),
        ];

        $updated = $wpdb->update($table, $update_data, ['id' => $id], ['%s','%s','%s','%s','%s'], ['%d']);

        if ($updated === false) {
            return new WP_Error('db_error', 'خطا در به‌روزرسانی بک لینک.', ['status' => 500]);
        }

        return ['message' => 'بک لینک با موفقیت به‌روزرسانی شد.'];
    }

    public static function delete_backlink(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'msr_backlinks';

        $id = intval($request->get_param('id'));
        if (!$id) return new WP_Error('invalid_id', 'شناسه معتبر نیست.', ['status' => 400]);

        $deleted = $wpdb->delete($table, ['id' => $id], ['%d']);

        if (!$deleted) {
            return new WP_Error('db_error', 'خطا در حذف بک لینک.', ['status' => 500]);
        }

        return ['message' => 'بک لینک با موفقیت حذف شد.'];
    }

    public static function get_backlinks(WP_REST_Request $request) {
        global $wpdb;
        $table = $wpdb->prefix . 'msr_backlinks';

        $now = current_time('mysql');
        $placement = $request->get_param('placement');

        $query = "SELECT * FROM $table WHERE (start_date IS NULL OR start_date <= %s) AND (end_date IS NULL OR end_date >= %s)";
        $params = [$now, $now];

        if ($placement) {
            $query .= " AND JSON_CONTAINS(placements, %s)";
            $params[] = json_encode($placement);
        }

        $results = $wpdb->get_results($wpdb->prepare($query, ...$params));

        return $results;
    }
    
}

add_action('rest_api_init', ['MSR_Backlink_API', 'init']);
