<?php
// Włącz buforowanie wyjścia na początku
ob_start();

// Dawid - usuń jak to zobaczysz
add_action('init', 'kv_repair_data');
/*
Plugin Name: Konfigurator Vectis
Plugin URI: https://github.com/DawidKolubka/Konfigurator-Vectis
Description: Wtyczka do zarządzania zamówieniami niezależnie od WooCommerce + konfigurator
Version: 0.11
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

// Załaduj plik integralności danych jako pierwszy - zawiera funkcje bezpieczeństwa
require_once plugin_dir_path(__FILE__) . 'includes/konfigurator/data_integrity.php';

// Załaduj system ról
require_once plugin_dir_path(__FILE__) . 'includes/roles.php';

// Załaduj system powiadomień
require_once plugin_dir_path(__FILE__) . 'includes/notifications.php';

// Załaduj pliki modułów
require_once plugin_dir_path(__FILE__) . 'includes/zamowienia/orders.php'; 
require_once plugin_dir_path(__FILE__) . 'includes/zamowienia/frontend.php';
require_once plugin_dir_path(__FILE__) . 'includes/konfigurator/configurator.php';
// Załaduj rozszerzenie skryptów
require_once plugin_dir_path(__FILE__) . 'includes/konfigurator/scripts-patch.php';

// Załaduj pliki administracyjne
require_once plugin_dir_path(__FILE__) . 'includes/zamowienia/admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/konfigurator/kreator.php';
require_once plugin_dir_path(__FILE__) . 'includes/konfigurator/repair_ids.php';

// Rejestracja hooków menu administracyjnego
add_action('admin_menu', 'kv_kreator_admin_menu');
add_action('admin_menu', 'kv_admin_menu');

// Rejestracja ładowania obrazków
function kv_enqueue_media_uploader() {
    if (isset($_GET['page']) && (strpos($_GET['page'], 'kv-') === 0)) {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
    }
}
add_action('admin_enqueue_scripts', 'kv_enqueue_media_uploader');

// Hook aktywacji wtyczki - zaktualizuje strukturę tabeli
register_activation_hook(__FILE__, 'kv_plugin_activation');
function kv_plugin_activation() {
    kv_create_orders_table();
}

// dodanie konfigurator.js
function kv_enqueue_configurator_assets() {
    // Dodaj style
    wp_enqueue_style(
        'kv-configurator-style',
        plugins_url('style.css', __FILE__),
        array(),
        '1.0.0'
    );
    
    // Dodaj skrypt
    wp_enqueue_script(
        'kv-configurator-script',
        plugins_url('scripts.js', __FILE__),
        array('jquery'),
        '1.0.0',
        true
    );
    
    // Opcjonalnie: Przekaż dane do JavaScriptu
    wp_localize_script('kv-configurator-script', 'kv_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
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

// Napraw strukturę danych mechanizmów i technologii
function kv_repair_data() {
    // Napraw mechanizmy
    $mechanizm_options = get_option('kv_mechanizm_options', []);
    $mechanizm_changed = false;
    
    foreach ($mechanizm_options as $id => &$mech) {
        // Upewnij się, że wszystkie wymagane pola istnieją
        if (!isset($mech['frame_image']) || empty($mech['frame_image'])) {
            $mech['frame_image'] = isset($mech['image']) ? $mech['image'] : '';
            $mechanizm_changed = true;
        }
        
        if (!isset($mech['snippet']) || empty($mech['snippet'])) {
            $mech['snippet'] = 'M' . $id;
            $mechanizm_changed = true;
        }
        
        if (!isset($mech['selected_colors']) || !is_array($mech['selected_colors'])) {
            $mech['selected_colors'] = [];
            $mechanizm_changed = true;
        }
    }
    
    if ($mechanizm_changed) {
        update_option('kv_mechanizm_options', $mechanizm_options);
        error_log('Naprawiono dane mechanizmów');
    }
    
    // Napraw technologie i ich relacje z mechanizmami
    $technologia_options = get_option('kv_technologia_options', []);
    $technologia_changed = false;
    
    foreach ($technologia_options as $id => &$tech) {
        // Upewnij się, że pole group jest liczbą całkowitą
        if (isset($tech['group'])) {
            $tech['group'] = intval($tech['group']);
            
            // Sprawdź, czy dany mechanizm istnieje
            if (!isset($mechanizm_options[$tech['group']])) {
                error_log("BŁĄD: Technologia #{$id} jest powiązana z nieistniejącym mechanizmem #{$tech['group']}");
                // Możesz usunąć tę linię jeśli nie chcesz automatycznie naprawiać relacji
                // $tech['group'] = array_key_first($mechanizm_options);
                // $technologia_changed = true;
            }
        } else {
            $tech['group'] = 0; // domyślna wartość, jeśli brak
            $technologia_changed = true;
        }
        
        // Dodaj inne potrzebne pola, jeśli ich brak
        if (!isset($tech['technology']) || empty($tech['technology'])) {
            $tech['technology'] = 'Technologia #' . $id;
            $technologia_changed = true;
        }
        
        if (!isset($tech['code'])) {
            $tech['code'] = 'T' . $id;
            $technologia_changed = true;
        }
        
        if (!isset($tech['price']) || !is_numeric($tech['price'])) {
            $tech['price'] = '0';
            $technologia_changed = true;
        }
    }
    
    // Sprawdź i napraw relacje technologii
    $technologia_options = get_option('kv_technologia_options', []);
    $technologia_changed = false;

    foreach ($technologia_options as $id => &$tech) {
        // Upewnij się, że relacje są poprawne
        if (isset($tech['group'])) {
            $group_id = (int)$tech['group'];
            if (!isset($mechanizm_options[$group_id])) {
                // Jeśli mechanizm nie istnieje, przypisz pierwszy dostępny
                $tech['group'] = array_key_first($mechanizm_options);
                error_log("Naprawiono: Technologia #{$id} była powiązana z nieistniejącym mechanizmem #{$group_id}");
                $technologia_changed = true;
            }
        }
    }

    if ($technologia_changed) {
        update_option('kv_technologia_options', $technologia_options);
        error_log('Naprawiono dane technologii');
    }
    
    if ($technologia_changed) {
        update_option('kv_technologia_options', $technologia_options);
        error_log('Naprawiono dane technologii');
    }
    
    // Popraw typ danych w relacjach
    $technologia_options = get_option('kv_technologia_options', []);
    $technologia_changed = false;

    foreach ($technologia_options as $id => &$tech) {
        // Upewnij się, że grupa jest zapisana jako string
        if (isset($tech['group'])) {
            // Zapisz jako string aby uniknąć problemów z porównaniami 0 vs '0'
            $tech['group'] = (string)$tech['group']; 
            $technologia_changed = true;
            
            // Sprawdź, czy grupa istnieje w mechanizmach
            if (!isset($mechanizm_options[(int)$tech['group']])) {
                error_log("Technologia #{$id} odwołuje się do nieistniejącego mechanizmu #{$tech['group']}");
            }
        }
        
        // Upewnij się, że kolor jest zapisany jako string
        if (isset($tech['color'])) {
            $tech['color'] = (string)$tech['color'];
            $technologia_changed = true;
        }
    }

    if ($technologia_changed) {
        update_option('kv_technologia_options', $technologia_options);
        error_log('Naprawiono typy danych w technologiach');
    }
    
    return $mechanizm_changed || $technologia_changed;
}

// Utworzenie tabeli zamówień przy aktywacji wtyczki
register_activation_hook(__FILE__, 'kv_activate_plugin');

function kv_activate_plugin() {
    // Napraw dane (istniejąca funkcja)
    kv_repair_data();
    
    // Utwórz tabelę zamówień
    if (function_exists('kv_create_orders_table')) {
        kv_create_orders_table();
    }
}

register_activation_hook(__FILE__, 'kv_repair_data');
add_action('plugins_loaded', 'kv_repair_data');

// Sprawdź i zaktualizuj strukturę bazy danych przy każdym ładowaniu wtyczki
add_action('plugins_loaded', 'kv_check_db_structure');
function kv_check_db_structure() {
    // Sprawdź wersję struktury bazy danych
    $db_version = get_option('kv_db_version', '0');
    $current_version = '1.0'; // Aktualna wersja struktury
    
    if (version_compare($db_version, $current_version, '<')) {
        kv_create_orders_table(); // To automatycznie zaktualizuje strukturę
        update_option('kv_db_version', $current_version);
        error_log('KV: Zaktualizowano strukturę bazy danych do wersji ' . $current_version);
    }
}