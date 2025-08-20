<?php
if ( ! defined( 'ABSPATH' ) ) exit;
add_action( 'init', function(){
    if ( function_exists('register_block_pattern_category') ) {
        register_block_pattern_category( 'wressla', ['label' => 'Wressla'] );
    }
    if ( function_exists('register_block_pattern') ) {
        register_block_pattern( 'wressla/hero', [
            'title' => 'Wressla – Hero',
            'categories' => ['wressla'],
            'content' => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}}} --><div class="wp-block-group alignfull" style="padding-top:60px;padding-bottom:60px"><div class="wp-block-columns is-layout-flex"><div class="wp-block-column"><h1>Wressla – Wrocławski Tramwaj Rzeczny</h1><p>Wrocław od strony wody: kameralne rejsy katamaranem stylizowanym na tramwaj. Do 12 osób, z przewodnikiem.</p><p><a class="wp-block-button__link" href="/rezerwacja">Zarezerwuj termin</a></p></div><div class="wp-block-column"></div></div></div><!-- /wp:group -->'
        ]);
        register_block_pattern( 'wressla/offers', [
            'title' => 'Wressla – Oferta',
            'categories' => ['wressla'],
            'content' => '<!-- wp:columns --><div class="wp-block-columns"><div class="wp-block-column"><h3>Rejs miejski 60′</h3><p>Esencja mostów i wysp.</p></div><div class="wp-block-column"><h3>Golden Hour 75–90′</h3><p>Zachód słońca i fotografia.</p></div><div class="wp-block-column"><h3>Tematyczny 90–120′</h3><p>Historia / architektura / przyroda.</p></div><div class="wp-block-column"><h3>Prywatny</h3><p>Scenariusz na miarę.</p></div></div><!-- /wp:columns -->'
        ]);
    }
});
