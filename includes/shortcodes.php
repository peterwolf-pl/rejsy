<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_enqueue_assets() {
    wp_enqueue_style('wressla-core', WRESSLA_CORE_URL . 'assets/wressla.css', [], WRESSLA_CORE_VER);
    wp_register_script(
        'wressla-rez',
        WRESSLA_CORE_URL . 'assets/js/rezerwacja.js',
        ['jquery'],
        WRESSLA_CORE_VER,
        true
    );
    wp_localize_script('wressla-rez', 'WRESSLA_REZ', [
        'ajax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wressla_rez_nonce'),
        'ok'    => __( 'Dziękujemy! Potwierdzimy termin e-mailem/SMS.', 'wressla-core' ),
        'fail'  => __( 'Ups, spróbuj ponownie lub zadzwoń.', 'wressla-core' )
    ]);
}
add_action('wp_enqueue_scripts','wressla_enqueue_assets');

function wressla_rezerwacja_shortcode( $atts = [] ) {
    wp_enqueue_script('wressla-rez');
    ob_start(); ?>

    <?php if( ! is_user_logged_in() ): ?>
        <div class="wressla-login-req">
            <p><?php _e('Do rezerwacji wymagane jest zalogowanie przez Google lub Facebook oraz podanie numeru telefonu.', 'wressla-core'); ?></p>
            <?php echo do_shortcode('[wressla_social_login]'); ?>
        </div>
        <?php return ob_get_clean(); endif; ?>

    <form id="wressla-rez-form" class="wressla-form">
        <div class="row">
            <div class="col">
                <label><?php _e('Data', 'wressla-core'); ?></label>
                <input type="date" name="date" required>
            </div>
            <div class="col">
                <label><?php _e('Godzina (preferencja)', 'wressla-core'); ?></label>
                <input type="time" name="time">
            </div>
            <div class="col">
                <label><?php _e('Osób', 'wressla-core'); ?></label>
                <input type="number" name="persons" min="1" max="12" required>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label><?php _e('Imię i nazwisko', 'wressla-core'); ?></label>
                <input type="text" name="name" value="<?php echo esc_attr( wp_get_current_user()->display_name ); ?>" required>
            </div>
            <div class="col">
                <label><?php _e('Telefon', 'wressla-core'); ?></label>
                <input type="tel" name="phone" required>
            </div>
            <div class="col">
                <label><?php _e('E-mail', 'wressla-core'); ?></label>
                <input type="email" name="email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label><?php _e('Język przewodnika', 'wressla-core'); ?></label>
                <select name="lang">
                    <option value="pl">Polski</option>
                    <option value="en">English</option>
                    <option value="de">Deutsch</option>
                </select>
            </div>
            <div class="col">
                <label><?php _e('Rodzaj rejsu', 'wressla-core'); ?></label>
                <select name="trip">
                    <?php
                    $trips = get_posts([ 'post_type'=>'wressla_trip', 'numberposts'=>-1, 'orderby'=>'menu_order title', 'order'=>'ASC' ]);
                    foreach( $trips as $t ){
                        echo '<option value="'.esc_attr($t->ID).'">'.esc_html($t->post_title).'</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <?php
        $trip_qs = intval($_GET['trip'] ?? 0);
        if ( $trip_qs && function_exists('get_field') ){
            $slots = get_field('wressla_slots', $trip_qs);
            if ( ! empty($slots) ){
                echo '<div class="row"><div class="col"><label>'.__('Wybierz termin (slot)', 'wressla-core').'</label><div class="wressla-slots">';
                foreach( $slots as $s ){
                    $cap = intval($s['capacity']);
                    if ( $cap > 0 ){
                        $v = esc_attr($s['date'].' '.$s['time']);
                        $label = esc_html($s['date'].' '.$s['time'].' · '.sprintf(__('%s miejsc','wressla-core'), $cap));
                        echo '<label><input type="radio" name="slot" value="'.$v.'"> '.$label.'</label><br/>';
                    }
                }
                echo '</div></div></div>';
            }
        }
        ?>

        <div class="row">
            <div class="col">
                <label><?php _e('Wiadomość', 'wressla-core'); ?></label>
                <textarea name="msg" rows="4" placeholder="<?php esc_attr_e('Dodatkowe informacje (np. okazja, dostępność).','wressla-core'); ?>"></textarea>
            </div>
        </div>

        <input type="hidden" name="action" value="wressla_submit_booking">
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce('wressla_rez_nonce') ); ?>">
        <button type="submit"><?php _e('Zarezerwuj', 'wressla-core'); ?></button>
        <div class="wressla-rez-status" aria-live="polite"></div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('wressla_rezerwacja','wressla_rezerwacja_shortcode');

function wressla_submit_booking() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error(['message'=>__('Musisz być zalogowany, aby zarezerwować.','wressla-core')], 401);
    }
    check_ajax_referer('wressla_rez_nonce','nonce');

    $data = [
        'date'    => sanitize_text_field($_POST['date'] ?? ''),
        'time'    => sanitize_text_field($_POST['time'] ?? ''),
        'persons' => intval($_POST['persons'] ?? 0),
        'name'    => sanitize_text_field($_POST['name'] ?? ''),
        'phone'   => sanitize_text_field($_POST['phone'] ?? ''),
        'email'   => sanitize_email($_POST['email'] ?? ''),
        'lang'    => sanitize_text_field($_POST['lang'] ?? 'pl'),
        'trip'    => sanitize_text_field($_POST['trip'] ?? ''),
        'msg'     => sanitize_textarea_field($_POST['msg'] ?? ''),
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? ''
    ];

    if ( ! empty($_POST['slot']) ){
        $slot = sanitize_text_field($_POST['slot']);
        if ( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $slot) ){
            list($data['date'],$data['time']) = explode(' ', $slot);
        }
    }

    if ( empty($data['phone']) ){
        wp_send_json_error(['message' => __('Telefon jest wymagany.', 'wressla-core')], 400);
    }

    // Capacity check & decrement
    $trip_id = intval($data['trip']);
    $persons = max(1, intval($data['persons']));
    if ( $trip_id && function_exists('get_field') && ! empty($data['date']) && ! empty($data['time']) ){
        $slots = get_field('wressla_slots', $trip_id);
        $found = false;
        if ( $slots ){
            foreach( $slots as $i => $s ){
                if ( $s['date'] === $data['date'] && $s['time'] === $data['time'] ){
                    $cap = intval($s['capacity']);
                    if ( $cap < $persons ){
                        wp_send_json_error(['message'=>__('Brak wystarczającej liczby miejsc na wybrany termin.','wressla-core')], 409);
                    }
                    $new_cap = max(0, $cap - $persons);
                    $s['capacity'] = $new_cap;
                    $slots[$i] = $s;
                    update_field('wressla_slots', $slots, $trip_id);
                    $found = true;
                    break;
                }
            }
        }
        if ( ! $found ){
            wp_send_json_error(['message'=>__('Wybrany termin nie istnieje.','wressla-core')], 404);
        }
    }

    $title = sprintf( 'Rezerwacja %s (%s %s) – %s os.', $data['trip'], $data['date'], $data['time'], $data['persons'] );
    $post_id = wp_insert_post([
        'post_type'   => 'wressla_booking',
        'post_status' => 'private',
        'post_title'  => $title,
        'meta_input'  => array_merge($data, ['user_id'=>get_current_user_id(), 'provider'=>get_user_meta(get_current_user_id(), 'wressla_provider', true)])
    ], true);

    if ( is_wp_error($post_id) ) {
        wp_send_json_error(['message' => $post_id->get_error_message()], 500);
    }

    // Email admin with ICS + gcal
    $admin = get_option('admin_email');
    $body  = "Nowa rezerwacja Wressla\n\n";
    foreach($data as $k=>$v) $body .= strtoupper($k).": ".$v."\n";

    if ( function_exists('wressla_write_ics_file') ){
        $ics_path = wressla_write_ics_file( $post_id );
    } else { $ics_path = ''; }
    $gcal = function_exists('wressla_make_gcal_link') ? wressla_make_gcal_link( $post_id ) : '';
    $body .= "\nDodaj do Google Calendar:\n".$gcal."\n";
    $attachments = $ics_path ? [$ics_path] : [];
    wp_mail( $admin, 'Wressla – Nowa rezerwacja', $body, [], $attachments );

    wp_send_json_success([
        'message' => __('Rezerwacja zapisana. Skontaktujemy się w celu potwierdzenia.', 'wressla-core'),
        'gcal'    => $gcal,
        'ics'     => rest_url( '/wressla/v1/booking-ics?id=' . $post_id )
    ]);
}
add_action('wp_ajax_wressla_submit_booking','wressla_submit_booking');
add_action('wp_ajax_nopriv_wressla_submit_booking','wressla_submit_booking');
