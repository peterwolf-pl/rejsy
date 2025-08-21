<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_settings_menu(){
    add_menu_page('Wressla','Wressla','manage_options','wressla-core','wressla_settings_page','dashicons-admin-generic',58);
    add_submenu_page('wressla-core','Kalendarz','Kalendarz','manage_options','wressla-bookings-calendar','wressla_bookings_calendar_page');
}
add_action('admin_menu','wressla_settings_menu');

function wressla_bookings_calendar_page(){
    if ( ! current_user_can('manage_options') ) return;
    $opts      = get_option('wressla_core_options',[]);
    $client_id = sanitize_text_field( $opts['gcal_client_id'] ?? '' );
    $tz = function_exists('wressla_get_timezone') ? wressla_get_timezone() : 'UTC';
    echo '<div class="wrap"><h1>' . esc_html__( 'Kalendarz rezerwacji', 'wressla-core' ) . '</h1>';
    if ( $client_id ) {
        $src = 'https://calendar.google.com/calendar/embed?src=' . rawurlencode( $client_id ) . '&ctz=' . rawurlencode( $tz );
        echo '<iframe src="' . esc_url( $src ) . '" style="border:0" width="100%" height="600" frameborder="0" scrolling="no"></iframe>';
    } else {
        echo '<p>' . esc_html__( 'Brak konfiguracji kalendarza Google.', 'wressla-core' ) . '</p>';
    }
    echo '</div>';
}

function wressla_register_settings(){
    register_setting('wressla_core_options_group','wressla_core_options',[
        'type' => 'array',
        'sanitize_callback' => 'wressla_sanitize_options',
        'default' => [
            'currency' => 'PLN',
            'enable_preconnect_fonts' => 0,
            'tz' => 'Europe/Warsaw',
            'location' => 'Wrocław, Polska',
            'google_client_id' => '',
            'facebook_app_id' => '',
            'facebook_app_secret' => '',
            'gcal_api_key' => '',
            'gcal_client_id' => ''
        ]
    ]);
}
add_action('admin_init','wressla_register_settings');

function wressla_sanitize_options($opts){
    $opts['currency'] = sanitize_text_field($opts['currency'] ?? 'PLN');
    $opts['enable_preconnect_fonts'] = !empty($opts['enable_preconnect_fonts']) ? 1 : 0;
    $opts['tz'] = sanitize_text_field($opts['tz'] ?? 'Europe/Warsaw');
    $opts['location'] = sanitize_text_field($opts['location'] ?? 'Wrocław, Polska');
    $opts['google_client_id'] = sanitize_text_field($opts['google_client_id'] ?? '');
    $opts['facebook_app_id'] = sanitize_text_field($opts['facebook_app_id'] ?? '');
    $opts['facebook_app_secret'] = sanitize_text_field($opts['facebook_app_secret'] ?? '');
    $opts['gcal_api_key'] = sanitize_text_field($opts['gcal_api_key'] ?? '');
    $opts['gcal_client_id'] = sanitize_text_field($opts['gcal_client_id'] ?? '');

    if ( ! empty( $opts['gcal_api_key'] ) && ! empty( $opts['gcal_client_id'] ) && function_exists( 'wressla_gcal_check_connection' ) ) {
        $check = wressla_gcal_check_connection( $opts['gcal_api_key'], $opts['gcal_client_id'] );
        $cache_key = 'wressla_gcal_conn_' . md5( $opts['gcal_api_key'] . '|' . $opts['gcal_client_id'] );
        set_transient( $cache_key, $check, 10 * MINUTE_IN_SECONDS );
        if ( is_wp_error( $check ) ) {
            add_settings_error( 'wressla_core_options', 'gcal', sprintf( __( 'Błąd połączenia z Google Calendar: %s', 'wressla-core' ), $check->get_error_message() ), 'error' );
        } else {
            add_settings_error( 'wressla_core_options', 'gcal', __( 'Połączenie z Google Calendar OK.', 'wressla-core' ), 'updated' );
        }
    }

    return $opts;
}

