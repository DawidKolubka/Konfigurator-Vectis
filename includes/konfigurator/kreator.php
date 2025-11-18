<?php
defined('ABSPATH') or die('Brak dostępu');

// Dołącz plik z funkcją strony "Seria" – upewnij się, że ścieżka jest poprawna
require_once __DIR__ . '/seria.php';
require_once __DIR__ . '/ksztalt.php';
require_once __DIR__ . '/uklady.php';
require_once __DIR__ . '/kolor-ramki.php';
require_once __DIR__ . '/technologia.php';
require_once __DIR__ . '/kolor-mechanizmu.php';
require_once __DIR__ . '/mechanizm.php';

/**
 * Rejestruje menu "Kreator" oraz podmenu "Seria" w panelu administracyjnym.
 */
function kv_kreator_admin_menu() {
    // główna pozycja menu "Kreator"
    add_menu_page(
        'Kreator Konfiguratora',   // Tytuł strony
        'Kreator',                 // Tytuł menu
        'manage_options',          // Uprawnienia
        'kv-kreator',              // Unikalny slug menu
        'kv_kreator_page',         // Funkcja wyświetlająca stronę główną kreatora
        'dashicons-admin-generic', // Ikona menu
        27                         // Pozycja menu
    );
    
    // podmenu "Seria"
    add_submenu_page(
        'kv-kreator',           // Rodzic – główna pozycja menu
        'Seria',                // Tytuł strony podmenu
        'Seria',                // Tytuł podmenu
        'manage_options',       // Uprawnienia
        'kv-seria',             // Unikalny slug podmenu
        'kv_admin_seria_page'   // Callback – funkcja zdefiniowana w seria.php
    );

    // podmenu "Kształt"
    add_submenu_page(
        'kv-kreator',
        'Kształt',
        'Kształt',
        'manage_options',
        'kv-ksztalt',
        'kv_admin_ksztalt_page'  // callback zdefiniowany w ksztalt.php
    );
    
    // podmenu Układy
    add_submenu_page(
        'kv-kreator',
        'Układy',
        'Układy',
        'manage_options',
        'kv-uklady',
        'kv_admin_uklady_page'
    );

      // Podmenu "Kolor Ramki"
      add_submenu_page(
        'kv-kreator',
        'Kolor Ramki',
        'Kolor Ramki',
        'manage_options',
        'kv-kolor-ramki',
        'kv_admin_kolor_ramki_page'
    );
// Podmenu "Kolor Mechanizmu"
                add_submenu_page(
                    'kv-kreator',
                    'Kolor Mechanizmu',
                    'Kolor Mechanizmu',
                    'manage_options',
                    'kv-kolor-mechanizmu',
                    'kv_admin_kolor_mechanizmu_page'
                );


        // Podmenu "Grupa Mechanizmów"
        add_submenu_page(
            'kv-kreator',
            'Grupa Mechanizmów',
            'Grupa Mechanizmów',
            'manage_options',
            'kv-mechanizm',
            'kv_admin_mechanizm_page'
        );

                // Podmenu "Produkty grupy"
                add_submenu_page(
                    'kv-kreator',
                    'Produkty grupy',
                    'Produkty grupy',
                    'manage_options',
                    'kv-technologia',
                    'kv_admin_technologia_page'
                );
        
}


/**
 * Strona główna Kreatora – dashboard.
 */
function kv_kreator_page() {
    echo '<div class="wrap">';
    echo '<h1>Kreator Konfiguratora</h1>';
    echo '<p>Wybierz opcję z menu po lewej stronie.</p>';
    
    // Narzędzia administracyjne (tylko dla administratorów)
    if (current_user_can('manage_options')) {
        echo '<div class="admin-tools" style="margin-top: 30px; padding: 15px; background: #f1f1f1; border: 1px solid #ddd; border-radius: 4px;">';
        echo '<h3>Narzędzia administracyjne</h3>';
        echo '<p>Narzędzia do zarządzania i naprawy konfiguratora:</p>';
        echo '<a href="' . admin_url('admin.php?page=kv-kreator&kv_repair_ids=1') . '" class="button button-primary" onclick="return confirm(\'Czy na pewno chcesz przeprowadzić naprawę ID? Ta operacja jest nieodwracalna.\');">Napraw ID mechanizmów i technologii</a>';
        echo '<p><small>Naprawia problemy z walidacją związane z mechanizmami i technologiami o ID 0.</small></p>';
        echo '</div>';
    }
    
    // Miejsce na dodatkowe sekcje
    do_action('kv_kreator_page_after');
    
    echo '</div>';
}
