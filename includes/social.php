<?php
if ( ! defined( 'ABSPATH' ) ) exit;
add_action('rest_api_init', function(){
    register_rest_route('wressla/v1','/google-login', [
        'methods' => 'POST','permission_callback' => '__return_true',
        'callback' => function( WP_REST_Request $req ){
            $token = sanitize_text_field( $req->get_param('id_token') );
            if ( empty($token) ) return new WP_REST_Response(['ok'=>false], 400);
            $opts = get_option('wressla_core_options',[]);
            $cid = $opts['google_client_id'] ?? '';
            $r = wp_remote_get( 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($token), ['timeout'=>15] );
            if ( is_wp_error($r) ) return new WP_REST_Response(['ok'=>false], 400);
            $code = wp_remote_retrieve_response_code($r);
            $body = json_decode( wp_remote_retrieve_body($r), true );
            if ( $code !== 200 || empty($body['email']) || ($cid && ($body['aud'] ?? '') !== $cid) ) return new WP_REST_Response(['ok'=>false], 400);
            $email = sanitize_email( $body['email'] ); $name = sanitize_text_field( $body['name'] ?? ( $body['given_name'] ?? 'Google User' ) );
            $user = get_user_by('email', $email);
            if ( ! $user ){ $uid = wp_create_user( $email, wp_generate_password(24), $email ); if ( is_wp_error($uid) ) return new WP_REST_Response(['ok'=>false], 400); wp_update_user(['ID'=>$uid,'display_name'=>$name]); update_user_meta($uid,'wressla_provider','google'); $user = get_user_by('id',$uid); }
            wp_set_current_user($user->ID); wp_set_auth_cookie($user->ID, true); update_user_meta($user->ID,'wressla_provider','google');
            return new WP_REST_Response(['ok'=>true], 200);
        }
    ]);
    register_rest_route('wressla/v1','/facebook-login', [
        'methods' => 'POST','permission_callback' => '__return_true',
        'callback' => function( WP_REST_Request $req ){
            $token = sanitize_text_field( $req->get_param('access_token') );
            if ( empty($token) ) return new WP_REST_Response(['ok'=>false], 400);
            $opts = get_option('wressla_core_options',[]);
            $app_id = $opts['facebook_app_id'] ?? ''; $app_secret = $opts['facebook_app_secret'] ?? '';
            if ( empty($app_id) || empty($app_secret) ) return new WP_REST_Response(['ok'=>false], 400);
            $debug_url = add_query_arg(['input_token'=>$token,'access_token'=>$app_id+'|'+$app_secret], 'https://graph.facebook.com/debug_token');
            $r = wp_remote_get($debug_url, ['timeout'=>15]); if ( is_wp_error($r) ) return new WP_REST_Response(['ok'=>false], 400);
            $data = json_decode( wp_remote_retrieve_body($r), true ); if ( empty($data['data']['is_valid']) ) return new WP_REST_Response(['ok'=>false], 400);
            $me = wp_remote_get( add_query_arg(['access_token'=>$token,'fields'=>'id,name,email'],'https://graph.facebook.com/me'), ['timeout'=>15] );
            if ( is_wp_error($me) ) return new WP_REST_Response(['ok'=>false], 400);
            $m = json_decode( wp_remote_retrieve_body($me), true ); $email = sanitize_email($m['email'] ?? 'fb_'.sanitize_text_field($m['id'] ?? wp_generate_uuid4()).'@example.com'); $name = sanitize_text_field($m['name'] ?? 'Facebook User');
            $user = get_user_by('email', $email); if ( ! $user ){ $uid = wp_create_user( $email, wp_generate_password(24), $email ); if ( is_wp_error($uid) ) return new WP_REST_Response(['ok'=>false], 400); wp_update_user(['ID'=>$uid,'display_name'=>$name]); update_user_meta($uid,'wressla_provider','facebook'); $user = get_user_by('id',$uid); }
            wp_set_current_user($user->ID); wp_set_auth_cookie($user->ID, true); update_user_meta($user->ID,'wressla_provider','facebook');
            return new WP_REST_Response(['ok'=>true], 200);
        }
    ]);
});
function wressla_social_login_shortcode(){
    $opts = get_option('wressla_core_options',[]);
    ob_start(); ?>
    <div id="wressla-social-login">
        <div class="providers">
            <?php if ( ! empty($opts['google_client_id']) ) : ?>
                <div id="g_id_onload" data-client_id="<?php echo esc_attr($opts['google_client_id']); ?>" data-context="signin" data-callback="wresslaGoogleCB" data-auto_prompt="false"></div>
                <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="signin_with" data-shape="rect" data-logo_alignment="left"></div>
                <script src="https://accounts.google.com/gsi/client" async defer></script>
            <?php endif; ?>
            <?php if ( ! empty($opts['facebook_app_id']) ) : ?>
                <div id="fb-root"></div>
                <div class="fb-login-button" data-onlogin="wresslaFacebookCB();" data-width="" data-size="large" data-button-type="continue_with" data-layout="default" data-auto-logout-link="false" data-use-continue-as="true"></div>
                <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
                <script>window.fbAsyncInit=function(){FB.init({appId:"<?php echo esc_js($opts['facebook_app_id']); ?>",cookie:true,xfbml:true,version:"v19.0"});};</script>
            <?php endif; ?>
        </div>
    </div>
    <script>
    function wresslaGoogleCB(resp){
        if(!resp || !resp.credential){ return; }
        fetch('<?php echo esc_js( rest_url('wressla/v1/google-login') ); ?>',{
            method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id_token: resp.credential})
        }).then(r=>r.json()).then(function(data){ if(data && data.ok){ location.reload(); } });
    }
    function wresslaFacebookCB(){
        FB.getLoginStatus(function(status){
            if(status && status.status==='connected'){
                fetch('<?php echo esc_js( rest_url('wressla/v1/facebook-login') ); ?>',{
                    method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({access_token: status.authResponse.accessToken})
                }).then(r=>r.json()).then(function(data){ if(data && data.ok){ location.reload(); } });
            }
        });
    }
    </script>
    <?php return ob_get_clean();
}
add_shortcode('wressla_social_login','wressla_social_login_shortcode');
