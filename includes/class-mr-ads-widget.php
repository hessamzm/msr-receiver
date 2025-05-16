<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_Ads_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'mr_ads_widget',
            'MR Ads Widget',
            [ 'description' => 'نمایش تبلیغات MSR بر اساس بازهٔ زمانی' ]
        );
    }

    public function widget( $args, $instance ) {
        global $wpdb;
        echo $args['before_widget'];

        $now = current_time( 'mysql' );

        // گرفتن تبلیغات معتبر: start_date <= now <= end_date
        $table = $wpdb->prefix . 'msr_ads';
        $ads = $wpdb->get_results( $wpdb->prepare("
            SELECT * FROM $table
            WHERE ( start_date IS NULL OR start_date <= %s )
              AND ( end_date   IS NULL OR end_date   >= %s )
            ORDER BY received_at DESC
            LIMIT 1
        ", $now, $now ) );

        if ( $ads ) {
            $ad = $ads[0];
            echo '<div class="mr-ads-widget">';

            // لینک دور تبلیغ
            if ( $ad->target_link ) {
                echo '<a href="' . esc_url( $ad->target_link ) . '" target="_blank">';
            }

            // بسته به نوع رسانه
            if ( $ad->media_type === 'video' ) {
                echo '<video controls style="max-width:100%;">';
                echo '<source src="' . esc_url( $ad->media_url ) . '" type="video/mp4">';
                echo 'ویدئوی شما پشتیبانی نمی‌شود.';
                echo '</video>';
            } else {
                // عکس یا گیف
                echo '<img src="' . esc_url( $ad->media_url ) . '" alt="' . esc_attr( $ad->alt_text ) . '" style="max-width:100%;">';
            }

            if ( $ad->target_link ) {
                echo '</a>';
            }

            echo '</div>';
        } else {
            // اگر هیچ تبلیغ فعالی نیست
            echo '<p>تبلیغی برای نمایش وجود ندارد.</p>';
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        // در این نسخه تنظیمات بی‌نیاز است
        echo '<p>این ابزارک تبلیغات MSR را در قالب نمایش می‌دهد.</p>';
    }
}

// ثبت ابزارک
add_action( 'widgets_init', function(){
    register_widget( 'MR_Ads_Widget' );
});
