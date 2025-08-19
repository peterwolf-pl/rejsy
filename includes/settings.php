<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_settings_menu(){
    add_menu_page(
        __('Wressla', 'wressla-core'),
        'Wressla',
        'manage_options',
        'wressla-core',
        'wressla_settings_page',
        'dashicons-admin-generic',
        58
    );
}
add_action('admin_menu','wressla_settings_menu');

function wressla_register_settings(){
    register_setting('wressla_core_options_group','wressla_core_options',[
        'type' => 'array',
        'sanitize_callback' => 'wressla_sanitize_options',
        'default' => [
            'currency' => 'PLN',
            'stripe_mode' => 'test',
            'stripe_secret_key' => '',
            'stripe_webhook_secret' => '',
            'success_url' => home_url('/rezerwacja/?status=ok'),
            'cancel_url'  => home_url('/rezerwacja/?status=cancel'),
            'enable_preconnect_fonts' => 0,
            'tz' => 'Europe/Warsaw',
            'location' => 'Wrocław, Polska'
        ]
    ]);
}
add_action('admin_init','wressla_register_settings');

function wressla_sanitize_options($opts){
    $opts['currency'] = sanitize_text_field($opts['currency'] ?? 'PLN');
    $opts['stripe_mode'] = in_array(($opts['stripe_mode'] ?? 'test'),['test','live']) ? $opts['stripe_mode'] : 'test';
    $opts['stripe_secret_key'] = sanitize_text_field($opts['stripe_secret_key'] ?? '');
    $opts['stripe_webhook_secret'] = sanitize_text_field($opts['stripe_webhook_secret'] ?? '');
    $opts['success_url'] = esc_url_raw($opts['success_url'] ?? home_url('/rezerwacja/?status=ok'));
    $opts['cancel_url'] = esc_url_raw($opts['cancel_url'] ?? home_url('/rezerwacja/?status=cancel'));
    $opts['enable_preconnect_fonts'] = !empty($opts['enable_preconnect_fonts']) ? 1 : 0;
    $opts['tz'] = sanitize_text_field($opts['tz'] ?? 'Europe/Warsaw');
    $opts['location'] = sanitize_text_field($opts['location'] ?? 'Wrocław, Polska');
    return $opts;
}

function wressla_settings_page(){
    if ( ! current_user_can('manage_options') ) return;
    $opts = get_option('wressla_core_options',[]);
    ?>
    <div class="wrap">
        <h1>Wressla – Ustawienia</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wressla_core_options_group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Waluta</th>
                    <td><input type="text" name="wressla_core_options[currency]" value="<?php echo esc_attr($opts['currency'] ?? 'PLN'); ?>" /></td>
                </tr>
                <tr><th colspan="2"><h2>Płatności Stripe</h2></th></tr>
                <tr>
                    <th scope="row">Tryb</th>
                    <td>
                        <select name="wressla_core_options[stripe_mode]">
                            <option value="test" <?php selected( $opts['stripe_mode'] ?? '', 'test'); ?>>Test</option>
                            <option value="live" <?php selected( $opts['stripe_mode'] ?? '', 'live'); ?>>Live</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Secret key</th>
                    <td><input type="password" name="wressla_core_options[stripe_secret_key]" value="<?php echo esc_attr($opts['stripe_secret_key'] ?? ''); ?>" size="60"/></td>
                </tr>
                <tr>
                    <th scope="row">Webhook secret</th>
                    <td><input type="password" name="wressla_core_options[stripe_webhook_secret]" value="<?php echo esc_attr($opts['stripe_webhook_secret'] ?? ''); ?>" size="60"/></td>
                </tr>
                <tr>
                    <th scope="row">Success URL</th>
                    <td><input type="text" name="wressla_core_options[success_url]" value="<?php echo esc_attr($opts['success_url'] ?? home_url('/rezerwacja/?status=ok')); ?>" size="80"/></td>
                </tr>
                <tr>
                    <th scope="row">Cancel URL</th>
                    <td><input type="text" name="wressla_core_options[cancel_url]" value="<?php echo esc_attr($opts['cancel_url'] ?? home_url('/rezerwacja/?status=cancel')); ?>" size="80"/></td>
                </tr>

                <tr><th colspan="2"><h2>Kalendarz</h2></th></tr>
                <tr>
                    <th scope="row">Strefa czasowa</th>
                    <td><input type="text" name="wressla_core_options[tz]" value="<?php echo esc_attr($opts['tz'] ?? 'Europe/Warsaw'); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row">Lokalizacja (mapa/spotkanie)</th>
                    <td><input type="text" name="wressla_core_options[location]" value="<?php echo esc_attr($opts['location'] ?? 'Wrocław, Polska'); ?>" size="60"/></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <p><strong>Subskrypcja ICS (Google Calendar → From URL):</strong><br/>
                        URL wszystkich rezerwacji: <code><?php echo esc_html( home_url('/?wressla_ics=1') ); ?></code><br/>
                        REST ICS (bookings): <code><?php echo esc_html( home_url('/wp-json/wressla/v1/calendar') ); ?></code><br/>
                        REST ICS (slots): <code><?php echo esc_html( home_url('/wp-json/wressla/v1/calendar?source=slots') ); ?></code></p>
                    </td>
                </tr>

                <tr><th colspan="2"><h2>Wydajność</h2></th></tr>
                <tr>
                    <th scope="row">Preconnect do Google Fonts</th>
                    <td><label><input type="checkbox" name="wressla_core_options[enable_preconnect_fonts]" value="1" <?php checked(1, intval($opts['enable_preconnect_fonts'] ?? 0)); ?>/> Włącz</label></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
