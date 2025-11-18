<?php
defined('ABSPATH') or die('Brak dostępu');

/**
 * Shortcode do wyświetlania zamówień użytkownika na frontend
 */
function kv_moje_zamowienia_shortcode($atts) {
    // Sprawdź czy użytkownik jest zalogowany
    if (!is_user_logged_in()) {
        return '<div class="kv-notice kv-notice-error">
            <p>Musisz być zalogowany, aby zobaczyć swoje zamówienia. <a href="' . wp_login_url() . '">Zaloguj się</a></p>
        </div>';
    }
    
    $user_id = get_current_user_id();
    $user_role = kv_get_user_configurator_role($user_id);
    
    // Obsługa akcji
    if (isset($_POST['kv_action']) && wp_verify_nonce($_POST['kv_frontend_nonce'], 'kv_frontend_action')) {
        $order_id = intval($_POST['order_id']);
        
        // Sprawdź uprawnienia w zależności od roli
        $order = kv_get_order_by_id($order_id);
        if (!$order) {
            return '<div class="kv-notice kv-notice-error"><p>Zamówienie nie istnieje.</p></div>';
        }
        
        // Sprawdź uprawnienia w zależności od roli
        $has_permission = false;
        switch ($user_role) {
            case 'administrator':
                $has_permission = true; // Administrator ma dostęp do wszystkiego
                break;
            case 'handlowiec':
                $has_permission = kv_user_can('kv_manage_client_orders'); // Handlowiec może zarządzać zamówieniami klientów
                break;
            case 'biuro':
                $has_permission = kv_user_can('kv_process_orders'); // Biuro może przetwarzać zamówienia
                break;
            case 'klient':
            default:
                $has_permission = ($order->user_id == $user_id); // Klient tylko swoje zamówienia
                break;
        }
        
        if (!$has_permission) {
            return '<div class="kv-notice kv-notice-error"><p>Nie masz uprawnień do tej akcji.</p></div>';
        }
        
        switch ($_POST['kv_action']) {
            case 'cancel_order':
                if (kv_cancel_order($order_id)) {
                    echo '<div class="kv-notice kv-notice-success"><p>Zamówienie zostało anulowane.</p></div>';
                } else {
                    echo '<div class="kv-notice kv-notice-error"><p>Błąd podczas anulowania zamówienia.</p></div>';
                }
                break;
                
            case 'edit_order':
                // Przekieruj do konfiguratora z ID zamówienia do edycji
                $current_page_url = get_permalink();
                $redirect_url = add_query_arg(array(
                    'edit_order' => $order_id,
                    'return_url' => urlencode($current_page_url)
                ), site_url('/konfigurator/'));
                
                wp_redirect($redirect_url);
                exit;
                break;
        }
    }
    
    ob_start();
    ?>
    
    <div class="kv-frontend-orders">
        <h2>Moje zamówienia 
            <small>(<?php echo esc_html(kv_get_role_display_name($user_role)); ?>)</small>
        </h2>
        
        <?php
        // Pobierz zamówienia w zależności od roli użytkownika
        switch ($user_role) {
            case 'administrator':
                // Administrator widzi wszystkie zamówienia
                $orders = kv_get_orders();
                break;
            case 'handlowiec':
                // Handlowiec widzi zamówienia swoich klientów (można rozszerzyć o przypisanie klientów)
                $orders = kv_get_orders(); // Na razie wszystkie, można dodać filtrowanie
                break;
            case 'biuro':
                // Biuro widzi zamówienia do przetworzenia (submitted, processing)
                $orders = kv_get_orders_by_status(['submitted', 'processing']);
                break;
            case 'klient':
            default:
                // Klient widzi tylko swoje zamówienia
                $orders = kv_get_user_orders($user_id);
                break;
        }
        
        if (empty($orders)) {
            if ($user_role === 'klient') {
                echo '<p>Nie masz jeszcze żadnych zamówień. <a href="' . site_url('/konfigurator/') . '">Rozpocznij konfigurację</a></p>';
            } else {
                echo '<p>Brak zamówień do wyświetlenia.</p>';
            }
        } else {
            ?>
            <div class="kv-orders-table-container">
                <table class="kv-orders-table">
                    <thead>
                        <tr>
                            <th>Nr zamówienia</th>
                            <th>Nr zamówienia klienta</th>
                            <th>Data utworzenia</th>
                            <th>Status</th>
                            <th>Szczegóły</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo esc_html($order->order_number); ?></td>
                                <td><?php echo esc_html($order->customer_order_number ?: '-'); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order->created_at)); ?></td>
                                <td>
                                    <span class="kv-status kv-status-<?php echo esc_attr($order->status); ?>">
                                        <?php echo kv_get_status_label($order->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="kv-btn kv-btn-secondary" onclick="toggleOrderDetails(<?php echo $order->id; ?>)">
                                        Pokaż szczegóły
                                    </button>
                                </td>
                                <td>
                                    <div class="kv-actions">
                                        <?php if ($order->status === 'draft'): ?>
                                            <form method="post" style="display: inline;">
                                                <?php wp_nonce_field('kv_frontend_action', 'kv_frontend_nonce'); ?>
                                                <input type="hidden" name="kv_action" value="edit_order">
                                                <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                                                <button type="submit" class="kv-btn kv-btn-primary">Edytuj</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($order->status, ['draft', 'submitted'])): ?>
                                            <form method="post" style="display: inline;" onsubmit="return confirm('Czy na pewno chcesz anulować to zamówienie?')">
                                                <?php wp_nonce_field('kv_frontend_action', 'kv_frontend_nonce'); ?>
                                                <input type="hidden" name="kv_action" value="cancel_order">
                                                <input type="hidden" name="order_id" value="<?php echo $order->id; ?>">
                                                <button type="submit" class="kv-btn kv-btn-danger">Anuluj</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <tr id="order-details-<?php echo $order->id; ?>" class="kv-order-details-row" style="display: none;">
                                <td colspan="6">
                                    <div class="kv-order-details">
                                        <?php kv_display_order_details_frontend($order); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="kv-new-order-section">
                <a href="<?php echo site_url('/konfigurator/?new_order=1'); ?>" class="kv-btn kv-btn-primary kv-btn-large">
                    Utwórz nowe zamówienie
                </a>
            </div>
            <?php
        }
        ?>
    </div>
    
    <style>
    .kv-frontend-orders {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .kv-orders-table-container {
        overflow-x: auto;
        margin: 20px 0;
    }
    
    .kv-orders-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .kv-orders-table th,
    .kv-orders-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .kv-orders-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .kv-orders-table tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .kv-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .kv-status-draft {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .kv-status-submitted {
        background-color: #d4edda;
        color: #155724;
    }
    
    .kv-status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .kv-status-completed {
        background-color: #cce5ff;
        color: #004085;
    }
    
    .kv-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        margin: 2px;
        transition: background-color 0.2s;
    }
    
    .kv-btn-primary {
        background-color: #007cba;
        color: white;
    }
    
    .kv-btn-primary:hover {
        background-color: #005a87;
    }
    
    .kv-btn-secondary {
        background-color: #6c757d;
        color: white;
    }
    
    .kv-btn-secondary:hover {
        background-color: #545b62;
    }
    
    .kv-btn-danger {
        background-color: #dc3545;
        color: white;
    }
    
    .kv-btn-danger:hover {
        background-color: #c82333;
    }
    
    .kv-btn-large {
        padding: 12px 24px;
        font-size: 16px;
    }
    
    .kv-actions {
        white-space: nowrap;
    }
    
    .kv-order-details {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 4px;
        margin: 10px 0;
    }
    
    .kv-notice {
        padding: 12px;
        margin: 20px 0;
        border-radius: 4px;
    }
    
    .kv-notice-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .kv-notice-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .kv-new-order-section {
        text-align: center;
        margin-top: 30px;
        padding: 30px;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
    
    @media (max-width: 768px) {
        .kv-orders-table {
            font-size: 12px;
        }
        
        .kv-btn {
            padding: 6px 12px;
            font-size: 12px;
        }
    }
    </style>
    
    <script>
    function toggleOrderDetails(orderId) {
        var row = document.getElementById('order-details-' + orderId);
        var button = event.target;
        
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
            button.textContent = 'Ukryj szczegóły';
        } else {
            row.style.display = 'none';
            button.textContent = 'Pokaż szczegóły';
        }
    }
    </script>
    
    <?php
    return ob_get_clean();
}

