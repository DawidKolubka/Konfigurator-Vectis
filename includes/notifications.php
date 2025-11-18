<?php
defined('ABSPATH') or die('Brak dostępu');

/**
 * System powiadomień mailowych dla konfiguratora Vectis
 */

/**
 * Wysyła powiadomienie email o nowym zamówieniu
 */
function kv_send_new_order_notification($order_id, $order_data) {
    // Pobierz szczegóły zamówienia
    $order = kv_get_order_by_id($order_id);
    if (!$order) {
        return false;
    }
    
    $customer_email = '';
    $customer_name = 'Klient';
    
    // Jeśli klient jest zalogowany, pobierz jego dane
    if ($order->user_id > 0) {
        $user = get_userdata($order->user_id);
        if ($user) {
            $customer_email = $user->user_email;
            $customer_name = $user->display_name ?: $user->user_login;
        }
    }
    
    // Wysłij powiadomienie do administratorów/biura
    kv_notify_admin_new_order($order, $order_data, $customer_name);
    
    // Wysłij potwierdzenie do klienta (jeśli jest zalogowany)
    if (!empty($customer_email)) {
        kv_notify_customer_order_confirmation($order, $order_data, $customer_email, $customer_name);
    }
    
    return true;
}

/**
 * Wysyła powiadomienie do administratorów o nowym zamówieniu
 */
function kv_notify_admin_new_order($order, $order_data, $customer_name) {
    // Pobierz adresy email administratorów i biura
    $recipients = kv_get_admin_notification_emails();
    
    if (empty($recipients)) {
        return false;
    }
    
    $subject = 'Nowe zamówienie w konfiguratorze Vectis - ' . $order->order_number;
    
    $message = kv_get_new_order_admin_email_template($order, $order_data, $customer_name);
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
    );
    
    $sent = false;
    foreach ($recipients as $email) {
        $result = wp_mail($email, $subject, $message, $headers);
        if ($result) {
            $sent = true;
        }
    }
    
    return $sent;
}

/**
 * Wysyła potwierdzenie zamówienia do klienta
 */
function kv_notify_customer_order_confirmation($order, $order_data, $customer_email, $customer_name) {
    $subject = 'Potwierdzenie zamówienia ' . $order->order_number . ' - ' . get_option('blogname');
    
    $message = kv_get_order_confirmation_email_template($order, $order_data, $customer_name);
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
    );
    
    return wp_mail($customer_email, $subject, $message, $headers);
}

/**
 * Wysyła powiadomienie o zmianie statusu zamówienia
 */
function kv_send_order_status_notification($order_id, $old_status, $new_status) {
    $order = kv_get_order_by_id($order_id);
    if (!$order || $order->user_id == 0) {
        return false;
    }
    
    $user = get_userdata($order->user_id);
    if (!$user) {
        return false;
    }
    
    $customer_email = $user->user_email;
    $customer_name = $user->display_name ?: $user->user_login;
    
    $status_messages = array(
        'submitted' => 'Twoje zamówienie zostało przesłane do realizacji',
        'processing' => 'Twoje zamówienie jest w trakcie przetwarzania',
        'completed' => 'Twoje zamówienie zostało ukończone',
        'cancelled' => 'Twoje zamówienie zostało anulowane'
    );
    
    if (!isset($status_messages[$new_status])) {
        return false;
    }
    
    $subject = 'Aktualizacja statusu zamówienia ' . $order->order_number . ' - ' . get_option('blogname');
    
    $message = kv_get_status_change_email_template($order, $old_status, $new_status, $customer_name);
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_option('blogname') . ' <' . get_option('admin_email') . '>'
    );
    
    return wp_mail($customer_email, $subject, $message, $headers);
}

/**
 * Pobiera adresy email do powiadomień administratorów
 */
function kv_get_admin_notification_emails() {
    $emails = array();
    
    // Dodaj email głównego administratora
    $admin_email = get_option('admin_email');
    if ($admin_email) {
        $emails[] = $admin_email;
    }
    
    // Pobierz użytkowników z rolą administrator, handlowiec i biuro
    $admin_roles = array('administrator', 'editor', 'author'); // WordPress roles
    
    foreach ($admin_roles as $role) {
        $users = get_users(array('role' => $role));
        foreach ($users as $user) {
            if (!in_array($user->user_email, $emails)) {
                $emails[] = $user->user_email;
            }
        }
    }
    
    // Pozwól na filtrowanie adresów
    return apply_filters('kv_admin_notification_emails', $emails);
}

/**
 * Template email dla administratorów o nowym zamówieniu
 */
