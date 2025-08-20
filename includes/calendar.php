<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_get_timezone(){
    $tz = get_option('timezone_string');
    if ( ! $tz ) $tz = 'Europe/Warsaw';
    $opts = get_option('wressla_core_options',[]);
    if ( ! empty($opts['tz']) ) $tz = $opts['tz'];
    return $tz;
}
function wressla_get_location(){
    $opts = get_option('wressla_core_options',[]);
    return sanitize_text_field( $opts['location'] ?? 'Wrocław, Polska' );
}

function wressla_base64url_encode( $data ){
    return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
}
function wressla_booking_times( $data ){
    $date = sanitize_text_field($data['date'] ?? '');
    $time = sanitize_text_field($data['time'] ?? '');
    if ( empty($date) ) $date = date('Y-m-d');
    if ( empty($time) ) $time = '12:00';
    $start_local = strtotime( $date . ' ' . $time );
    $dur = 60;
    $trip_id = intval($data['trip'] ?? 0);
    if ( $trip_id && function_exists('get_field') ){
        $d = get_field('wressla_duration', $trip_id);
        if ( $d ) $dur = intval($d);
    }
    $end_local = $start_local + $dur * 60;
    return [$start_local, $end_local];
}
function wressla_format_dt_gcal( $ts, $tz ){
    $dt = new DateTime('@'.$ts);
    try { $dt->setTimezone( new DateTimeZone($tz) ); } catch(Exception $e) {}
    return $dt->format('Ymd\THis');
}
function wressla_make_gcal_link( $booking_id ){
    $meta = get_post_meta($booking_id);
    $data = []; foreach($meta as $k=>$v){ $data[$k] = is_array($v) ? $v[0] : $v; }
    if ( empty($data['date']) ) return '';
    $tz = wressla_get_timezone();
    list($start, $end) = wressla_booking_times($data);
    $text = rawurlencode( get_the_title($booking_id) );
    $details = rawurlencode( sprintf("Rejs Wressla\nOsób: %s\nTelefon: %s\nE-mail: %s\nUwagi: %s",
        $data['persons'] ?? '', $data['phone'] ?? '', $data['email'] ?? '', $data['msg'] ?? ''
    ));
    $loc = rawurlencode( wressla_get_location() );
    $dates = wressla_format_dt_gcal($start,$tz) . '/' . wressla_format_dt_gcal($end,$tz);
    $ctz = rawurlencode($tz);
    return "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$text}&dates={$dates}&details={$details}&location={$loc}&ctz={$ctz}";
}
function wressla_booking_to_ics( $booking_id ){
    $post = get_post($booking_id);
    if ( ! $post || $post->post_type !== 'wressla_booking' ) return '';
    $meta = get_post_meta($booking_id);
    $data = []; foreach($meta as $k=>$v){ $data[$k] = is_array($v) ? $v[0] : $v; }
    $tzid = wressla_get_timezone();
    list($start, $end) = wressla_booking_times($data);
    $uid = 'wressla-booking-'.$booking_id.'@'.parse_url( home_url(), PHP_URL_HOST );
    $summary = wp_strip_all_tags( get_the_title($booking_id) );
    $desc = "Rejs Wressla\\nOsób: ".($data['persons'] ?? '')."\\nTelefon: ".($data['phone'] ?? '')."\\nE-mail: ".($data['email'] ?? '')."\\nUwagi: ".str_replace(["\r","\n"],[' ',' '], ($data['msg'] ?? ''));
    $loc = wressla_get_location();
    $fmt = function($ts,$tz) { $dt = new DateTime('@'.$ts); try { $dt->setTimezone( new DateTimeZone($tz) ); } catch(Exception $e) {} return $dt->format('Ymd\THis'); };
    $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Wressla//Core//PL\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n";
    $ics .= "BEGIN:VEVENT\r\nUID:$uid\r\nDTSTAMP:".$fmt(time(),'UTC')."Z\r\n";
    $ics .= "DTSTART;TZID:$tzid:".$fmt($start,$tzid)."\r\n";
    $ics .= "DTEND;TZID:$tzid:".$fmt($end,$tzid)."\r\n";
    $ics .= "SUMMARY:".esc_html( $summary )."\r\n";
    $ics .= "DESCRIPTION:".$desc."\r\n";
    $ics .= "LOCATION:".esc_html( $loc )."\r\n";
    $ics .= "END:VEVENT\r\nEND:VCALENDAR\r\n";
    return $ics;
}
function wressla_write_ics_file( $booking_id ){
    $ics = wressla_booking_to_ics($booking_id);
    if ( empty($ics) ) return '';
    $up = wp_upload_dir();
    $dir = trailingslashit($up['basedir']).'wressla';
    if ( ! file_exists($dir) ) wp_mkdir_p($dir);
    $path = trailingslashit($dir)."booking-$booking_id.ics";
    file_put_contents($path, $ics);
    return $path;
}

