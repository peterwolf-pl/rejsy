<?php
if ( ! defined( 'ABSPATH' ) ) exit;
add_filter( 'rank_math/frontend/title', function( $title ){
    if ( is_front_page() ) return 'Wressla – Wrocławski Tramwaj Rzeczny | Rejsy po Odrze';
    return $title;
});
add_filter( 'rank_math/frontend/description', function( $desc ){
    if ( is_front_page() ) return 'Rejsy katamaranem stylizowanym na tramwaj. Do 12 osób, przewodnik, elastyczne trasy po Odrze. Zarezerwuj Wrocław od strony wody.';
    return $desc;
});