function kv_get_new_order_admin_email_template($order, $order_data, $customer_name) {
    $admin_url = admin_url('admin.php?page=konfigurator-vectis');
    $site_name = get_option('blogname');
    
    ob_start();
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background-color: #0073aa; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .order-details { background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #0073aa; }
            .button { background-color: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0; }
            .footer { background-color: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>🆕 Nowe zamówienie w konfiguratorze</h1>
            <p><?php echo esc_html($site_name); ?></p>
        </div>
        
        <div class="content">
            <p>Witaj!</p>
            <p>Otrzymaliśmy nowe zamówienie w konfiguratorze Vectis.</p>
            
            <div class="order-details">
                <h3>📋 Szczegóły zamówienia</h3>
                <p><strong>Numer zamówienia:</strong> <?php echo esc_html($order->order_number); ?></p>
                <p><strong>Klient:</strong> <?php echo esc_html($customer_name); ?></p>
                <p><strong>Data utworzenia:</strong> <?php echo date('d.m.Y H:i', strtotime($order->created_at)); ?></p>
                <p><strong>Status:</strong> <?php echo esc_html(kv_get_status_label($order->status)); ?></p>
                
                <?php if (!empty($order->customer_order_number)): ?>
                <p><strong>Numer zamówienia klienta:</strong> <?php echo esc_html($order->customer_order_number); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($order->order_notes)): ?>
                <p><strong>Uwagi do zamówienia:</strong><br>
                <?php echo nl2br(esc_html($order->order_notes)); ?></p>
                <?php endif; ?>
            </div>
            
            <p>Możesz przejrzeć szczegóły zamówienia i zarządzać nim w panelu administracyjnym.</p>
            
            <a href="<?php echo esc_url($admin_url); ?>" class="button">👀 Zobacz zamówienie w panelu</a>
        </div>
        
        <div class="footer">
            <p>To powiadomienie zostało wysłane automatycznie przez system <?php echo esc_html($site_name); ?></p>
            <p>Data wysłania: <?php echo date('d.m.Y H:i'); ?></p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Template email potwierdzenia dla klienta
 */
function kv_get_order_confirmation_email_template($order, $order_data, $customer_name) {
    $site_name = get_option('blogname');
    $my_account_url = site_url('/moje-konto/'); // Dostosuj URL do swojej strony
    
    ob_start();
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .order-details { background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #28a745; }
            .button { background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 5px; }
            .footer { background-color: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .highlight { background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>✅ Potwierdzenie zamówienia</h1>
            <p><?php echo esc_html($site_name); ?></p>
        </div>
        
        <div class="content">
            <p>Witaj <?php echo esc_html($customer_name); ?>!</p>
            <p>Dziękujemy za złożenie zamówienia w naszym konfiguratorze. Twoje zamówienie zostało pomyślnie zapisane w systemie.</p>
            
            <div class="order-details">
                <h3>📋 Twoje zamówienie</h3>
                <p><strong>Numer zamówienia:</strong> <?php echo esc_html($order->order_number); ?></p>
                <p><strong>Data złożenia:</strong> <?php echo date('d.m.Y H:i', strtotime($order->created_at)); ?></p>
                <p><strong>Aktualny status:</strong> <?php echo esc_html(kv_get_status_label($order->status)); ?></p>
                
                <?php if (!empty($order->customer_order_number)): ?>
                <p><strong>Twój numer zamówienia:</strong> <?php echo esc_html($order->customer_order_number); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($order->order_notes)): ?>
                <p><strong>Uwagi do zamówienia:</strong><br>
                <?php echo nl2br(esc_html($order->order_notes)); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="highlight">
                <h4>📧 Co dalej?</h4>
                <p>Nasze biuro skontaktuje się z Tobą w ciągu <strong>24 godzin</strong> w celu potwierdzenia szczegółów zamówienia.</p>
                <p>O wszelkich zmianach statusu zamówienia będziemy Cię informować na bieżąco.</p>
            </div>
            
            <p>Możesz śledzić status swojego zamówienia i zarządzać nim w swoim koncie.</p>
            
            <div style="text-align: center;">
                <a href="<?php echo esc_url($my_account_url); ?>" class="button">👤 Moje konto</a>
                <a href="<?php echo esc_url(site_url('/konfigurator/')); ?>" class="button" style="background-color: #0073aa;">🆕 Nowe zamówienie</a>
            </div>
        </div>
        
        <div class="footer">
            <p>Dziękujemy za zaufanie i wybór <?php echo esc_html($site_name); ?></p>
            <p>W razie pytań skontaktuj się z nami: <?php echo esc_html(get_option('admin_email')); ?></p>
            <p>Data wysłania: <?php echo date('d.m.Y H:i'); ?></p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Template email powiadomienia o zmianie statusu
 */
function kv_get_status_change_email_template($order, $old_status, $new_status, $customer_name) {
    $site_name = get_option('blogname');
    $my_account_url = site_url('/moje-konto/');
    
    $status_colors = array(
        'submitted' => '#17a2b8',
        'processing' => '#ffc107',
        'completed' => '#28a745',
        'cancelled' => '#dc3545'
    );
    
    $status_icons = array(
        'submitted' => '📤',
        'processing' => '⚙️',
        'completed' => '✅',
        'cancelled' => '❌'
    );
    
    $color = isset($status_colors[$new_status]) ? $status_colors[$new_status] : '#6c757d';
    $icon = isset($status_icons[$new_status]) ? $status_icons[$new_status] : '📋';
    
    ob_start();
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background-color: <?php echo $color; ?>; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .status-update { background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid <?php echo $color; ?>; text-align: center; }
            .order-details { background-color: #f8f9fa; padding: 15px; margin: 20px 0; border: 1px solid #dee2e6; border-radius: 4px; }
            .button { background-color: <?php echo $color; ?>; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 0; }
            .footer { background-color: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1><?php echo $icon; ?> Aktualizacja statusu zamówienia</h1>
            <p><?php echo esc_html($site_name); ?></p>
        </div>
        
        <div class="content">
            <p>Witaj <?php echo esc_html($customer_name); ?>!</p>
            
            <div class="status-update">
                <h3>Status Twojego zamówienia został zmieniony</h3>
                <p style="font-size: 18px; margin: 15px 0;">
                    <strong>Zamówienie:</strong> <?php echo esc_html($order->order_number); ?>
                </p>
                <p style="font-size: 16px; margin: 10px 0;">
                    <span style="color: #6c757d;"><?php echo esc_html(kv_get_status_label($old_status)); ?></span> 
                    → 
                    <span style="color: <?php echo $color; ?>; font-weight: bold;"><?php echo esc_html(kv_get_status_label($new_status)); ?></span>
                </p>
            </div>
            
            <div class="order-details">
                <h4>Szczegóły zamówienia</h4>
                <p><strong>Numer zamówienia:</strong> <?php echo esc_html($order->order_number); ?></p>
                <p><strong>Data złożenia:</strong> <?php echo date('d.m.Y H:i', strtotime($order->created_at)); ?></p>
                
                <?php if (!empty($order->customer_order_number)): ?>
                <p><strong>Twój numer zamówienia:</strong> <?php echo esc_html($order->customer_order_number); ?></p>
                <?php endif; ?>
            </div>
            
            <?php
            // Dodaj specjalne wiadomości dla różnych statusów
            switch ($new_status) {
                case 'submitted':
                    echo '<p>📤 Twoje zamówienie zostało oficjalnie przesłane do realizacji. Nasze biuro rozpocznie jego przetwarzanie.</p>';
                    break;
                case 'processing':
                    echo '<p>⚙️ Twoje zamówienie jest obecnie przetwarzane przez nasz zespół. Skontaktujemy się z Tobą w razie potrzeby dodatkowych informacji.</p>';
                    break;
                case 'completed':
                    echo '<p>🎉 Gratulacje! Twoje zamówienie zostało ukończone. Skontaktuj się z nami, aby ustalić szczegóły odbioru lub dostawy.</p>';
                    break;
                case 'cancelled':
                    echo '<p>❌ Twoje zamówienie zostało anulowane. Jeśli masz pytania, skontaktuj się z naszym biurem obsługi klienta.</p>';
                    break;
            }
            ?>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="<?php echo esc_url($my_account_url); ?>" class="button">👀 Zobacz szczegóły zamówienia</a>
            </div>
        </div>
        
        <div class="footer">
            <p>Dziękujemy za zaufanie - <?php echo esc_html($site_name); ?></p>
            <p>Kontakt: <?php echo esc_html(get_option('admin_email')); ?></p>
            <p>Data wysłania: <?php echo date('d.m.Y H:i'); ?></p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Pobiera etykietę statusu zamówienia
 */
function kv_get_status_label($status) {
    $labels = array(
        'draft' => 'Wersja robocza',
        'submitted' => 'Przesłane',
        'processing' => 'W trakcie realizacji',
        'completed' => 'Ukończone',
        'cancelled' => 'Anulowane'
    );
    
    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
}