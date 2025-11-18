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
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            order_number varchar(50) NOT NULL,
            customer_order_number varchar(100),
            order_data text NOT NULL,
            order_notes text,
            status varchar(20) DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX user_id_idx (user_id),
            INDEX status_idx (status)
        ) $charset_collate;";

        // Załadowanie funkcji dbDelta() do obsługi aktualizacji bazy danych
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        error_log("kv_create_orders_table: Wynik dbDelta: " . print_r($result, true));
        
        // Sprawdź czy tabela istnieje
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ($table_exists) {
            error_log("kv_create_orders_table: Tabela $table_name istnieje");
            
            // Sprawdź czy nowe kolumny istnieją i dodaj je jeśli nie
            kv_update_orders_table_structure();
        } else {
            error_log("kv_create_orders_table: BŁĄD - Tabela $table_name nie została utworzona!");
        }
    }
}

if ( ! function_exists('kv_update_orders_table_structure') ) {
    /**
     * Aktualizuje strukturę tabeli zamówień, dodając nowe kolumny jeśli nie istnieją
     */
    function kv_update_orders_table_structure() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vectis_orders';
        
        // Sprawdź które kolumny istnieją
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $existing_columns = array();
        foreach ($columns as $column) {
            $existing_columns[] = $column->Field;
        }
        
        // Dodaj brakujące kolumny
        $new_columns = array(
            'user_id' => "ALTER TABLE $table_name ADD COLUMN user_id bigint(20) unsigned NOT NULL DEFAULT 0",
            'customer_order_number' => "ALTER TABLE $table_name ADD COLUMN customer_order_number varchar(100)",
            'order_notes' => "ALTER TABLE $table_name ADD COLUMN order_notes text",
            'status' => "ALTER TABLE $table_name ADD COLUMN status varchar(20) DEFAULT 'draft'",
            'updated_at' => "ALTER TABLE $table_name ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        );
        
        foreach ($new_columns as $column_name => $sql) {
            if (!in_array($column_name, $existing_columns)) {
                $wpdb->query($sql);
                error_log("kv_update_orders_table_structure: Dodano kolumnę $column_name");
            }
        }
        
        // Dodaj indeksy jeśli nie istnieją
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
        $existing_indexes = array();
        foreach ($indexes as $index) {
            $existing_indexes[] = $index->Key_name;
        }
        
        if (!in_array('user_id_idx', $existing_indexes)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX user_id_idx (user_id)");
            error_log("kv_update_orders_table_structure: Dodano indeks user_id_idx");
        }
        
        if (!in_array('status_idx', $existing_indexes)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX status_idx (status)");
            error_log("kv_update_orders_table_structure: Dodano indeks status_idx");
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

if ( ! function_exists('kv_get_orders_by_status') ) {
    /**
     * Pobiera zamówienia o określonym statusie z bazy.
     *
     * @param array $statuses Lista statusów do pobrania
     * @return array Lista zamówień o określonym statusie.
     */
    function kv_get_orders_by_status($statuses = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vectis_orders';

        if (empty($statuses)) {
            return array();
        }

        // Przygotuj placeholdery dla IN clause
        $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE status IN ($placeholders) ORDER BY created_at DESC",
            $statuses
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
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
        
        // Pobierz ID aktualnie zalogowanego użytkownika (0 jeśli niezalogowany)
        $user_id = get_current_user_id();
        
        // Pobierz dodatkowe dane z order_data jeśli istnieją
        $customer_order_number = isset($order_data['customer_order_number']) ? $order_data['customer_order_number'] : '';
        $order_notes = isset($order_data['order_notes']) ? $order_data['order_notes'] : '';
        $status = isset($order_data['status']) ? $order_data['status'] : 'draft';
        
        // Zapisz zamówienie do bazy
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'order_number' => $order_number,
                'customer_order_number' => $customer_order_number,
                'order_data' => $serialized_data,
                'order_notes' => $order_notes,
                'status' => $status,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('kv_save_configurator_order: Błąd zapisywania zamówienia do bazy: ' . $wpdb->last_error);
            error_log('kv_save_configurator_order: Próbowałem wykonać: ' . $wpdb->last_query);
            return false;
        }
        
        $order_id = $wpdb->insert_id;
        error_log('kv_save_configurator_order: Zamówienie zapisane z ID: ' . $order_id);
        
        // Wyślij powiadomienia email
        if (function_exists('kv_send_new_order_notification')) {
            kv_send_new_order_notification($order_id, $order_data);
        }
        
        return $order_id;
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
        
        // Pobierz dodatkowe dane z order_data jeśli istnieją
        $customer_order_number = isset($order_data['customer_order_number']) ? $order_data['customer_order_number'] : '';
        $order_notes = isset($order_data['order_notes']) ? $order_data['order_notes'] : '';
        $status = isset($order_data['status']) ? $order_data['status'] : 'draft';
        
        // Zaktualizuj zamówienie w bazie
        $result = $wpdb->update(
            $table_name,
            array(
                'order_data' => $serialized_data,
                'customer_order_number' => $customer_order_number,
                'order_notes' => $order_notes,
                'status' => $status,
                'updated_at' => current_time('mysql') // Zaktualizuj datę modyfikacji
            ),
            array('id' => $order_id),
            array('%s', '%s', '%s', '%s', '%s'),
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
        
        // Sprawdź uprawnienia - użytkownik może edytować tylko swoje zamówienia (chyba że jest adminem)
        if (!current_user_can('manage_options') && is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            if (isset($order['user_id']) && intval($order['user_id']) !== $current_user_id) {
                return false; // Brak uprawnień
            }
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

if ( ! function_exists('kv_update_order_status_with_notification') ) {
    /**
     * Aktualizuje status zamówienia i wysyła powiadomienie
     */
    function kv_update_order_status_with_notification($order_id, $new_status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vectis_orders';
        
        // Pobierz obecny status
        $current_order = $wpdb->get_row(
            $wpdb->prepare("SELECT status FROM $table_name WHERE id = %d", $order_id)
        );
        
        if (!$current_order) {
            return false;
        }
        
        $old_status = $current_order->status;
        
        // Aktualizuj status
        $result = $wpdb->update(
            $table_name,
            array('status' => $new_status),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false && $old_status !== $new_status) {
            // Wyślij powiadomienie o zmianie statusu
            if (function_exists('kv_send_order_status_notification')) {
                kv_send_order_status_notification($order_id, $old_status, $new_status);
            }
        }
        
        return $result;
    }
}
