<?php
defined('ABSPATH') or die('Brak dostępu');

if ( ! function_exists('kv_create_orders_table') ) {
    /**
     * Tworzy tabelę do przechowywania zamówień.
     */
    function kv_create_orders_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vectis_orders'; // np. wp_vectis_orders
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            order_data text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Załadowanie funkcji dbDelta() do obsługi aktualizacji bazy danych
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

if ( ! function_exists('kv_get_orders') ) {
    /**
     * Pobiera wszystkie zamówienia z bazy.
     *
     * @return array Lista zamówień.
     */
    function kv_get_orders() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vectis_orders';

        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);
        return $results;
    }
}
