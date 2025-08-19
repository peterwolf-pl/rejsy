<?php
if ( ! defined( 'ABSPATH' ) ) exit;
add_action('rest_api_init', function(){
    register_rest_route('wressla/v1','/slots', [
        'methods' => 'GET',
        'callback' => function( WP_REST_Request $req ){
            $id = intval( $req->get_param('id') );
            if ( ! $id ) return new WP_REST_Response([], 200);
            if ( ! function_exists('get_field') ) return new WP_REST_Response([], 200);
            $slots = get_field('wressla_slots', $id) ?: [];
            return new WP_REST_Response($slots, 200);
        },
        'permission_callback' => '__return_true',
    ]);
});
