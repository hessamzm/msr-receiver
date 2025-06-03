<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_Endpoints {

    public static function register_routes() {

        // مرحله ۱: ایجاد پست با تیتر و محتوا
        register_rest_route('msr/v1', '/report', [
            'methods'  => 'POST',
            'callback' => [__CLASS__, 'create_report'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);

        // مرحله ۲: افزودن عکس شاخص
        register_rest_route('msr/v1', '/report/(?P<id>\d+)/image', [
            'methods'  => 'PUT',
            'callback' => [__CLASS__, 'update_image'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);

        // مرحله ۳: افزودن دسته‌بندی
        register_rest_route('msr/v1', '/report/(?P<id>\d+)/category', [
            'methods'  => 'PUT',
            'callback' => [__CLASS__, 'update_category'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);

        // مرحله ۴: افزودن برچسب‌ها
        register_rest_route('msr/v1', '/report/(?P<id>\d+)/tags', [
            'methods'  => 'PUT',
            'callback' => [__CLASS__, 'update_tags'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);

        // مرحله ۵: افزودن اطلاعات سئو
        register_rest_route('msr/v1', '/report/(?P<id>\d+)/seo', [
            'methods'  => 'PUT',
            'callback' => [__CLASS__, 'update_seo'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);

        // مرحله بررسی اطلاعات پست
        register_rest_route('msr/v1', '/report/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [__CLASS__, 'get_report'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);

        // حذف پست
        register_rest_route('msr/v1', '/report/(?P<id>\d+)', [
            'methods'  => 'DELETE',
            'callback' => [__CLASS__, 'delete_report'],
            'permission_callback' => [__CLASS__, 'check_auth']
        ]);
        // مسیر ویرایش عنوان و محتوا
register_rest_route('msr/v1', '/report/(?P<id>\d+)', [
    'methods'  => 'PUT',
    'callback' => [__CLASS__, 'update_report'],
    'permission_callback' => [__CLASS__, 'check_auth']
]);

// مسیر زمان‌بندی انتشار
register_rest_route('msr/v1', '/report/(?P<id>\d+)/schedule', [
    'methods'  => 'PUT',
    'callback' => [__CLASS__, 'update_schedule'],
    'permission_callback' => [__CLASS__, 'check_auth']
]);
    }

    public static function check_auth() {
        return MR_JWT::authorize_request() !== false;
    }

    public static function create_report($request) {
        $title   = sanitize_text_field($request->get_param('title'));
        $content = wp_kses_post($request->get_param('content'));

        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'draft',
            'post_type'    => 'post',
            'post_author'  => get_option('msr_default_author_id', 1),
        ]);

        if (is_wp_error($post_id)) {
            return new WP_Error('insert_failed', 'خطا در ایجاد پست.', ['status' => 500]);
        }

        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}msr_reports", [
            'wp_post_id' => $post_id,
            'from_ip'    => $_SERVER['REMOTE_ADDR'],
            'status'     => 'draft',
            'log'        => json_encode(['created' => current_time('mysql')])
        ]);

        return ['post_id' => $post_id];
    }

public static function update_image( $request ) {
    $post_id = intval( $request['id'] );
    $image_url        = esc_url_raw( $request->get_param( 'image_url' ) );
    $image_title      = sanitize_text_field( $request->get_param( 'image_title' ) );
    $image_caption    = sanitize_text_field( $request->get_param( 'image_caption' ) );
    $image_description = wp_kses_post( $request->get_param( 'image_description' ) );
    $image_alt        = sanitize_text_field( $request->get_param( 'image_alt' ) );

    if ( ! MR_Handler::post_exists_in_msr( $post_id ) ) {
        return new WP_Error( 'not_found', 'پست در جدول افزونه ثبت نشده است.', [ 'status' => 404 ] );
    }

    if ( ! $image_url ) {
        return new WP_Error( 'no_image', 'آدرس تصویر ارسال نشده است.', [ 'status' => 400 ] );
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    // دانلود فایل
    $tmp = download_url( $image_url );
    if ( is_wp_error( $tmp ) ) {
        return new WP_Error( 'download_error', 'دانلود تصویر با خطا مواجه شد.', [ 'status' => 500 ] );
    }

    $file_array = [
        'name'     => basename( $image_url ),
        'tmp_name' => $tmp,
    ];

    // آپلود به رسانه
    $attach_id = media_handle_sideload( $file_array, $post_id );
    if ( is_wp_error( $attach_id ) ) {
        @unlink( $tmp );
        return new WP_Error( 'media_error', 'آپلود تصویر شکست خورد.', [ 'status' => 500 ] );
    }

    // به‌روزرسانی متادیتای تصویر
    // عنوان
    if ( $image_title ) {
        wp_update_post( [
            'ID'         => $attach_id,
            'post_title' => $image_title,
        ] );
    }

    // توضیح کوتاه (excerpt)
    if ( $image_caption ) {
        wp_update_post( [
            'ID'           => $attach_id,
            'post_excerpt' => $image_caption,
        ] );
    }

    // توضیح کامل (content)
    if ( $image_description ) {
        wp_update_post( [
            'ID'           => $attach_id,
            'post_content' => $image_description,
        ] );
    }

    // متن جایگزین
    if ( $image_alt ) {
        update_post_meta( $attach_id, '_wp_attachment_image_alt', $image_alt );
    }

    // تعیین به‌عنوان تصویر شاخص
    set_post_thumbnail( $post_id, $attach_id );

    MR_Handler::add_log( $post_id, 'تصویر شاخص و متا نوشته شدند' );

    return [
        'message'      => 'تصویر شاخص و متای آن با موفقیت ثبت شدند',
        'attachment_id' => $attach_id,
    ];
}


    public static function update_category($request) {
    $post_id = intval($request['id']);
    if (!MR_Handler::post_exists_in_msr($post_id)) {
        return new WP_Error('not_found', 'پست در جدول افزونه ثبت نشده است.', ['status' => 404]);
    }

    $categories = $request->get_param('categories');
    if (!is_array($categories)) return new WP_Error('invalid_data', 'دسته‌ها باید آرایه باشند.', ['status' => 400]);

    $cat_ids = [];
    foreach ($categories as $cat_name) {
        $term = term_exists($cat_name, 'category');
        if (!$term) {
            $term = wp_insert_term($cat_name, 'category');
        }
        $cat_ids[] = is_array($term) ? $term['term_id'] : $term;
    }

    wp_set_post_categories($post_id, $cat_ids);
    MR_Handler::add_log($post_id, 'دسته‌بندی‌ها ثبت شدند');

    return ['message' => 'دسته‌بندی‌ها ثبت شدند'];
}

   public static function update_tags($request) {
    $post_id = intval($request['id']);
    if (!MR_Handler::post_exists_in_msr($post_id)) {
        return new WP_Error('not_found', 'پست در جدول افزونه ثبت نشده است.', ['status' => 404]);
    }

    $tags = $request->get_param('tags');
    if (!is_array($tags)) return new WP_Error('invalid_tags', 'برچسب‌ها باید آرایه باشند.', ['status' => 400]);

    wp_set_post_tags($post_id, $tags, false);
    MR_Handler::add_log($post_id, 'برچسب‌ها ثبت شدند');

    return ['message' => 'برچسب‌ها ثبت شدند'];
}


public static function update_seo($request) {
    $post_id = intval($request['id']);
    if (!MR_Handler::post_exists_in_msr($post_id)) {
        return new WP_Error('not_found', 'پست در جدول افزونه ثبت نشده است.', ['status' => 404]);
    }

    $meta = [
        '_yoast_wpseo_focuskw'        => sanitize_text_field($request->get_param('focus_keyword')),
        '_yoast_wpseo_metadesc'       => sanitize_text_field($request->get_param('meta_description')),
        '_yoast_wpseo_focuskeywords'  => sanitize_text_field($request->get_param('focus_synonyms'))
    ];

    foreach ($meta as $key => $value) {
        if ($value) {
            update_post_meta($post_id, $key, $value);
        }
    }

    MR_Handler::add_log($post_id, 'اطلاعات سئو ثبت شد');
    MR_Handler::update_status($post_id, 'complete');

      // انتشار پست پس از کامل شدن همه مراحل
    wp_update_post([
        'ID' => $post_id,
        'post_status' => 'publish'
    ]);

    return ['message' => 'اطلاعات سئو ثبت شد و وضعیت به complete تغییر یافت'];
}


    public static function get_report($request) {
        $post_id = intval($request['id']);
        $post = get_post($post_id);
        if (!$post) return new WP_Error('not_found', 'پست یافت نشد.', ['status' => 404]);

        return [
            'ID'      => $post->ID,
            'title'   => $post->post_title,
            'content' => $post->post_content,
            'status'  => $post->post_status,
            'url'     => get_permalink($post)
        ];
    }

    public static function delete_report($request) {
        $post_id = intval($request['id']);
        $deleted = wp_delete_post($post_id, true);
        if (!$deleted) return new WP_Error('delete_failed', 'خطا در حذف پست.', ['status' => 500]);

        return ['message' => 'پست حذف شد'];
    }
    public static function update_schedule($request) {
    $post_id = intval($request['id']);

    if (!MR_Handler::post_exists_in_msr($post_id)) {
        return new WP_Error('not_found', 'پست در جدول افزونه ثبت نشده است.', ['status' => 404]);
    }

    $datetime = $request->get_param('publish_datetime');
    if (!$datetime) {
        return new WP_Error('invalid_datetime', 'تاریخ و زمان انتشار ارسال نشده است.', ['status' => 400]);
    }

    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return new WP_Error('invalid_datetime', 'فرمت تاریخ و زمان معتبر نیست.', ['status' => 400]);
    }

    $post_status = (time() > $timestamp) ? 'publish' : 'future';

    $result = wp_update_post([
        'ID' => $post_id,
        'post_date' => date('Y-m-d H:i:s', $timestamp),
        'post_date_gmt' => gmdate('Y-m-d H:i:s', $timestamp),
        'post_status' => $post_status,
    ], true);

    if (is_wp_error($result)) {
        return new WP_Error('update_failed', 'خطا در به‌روزرسانی زمان انتشار پست.', ['status' => 500]);
    }

    MR_Handler::add_log($post_id, 'زمان‌بندی انتشار پست بروزرسانی شد');

    return ['message' => 'زمان‌بندی انتشار پست با موفقیت به‌روزرسانی شد'];
}
public static function update_report($request) {
    $post_id = intval($request['id']);

    if (!MR_Handler::post_exists_in_msr($post_id)) {
        return new WP_Error('not_found', 'پست در جدول افزونه ثبت نشده است.', ['status' => 404]);
    }

    $title = sanitize_text_field($request->get_param('title'));
    $content = wp_kses_post($request->get_param('content'));

    $update_data = ['ID' => $post_id];

    if ($title !== null) {
        $update_data['post_title'] = $title;
    }

    if ($content !== null) {
        $update_data['post_content'] = $content;
    }

    $result = wp_update_post($update_data, true);

    if (is_wp_error($result)) {
        return new WP_Error('update_failed', 'خطا در به‌روزرسانی پست.', ['status' => 500]);
    }

    MR_Handler::add_log($post_id, 'عنوان و محتوای پست ویرایش شد');

    return ['message' => 'عنوان و محتوای پست با موفقیت به‌روزرسانی شدند'];
}

}