function wressla_settings_page(){
    if ( ! current_user_can('manage_options') ) return;
    $opts = get_option('wressla_core_options',[]);
    ?>
    <div class="wrap">
        <h1>Wressla – Ustawienia</h1>
        <?php settings_errors(); ?>
        <form method="post" action="options.php">
            <?php settings_fields('wressla_core_options_group'); ?>
            <table class="form-table">
                <tr><th colspan="2"><h2>Logowanie społecznościowe</h2></th></tr>
                <tr><th>Google Client ID</th><td><input type="text" name="wressla_core_options[google_client_id]" value="<?php echo esc_attr($opts['google_client_id'] ?? ''); ?>" size="60"></td></tr>
                <tr><th>Facebook App ID</th><td><input type="text" name="wressla_core_options[facebook_app_id]" value="<?php echo esc_attr($opts['facebook_app_id'] ?? ''); ?>"></td></tr>
                <tr><th>Facebook App Secret</th><td><input type="password" name="wressla_core_options[facebook_app_secret]" value="<?php echo esc_attr($opts['facebook_app_secret'] ?? ''); ?>" size="60"></td></tr>

                <tr><th colspan="2"><h2>Kalendarz</h2></th></tr>
                <tr><th>Strefa czasowa</th><td><input type="text" name="wressla_core_options[tz]" value="<?php echo esc_attr($opts['tz'] ?? 'Europe/Warsaw'); ?>"></td></tr>
                <tr><th>Lokalizacja (mapa/spotkanie)</th><td><input type="text" name="wressla_core_options[location]" value="<?php echo esc_attr($opts['location'] ?? 'Wrocław, Polska'); ?>" size="60"></td></tr>
                <tr><th>Google Calendar API Key</th><td><input type="text" name="wressla_core_options[gcal_api_key]" value="<?php echo esc_attr($opts['gcal_api_key'] ?? ''); ?>" size="60"></td></tr>
                <tr><th>Google Calendar Client ID</th><td><input type="text" name="wressla_core_options[gcal_client_id]" value="<?php echo esc_attr($opts['gcal_client_id'] ?? ''); ?>" size="60"></td></tr>
                <tr><th>Status połączenia</th><td>
                    <?php
                    if ( ! empty( $opts['gcal_api_key'] ) && ! empty( $opts['gcal_client_id'] ) && function_exists( 'wressla_gcal_connection_status' ) ) {
                        $check = wressla_gcal_connection_status();
                        if ( is_wp_error( $check ) ) {
                            echo '<span style="color:red">' . esc_html( $check->get_error_message() ) . '</span>';
                        } else {
                            echo '<span style="color:green">' . esc_html__( 'Połączono', 'wressla-core' ) . '</span>';
                        }
                    } else {
                        echo '<span>' . esc_html__( 'Brak konfiguracji', 'wressla-core' ) . '</span>';
                    }
                    ?>
                </td></tr>
                <tr><td colspan="2">
                    <p><strong>Subskrypcja ICS (Google Calendar → From URL):</strong><br>
                    Rezerwacje: <code><?php echo esc_html( home_url('/?wressla_ics=1') ); ?></code><br>
                    REST ICS (bookings): <code><?php echo esc_html( home_url('/wp-json/wressla/v1/calendar') ); ?></code><br>
                    REST ICS (slots): <code><?php echo esc_html( home_url('/wp-json/wressla/v1/calendar?source=slots') ); ?></code></p>
                </td></tr>

                <tr><th colspan="2"><h2>Wydajność</h2></th></tr>
                <tr><th>Preconnect do Google Fonts</th><td><label><input type="checkbox" name="wressla_core_options[enable_preconnect_fonts]" value="1" <?php checked(1, intval($opts['enable_preconnect_fonts'] ?? 0)); ?>> Włącz</label></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
