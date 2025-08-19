<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_create_stripe_session( $booking_id, $amount, $customer_email ){
    $opts = get_option('wressla_core_options',[]);
    $secret = $opts['stripe_secret_key'] ?? '';
    if ( empty($secret) || $amount <= 0 ) return new WP_Error('stripe_disabled','Brak konfiguracji Stripe lub kwota 0.');

    $success = esc_url_raw( add_query_arg(['wressla_paid'=>'1','booking'=>$booking_id], $opts['success_url'] ?? home_url('/rezerwacja/?status=ok')) );
    $cancel  = esc_url_raw( add_query_arg(['wressla_cancel'=>'1','booking'=>$booking_id], $opts['cancel_url']  ?? home_url('/rezerwacja/?status=cancel')) );

    $args = [
        'headers' => [ 'Authorization' => 'Bearer ' . $secret ],
        'body' => [
            'mode' => 'payment',
            'success_url' => $success,
            'cancel_url'  => $cancel,
            'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]' => strtolower( $opts['currency'] ?? 'pln' ),
            'line_items[0][price_data][product_data][name]' => get_the_title($booking_id),
            'line_items[0][price_data][unit_amount]' => intval( round($amount * 100) ),
            'line_items[0][quantity]' => 1,
            'customer_email' => sanitize_email($customer_email)
        ],
        'timeout' => 20
    ];

    $resp = wp_remote_post( 'https://api.stripe.com/v1/checkout/sessions', $args );
    if ( is_wp_error($resp) ) return $resp;
    $code = wp_remote_retrieve_response_code($resp);
    $body = json_decode( wp_remote_retrieve_body($resp), true );
    if ( $code >= 200 && $code < 300 && ! empty($body['url']) ){
        update_post_meta( $booking_id, '_stripe_session_id', sanitize_text_field( $body['id'] ) );
        update_post_meta( $booking_id, '_payment_status', 'pending' );
        return $body['url'];
    }
    return new WP_Error('stripe_error', 'Stripe error: '. wp_remote_retrieve_body($resp) );
}

add_action('template_redirect', function(){
    if ( isset($_GET['wressla_paid'], $_GET['booking']) && intval($_GET['booking']) ){
        $booking = get_post(intval($_GET['booking']));
        if ( $booking && $booking->post_type === 'wressla_booking' ){
            update_post_meta( $booking->ID, '_payment_status', 'paid' );
        }
    }
});

add_action('rest_api_init', function(){
    register_rest_route('wressla/v1','/stripe-webhook', [
        'methods'  => 'POST',
        'callback' => 'wressla_stripe_webhook_cb',
        'permission_callback' => '__return_true',
    ]);
});

function wressla_stripe_webhook_cb( WP_REST_Request $req ){
    $payload = $req->get_body();
    $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $opts = get_option('wressla_core_options',[]);
    $secret = $opts['stripe_webhook_secret'] ?? '';

    if ( ! empty($secret) && ! wressla_verify_stripe_signature($payload, $sig, $secret) ){
        return new WP_REST_Response(['ok'=>false,'reason'=>'bad signature'], 400);
    }

    $event = json_decode($payload, true);
    if ( isset($event['type']) && $event['type'] === 'checkout.session.completed' ){
        $sid = $event['data']['object']['id'] ?? '';
        $booking_id = wressla_find_booking_by_session($sid);
        if ( $booking_id ){
            update_post_meta( $booking_id, '_payment_status', 'paid' );
            return new WP_REST_Response(['ok'=>true], 200);
        }
    }
    return new WP_REST_Response(['ok'=>true], 200);
}

function wressla_find_booking_by_session( $sid ){
    $q = new WP_Query([
        'post_type' => 'wressla_booking',
        'post_status' => 'private',
        'meta_key' => '_stripe_session_id',
        'meta_value' => sanitize_text_field($sid),
        'fields' => 'ids',
        'posts_per_page' => 1
    ]);
    $id = $q->posts[0] ?? 0;
    wp_reset_postdata();
    return $id ? intval($id) : 0;
}

function wressla_verify_stripe_signature( $payload, $sig_header, $secret ){
    if ( empty($sig_header) ) return false;
    $parts = [];
    foreach( explode(',', $sig_header) as $p ){
        $pair = explode('=', $p, 2);
        $k = trim($pair[0] ?? '');
        $v = trim($pair[1] ?? '');
        if ( $k && $v ) $parts[$k] = $v;
    }
    if ( empty($parts['t']) || empty($parts['v1']) ) return false;
    $signed_payload = $parts['t'] . '.' . $payload;
    $computed = hash_hmac('sha256', $signed_payload, $secret);
    return hash_equals( $computed, $parts['v1'] );
}
