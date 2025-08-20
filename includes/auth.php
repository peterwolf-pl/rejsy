<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_register_client_role(){
    if ( ! get_role('wressla_client') ){
        add_role('wressla_client', __('Klient','wressla-core'), ['read'=>true]);
    }
}
add_action('init','wressla_register_client_role');

function wressla_register_shortcode(){
    if ( is_user_logged_in() ){
        return '<p>'.esc_html__('Jesteś już zalogowany.','wressla-core').'</p>';
    }
    $out = '';
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty($_POST['wressla_reg_nonce']) && wp_verify_nonce($_POST['wressla_reg_nonce'],'wressla_reg') ){
        $email = sanitize_email($_POST['email'] ?? '');
        $pass  = $_POST['pass'] ?? '';
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $errors = new WP_Error();
        if ( ! is_email($email) ) $errors->add('email', __('Niepoprawny e-mail.','wressla-core'));
        if ( email_exists($email) ) $errors->add('exists', __('Adres e-mail już istnieje.','wressla-core'));
        if ( empty($phone) ) $errors->add('phone', __('Telefon jest wymagany.','wressla-core'));
        if ( strlen($pass) < 6 ) $errors->add('pass', __('Hasło musi mieć co najmniej 6 znaków.','wressla-core'));
        if ( empty($errors->errors) ){
            $uid = wp_create_user($email, $pass, $email);
            if ( is_wp_error($uid) ){
                $out .= '<p class="error">'.esc_html($uid->get_error_message()).'</p>';
            } else {
                wp_update_user(['ID'=>$uid,'role'=>'wressla_client']);
                update_user_meta($uid,'wressla_phone',$phone);
                $key = wp_generate_password(20,false);
                update_user_meta($uid,'wressla_email_verified',0);
                update_user_meta($uid,'wressla_verification_key',$key);
                $link = add_query_arg(['wressla_verify'=>$key,'uid'=>$uid], home_url('/'));
                wp_mail($email, __('Wressla – potwierdź e-mail','wressla-core'), sprintf(__('Kliknij, aby potwierdzić e-mail: %s','wressla-core'), $link));
                $out .= '<p>'.esc_html__('Dziękujemy za rejestrację. Sprawdź e-mail, aby potwierdzić konto.','wressla-core').'</p>';
                return $out;
            }
        } else {
            foreach( $errors->get_error_messages() as $m ){
                $out .= '<p class="error">'.esc_html($m).'</p>';
            }
        }
    }
    $out .= '<form method="post" class="wressla-form wressla-register-form">';
    $out .= '<div class="row"><div class="col"><label>'.esc_html__('E-mail','wressla-core').' <input type="email" name="email" required></label></div></div>';
    $out .= '<div class="row"><div class="col"><label>'.esc_html__('Telefon','wressla-core').' <input type="tel" name="phone" required></label></div></div>';
    $out .= '<div class="row"><div class="col"><label>'.esc_html__('Hasło','wressla-core').' <input type="password" name="pass" required></label></div></div>';
    $out .= wp_nonce_field('wressla_reg','wressla_reg_nonce',true,false);
    $out .= '<button type="submit">'.esc_html__('Zarejestruj','wressla-core').'</button>';
    $out .= '</form>';
    return $out;
}
add_shortcode('wressla_register','wressla_register_shortcode');

function wressla_handle_email_verification(){
    $key = sanitize_text_field($_GET['wressla_verify'] ?? '');
    $uid = intval($_GET['uid'] ?? 0);
    if ( $key && $uid ){
        $stored = get_user_meta($uid,'wressla_verification_key',true);
        if ( $stored && hash_equals($stored,$key) ){
            update_user_meta($uid,'wressla_email_verified',1);
            delete_user_meta($uid,'wressla_verification_key');
            wp_die(__('Adres e-mail został potwierdzony. Możesz się zalogować.','wressla-core'));
        }
    }
}
add_action('init','wressla_handle_email_verification');
