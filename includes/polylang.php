<?php
if ( ! defined( 'ABSPATH' ) ) exit;
function wressla_register_strings() {
    if ( function_exists('pll_register_string') ) {
        $strings = [
            'Zarezerwuj' => 'Button label',
            'Telefon jest wymagany.' => 'Validation',
            'Brak wystarczającej liczby miejsc na wybrany termin.' => 'Validation',
            'Wybrany termin nie istnieje.' => 'Validation',
            'Do rezerwacji wymagane jest zalogowanie przez Google lub Facebook oraz podanie numeru telefonu.' => 'UI'
        ];
        foreach( $strings as $s => $c ){
            pll_register_string( $s, $s, 'Wressla Core', true );
        }
    }
}
add_action('init','wressla_register_strings');
