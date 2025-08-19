<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_register_cpts() {

    // Booking CPT (private)
    register_post_type( 'wressla_booking', [
        'label' => __( 'Rezerwacje', 'wressla-core' ),
        'labels' => [
            'name' => __( 'Rezerwacje', 'wressla-core' ),
            'singular_name' => __( 'Rezerwacja', 'wressla-core' )
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'supports' => ['title','custom-fields'],
        'menu_icon' => 'dashicons-tickets-alt'
    ]);

    // Trip/Offer CPT (optional public)
    register_post_type( 'wressla_trip', [
        'label' => __( 'Rejsy (oferta)', 'wressla-core' ),
        'labels' => [
            'name' => __( 'Rejsy', 'wressla-core' ),
            'singular_name' => __( 'Rejs', 'wressla-core' )
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'rejs'],
        'menu_icon' => 'dashicons-flag',
        'supports' => ['title','editor','excerpt','thumbnail','custom-fields']
    ]);
}
add_action( 'init', 'wressla_register_cpts' );
