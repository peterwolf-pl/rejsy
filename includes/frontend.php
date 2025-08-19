<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_currency(){
    $opts = get_option('wressla_core_options',[]);
    return strtoupper($opts['currency'] ?? 'PLN');
}
function wressla_format_money( $amount ){
    $cur = wressla_currency();
    if ( ! is_numeric($amount) ) return '';
    $formatted = number_format( (float)$amount, 2, ',', ' ' );
    return $cur === 'PLN' ? $formatted . ' zł' : $formatted . ' ' . $cur;
}

// [wressla_oferta]
function wressla_oferta_shortcode( $atts = [] ){
    $q = new WP_Query([
        'post_type' => 'wressla_trip',
        'posts_per_page' => -1,
        'orderby' => 'menu_order title',
        'order' => 'ASC'
    ]);
    ob_start();
    echo '<div class="wressla-oferta-grid">';
    while( $q->have_posts() ){ $q->the_post();
        $price = function_exists('get_field') ? get_field('wressla_price') : '';
        $dur   = function_exists('get_field') ? get_field('wressla_duration') : '';
        echo '<article class="wressla-card">';
        if ( has_post_thumbnail() ) the_post_thumbnail('medium');
        echo '<h3>'.esc_html( get_the_title() ).'</h3>';
        if ( $dur ) echo '<p><strong>'.intval($dur).' min</strong></p>';
        if ( $price ) echo '<p class="price">'.wressla_format_money($price).'</p>';
        echo '<p>'.esc_html( get_the_excerpt() ).'</p>';
        echo '<p><a class="button" href="'.esc_url( home_url('/rezerwacja/?trip=' . get_the_ID()) ).'">'.__('Zarezerwuj','wressla-core').'</a></p>';
        echo '</article>';
    }
    wp_reset_postdata();
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('wressla_oferta','wressla_oferta_shortcode');

// [wressla_kalendarz id="123"]
function wressla_kalendarz_shortcode( $atts ){
    $atts = shortcode_atts(['id'=>0], $atts);
    $id = intval($atts['id']);
    if ( ! $id ) return '';
    if ( ! function_exists('get_field') ) return '<p>'.__('Dodaj dostępne terminy w ACF.','wressla-core').'</p>';
    $slots = get_field('wressla_slots', $id);
    if ( empty($slots) ) return '<p>'.__('Brak terminów – skontaktuj się telefonicznie.','wressla-core').'</p>';

    ob_start();
    echo '<div class="wressla-kalendarz"><ul>';
    foreach( $slots as $i => $s ){
        $label = esc_html( $s['date'] . ' ' . $s['time'] . ' · ' . sprintf(__('%s miejsc','wressla-core'), intval($s['capacity'])) );
        $value = esc_attr( $s['date'] . ' ' . $s['time'] );
        echo '<li><label><input type="radio" name="slot" value="'.$value.'" /> '.$label.'</label></li>';
    }
    echo '</ul></div>';
    return ob_get_clean();
}
add_shortcode('wressla_kalendarz','wressla_kalendarz_shortcode');
