<?php
// Włącz buforowanie wyjścia na początku
ob_start();

/*
Plugin Name: Konfigurator Vectis
Plugin URI: https://github.com/DawidKolubka/Konfigurator-Vectis
Description: Wtyczka do zarządzania zamówieniami niezależnie od WooCommerce + konfigurator
Version: 0.91
Author: Dawid Kolubka
Author URI: https://net-help.pl.pl/
License: GPL2
*/

// Zabezpieczenie przed bezpośrednim dostępem
defined('ABSPATH') or die('Brak dostępu');

// Włączenie sesji dla konfiguratora - zmodyfikowane dla unikania błędów
function kv_start_session() {
    // Sprawdzamy czy nie wysłano już nagłówków
    if (!headers_sent() && !session_id()) {
        @session_start();
    }
}
add_action('init', 'kv_start_session', 1);

// Zabezpieczenie przed bezpośrednim dostępem do pluggable.php
// Przesunięte po inicjalizacji sesji
if (!function_exists('wp_create_nonce')) {
    require_once(ABSPATH . 'wp-includes/pluggable.php');
}

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

// Dodaj funkcję do pobierania wartości z sesji
function get_configurator_step() {
    // Sprawdź bezpieczeństwo
    if (!isset($_POST['step'])) {
        wp_send_json_error('Brak parametru step');
        return;
    }
    
    // Pobierz wartość z sesji
    $step = sanitize_text_field($_POST['step']);
    $value = isset($_SESSION['configurator'][$step]) ? $_SESSION['configurator'][$step] : '';
    
    // Zwróć wartość
    wp_send_json_success($value);
    wp_die();
}
add_action('wp_ajax_get_configurator_step', 'get_configurator_step');
add_action('wp_ajax_nopriv_get_configurator_step', 'get_configurator_step');

// Dodaj funkcję do zapisywania wartości w sesji
function save_configurator_step() {
    error_log('KONFIGURATOR: Wywołanie save_configurator_step z danymi: ' . print_r($_POST, true));
    
    // Sprawdź, czy otrzymano dane
    if (!isset($_POST['step']) || !isset($_POST['value'])) {
        error_log('KONFIGURATOR: Brak wymaganych danych w żądaniu');
        wp_send_json_error('Brak danych');
        return;
    }

    // Pobierz dane
    $step = sanitize_text_field($_POST['step']);
    $value = sanitize_text_field($_POST['value']);

    // Zapisz dane do sesji
    $_SESSION['configurator'][$step] = $value;
    error_log('KONFIGURATOR: Zapisano do sesji ' . $step . ' = ' . $value);
    
    // Sprawdź sesję po zapisie
    error_log('KONFIGURATOR: Zawartość sesji po zapisie: ' . print_r($_SESSION['configurator'], true));

    // Zwróć sukces z dodatkową informacją o tym, co zostało zapisane
    wp_send_json_success(array(
        'saved_step' => $step,
        'saved_value' => $value
    ));
}
add_action('wp_ajax_save_configurator_step', 'save_configurator_step');
add_action('wp_ajax_nopriv_save_configurator_step', 'save_configurator_step');

// Napraw strukturę danych mechanizmów, gdy jakieś pola są uszkodzone
function kv_repair_mechanizm_data() {
    $mechanizm_options = get_option('kv_mechanizm_options', []);
    $changed = false;
    
    foreach ($mechanizm_options as $id => &$mech) {
        // Upewnij się, że wszystkie wymagane pola istnieją
        if (!isset($mech['frame_image'])) {
            $mech['frame_image'] = '';
            $changed = true;
        }
        
        if (!isset($mech['snippet'])) {
            $mech['snippet'] = 'MECH' . $id; // Domyślny kod
            $changed = true;
        }
        
        if (!isset($mech['selected_colors']) || !is_array($mech['selected_colors'])) {
            $mech['selected_colors'] = [];
            $changed = true;
        }
    }
    
    if ($changed) {
        update_option('kv_mechanizm_options', $mechanizm_options);
        error_log('Naprawiono uszkodzone dane mechanizmów');
    }
}

// Uruchom naprawę podczas aktywacji pluginu
register_activation_hook(__FILE__, 'kv_repair_mechanizm_data');

// Uruchom również teraz, w przypadku aktualizacji pluginu
add_action('plugins_loaded', 'kv_repair_mechanizm_data');
