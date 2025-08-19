<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('acf/init', function(){
    if ( ! function_exists('acf_add_local_field_group') ) return;

    acf_add_local_field_group([
        'key' => 'group_wressla_trip',
        'title' => 'Wressla – Parametry rejsu',
        'fields' => [
            [
                'key' => 'field_wressla_duration',
                'label' => 'Czas trwania (minuty)',
                'name' => 'wressla_duration',
                'type' => 'number',
                'min' => 30,
                'step' => 5,
                'wrapper' => ['width'=>'33']
            ],
            [
                'key' => 'field_wressla_price',
                'label' => 'Cena bazowa',
                'name' => 'wressla_price',
                'type' => 'number',
                'prepend' => get_option('wressla_core_options')['currency'] ?? 'PLN',
                'wrapper' => ['width'=>'33']
            ],
            [
                'key' => 'field_wressla_deposit',
                'label' => 'Zaliczka (opcjonalnie)',
                'name' => 'wressla_deposit',
                'type' => 'number',
                'instructions' => 'Jeśli ustawiona i Stripe skonfigurowany – można zapłacić przy rezerwacji.',
                'wrapper' => ['width'=>'33']
            ],
            [
                'key' => 'field_wressla_langs',
                'label' => 'Języki przewodnika',
                'name' => 'wressla_langs',
                'type' => 'checkbox',
                'choices' => ['pl'=>'PL','en'=>'EN','de'=>'DE'],
                'default_value' => ['pl'],
                'layout' => 'horizontal'
            ],
            [
                'key' => 'field_wressla_slots',
                'label' => 'Dostępne terminy',
                'name' => 'wressla_slots',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Dodaj termin',
                'sub_fields' => [
                    [
                        'key' => 'field_wressla_slot_date',
                        'label' => 'Data',
                        'name' => 'date',
                        'type' => 'date_picker',
                        'display_format' => 'Y-m-d',
                        'return_format' => 'Y-m-d'
                    ],
                    [
                        'key' => 'field_wressla_slot_time',
                        'label' => 'Godzina startu',
                        'name' => 'time',
                        'type' => 'time_picker',
                        'display_format' => 'H:i',
                        'return_format' => 'H:i'
                    ],
                    [
                        'key' => 'field_wressla_slot_capacity',
                        'label' => 'Liczba miejsc',
                        'name' => 'capacity',
                        'type' => 'number',
                        'min' => 1,
                        'max' => 12,
                        'default_value' => 12
                    ]
                ]
            ]
        ],
        'location' => [[ [ 'param'=>'post_type','operator'=>'==','value'=>'wressla_trip' ] ]],
    ]);
});
