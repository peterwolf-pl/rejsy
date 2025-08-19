<?php
if ( ! defined( 'ABSPATH' ) ) exit;
function wressla_output_hreflang(){
    if ( function_exists('pll_the_languages') ){
        $langs = pll_the_languages(['raw'=>1]);
        if ( ! empty($langs) ){
            foreach( $langs as $l ){
                if ( ! empty($l['url']) && ! empty($l['locale']) ){
                    echo '<link rel="alternate" hreflang="'.esc_attr($l['locale']).'" href="'.esc_url($l['url']).'"/>'."\n";
                }
            }
        }
    }
}
add_action('wp_head','wressla_output_hreflang', 5);
