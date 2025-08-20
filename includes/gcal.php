<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_gcal_is_free( $start_ts, $end_ts ){
    $opts = get_option('wressla_core_options', []);
    $api_key = sanitize_text_field( $opts['gcal_api_key'] ?? '' );
    $cal_id  = sanitize_text_field( $opts['gcal_calendar_id'] ?? '' );
    if ( empty($api_key) || empty($cal_id) ) return true; // no config -> assume free

    $tz = function_exists('wressla_get_timezone') ? wressla_get_timezone() : 'UTC';
    $start = new DateTime('@'.$start_ts);
    $end   = new DateTime('@'.$end_ts);
    try {
        $start->setTimezone( new DateTimeZone($tz) );
        $end->setTimezone( new DateTimeZone($tz) );
    } catch ( Exception $e ) {}

    $params = [
        'timeMin' => $start->format(DateTime::RFC3339),
        'timeMax' => $end->format(DateTime::RFC3339),
        'singleEvents' => 'true',
        'maxResults' => 1,
        'key' => $api_key
    ];
    $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($cal_id) . '/events?' . http_build_query($params);
    $resp = wp_remote_get( $url );
    if ( is_wp_error($resp) ) return true;
    $code = wp_remote_retrieve_response_code( $resp );
    if ( $code !== 200 ) return true;
    $body = json_decode( wp_remote_retrieve_body($resp), true );
    if ( ! empty( $body['items'] ) ) return false; // conflict
    return true;
}

function wressla_gcal_add_booking_event( $booking_id ){
    $opts = get_option('wressla_core_options', []);
    $api_key = sanitize_text_field( $opts['gcal_api_key'] ?? '' );
    $cal_id  = sanitize_text_field( $opts['gcal_calendar_id'] ?? '' );
    if ( empty($api_key) || empty($cal_id) ) return; // brak konfiguracji

    $meta = get_post_meta($booking_id);
    $data = [];
    foreach( $meta as $k => $v ){
        $data[$k] = is_array($v) ? $v[0] : $v;
    }
    if ( empty($data['date']) ) return;
    if ( ! function_exists('wressla_booking_times') ) return;

    list($start_ts, $end_ts) = wressla_booking_times( $data );
    $tz = function_exists('wressla_get_timezone') ? wressla_get_timezone() : 'UTC';

    $start = new DateTime('@'.$start_ts);
    $end   = new DateTime('@'.$end_ts);
    try {
        $start->setTimezone( new DateTimeZone($tz) );
        $end->setTimezone( new DateTimeZone($tz) );
    } catch ( Exception $e ) {}

    $event = [
        'summary'     => get_the_title( $booking_id ),
        'description' => sprintf(
            "Rejs Wressla\nOsób: %s\nTelefon: %s\nE-mail: %s\nUwagi: %s",
            $data['persons'] ?? '',
            $data['phone'] ?? '',
            $data['email'] ?? '',
            $data['msg'] ?? ''
        ),
        'start' => [
            'dateTime' => $start->format(DateTime::RFC3339),
            'timeZone' => $tz
        ],
        'end'   => [
            'dateTime' => $end->format(DateTime::RFC3339),
            'timeZone' => $tz
        ],
        'location' => function_exists('wressla_get_location') ? wressla_get_location() : ''
    ];

    $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($cal_id) . '/events?key=' . $api_key;
    $resp = wp_remote_post( $url, [
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => wp_json_encode( $event ),
        'timeout' => 15,
    ] );
    if ( is_wp_error( $resp ) ) {
        error_log( 'GCal insert error: ' . $resp->get_error_message() );
    } elseif ( wp_remote_retrieve_response_code( $resp ) >= 300 ) {
        error_log( 'GCal insert error: HTTP ' . wp_remote_retrieve_response_code( $resp ) );
    }
}
