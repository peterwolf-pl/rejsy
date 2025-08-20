<?php
/**
 * Plugin Name: Wressla Core
 * Description: Rezerwacje z potwierdzeniem e-mail, auto-dekrementacją miejsc i integracją z Kalendarzem (GCal + ICS). + CPT, schema, RankMath, Polylang, patterns.
 * Version: 1.0.3
 * Author: Peter + ChatGPT
 * License: GPLv2 or later
 * Text Domain: wressla-core
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WRESSLA_CORE_VER', '1.0.3' );
define( 'WRESSLA_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'WRESSLA_CORE_URL', plugin_dir_url( __FILE__ ) );

function wressla_core_require( $rel ){
    $path = WRESSLA_CORE_DIR . $rel;
    if ( file_exists($path) ) { require_once $path; }
    else { error_log('Wressla Core: missing include ' . $path); }
}

// Core modules
wressla_core_require('includes/register-cpt.php');
wressla_core_require('includes/shortcodes.php');
wressla_core_require('includes/schema.php');
wressla_core_require('includes/rankmath.php');
wressla_core_require('includes/polylang.php');
wressla_core_require('includes/block-patterns.php');

// Extensions
wressla_core_require('includes/settings.php');
wressla_core_require('includes/acf.php');
wressla_core_require('includes/frontend.php');
wressla_core_require('includes/sitemap.php');
wressla_core_require('includes/hreflang.php');
wressla_core_require('includes/performance.php');
wressla_core_require('includes/rest.php');
wressla_core_require('includes/calendar.php');
wressla_core_require('includes/gcal.php');

register_activation_hook( __FILE__, function(){
    if ( function_exists('wressla_register_cpts') ) {
        wressla_register_cpts();
    }
    flush_rewrite_rules();
});
register_deactivation_hook( __FILE__, function(){
    flush_rewrite_rules();
});
