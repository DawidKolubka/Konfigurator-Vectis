<?php
defined('ABSPATH') or die('Brak dostępu');

if ( ! function_exists('kv_admin_menu') ) {
    /**
     * Funkcja dodająca pozycję w menu administracyjnym.
     */
    function kv_admin_menu() {
        add_menu_page(
            'Zamówienia',          // Tytuł strony
            'Zamówienia',          // Tytuł menu
            'manage_options',               // Uprawnienia
            'konfigurator-vectis',          // Unikalny slug menu
            'kv_admin_page',                // Funkcja wyświetlająca zawartość strony
            'dashicons-admin-generic',      // Ikona menu (opcjonalnie)
            6                               // Pozycja menu (opcjonalnie)
        );
    }
}

if ( ! function_exists('kv_admin_page') ) {
    /**
     * Funkcja wyświetlająca zawartość strony administracyjnej wtyczki.
     */
    function kv_admin_page() {
        echo '<div class="wrap">';
        echo '<h1>Panel administracyjny Konfiguratora Vectis</h1>';
        echo '<p>Tutaj możesz zarządzać zamówieniami oraz konfiguracją wtyczkii.</p>';
        
        // Przykładowe wyświetlenie zamówień (jeśli funkcja kv_get_orders() jest dostępna)
        if ( function_exists('kv_get_orders') ) {
            $orders = kv_get_orders();
            if ( ! empty( $orders ) ) {
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr>';
                echo '<th>ID</th>';
                echo '<th>Numer zamówienia</th>';
                echo '<th>Dane zamówienia</th>';
                echo '<th>Data utworzenia</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                foreach ( $orders as $order ) {
                    $order_data = maybe_unserialize( $order['order_data'] );
                    echo '<tr>';
                    echo '<td>' . esc_html( $order['id'] ) . '</td>';
                    echo '<td>' . esc_html( $order['order_number'] ) . '</td>';
                    echo '<td>';
                    if ( is_array( $order_data ) ) {
                        foreach ( $order_data as $key => $value ) {
                            echo '<strong>' . esc_html( $key ) . '</strong>: ' . esc_html( $value ) . '<br>';
                        }
                    } else {
                        echo esc_html( $order_data );
                    }
                    echo '</td>';
                    echo '<td>' . esc_html( $order['created_at'] ) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>Brak zamówień.</p>';
            }
        }
        echo '</div>';
    }
}