function wressla_insert_booking_to_gcal( $booking_id ){
    $opts = get_option('wressla_core_options', []);
    $cal_id = sanitize_text_field( $opts['google_calendar_id'] ?? '' );
    $creds_json = $opts['google_service_account_json'] ?? '';
    if ( empty($cal_id) || empty($creds_json) ) return;

    $creds = json_decode( $creds_json, true );
    if ( empty($creds['client_email']) || empty($creds['private_key']) ) return;

    $now = time();
    $jwt_header = wressla_base64url_encode( json_encode(['alg'=>'RS256','typ'=>'JWT']) );
    $jwt_claim = wressla_base64url_encode( json_encode([
        'iss'   => $creds['client_email'],
        'scope' => 'https://www.googleapis.com/auth/calendar',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now,
    ]) );
    $sig_input = $jwt_header . '.' . $jwt_claim;
    openssl_sign( $sig_input, $signature, $creds['private_key'], 'sha256' );
    $jwt = $sig_input . '.' . wressla_base64url_encode( $signature );

    $resp = wp_remote_post( 'https://oauth2.googleapis.com/token', [
        'body' => [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ],
    ]);
    if ( is_wp_error($resp) ) return;
    $data = json_decode( wp_remote_retrieve_body($resp), true );
    $token = $data['access_token'] ?? '';
    if ( empty($token) ) return;

    $meta = get_post_meta($booking_id);
    $bdata = [];
    foreach( $meta as $k=>$v ){ $bdata[$k] = is_array($v) ? $v[0] : $v; }
    if ( empty($bdata['date']) ) return;
    $tz = wressla_get_timezone();
    list($start, $end) = wressla_booking_times($bdata);
    $event = [
        'summary'     => get_the_title($booking_id),
        'description' => sprintf(
            "Rejs Wressla\nOsób: %s\nTelefon: %s\nE-mail: %s\nUwagi: %s",
            $bdata['persons'] ?? '',
            $bdata['phone'] ?? '',
            $bdata['email'] ?? '',
            $bdata['msg'] ?? ''
        ),
        'location'    => wressla_get_location(),
        'start'       => [ 'dateTime' => gmdate('c', $start), 'timeZone' => $tz ],
        'end'         => [ 'dateTime' => gmdate('c', $end), 'timeZone' => $tz ],
    ];

    wp_remote_post( 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($cal_id) . '/events', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ],
        'body' => wp_json_encode( $event ),
    ] );
}
add_action('rest_api_init', function(){
    register_rest_route('wressla/v1','/booking-ics', [
        'methods'  => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function( WP_REST_Request $req ){
            $id = intval( $req->get_param('id') );
            if ( ! $id ) return new WP_REST_Response('Missing id', 400);
            $ics = wressla_booking_to_ics($id);
            $resp = new WP_REST_Response( $ics, 200 );
            $resp->set_headers([ 'Content-Type' => 'text/calendar; charset=utf-8' ]);
            return $resp;
        }
    ]);
    register_rest_route('wressla/v1','/calendar', [
        'methods'  => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function( WP_REST_Request $req ){
            $source = sanitize_text_field( $req->get_param('source') ?? 'bookings' );
            $trip_id = intval( $req->get_param('trip') );
            $tzid = wressla_get_timezone();
            $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Wressla//Core//PL\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n";
            if ( $source === 'bookings' ){
                $q = new WP_Query([ 'post_type'=>'wressla_booking','post_status'=>'private','posts_per_page'=>200,'orderby'=>'date','order'=>'DESC' ]);
                while( $q->have_posts() ){ $q->the_post();
                    $ics .= substr( wressla_booking_to_ics( get_the_ID() ), strlen("BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Wressla//Core//PL\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n") );
                } wp_reset_postdata();
            } else {
                if ( ! function_exists('get_field') ){
                    $resp = new WP_REST_Response( 'ACF required for slots feed', 400 );
                    $resp->set_headers([ 'Content-Type' => 'text/plain; charset=utf-8' ]);
                    return $resp;
                }
                $trips = [];
                if ( $trip_id ){ $trips = [$trip_id]; }
                else {
                    $q = new WP_Query([ 'post_type'=>'wressla_trip','posts_per_page'=>-1 ]);
                    while( $q->have_posts() ){ $q->the_post(); $trips[] = get_the_ID(); }
                    wp_reset_postdata();
                }
                foreach( $trips as $tid ){
                    $slots = get_field('wressla_slots', $tid) ?: [];
                    $title = get_the_title($tid);
                    $dur = get_field('wressla_duration', $tid) ?: 60;
                    foreach( $slots as $s ){
                        $start = strtotime($s['date'].' '.$s['time']);
                        $end = $start + intval($dur)*60;
                        $uid = 'wressla-slot-'.$tid.'-'.md5($s['date'].' '.$s['time']).'@'.parse_url( home_url(), PHP_URL_HOST );
                        $ics .= "BEGIN:VEVENT\r\nUID:$uid\r\nDTSTAMP:".gmdate('Ymd\\THis')."Z\r\n";
                        $ics .= "DTSTART;TZID:$tzid:".(new DateTime('@'.$start))->setTimezone(new DateTimeZone($tzid))->format('Ymd\\THis')."\r\n";
                        $ics .= "DTEND;TZID:$tzid:".(new DateTime('@'.$end))->setTimezone(new DateTimeZone($tzid))->format('Ymd\\THis')."\r\n";
                        $ics .= "SUMMARY:".esc_html($title)." – dostępny termin\r\n";
                        $ics .= "LOCATION:".esc_html( wressla_get_location() )."\r\n";
                        $ics .= "END:VEVENT\r\n";
                    }
                }
            }
            $ics .= "END:VCALENDAR\r\n";
            $resp = new WP_REST_Response( $ics, 200 );
            $resp->set_headers([ 'Content-Type' => 'text/calendar; charset=utf-8' ]);
            return $resp;
        }
    ]);
});
add_filter('query_vars', function($vars){ $vars[]='wressla_ics'; return $vars; });
add_action('template_redirect', function(){
    $ics = get_query_var('wressla_ics');
    if ( $ics ){
        $req = new WP_REST_Request('GET','/wressla/v1/calendar');
        $resp = rest_do_request($req);
        if ( $resp instanceof WP_REST_Response ){
            header('Content-Type: text/calendar; charset=utf-8');
            echo $resp->get_data(); exit;
        }
    }
});

function wressla_admin_calendar_page(){
    if ( ! current_user_can('manage_options') ) return;
    $opts = get_option('wressla_core_options', []);
    $cal_id = sanitize_text_field( $opts['google_calendar_id'] ?? '' );
    $tz = wressla_get_timezone();
    echo '<div class="wrap"><h1>Kalendarz rezerwacji</h1>';
    if ( $cal_id ){
        $src = 'https://calendar.google.com/calendar/embed?src=' . rawurlencode($cal_id) . '&ctz=' . rawurlencode($tz);
        echo '<iframe src="' . esc_url($src) . '" style="border:0" width="100%" height="600" frameborder="0" scrolling="no"></iframe>';
    } else {
        echo '<p>' . esc_html__( 'Brak skonfigurowanego kalendarza Google.', 'wressla-core' ) . '</p>';
    }
    echo '</div>';
}

function wressla_admin_calendar_menu(){
    add_submenu_page('wressla-core', 'Kalendarz', 'Kalendarz', 'manage_options', 'wressla-calendar', 'wressla_admin_calendar_page');
}
add_action('admin_menu','wressla_admin_calendar_menu');
