<?php
if ( ! defined( 'ABSPATH' ) ) exit;
function wressla_register_cpts() {
    register_post_type( 'wressla_booking', [
        'label' => __( 'Rezerwacje', 'wressla-core' ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => ['title','custom-fields'],
        'menu_icon' => 'dashicons-tickets-alt'
    ]);
    register_post_type( 'wressla_trip', [
        'label' => __( 'Rejsy (oferta)', 'wressla-core' ),
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'rejs'],
        'menu_icon' => 'dashicons-flag',
        'supports' => ['title','editor','excerpt','thumbnail','custom-fields']
    ]);
}
add_action( 'init', 'wressla_register_cpts' );
