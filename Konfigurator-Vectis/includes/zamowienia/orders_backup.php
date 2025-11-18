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

        error_log("kv_create_orders_table: Tworzenie/sprawdzanie tabeli: " . $table_name);

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            order_data text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Załadowanie funkcji dbDelta() do obsługi aktualizacji bazy danych
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        error_log("kv_create_orders_table: Wynik dbDelta: " . print_r($result, true));
        
        // Sprawdź czy tabela istnieje
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ($table_exists) {
            error_log("kv_create_orders_table: Tabela $table_name istnieje");
        } else {
            error_log("kv_create_orders_table: BŁĄD - Tabela $table_name nie została utworzona!");
        }
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

if ( ! function_exists('kv_save_configurator_order') ) {
    /**
     * Zapisuje zamówienie z konfiguratora do bazy danych.
     *
     * @param array $order_data Dane zamówienia z sesji konfiguratora
     * @return int|false ID zapisanego zamówienia lub false w przypadku błędu
     */
    function kv_save_configurator_order($order_data) {
        global $wpdb;
        
        error_log("kv_save_configurator_order: Rozpoczynam zapisywanie zamówienia");
        
        // Upewnij się, że tabela istnieje
        kv_create_orders_table();
        
        $table_name = $wpdb->prefix . 'vectis_orders';
        error_log("kv_save_configurator_order: Nazwa tabeli: " . $table_name);
        
        // Generuj unikalny numer zamówienia
        $order_number = 'KV-' . date('Y') . '-' . sprintf('%06d', wp_rand(1, 999999));
        error_log("kv_save_configurator_order: Wygenerowany numer zamówienia: " . $order_number);
        
        // Przygotuj dane do zapisu
        $serialized_data = maybe_serialize($order_data);
        error_log("kv_save_configurator_order: Długość serializowanych danych: " . strlen($serialized_data));
        
        // Zapisz zamówienie do bazy
        $result = $wpdb->insert(
            $table_name,
            array(
                'order_number' => $order_number,
                'order_data' => $serialized_data,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('kv_save_configurator_order: Błąd zapisywania zamówienia do bazy: ' . $wpdb->last_error);
            error_log('kv_save_configurator_order: Próbowałem wykonać: ' . $wpdb->last_query);
            return false;
        }
        
        $insert_id = $wpdb->insert_id;
        error_log("kv_save_configurator_order: Zamówienie zapisane pomyślnie z ID: " . $insert_id);
        
        return $insert_id;
    }
}

if ( ! function_exists('kv_update_existing_order') ) {
    /**
     * Aktualizuje istniejące zamówienie w bazie danych
     */
    function kv_update_existing_order($order_id, $order_data) {
        global $wpdb;
        
        error_log("kv_update_existing_order: Rozpoczynam aktualizację zamówienia ID: " . $order_id);
        
        $table_name = $wpdb->prefix . 'vectis_orders';
        
        // Przygotuj dane do zapisu
        $serialized_data = maybe_serialize($order_data);
        error_log("kv_update_existing_order: Długość serializowanych danych: " . strlen($serialized_data));
        
        // Zaktualizuj zamówienie w bazie
        $result = $wpdb->update(
            $table_name,
            array(
                'order_data' => $serialized_data,
                'created_at' => current_time('mysql') // Zaktualizuj datę modyfikacji
            ),
            array('id' => $order_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            error_log('kv_update_existing_order: Błąd aktualizacji zamówienia w bazie: ' . $wpdb->last_error);
            error_log('kv_update_existing_order: Próbowałem wykonać: ' . $wpdb->last_query);
            return false;
        }
        
        error_log("kv_update_existing_order: Zamówienie zaktualizowane pomyślnie, ID: " . $order_id . ", liczba zmienionych wierszy: " . $result);
        
        return $order_id;
    }
}

if ( ! function_exists('kv_delete_order') ) {
    /**
     * Usuwa zamówienie z bazy danych
     */
    function kv_delete_order($order_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vectis_orders';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $order_id),
            array('%d')
        );
        
        return $result !== false;
    }
}

if ( ! function_exists('kv_duplicate_order') ) {
    /**
     * Duplikuje zamówienie
     */
    function kv_duplicate_order($order_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vectis_orders';
        
        // Pobierz oryginalne zamówienie
        $original_order = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id),
            ARRAY_A
        );
        
        if (!$original_order) {
            return false;
        }
        
        // Wygeneruj nowy numer zamówienia
        $new_order_number = 'KV-' . date('Y') . '-' . sprintf('%06d', wp_rand(1, 999999));
        
        // Wstaw zduplikowane zamówienie
        $result = $wpdb->insert(
            $table_name,
            array(
                'order_number' => $new_order_number,
                'order_data' => $original_order['order_data'],
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
}

if ( ! function_exists('kv_get_order_for_edit') ) {
    /**
     * Pobiera zamówienie do edycji
     */
    function kv_get_order_for_edit($order_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vectis_orders';
        
        $order = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id),
            ARRAY_A
        );
        
        if (!$order) {
            return false;
        }
        
        return array(
            'id' => $order['id'],
            'order_number' => $order['order_number'],
            'order_data' => maybe_unserialize($order['order_data']),
            'created_at' => $order['created_at']
        );
    }
}

if ( ! function_exists('kv_update_order_details') ) {
    /**
     * Aktualizuje podstawowe dane zamówienia
     */
    function kv_update_order_details($order_id, $order_number, $customer_order_number, $order_notes) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vectis_orders';
        
        // Pobierz aktualne dane zamówienia
        $current_order = $wpdb->get_row(
            $wpdb->prepare("SELECT order_data FROM $table_name WHERE id = %d", $order_id),
            ARRAY_A
        );
        
        if (!$current_order) {
            return false;
        }
        
        // Zaktualizuj dane w strukturze zamówienia
        $order_data = maybe_unserialize($current_order['order_data']);
        $order_data['customer_order_number'] = $customer_order_number;
        $order_data['order_notes'] = $order_notes;
        
        // Zapisz z powrotem do bazy
        return $wpdb->update(
            $table_name,
            array(
                'order_number' => $order_number,
                'order_data' => maybe_serialize($order_data)
            ),
            array('id' => $order_id),
            array('%s', '%s'),
            array('%d')
        );
    }
}
