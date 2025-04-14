<?php
/*
Plugin Name: Konfigurator Vectis
Plugin URI: https://twojastrona.pl/
Description: Wtyczka do zarządzania zamówieniami niezależnie od WooCommerce + konfigurator
Version: 0.8
Author: Dawid Kolubka
Author URI: https://net-help.pl.pl/
License: GPL2
*/

// Zabezpieczenie przed bezpośrednim dostępem
defined('ABSPATH') or die('Brak dostępu');

// Zabezpieczenie przed bezpośrednim dostępem
if ( ! function_exists('wp_create_nonce') ) {
    require_once( ABSPATH . 'wp-includes/pluggable.php' );
}

// Włączenie sesji dla konfiguratora
function kv_start_session() {
    if(!session_id()) {
        session_start();
    }
}
add_action('init', 'kv_start_session', 1);

// Załaduj pliki modułów
require_once plugin_dir_path(__FILE__) . 'includes/zamowienia/orders.php'; 
require_once plugin_dir_path(__FILE__) . 'includes/konfigurator/configurator.php';

// Załaduj pliki administracyjne
require_once plugin_dir_path(__FILE__) . 'includes/zamowienia/admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/konfigurator/kreator.php';

// Rejestracja hooków menu administracyjnego
add_action('admin_menu', 'kv_kreator_admin_menu');
add_action('admin_menu', 'kv_admin_menu');

// Rejestracja ładowania obrazków
function kv_enqueue_media_uploader() {
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'kv_enqueue_media_uploader');


// dodanie konfigurator.js
function kv_enqueue_configurator_assets() {
    wp_enqueue_style(
        'kv-configurator-style',
        plugins_url('style.css', __FILE__),
        array(),
        '1.0.0'
    );
    
    wp_enqueue_script(
        'kv-konfigurator-js',
        plugins_url('scripts.js', __FILE__),
        array('jquery'),
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'kv_enqueue_configurator_assets');





