<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wressla_output_schema() {
    if ( ! is_front_page() ) return;

    $schema_local = [
        "@context" => "https://schema.org",
        "@type"    => ["LocalBusiness","TouristTrip"],
        "name"     => "Wressla – Wrocławski Tramwaj Rzeczny",
        "url"      => home_url( '/' ),
        "areaServed" => "Wrocław, Poland",
        "description" => "Kameralne rejsy katamaranem stylizowanym na tramwaj: do 12 osób, przewodnik, elastyczne trasy po Odrze i odnogach.",
        "telephone" => get_bloginfo('admin_email'),
        "offers"   => [
            ["@type"=>"Offer","name"=>"Rejs miejski 60′","priceCurrency"=>"PLN"],
            ["@type"=>"Offer","name"=>"Golden Hour 75–90′","priceCurrency"=>"PLN"],
            ["@type"=>"Offer","name"=>"Rejs tematyczny 90–120′","priceCurrency"=>"PLN"],
            ["@type"=>"Offer","name"=>"Rejs prywatny","priceCurrency"=>"PLN"]
        ]
    ];

    $schema_faq = [
        "@context" => "https://schema.org",
        "@type"    => "FAQPage",
        "mainEntity" => [[
            "@type"=>"Question",
            "name"=>"Czy Wressla to tramwaj wodny?",
            "acceptedAnswer"=>["@type"=>"Answer","text"=>"Styl tak, ale to kameralny katamaran dla małych grup – bliżej, spokojniej, z przewodnikiem."]
        ],[
            "@type"=>"Question",
            "name"=>"Ile osób wejdzie na pokład?",
            "acceptedAnswer"=>["@type"=>"Answer","text"=>"Do 12 pasażerów + załoga."]
        ],[
            "@type"=>"Question",
            "name"=>"Skąd startujemy?",
            "acceptedAnswer"=>["@type"=>"Answer","text"=>"Z przystani w centrum; dokładne miejsce potwierdzamy po rezerwacji."]
        ],[
            "@type"=>"Question",
            "name"=>"Czy rejsy odbywają się przy deszczu?",
            "acceptedAnswer"=>["@type"=>"Answer","text"=>"Lekkie opady – tak; przy silnym wietrze/alertach – przekładamy."]
        ]]
    ];

    echo '<script type="application/ld+json">'.wp_json_encode($schema_local).'</script>';
    echo '<script type="application/ld+json">'.wp_json_encode($schema_faq).'</script>';
}
add_action('wp_head','wressla_output_schema', 20);
