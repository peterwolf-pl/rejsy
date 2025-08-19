<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_register_strings() {
    if ( function_exists('pll_register_string') ) {
        $strings = [
            'Zarezerwuj' => 'Button label',
            'Data' => 'Field label',
            'Godzina (preferencja)' => 'Field label',
            'Osób' => 'Field label',
            'Imię i nazwisko' => 'Field label',
            'Telefon' => 'Field label',
            'E‑mail' => 'Field label',
            'Język przewodnika' => 'Field label',
            'Rodzaj rejsu' => 'Field label',
            'Wiadomość' => 'Field label',
            'Dziękujemy! Potwierdzimy termin e‑mailem/SMS.' => 'UI',
            'Ups, spróbuj ponownie lub zadzwoń.' => 'UI',
            'Rezerwacja zapisana. Skontaktujemy się w celu potwierdzenia.' => 'UI'
        ];
        foreach( $strings as $s => $c ){
            pll_register_string( $s, $s, 'Wressla Core', true );
        }
    }
}
add_action('init','wressla_register_strings');
