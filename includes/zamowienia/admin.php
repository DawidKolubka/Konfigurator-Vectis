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
        // Sprawdź uprawnienia na podstawie ról
        $user_role = kv_get_user_configurator_role();
        
        if (!kv_user_has_role('biuro')) {
            echo '<div class="notice notice-error"><p>Nie masz uprawnień do tej strony.</p></div>';
            return;
        }
        
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
        echo '<h1>Panel administracyjny Konfiguratora Vectis 
            <small>(Twoja rola: ' . esc_html(kv_get_role_display_name($user_role)) . ')</small>
        </h1>';
        echo '<p>Tutaj możesz zarządzać zamówieniami z konfiguratora.</p>';
        
        // Sprawdź czy funkcja do pobierania zamówień istnieje
        if ( function_exists('kv_get_orders') ) {
            // Pobierz zamówienia w zależności od roli
            switch ($user_role) {
                case 'administrator':
                    $orders = kv_get_orders();
                    break;
                case 'handlowiec':
                    $orders = kv_get_orders(); // Można dodać filtrowanie dla konkretnych klientów
                    break;
                case 'biuro':
                    $orders = kv_get_orders_by_status(['submitted', 'processing', 'partially_completed', 'draft']);
                    break;
                default:
                    $orders = array();
                    break;
            }
            
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
                echo '<th style="width: 150px;">Komentarze</th>';
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
                    
                    // Status zamówienia z możliwością zmiany
                    $status = isset($order['status']) ? $order['status'] : 'draft';
                    $status_label = kv_get_status_label($status);
                    $status_class = 'status-' . $status;
                    
                    echo '<td>';
                    echo '<span class="' . $status_class . '">' . esc_html($status_label) . '</span>';
                    
                    // Dodaj dropdown do zmiany statusu dla uprawnnionych ról
                    if (kv_user_has_role('biuro')) {
                        echo '<br><select onchange="changeOrderStatus(' . $order['id'] . ', this.value)" style="margin-top: 5px; font-size: 11px;">';
                        echo '<option value="">-- Zmień status --</option>';
                        echo '<option value="draft"' . selected($status, 'draft', false) . '>Wersja robocza</option>';
                        echo '<option value="new"' . selected($status, 'new', false) . '>Nowe</option>';
                        echo '<option value="submitted"' . selected($status, 'submitted', false) . '>Wysłane</option>';
                        echo '<option value="processing"' . selected($status, 'processing', false) . '>W realizacji</option>';
                        echo '<option value="partially_completed"' . selected($status, 'partially_completed', false) . '>Częściowo zrealizowane</option>';
                        echo '<option value="completed"' . selected($status, 'completed', false) . '>Zrealizowane</option>';
                        echo '<option value="cancelled"' . selected($status, 'cancelled', false) . '>Niezrealizowane</option>';
                        echo '</select>';
                    }
                    echo '</td>';
                    
                    // Numer zamówienia klienta
                    $customer_order_number = isset($order['customer_order_number']) ? $order['customer_order_number'] : 
                        (isset($order_data['customer_order_number']) ? $order_data['customer_order_number'] : '-');
                    echo '<td>' . esc_html($customer_order_number) . '</td>';
                    
                    // Uwagi - sprawdź najpierw w kolumnie order_notes, potem w order_data
                    $order_notes = isset($order['order_notes']) ? $order['order_notes'] : 
                        (isset($order_data['order_notes']) ? $order_data['order_notes'] : '-');
                    echo '<td title="' . esc_attr($order_notes) . '">' . 
                         esc_html(strlen($order_notes) > 50 ? substr($order_notes, 0, 50) . '...' : $order_notes) . '</td>';
                    
                    // Komentarze
                    echo '<td>';
                    $comments_count = kv_get_order_comments_count($order['id']);
                    if ($comments_count > 0) {
                        echo '<button class="button button-small" onclick="toggleComments(' . $order['id'] . ')" style="font-size: 11px; padding: 2px 6px;">';
                        echo '💬 Pokaż (' . $comments_count . ')';
                        echo '</button>';
                        echo '<div id="comments-' . $order['id'] . '" class="order-comments-container" style="display: none; margin-top: 10px;">';
                        kv_display_order_comments($order['id']);
                        echo '</div>';
                    } else {
                        echo '<span style="color: #999; font-size: 11px;">Brak komentarzy</span>';
                    }
                    echo '</td>';
                    
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
                    
                    // Przycisk dodawania komentarza
                    echo '<div class="action-button add-comment"><a href="#" onclick="openCommentModal(' . $order['id'] . ')" title="Dodaj komentarz" class="button button-small">💬 Dodaj komentarz</a></div>';
                    
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
        
        <!-- Modal do dodawania komentarzy -->
        <div id="comment-modal" style="display: none;">
            <div class="comment-modal-content">
                <h3>Dodaj komentarz do zamówienia</h3>
                <form id="comment-form">
                    <input type="hidden" id="comment-order-id" name="order_id" value="">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="comment-text">Komentarz:</label></th>
                            <td><textarea id="comment-text" name="comment_text" rows="4" cols="50" required style="width: 100%;"></textarea></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" class="button-primary" onclick="saveComment()">Dodaj komentarz</button>
                        <button type="button" class="button" onclick="closeCommentModal()">Anuluj</button>
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
        
        function openCommentModal(orderId) {
            document.getElementById('comment-order-id').value = orderId;
            document.getElementById('comment-text').value = '';
            
            // Pokaż modal
            var modal = document.getElementById('comment-modal');
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
            overlay.id = 'modal-overlay-comment';
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.background = 'rgba(0,0,0,0.5)';
            overlay.style.zIndex = '999998';
            overlay.onclick = closeCommentModal;
            document.body.appendChild(overlay);
        }
        
        function closeCommentModal() {
            document.getElementById('comment-modal').style.display = 'none';
            var overlay = document.getElementById('modal-overlay-comment');
            if (overlay) {
                overlay.remove();
            }
        }
        
        function saveComment() {
            var orderId = document.getElementById('comment-order-id').value;
            var commentText = document.getElementById('comment-text').value;
            
            if (!commentText.trim()) {
                alert('Proszę wpisać treść komentarza.');
                return;
            }
            
            jQuery.post(ajaxurl, {
                action: 'kv_add_order_comment',
                order_id: orderId,
                comment_text: commentText,
                nonce: '<?php echo wp_create_nonce('kv_comment_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('✅ Komentarz został dodany!');
                    closeCommentModal();
                    location.reload(); // Odśwież stronę, aby zobaczyć nowy komentarz
                } else {
                    alert('❌ Błąd: ' + response.data);
                }
            }).fail(function() {
                alert('❌ Błąd komunikacji z serwerem');
            });
        }
        
        function toggleComments(orderId) {
            var container = document.getElementById('comments-' + orderId);
            if (container.style.display === 'none') {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
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
        
        .status-new {
            background-color: #e0cffc;
            color: #6f42c1;
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
            background-color: #d4edda;
            color: #155724;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-processing {
            background-color: #fff3cd;
            color: #856404;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-partially_completed {
            background-color: #ffeaa7;
            color: #d63384;
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
        
        .comment-modal-content h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        /* Style dla przycisków komentarzy */
        .action-button.add-comment .button {
            background: #8e44ad;
            color: white;
            border-color: #6c3483;
        }
        
        .action-button.add-comment .button:hover {
            background: #6c3483;
            border-color: #512e62;
        }
        
        /* Style dla kontenera komentarzy */
        .order-comments-container {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .comment-item {
            padding: 10px;
            background: white;
            border-left: 3px solid #8e44ad;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        
        .comment-item:last-child {
            margin-bottom: 0;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 11px;
            color: #666;
        }
        
        .comment-author {
            font-weight: bold;
            color: #8e44ad;
        }
        
        .comment-date {
            font-style: italic;
        }
        
        .comment-text {
            font-size: 12px;
            line-height: 1.5;
            color: #333;
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

// Obsługa AJAX do zmiany statusu zamówienia
add_action('wp_ajax_kv_change_order_status', 'kv_change_order_status_ajax');
function kv_change_order_status_ajax() {
    // Sprawdź uprawnienia
    if (!kv_user_has_role('biuro')) {
        wp_die('Nie masz uprawnień do tej akcji');
    }
    
    // Sprawdź nonce
    if (!wp_verify_nonce($_POST['nonce'], 'kv_admin_nonce')) {
        wp_die('Nieprawidłowy token bezpieczeństwa');
    }
    
    $order_id = intval($_POST['order_id']);
    $new_status = sanitize_text_field($_POST['status']);
    
    // Walidacja statusu
    $allowed_statuses = array('draft', 'new', 'submitted', 'processing', 'partially_completed', 'completed', 'cancelled');
    if (!in_array($new_status, $allowed_statuses)) {
        wp_send_json_error('Nieprawidłowy status');
    }
    
    // Aktualizuj status z powiadomieniem
    if (function_exists('kv_update_order_status_with_notification')) {
        $result = kv_update_order_status_with_notification($order_id, $new_status);
    } else {
        // Fallback - zwykła aktualizacja bez powiadomienia
        global $wpdb;
        $table_name = $wpdb->prefix . 'vectis_orders';
        $result = $wpdb->update(
            $table_name,
            array('status' => $new_status),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );
    }
    
    if ($result !== false) {
        wp_send_json_success(array(
            'message' => 'Status zamówienia został zaktualizowany',
            'new_status' => $new_status,
            'new_status_label' => kv_get_status_label($new_status)
        ));
    } else {
        wp_send_json_error('Błąd podczas aktualizacji statusu');
    }
}

// JavaScript do obsługi zmiany statusu
add_action('admin_footer', 'kv_admin_status_change_script');
function kv_admin_status_change_script() {
    $current_screen = get_current_screen();
    if ($current_screen && $current_screen->id === 'toplevel_page_konfigurator-vectis') {
        ?>
        <script type="text/javascript">
        function changeOrderStatus(orderId, newStatus) {
            if (!newStatus) return; // Jeśli wybrano "-- Zmień status --"
            
            if (!confirm('Czy na pewno chcesz zmienić status zamówienia? Klient zostanie powiadomiony o zmianie.')) {
                // Reset selecta jeśli anulowano
                event.target.selectedIndex = 0;
                return;
            }
            
            jQuery.post(ajaxurl, {
                action: 'kv_change_order_status',
                order_id: orderId,
                status: newStatus,
                nonce: '<?php echo wp_create_nonce('kv_admin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('✅ ' + response.data.message);
                    location.reload(); // Odśwież stronę, aby zobaczyć zmiany
                } else {
                    alert('❌ Błąd: ' + response.data);
                    // Reset selecta
                    event.target.selectedIndex = 0;
                }
            }).fail(function() {
                alert('❌ Błąd komunikacji z serwerem');
                // Reset selecta
                event.target.selectedIndex = 0;
            });
        }
        </script>
        <?php
    }
}

// Handler AJAX do dodawania komentarzy
add_action('wp_ajax_kv_add_order_comment', 'kv_add_order_comment_ajax');
function kv_add_order_comment_ajax() {
    // Sprawdź uprawnienia
    if (!kv_user_has_role('biuro')) {
        wp_send_json_error('Nie masz uprawnień do tej akcji');
    }
    
    // Sprawdź nonce
    if (!wp_verify_nonce($_POST['nonce'], 'kv_comment_nonce')) {
        wp_send_json_error('Nieprawidłowy token bezpieczeństwa');
    }
    
    $order_id = intval($_POST['order_id']);
    $comment_text = sanitize_textarea_field($_POST['comment_text']);
    
    if (empty($comment_text)) {
        wp_send_json_error('Komentarz nie może być pusty');
    }
    
    $comment_id = kv_add_order_comment($order_id, $comment_text);
    
    if ($comment_id) {
        wp_send_json_success(array(
            'message' => 'Komentarz został dodany',
            'comment_id' => $comment_id
        ));
    } else {
        wp_send_json_error('Błąd podczas dodawania komentarza');
    }
}

if ( ! function_exists('kv_display_order_comments') ) {
    /**
     * Wyświetla komentarze dla zamówienia
     */
    function kv_display_order_comments($order_id) {
        $comments = kv_get_order_comments($order_id);
        
        if (empty($comments)) {
            echo '<p style="color: #999; font-style: italic;">Brak komentarzy</p>';
            return;
        }
        
        foreach ($comments as $comment) {
            $author_name = !empty($comment['display_name']) ? $comment['display_name'] : $comment['user_login'];
            $comment_date = date('d.m.Y H:i', strtotime($comment['created_at']));
            
            echo '<div class="comment-item">';
            echo '<div class="comment-header">';
            echo '<span class="comment-author">' . esc_html($author_name) . '</span>';
            echo '<span class="comment-date">' . esc_html($comment_date) . '</span>';
            echo '</div>';
            echo '<div class="comment-text">' . nl2br(esc_html($comment['comment_text'])) . '</div>';
            echo '</div>';
        }
    }
}
