<?php
/**
 * Funkcja do wczytywania pliku scripts-patch.js
 * Dodaje dodatkową integrację konfiguratora w Kroku 4
 * - Wsparcie dla unikalnych technologii
 * - Automatyczne dopasowywanie kolorów
 */
function kv_enqueue_scripts_patch() {
    // Dodajemy tylko na stronach z konfiguratorem
    if (has_shortcode(get_post()->post_content, 'konfigurator_vectis')) {
        wp_enqueue_script(
            'kv-scripts-patch', 
            plugin_dir_url(__FILE__) . 'scripts-patch.js', 
            array('jquery'), 
            '1.0.0', 
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'kv_enqueue_scripts_patch');
