<?php
if ( ! defined( 'ABSPATH' ) ) exit;
add_filter('wp_get_attachment_image_attributes', function($attr){
    $attr['loading'] = $attr['loading'] ?? 'lazy';
    $attr['decoding'] = 'async';
    return $attr;
}, 10, 1);
add_action('wp_head', function(){
    $opts = get_option('wressla_core_options',[]);
    if ( ! empty($opts['enable_preconnect_fonts']) ){
        echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>'."\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'."\n";
    }
}, 1);
