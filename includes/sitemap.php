<?php
if ( ! defined( 'ABSPATH' ) ) exit;
add_filter( 'rank_math/sitemap/post_types', function( $post_types ){
    $post_types['wressla_trip'] = true;
    return $post_types;
});