/**
 * Pobiera zamówienia konkretnego użytkownika
 */
function kv_get_user_orders($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vectis_orders';
    
    $orders = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
        $user_id
    ));
    
    return $orders ? $orders : array();
}

/**
 * Pobiera pojedyncze zamówienie po ID
 */
function kv_get_order_by_id($order_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vectis_orders';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $order_id
    ));
}

/**
 * Anuluje zamówienie
 */
function kv_cancel_order($order_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vectis_orders';
    
    $result = $wpdb->update(
        $table_name,
        array('status' => 'cancelled'),
        array('id' => $order_id),
        array('%s'),
        array('%d')
    );
    
    return $result !== false;
}

/**
 * Zwraca etykietę statusu
 */
function kv_get_status_label($status) {
    $labels = array(
        'draft' => 'Wersja robocza',
        'submitted' => 'Wysłane',
        'cancelled' => 'Anulowane',
        'completed' => 'Zakończone'
    );
    
    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
}

/**
 * Wyświetla szczegóły zamówienia na frontend
 */
function kv_display_order_details_frontend($order) {
    $order_data = json_decode($order->order_data, true);
    
    if (empty($order_data)) {
        echo '<p>Brak szczegółów zamówienia.</p>';
        return;
    }
    
    // Pobierz opcje z bazy danych
    $seria_options = get_option('kv_seria_options', array());
    $ksztalt_options = get_option('kv_ksztalt_options', array());
    $uklad_options = get_option('kv_uklad_options', array());
    $kolor_ramki_options = get_option('kv_kolor_ramki_options', array());
    $mechanizm_options = get_option('kv_mechanizm_options', array());
    $technologia_options = get_option('kv_technologia_options', array());
    $kolor_mechanizmu_options = get_option('kv_kolor_mechanizmu_options', array());
    
    echo '<div class="order-details-frontend">';
    
    if (!empty($order->order_notes)) {
        echo '<div class="order-notes">';
        echo '<h4>Notatki do zamówienia:</h4>';
        echo '<p>' . esc_html($order->order_notes) . '</p>';
        echo '</div>';
    }
    
    if (isset($order_data['items']) && is_array($order_data['items'])) {
        echo '<h4>Pozycje zamówienia (' . count($order_data['items']) . '):</h4>';
        
        foreach ($order_data['items'] as $index => $item) {
            echo '<div class="order-item-frontend">';
            echo '<h5>Pozycja ' . ($index + 1) . '</h5>';
            
            // Kod produktu
            $product_code = kv_generate_product_code_frontend($item, $seria_options, $ksztalt_options, $uklad_options, $kolor_ramki_options, $mechanizm_options);
            if ($product_code) {
                echo '<div class="product-code-frontend"><strong>Kod produktu:</strong> ' . esc_html($product_code) . '</div>';
            }
            
            echo '<div class="order-detail-grid">';
            
            // Seria
            if (isset($item['seria']) && isset($seria_options[$item['seria']])) {
                echo '<div class="detail-item"><span class="label">Seria:</span> ' . esc_html($seria_options[$item['seria']]['name']) . '</div>';
            }
            
            // Kształt
            if (isset($item['ksztalt']) && isset($ksztalt_options[$item['ksztalt']])) {
                echo '<div class="detail-item"><span class="label">Kształt:</span> ' . esc_html($ksztalt_options[$item['ksztalt']]['name']) . '</div>';
            }
            
            // Układ
            if (isset($item['uklad']) && isset($uklad_options[$item['uklad']])) {
                echo '<div class="detail-item"><span class="label">Układ:</span> ' . esc_html($uklad_options[$item['uklad']]['name']) . '</div>';
            }
            
            // Kolor ramki
            if (isset($item['kolor_ramki']) && isset($kolor_ramki_options[$item['kolor_ramki']])) {
                echo '<div class="detail-item"><span class="label">Kolor ramki:</span> ' . esc_html($kolor_ramki_options[$item['kolor_ramki']]['name']) . '</div>';
            }
            
            // Mechanizmy
            if (isset($item['mechanizmy']) && is_array($item['mechanizmy'])) {
                echo '<div class="mechanisms-section">';
                echo '<h6>Mechanizmy:</h6>';
                
                foreach ($item['mechanizmy'] as $slot_index => $slot_data) {
                    if (empty($slot_data)) continue;
                    
                    echo '<div class="mechanism-slot">';
                    echo '<strong>Pozycja ' . ($slot_index + 1) . ':</strong><br>';
                    
                    if (isset($slot_data['mechanizm_id']) && isset($mechanizm_options[$slot_data['mechanizm_id']])) {
                        echo 'Mechanizm: ' . esc_html($mechanizm_options[$slot_data['mechanizm_id']]['name']) . '<br>';
                    }
                    
                    if (isset($slot_data['technologia_id']) && isset($technologia_options[$slot_data['technologia_id']])) {
                        echo 'Technologia: ' . esc_html($technologia_options[$slot_data['technologia_id']]['name']) . '<br>';
                    }
                    
                    if (isset($slot_data['kolor_mechanizmu_id']) && isset($kolor_mechanizmu_options[$slot_data['kolor_mechanizmu_id']])) {
                        echo 'Kolor: ' . esc_html($kolor_mechanizmu_options[$slot_data['kolor_mechanizmu_id']]['name']) . '<br>';
                    }
                    
                    echo '</div>';
                }
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
    }
    
    echo '</div>';
    
    // Style dla szczegółów
    echo '<style>
    .order-details-frontend {
        font-size: 14px;
        line-height: 1.5;
    }
    
    .order-item-frontend {
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .order-item-frontend h5 {
        margin: 0 0 10px 0;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }
    
    .order-item-frontend h6 {
        margin: 10px 0 5px 0;
        color: #555;
    }
    
    .product-code-frontend {
        background: #e3f2fd;
        padding: 8px;
        border-radius: 4px;
        margin-bottom: 10px;
        font-family: monospace;
    }
    
    .order-detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
    }
    
    .detail-item {
        padding: 5px 0;
    }
    
    .detail-item .label {
        font-weight: 600;
        color: #555;
    }
    
    .mechanisms-section {
        grid-column: 1 / -1;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }
    
    .mechanism-slot {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 8px;
    }
    
    .order-notes {
        background: #fff3cd;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
    }
    
    .order-notes h4 {
        margin: 0 0 5px 0;
        color: #856404;
    }
    
    .order-notes p {
        margin: 0;
        color: #856404;
    }
    </style>';
}

/**
 * Generuje kod produktu dla frontend
 */
function kv_generate_product_code_frontend($item, $seria_options, $ksztalt_options, $uklad_options, $kolor_ramki_options, $mechanizm_options) {
    // Implementacja analogiczna do kv_generate_product_code_admin
    $code_parts = array();
    
    // Seria
    if (isset($item['seria']) && isset($seria_options[$item['seria']])) {
        $code_parts[] = $seria_options[$item['seria']]['code'] ?? '';
    }
    
    // Kształt
    if (isset($item['ksztalt']) && isset($ksztalt_options[$item['ksztalt']])) {
        $code_parts[] = $ksztalt_options[$item['ksztalt']]['code'] ?? '';
    }
    
    // Układ
    if (isset($item['uklad']) && isset($uklad_options[$item['uklad']])) {
        $code_parts[] = $uklad_options[$item['uklad']]['code'] ?? '';
    }
    
    // Kolor ramki
    if (isset($item['kolor_ramki']) && isset($kolor_ramki_options[$item['kolor_ramki']])) {
        $code_parts[] = $kolor_ramki_options[$item['kolor_ramki']]['code'] ?? '';
    }
    
    // Mechanizmy
    if (isset($item['mechanizmy']) && is_array($item['mechanizmy'])) {
        foreach ($item['mechanizmy'] as $slot_data) {
            if (isset($slot_data['mechanizm_id']) && isset($mechanizm_options[$slot_data['mechanizm_id']])) {
                $code_parts[] = $mechanizm_options[$slot_data['mechanizm_id']]['code'] ?? '';
            }
        }
    }
    
    return implode('-', array_filter($code_parts));
}

// Rejestracja shortcode
add_shortcode('moje-zamowienia', 'kv_moje_zamowienia_shortcode');
