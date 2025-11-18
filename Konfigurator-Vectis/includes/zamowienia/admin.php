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
        // Obsługa akcji
        if (isset($_GET['action']) && isset($_GET['order_id'])) {
            $order_id = intval($_GET['order_id']);
            $action = sanitize_text_field($_GET['action']);
            
            switch ($action) {
                case 'delete':
                    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_order_' . $order_id)) {
                        kv_delete_order($order_id);
                        echo '<div class="notice notice-success is-dismissible"><p>Zamówienie zostało usunięte.</p></div>';
                    }
                    break;
                    
                case 'duplicate':
                    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'duplicate_order_' . $order_id)) {
                        $new_order_id = kv_duplicate_order($order_id);
                        if ($new_order_id) {
                            echo '<div class="notice notice-success is-dismissible"><p>Zamówienie zostało zduplikowane. Nowe ID: ' . $new_order_id . '</p></div>';
                        } else {
                            echo '<div class="notice notice-error is-dismissible"><p>Błąd podczas duplikowania zamówienia.</p></div>';
                        }
                    }
                    break;
            }
        }
        
        // Obsługa edycji zamówienia
        if (isset($_POST['edit_order']) && wp_verify_nonce($_POST['order_edit_nonce'], 'edit_order')) {
            $order_id = intval($_POST['order_id']);
            $new_order_number = sanitize_text_field($_POST['order_number']);
            $new_customer_number = sanitize_text_field($_POST['customer_order_number']);
            $new_order_notes = sanitize_textarea_field($_POST['order_notes']);
            
            if (kv_update_order_details($order_id, $new_order_number, $new_customer_number, $new_order_notes)) {
                echo '<div class="notice notice-success is-dismissible"><p>Dane zamówienia zostały zaktualizowane.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Błąd podczas aktualizacji zamówienia.</p></div>';
            }
        }
        
        echo '<div class="wrap">';
        echo '<h1>Panel administracyjny Konfiguratora Vectis</h1>';
        echo '<p>Tutaj możesz zarządzać zamówieniami z konfiguratora.</p>';
        
        // Sprawdź czy funkcja do pobierania zamówień istnieje
        if ( function_exists('kv_get_orders') ) {
            $orders = kv_get_orders();
            if ( ! empty( $orders ) ) {
                echo '<h2>Lista zamówień (' . count($orders) . ')</h2>';
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr>';
                echo '<th style="width: 60px;">ID</th>';
                echo '<th style="width: 120px;">Numer zamówienia</th>';
                echo '<th style="width: 100px;">Data utworzenia</th>';
                echo '<th style="width: 80px;">Status</th>';
                echo '<th style="width: 120px;">Numer klienta</th>';
                echo '<th style="width: 150px;">Uwagi</th>';
                echo '<th>Szczegóły zamówienia</th>';
                echo '<th style="width: 200px;">Akcje</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                
                foreach ( $orders as $order ) {
                    $order_data = maybe_unserialize( $order['order_data'] );
                    echo '<tr>';
                    echo '<td>' . esc_html( $order['id'] ) . '</td>';
                    echo '<td><strong>' . esc_html( $order['order_number'] ) . '</strong></td>';
                    echo '<td>' . esc_html( date('d.m.Y H:i', strtotime($order['created_at'])) ) . '</td>';
                    
                    // Status zamówienia
                    $status = isset($order['status']) ? $order['status'] : 'draft';
                    $status_label = kv_get_status_label($status);
                    $status_class = 'status-' . $status;
                    echo '<td><span class="' . $status_class . '">' . esc_html($status_label) . '</span></td>';
                    
                    // Numer zamówienia klienta
                    $customer_order_number = isset($order['customer_order_number']) ? $order['customer_order_number'] : 
                        (isset($order_data['customer_order_number']) ? $order_data['customer_order_number'] : '-');
                    echo '<td>' . esc_html($customer_order_number) . '</td>';
                    
                    // Uwagi - sprawdź najpierw w kolumnie order_notes, potem w order_data
                    $order_notes = isset($order['order_notes']) ? $order['order_notes'] : 
                        (isset($order_data['order_notes']) ? $order_data['order_notes'] : '-');
                    echo '<td title="' . esc_attr($order_notes) . '">' . 
                         esc_html(strlen($order_notes) > 50 ? substr($order_notes, 0, 50) . '...' : $order_notes) . '</td>';
                    
                    // Szczegóły zamówienia
                    echo '<td>';
                    if ( is_array( $order_data ) ) {
                        kv_display_order_details($order_data);
                    } else {
                        echo esc_html( $order_data );
                    }
                    echo '</td>';
                    
                    // Kolumna akcji
                    echo '<td>';
                    echo '<div class="order-actions">';
                    
                    // Link edycji dla handlowców
                    $edit_url = home_url('/konfigurator/?edit_order=' . $order['id'] . '&step=5');
                    echo '<div class="action-button edit-config"><a href="' . esc_url($edit_url) . '" target="_blank" title="Otwórz konfigurator z tym zamówieniem" class="button button-small">🔧 Edytuj w konfiguratorze</a></div>';
                    
                    // Przycisk edycji podstawowych danych
                    echo '<div class="action-button edit-data"><a href="#" onclick="openEditModal(' . $order['id'] . ', \'' . esc_js($order['order_number']) . '\', \'' . esc_js($customer_order_number) . '\', \'' . esc_js($order_notes) . '\')" title="Edytuj podstawowe dane zamówienia" class="button button-small">📝 Edytuj dane</a></div>';
                    
                    // Przycisk duplikowania
                    $duplicate_url = add_query_arg([
                        'action' => 'duplicate',
                        'order_id' => $order['id'],
                        '_wpnonce' => wp_create_nonce('duplicate_order_' . $order['id'])
                    ]);
                    echo '<div class="action-button duplicate"><a href="' . esc_url($duplicate_url) . '" onclick="return confirm(\'Czy na pewno chcesz zduplikować to zamówienie?\')" title="Duplikuj zamówienie" class="button button-small">📋 Duplikuj</a></div>';
                    
                    // Przycisk usuwania
                    $delete_url = add_query_arg([
                        'action' => 'delete',
                        'order_id' => $order['id'],
                        '_wpnonce' => wp_create_nonce('delete_order_' . $order['id'])
                    ]);
                    echo '<div class="action-button delete"><a href="' . esc_url($delete_url) . '" onclick="return confirm(\'Czy na pewno chcesz usunąć to zamówienie? Ta akcja jest nieodwracalna.\')" title="Usuń zamówienie" class="button button-small delete-btn">🗑️ Usuń</a></div>';
                    
                    echo '</div>';
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<div class="notice notice-info"><p>Brak zamówień w systemie.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Funkcja pobierania zamówień nie jest dostępna.</p></div>';
        }
        
        echo '</div>';
        
        // Modal do edycji podstawowych danych zamówienia
        ?>
        <div id="edit-order-modal" style="display: none;">
            <div class="edit-modal-content">
                <h3>Edytuj dane zamówienia</h3>
                <form method="post" id="edit-order-form">
                    <?php wp_nonce_field('edit_order', 'order_edit_nonce'); ?>
                    <input type="hidden" id="edit-order-id" name="order_id" value="">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="edit-order-number">Numer zamówienia:</label></th>
                            <td><input type="text" id="edit-order-number" name="order_number" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="edit-customer-number">Nazwa projektuw podsumo:</label></th>
                            <td><input type="text" id="edit-customer-number" name="customer_order_number" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="edit-order-notes">Uwagi:</label></th>
                            <td><textarea id="edit-order-notes" name="order_notes" rows="4" cols="50"></textarea></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="edit_order" class="button-primary" value="Zapisz zmiany">
                        <button type="button" class="button" onclick="closeEditModal()">Anuluj</button>
                    </p>
                </form>
            </div>
        </div>
        
        <script>
        function openEditModal(orderId, orderNumber, customerNumber, orderNotes) {
            document.getElementById('edit-order-id').value = orderId;
            document.getElementById('edit-order-number').value = orderNumber;
            document.getElementById('edit-customer-number').value = customerNumber;
            document.getElementById('edit-order-notes').value = orderNotes;
            
            // Pokaż modal
            var modal = document.getElementById('edit-order-modal');
            modal.style.display = 'block';
            modal.style.position = 'fixed';
            modal.style.top = '50%';
            modal.style.left = '50%';
            modal.style.transform = 'translate(-50%, -50%)';
            modal.style.zIndex = '999999';
            modal.style.background = 'white';
            modal.style.padding = '20px';
            modal.style.border = '1px solid #ccc';
            modal.style.borderRadius = '5px';
            modal.style.boxShadow = '0 4px 8px rgba(0,0,0,0.3)';
            modal.style.maxWidth = '600px';
            modal.style.width = '90%';
            
            // Dodaj overlay
            var overlay = document.createElement('div');
            overlay.id = 'modal-overlay';
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.background = 'rgba(0,0,0,0.5)';
            overlay.style.zIndex = '999998';
            overlay.onclick = closeEditModal;
            document.body.appendChild(overlay);
        }
        
        function closeEditModal() {
            document.getElementById('edit-order-modal').style.display = 'none';
            var overlay = document.getElementById('modal-overlay');
            if (overlay) {
                overlay.remove();
            }
        }
        </script>
        
        <style>
        .order-details {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }
        .order-item {
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 3px;
        }
        .order-item h4 {
            margin: 0 0 8px 0;
            color: #0073aa;
            font-size: 13px;
        }
        .order-detail-row {
            margin: 3px 0;
        }
        .order-detail-label {
            font-weight: bold;
            display: inline-block;
            min-width: 80px;
        }
        .product-code {
            background: #e7f3ff;
            padding: 2px 5px;
            border-radius: 2px;
            font-family: monospace;
            font-size: 11px;
            display: inline-block;
            margin-top: 5px;
        }
        .mechanism-details {
            margin-left: 15px;
            padding: 5px;
            background: #fafafa;
            border-left: 3px solid #0073aa;
            margin-top: 5px;
        }
        
        /* Style dla statusów zamówień */
        .status-draft {
            background-color: #fff3cd;
            color: #856404;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-submitted {
            background-color: #d4edda;
            color: #155724;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        /* Style dla przycisków akcji */
        .order-actions {
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin-top: 5px;
            min-width: 160px;
        }
        
        .action-button {
            display: block;
            width: 100%;
        }
        
        .action-button .button {
            width: 100%;
            text-align: center;
            font-size: 11px;
            padding: 4px 8px;
            border: 1px solid #ccc;
            background: #f7f7f7;
            color: #333;
            text-decoration: none;
            border-radius: 3px;
            display: block;
            box-sizing: border-box;
            transition: all 0.2s;
            line-height: 1.2;
        }
        
        .action-button .button:hover {
            background: #e6e6e6;
            border-color: #999;
            color: #000;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .action-button.edit-config .button {
            background: #0073aa;
            color: white;
            border-color: #005177;
        }
        
        .action-button.edit-config .button:hover {
            background: #005177;
            border-color: #003f5c;
        }
        
        .action-button.edit-data .button {
            background: #007cba;
            color: white;
            border-color: #005a87;
        }
        
        .action-button.edit-data .button:hover {
            background: #005a87;
            border-color: #004663;
        }
        
        .action-button.duplicate .button {
            background: #00a32a;
            color: white;
            border-color: #007a1f;
        }
        
        .action-button.duplicate .button:hover {
            background: #007a1f;
            border-color: #005b17;
        }
        
        .action-button.delete .button {
            background: #dc3232;
            color: white;
            border-color: #b32d2e;
        }
        
        .action-button.delete .button:hover {
            background: #b32d2e;
            border-color: #8a2424;
        }
        
        /* Modal style */
        .edit-modal-content h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        /* Responsywność dla tabeli */
        @media (max-width: 1200px) {
            .wp-list-table th,
            .wp-list-table td {
                padding: 8px 4px;
                font-size: 12px;
            }
            .order-details {
                max-height: 200px;
            }
        }
        </style>
        <?php
    }
}

if ( ! function_exists('kv_display_order_details') ) {
    /**
     * Wyświetla szczegóły zamówienia w czytelny sposób
     */
    function kv_display_order_details($order_data) {
        echo '<div class="order-details">';
        
        // Pobierz opcje z bazy
        $seria_options = get_option('kv_seria_options', []);
        $ksztalt_options = get_option('kv_ksztalt_options', []);
        $uklad_options = get_option('kv_uklad_options', []);
        $kolor_ramki_options = get_option('kv_kolor_ramki_options', []);
        $mechanizm_options = get_option('kv_mechanizm_options', []);
        $technologia_options = get_option('kv_technologia_options', []);
        $kolor_mechanizmu_options = get_option('kv_kolor_mechanizmu_options', []);
        
        // Wyświetl pozycje z zamówienia
        if (isset($order_data['items']) && is_array($order_data['items'])) {
            foreach ($order_data['items'] as $index => $item) {
                echo '<div class="order-item">';
                echo '<h4>Pozycja ' . ($index + 1) . '</h4>';
                
                // Podstawowe informacje
                if (isset($item['seria'])) {
                    echo '<div class="order-detail-row"><span class="order-detail-label">Seria:</span> ' . esc_html($item['seria']) . '</div>';
                }
                
                if (isset($item['ksztalt'])) {
                    $ksztalt_name = isset($ksztalt_options[$item['ksztalt']]['name']) ? $ksztalt_options[$item['ksztalt']]['name'] : 'ID: ' . $item['ksztalt'];
                    echo '<div class="order-detail-row"><span class="order-detail-label">Kształt:</span> ' . esc_html($ksztalt_name) . '</div>';
                }
                
                if (isset($item['uklad'])) {
                    $uklad_name = isset($uklad_options[$item['uklad']]['name']) ? $uklad_options[$item['uklad']]['name'] : 'ID: ' . $item['uklad'];
                    echo '<div class="order-detail-row"><span class="order-detail-label">Układ:</span> ' . esc_html($uklad_name) . '</div>';
                }
                
                if (isset($item['kolor_ramki'])) {
                    $ramka_name = isset($kolor_ramki_options[$item['kolor_ramki']]['name']) ? $kolor_ramki_options[$item['kolor_ramki']]['name'] : 'ID: ' . $item['kolor_ramki'];
                    echo '<div class="order-detail-row"><span class="order-detail-label">Kolor ramki:</span> ' . esc_html($ramka_name) . '</div>';
                }
                
                // Ilość jeśli dostępna
                if (isset($item['quantity'])) {
                    echo '<div class="order-detail-row"><span class="order-detail-label">Ilość:</span> ' . esc_html($item['quantity']) . '</div>';
                }
                
                // Mechanizmy
                $mechanism_found = false;
                for ($i = 0; $i < 10; $i++) { // Sprawdź do 10 slotów
                    if (isset($item['mechanizm_' . $i])) {
                        if (!$mechanism_found) {
                            echo '<div class="order-detail-row"><span class="order-detail-label">Mechanizmy:</span></div>';
                            $mechanism_found = true;
                        }
                        
                        echo '<div class="mechanism-details">';
                        echo '<strong>Slot ' . ($i + 1) . ':</strong><br>';
                        
                        $mech_id = $item['mechanizm_' . $i];
                        $mech_name = isset($mechanizm_options[$mech_id]['name']) ? $mechanizm_options[$mech_id]['name'] : 'ID: ' . $mech_id;
                        echo 'Mechanizm: ' . esc_html($mech_name) . '<br>';
                        
                        if (isset($item['technologia_' . $i])) {
                            $tech_id = $item['technologia_' . $i];
                            $tech_name = isset($technologia_options[$tech_id]['technology']) ? $technologia_options[$tech_id]['technology'] : 'ID: ' . $tech_id;
                            echo 'Technologia: ' . esc_html($tech_name) . '<br>';
                        }
                        
                        if (isset($item['kolor_mechanizmu_' . $i])) {
                            $color_id = $item['kolor_mechanizmu_' . $i];
                            $color_name = isset($kolor_mechanizmu_options[$color_id]['name']) ? $kolor_mechanizmu_options[$color_id]['name'] : 'ID: ' . $color_id;
                            echo 'Kolor mechanizmu: ' . esc_html($color_name) . '<br>';
                        }
                        
                        echo '</div>';
                    }
                }
                
                // Generuj kod produktu (uproszczony)
                if (isset($item['seria'], $item['ksztalt'], $item['uklad'])) {
                    $product_code = kv_generate_product_code_admin($item, $seria_options, $ksztalt_options, $uklad_options, $kolor_ramki_options, $mechanizm_options);
                    if ($product_code) {
                        echo '<div class="product-code">Kod produktu: ' . esc_html($product_code) . '</div>';
                    }
                }
                
                echo '</div>';
            }
        } else {
            echo '<div class="notice notice-warning inline"><p>Brak szczegółów pozycji w zamówieniu.</p></div>';
        }
        
        echo '</div>';
    }
}

if ( ! function_exists('kv_generate_product_code_admin') ) {
    /**
     * Generuje kod produktu dla potrzeb panelu administracyjnego
     */
    function kv_generate_product_code_admin($item, $seria_options, $ksztalt_options, $uklad_options, $kolor_ramki_options, $mechanizm_options) {
        // Kod serii
        $seria_code = 'IS';
        if (isset($item['seria'])) {
            foreach ($seria_options as $seria_option) {
                if (isset($seria_option['name']) && $seria_option['name'] === $item['seria'] && isset($seria_option['fragment'])) {
                    $seria_code = $seria_option['fragment'];
                    break;
                }
            }
        }
        
        // Kod kształtu  
        $ksztalt_code = '0';
        if (isset($item['ksztalt']) && isset($ksztalt_options[$item['ksztalt']]['snippet'])) {
            $ksztalt_code = $ksztalt_options[$item['ksztalt']]['snippet'];
        }
        
        // Kod układu
        $uklad_code = '00';
        if (isset($item['uklad'])) {
            $uklad_data = $uklad_options[$item['uklad']] ?? [];
            if (isset($uklad_data['code']) && !empty($uklad_data['code'])) {
                $uklad_code = $uklad_data['code'];
            } elseif (isset($uklad_data['snippet']) && !empty($uklad_data['snippet'])) {
                $uklad_code = $uklad_data['snippet'];
            }
        }
        
        // Kod koloru ramki
        $frame_color_code = '00';
        if (isset($item['kolor_ramki']) && isset($kolor_ramki_options[$item['kolor_ramki']])) {
            $color_data = $kolor_ramki_options[$item['kolor_ramki']];
            if (isset($color_data['code']) && !empty($color_data['code'])) {
                $frame_color_code = $color_data['code'];
            } elseif (isset($color_data['snippet']) && !empty($color_data['snippet'])) {
                $frame_color_code = $color_data['snippet'];
            }
        }
        
        // Kod mechanizmów
        $mech_code = '';
        for ($i = 0; $i < 10; $i++) {
            if (isset($item['mechanizm_' . $i])) {
                $mech_id = $item['mechanizm_' . $i];
                if (isset($mechanizm_options[$mech_id]['snippet'])) {
                    $mech_code .= $mechanizm_options[$mech_id]['snippet'];
                }
            }
        }
        
        $mech_code = str_pad($mech_code, 5, '0');
        $uklad_code = str_pad(substr($uklad_code, 0, 2), 2, '0');
        $frame_color_code = str_pad(substr($frame_color_code, 0, 2), 2, '0');
        
        return strtoupper($seria_code . $ksztalt_code . "0-" . $mech_code . "-" . $uklad_code . $frame_color_code);
    }
}
