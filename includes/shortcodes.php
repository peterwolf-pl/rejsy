<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_enqueue_assets() {
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
        'ok'    => __( 'Dziękujemy! Potwierdzimy termin e‑mailem/SMS.', 'wressla-core' ),
        'fail'  => __( 'Ups, spróbuj ponownie lub zadzwoń.', 'wressla-core' )
    ]);
}
add_action('wp_enqueue_scripts','wressla_enqueue_assets');

function wressla_rezerwacja_shortcode( $atts = [] ) {
    wp_enqueue_script('wressla-rez');

    $defaults = [
        'thanks' => __( 'Dziękujemy za rezerwację – odezwiemy się wkrótce.', 'wressla-core' )
    ];
    $atts = shortcode_atts($defaults, $atts);

    ob_start(); ?>
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
                <input type="text" name="name" required>
            </div>
            <div class="col">
                <label><?php _e('Telefon', 'wressla-core'); ?></label>
                <input type="tel" name="phone" required>
            </div>
            <div class="col">
                <label><?php _e('E‑mail', 'wressla-core'); ?></label>
                <input type="email" name="email" required>
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
                    <option value="miejski">Miejski 60′</option>
                    <option value="zachod">Golden Hour 75–90′</option>
                    <option value="tematyczny">Tematyczny 90–120′</option>
                    <option value="prywatny">Prywatny</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <label><?php _e('Wiadomość', 'wressla-core'); ?></label>
                <textarea name="msg" rows="4" placeholder="<?php esc_attr_e('Dodatkowe informacje (np. okazja, dostępność).','wressla-core'); ?>"></textarea>
            </div>
        </div>

        <input type="hidden" name="action" value="wressla_submit_booking">
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce('wressla_rez_nonce') ); ?>">
        <div class="row"><div class="col">
            <label><input type="checkbox" name="pay_now" value="1"> <?php _e('Zapłać zaliczkę teraz (Stripe)', 'wressla-core'); ?></label>
        </div></div>
        <button type="submit"><?php _e('Zarezerwuj', 'wressla-core'); ?></button>
        <div class="wressla-rez-status" aria-live="polite"></div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('wressla_rezerwacja','wressla_rezerwacja_shortcode');

function wressla_submit_booking() {
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

    if ( empty($data['date']) || empty($data['name']) || empty($data['phone']) || empty($data['email']) ) {
        wp_send_json_error(['message' => __('Brak wymaganych danych.', 'wressla-core')], 400);
    }

    $title = sprintf( 'Rezerwacja %s (%s %s) – %s os.', $data['trip'], $data['date'], $data['time'], $data['persons'] );
    $post_id = wp_insert_post([
        'post_type'   => 'wressla_booking',
        'post_status' => 'private',
        'post_title'  => $title,
        'meta_input'  => $data
    ], true);

    if ( is_wp_error($post_id) ) {
        wp_send_json_error(['message' => $post_id->get_error_message()], 500);
    }

    // E-mail do administratora
    $admin = get_option('admin_email');
    $body  = "Nowa rezerwacja Wressla\n\n";
    foreach($data as $k=>$v) $body .= strtoupper($k).": ".$v."\n";
    $ics_path = function_exists('wressla_write_ics_file') ? wressla_write_ics_file( $post_id ) : '';
    $gcal = function_exists('wressla_make_gcal_link') ? wressla_make_gcal_link( $post_id ) : '';
    $body .= "\nDodaj do Google Calendar:\n".$gcal."\n";
    $attachments = $ics_path ? [$ics_path] : [];
    wp_mail( $admin, 'Wressla – Nowa rezerwacja', $body, [], $attachments );

    
    // Payment integration (optional)
    $redirect = '';
    if ( ! empty($_POST['pay_now']) && ! empty($data['email']) ) {
        // If 'trip' is a post ID, try to read ACF deposit
        $trip_id = intval($data['trip']);
        $deposit = 0;
        if ( $trip_id && function_exists('get_field') ){
            $deposit = floatval( get_field('wressla_deposit', $trip_id) );
        }
        if ( $deposit > 0 ){
            $redir = wressla_create_stripe_session( $post_id, $deposit, $data['email'] );
            if ( ! is_wp_error($redir) ){
                $redirect = $redir;
            }
        }
    }

    wp_send_json_success([
        'message' => __('Rezerwacja zapisana. Skontaktujemy się w celu potwierdzenia.', 'wressla-core'),
        'redirect' => $redirect,
        'gcal' => ( function_exists('wressla_make_gcal_link') ? wressla_make_gcal_link( $post_id ) : '' ),
        'ics' => ( function_exists('rest_url') ? rest_url( '/wressla/v1/booking-ics?id=' . $post_id ) : '' )
    ]);

}
add_action('wp_ajax_wressla_submit_booking','wressla_submit_booking');
add_action('wp_ajax_nopriv_wressla_submit_booking','wressla_submit_booking');
