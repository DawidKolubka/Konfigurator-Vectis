<?php
// podsumowanie.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Wyświetl komunikat o zapisaniu zamówienia
if (isset($_GET['order_saved']) && $_GET['order_saved'] == 1) {
    echo '<div class="notice notice-success" style="padding: 15px; margin: 20px 0; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;">';
    echo '<h3 style="margin: 0 0 10px 0;">✅ Dziękujęmy za wypełnienie konfiguratora!</h3>';
    echo '<p style="margin: 0;">Twoje zamówienie zostało pomyślnie zapisane w systemie.</p>';
    
    // Sprawdź czy użytkownik jest zalogowany i dodaj informację o powiadomieniach
    if (is_user_logged_in()) {
        echo '<p style="margin: 10px 0 0 0;"><small>📧 Potwierdzenie zamówienia zostało wysłane na Twój adres email.</small></p>';
    }
    echo '</div>';
    
    // Dodaj przyciski akcji po komunikacie sukcesu
    echo '<div class="success-action-buttons" style="text-align: center; margin: 30px 0;">';
    echo '<a href="/moje-konto" class="button button-primary" style="margin-right: 15px; padding: 12px 24px; text-decoration: none;">👤 Przejdź do Moje Konto</a>';
    echo '<a href="' . add_query_arg('new_order', '1', remove_query_arg(['order_saved', 'step'])) . '" class="button button-secondary" style="padding: 12px 24px; text-decoration: none;">🆕 Zacznij kolejne zamówienie</a>';
    echo '</div>';
}

// Wyświetl komunikat o aktualizacji zamówienia
if (isset($_GET['order_updated']) && $_GET['order_updated'] == 1) {
    echo '<div class="notice notice-success" style="padding: 15px; margin: 20px 0; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; color: #155724;">';
    echo '<h3 style="margin: 0 0 10px 0;">✅ Zamówienie zostało zaktualizowane!</h3>';
    echo '<p style="margin: 0 0 15px 0;">Zmiany w zamówieniu zostały pomyślnie zapisane w systemie.</p>';
    
    // Przycisk powrotu do panelu administracyjnego
    $admin_panel_url = admin_url('admin.php?page=konfigurator-vectis');
    echo '<a href="' . esc_url($admin_panel_url) . '" class="button button-primary" style="background: #0073aa; border-color: #0073aa; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 3px; display: inline-block;">';
    echo '← Powrót do panelu zamówień';
    echo '</a>';
    
    echo '</div>';
}

// Wyświetl komunikat o błędzie zapisywania
if (isset($_GET['save_error']) && $_GET['save_error'] == 1) {
    echo '<div class="notice notice-error" style="padding: 15px; margin: 20px 0; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">';
    echo '<h3 style="margin: 0 0 10px 0;">❌ Błąd zapisywania</h3>';
    echo '<p style="margin: 0;">Wystąpił błąd podczas zapisywania zamówienia. Prosimy spróbować ponownie lub skontaktować się z administratorem.</p>';
    echo '</div>';
} elseif (isset($_GET['save_error']) && $_GET['save_error'] == 2) {
    echo '<div class="notice notice-warning" style="padding: 15px; margin: 20px 0; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; color: #856404;">';
    echo '<h3 style="margin: 0 0 10px 0;">⚠️ Brak danych do zapisania</h3>';
    echo '<p style="margin: 0;">Nie ma żadnych danych do zapisania. Skonfiguruj przynajmniej jedną pozycję przed zapisaniem zamówienia.</p>';
    echo '</div>';
} elseif (isset($_GET['save_error']) && $_GET['save_error'] == 3) {
    echo '<div class="notice notice-error" style="padding: 15px; margin: 20px 0; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">';
    echo '<h3 style="margin: 0 0 10px 0;">❌ Błąd aktualizacji</h3>';
    echo '<p style="margin: 0;">Wystąpił błąd podczas aktualizacji zamówienia. Prosimy spróbować ponownie lub skontaktować się z administratorem.</p>';
    echo '</div>';
}

// DEBUG: Wyświetl zawartość sesji konfiguratora do debugowania
error_log("PODSUMOWANIE - Zawartość sesji kv_configurator: " . print_r($_SESSION['kv_configurator'] ?? 'BRAK', true));

// Inicjalizacja tablicy pozycji w sesji, jeśli nie istnieje e
if (!isset($_SESSION['kv_configurator']['items'])) {
    $_SESSION['kv_configurator']['items'] = [];
}

// Obsługa przycisku "Dodaj kolejną pozycję"
if (isset($_POST['add_item'])) {
    // Najpierw weryfikuj nonce
    if (!isset($_POST['kv_configurator_nonce']) || !wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')) {
        die('Błąd: nieprawidłowy nonce.');
    }
    
    // Zapisz numer zamówienia klienta do sesji, jeśli został podany
    if (isset($_POST['customer_order_number'])) {
        $_SESSION['kv_configurator']['customer_order_number'] = sanitize_text_field($_POST['customer_order_number']);
    }
    
    // Zapisz uwagi do zamówienia do sesji, jeśli zostały podane
    if (isset($_POST['order_notes'])) {
        $_SESSION['kv_configurator']['order_notes'] = sanitize_textarea_field($_POST['order_notes']);
    }
    
    // Reszta kodu bez zmian
    $current_config = $_SESSION['kv_configurator'];
    $items = $current_config['items'] ?? [];
    
    // Zachowaj ważne dane przed czyszczeniem
    $customer_order_number = isset($current_config['customer_order_number']) ? $current_config['customer_order_number'] : '';
    $order_notes = isset($current_config['order_notes']) ? $current_config['order_notes'] : '';
    
    // WALIDACJA: Sprawdź czy bieżąca konfiguracja zawiera kompletne dane produktu
    $has_complete_config = false;
    
    // Sprawdź czy są podstawowe dane konfiguracji (seria, ksztalt, uklad)
    if (isset($current_config['seria']) && !empty($current_config['seria']) &&
        isset($current_config['ksztalt']) && ($current_config['ksztalt'] !== '' && $current_config['ksztalt'] !== null) &&
        isset($current_config['uklad']) && ($current_config['uklad'] !== '' && $current_config['uklad'] !== null)) {
        $has_complete_config = true;
    }
    
    // Usuń informacje meta z konfiguracji do zapisania
    unset($current_config['items']);
    unset($current_config['customer_order_number']); 
    unset($current_config['order_notes']);
    unset($current_config['editing_order_id']);
    unset($current_config['editing_order_number']);
    unset($current_config['editing_mode']);
    
    // Dodaj bieżącą konfigurację do zapisanych pozycji TYLKO jeśli jest kompletna
    if ($has_complete_config) {
        $items[] = $current_config;
        error_log("DODANO nową pozycję do zamówienia - seria: " . $current_config['seria'] . ", ksztalt: " . $current_config['ksztalt'] . ", uklad: " . $current_config['uklad']);
    } else {
        error_log("NIE DODANO pozycji - brak kompletnej konfiguracji. Current config: " . print_r($current_config, true));
    }
    
    // Wyczyść całą konfigurację sesji i zachowaj tylko zapisane pozycje oraz meta dane
    $_SESSION['kv_configurator'] = [
        'items' => $items,
        'customer_order_number' => $customer_order_number,
        'order_notes' => $order_notes,
        'quantity' => 1  // Domyślna ilość dla nowej pozycji
    ];
    
    // Przekierowanie na stronę konfiguratora do kroku 1
    wp_redirect(add_query_arg('step', 1, home_url('/konfigurator/')));
    exit;
}

// UWAGA: Obsługa przycisku "Złóż zamówienie" została przeniesiona do configurator.php 
// pod obsługę kv_global_save - ta sekcja została usunięta aby uniknąć dublowania pozycji

// Obsługa przycisku "Usuń pozycję"
if (isset($_POST['delete_item']) && isset($_POST['kv_configurator_nonce']) && wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')) {
    $item_index = intval($_POST['delete_item']);
    
    if (isset($_SESSION['kv_configurator']['items'][$item_index])) {
        // Usuń pozycję z tablicy
        unset($_SESSION['kv_configurator']['items'][$item_index]);
        
        // Przeidkowanie indeksów (aby nie było dziur w numeracji)
        $_SESSION['kv_configurator']['items'] = array_values($_SESSION['kv_configurator']['items']);
        
        // Przekierowanie żeby uniknąć ponownego wysłania formularza
        // Jawne przekierowanie na podsumowanie z prawidłowym parametrem
        wp_redirect(add_query_arg('step', 5, home_url('/konfigurator/')));
        exit;
    }
}

// Obsługa przycisku "Edytuj pozycję"
if (isset($_POST['edit_item']) && isset($_POST['kv_configurator_nonce']) && wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')) {
    $item_index = intval($_POST['edit_item']);
    
    if (isset($_SESSION['kv_configurator']['items'][$item_index])) {
        // Pobierz dane pozycji do edycji
        $item_to_edit = $_SESSION['kv_configurator']['items'][$item_index];
        
        // Usuń pozycję z listy zapisanych pozycji
        unset($_SESSION['kv_configurator']['items'][$item_index]);
        $_SESSION['kv_configurator']['items'] = array_values($_SESSION['kv_configurator']['items']);
        
        // Załaduj dane pozycji do bieżącej konfiguracji
        foreach ($item_to_edit as $key => $value) {
            $_SESSION['kv_configurator'][$key] = $value;
        }
        
        // Ustaw znacznik trybu edycji
        $_SESSION['kv_configurator']['editing_mode'] = true;
        
        // Przekierowanie bezpośrednio do kroku 4 (edycja mechanizmów)
        wp_redirect(add_query_arg('step', 4, home_url('/konfigurator/')));
        exit;
    }
}

// Obsługa przycisku "Wyczyść bieżącą konfigurację"
if (isset($_POST['clear_current_config']) && isset($_POST['kv_configurator_nonce']) && wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')) {
    // Zachowaj tylko pozycje zapisane wcześniej, numer zamówienia klienta i uwagi
    $items = isset($_SESSION['kv_configurator']['items']) ? $_SESSION['kv_configurator']['items'] : [];
    $customer_order_number = isset($_SESSION['kv_configurator']['customer_order_number']) ? $_SESSION['kv_configurator']['customer_order_number'] : '';
    $order_notes = isset($_SESSION['kv_configurator']['order_notes']) ? $_SESSION['kv_configurator']['order_notes'] : '';
    
    // Wyczyść całą konfigurację
    $_SESSION['kv_configurator'] = [
        'items' => $items,
        'customer_order_number' => $customer_order_number,
        'order_notes' => $order_notes
    ];
    
    // Jeśli nie ma zapisanych pozycji, przekieruj do kroku 1, w przeciwnym razie zostań na podsumowaniu
    if (empty($items)) {
        wp_redirect(add_query_arg('step', 1, home_url('/konfigurator/')));
    } else {
        wp_redirect(add_query_arg('step', 5, home_url('/konfigurator/')));
    }
    exit;
}

// Obsługa przycisku "Edytuj bieżącą konfigurację"
if (isset($_POST['edit_current_config']) && isset($_POST['kv_configurator_nonce']) && wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')) {
    // Ustaw znacznik trybu edycji
    $_SESSION['kv_configurator']['editing_mode'] = true;
    
    // Przekieruj bezpośrednio do kroku 4 (edycja mechanizmów) z zachowaniem bieżącej konfiguracji
    wp_redirect(add_query_arg('step', 4, home_url('/konfigurator/')));
    exit;
}

// Obsługa aktualizacji ilości dla zapisanej pozycji
// Sprawdzamy wszystkie możliwe przyciski update_saved_item_quantity_X
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'update_saved_item_quantity_') === 0 && isset($_POST['kv_configurator_nonce']) && wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')) {
            // Wyciągnij indeks z nazwy przycisku
            $item_index = intval(str_replace('update_saved_item_quantity_', '', $key));
            
            // Sprawdź czy istnieją odpowiednie pola
            $quantity_field = 'saved_quantity_' . $item_index;
            if (isset($_POST[$quantity_field])) {
                $quantity = max(1, intval($_POST[$quantity_field]));
                
                if (isset($_SESSION['kv_configurator']['items'][$item_index])) {
                    $_SESSION['kv_configurator']['items'][$item_index]['quantity'] = $quantity;
                    
                    // Przekierowanie na podsumowanie
                    wp_redirect(add_query_arg('step', 5, home_url('/konfigurator/')));
                    exit;
                }
            }
            break; // Przerywamy po znalezieniu pierwszego pasującego przycisku
        }
    }
}

// Obsługa aktualizacji ilości dla bieżącej konfiguracji
if (isset($_POST['update_current_quantity']) && isset($_POST['current_quantity']) && isset($_POST['kv_configurator_nonce']) && wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')) {
    $quantity = max(1, intval($_POST['current_quantity']));
    $_SESSION['kv_configurator']['quantity'] = $quantity;
    
    // Przekierowanie na podsumowanie
    wp_redirect(add_query_arg('step', 5, home_url('/konfigurator/')));
    exit;
}

// Obsługa aktualizacji koloru ramki dla bieżącej konfiguracji
if (isset($_POST['update_frame_color_current']) && isset($_POST['frame_color_current']) && isset($_POST['kv_configurator_nonce']) && wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')) {
    $new_frame_color = sanitize_text_field($_POST['frame_color_current']);
    $_SESSION['kv_configurator']['kolor_ramki'] = $new_frame_color;
    
    // Przekierowanie na podsumowanie
    wp_redirect(add_query_arg('step', 5, home_url('/konfigurator/')));
    exit;
}

// Obsługa aktualizacji koloru ramki dla zapisanych pozycji
if (!empty($_POST)) {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'update_frame_color_') === 0 && isset($_POST['kv_configurator_nonce']) && wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')) {
            // Wyciągnij indeks z nazwy przycisku
            $item_index = intval(str_replace('update_frame_color_', '', $key));
            
            // Sprawdź czy istnieją odpowiednie pola
            $frame_color_field = 'frame_color_' . $item_index;
            if (isset($_POST[$frame_color_field])) {
                $new_frame_color = sanitize_text_field($_POST[$frame_color_field]);
                
                if (isset($_SESSION['kv_configurator']['items'][$item_index])) {
                    $_SESSION['kv_configurator']['items'][$item_index]['kolor_ramki'] = $new_frame_color;
                    
                    // Przekierowanie na podsumowanie
                    wp_redirect(add_query_arg('step', 5, home_url('/konfigurator/')));
                    exit;
                }
            }
            break; // Przerywamy po znalezieniu pierwszego pasującego przycisku
        }
    }
}

// (A) Pobranie danych z bazy – tak samo, jak w krokach 1-4
$uklad_options            = get_option('kv_uklad_options', []);
$kolor_ramki_options      = get_option('kv_kolor_ramki_options', []);
$mechanizm_options        = get_option('kv_mechanizm_options', []);
$technologia_options      = get_option('kv_technologia_options', []);
$kolor_mechanizmu_options = get_option('kv_kolor_mechanizmu_options', []);
$ksztalt_options          = get_option('kv_ksztalt_options', []);

// Filtrowanie placeholderów (elementy o indeksie 0)
if (!empty($kolor_ramki_options) && isset($kolor_ramki_options[0]) && 
    (isset($kolor_ramki_options[0]['snippet']) && $kolor_ramki_options[0]['snippet'] === 'placeholder')) {
    unset($kolor_ramki_options[0]);
}

if (!empty($kolor_mechanizmu_options) && isset($kolor_mechanizmu_options[0]) && 
    (isset($kolor_mechanizmu_options[0]['snippet']) && $kolor_mechanizmu_options[0]['snippet'] === 'placeholder')) {
    unset($kolor_mechanizmu_options[0]);
}
$seria_options            = get_option('kv_seria_options', []);

// Definicja domyślnego obrazka dla pustego slotu
$empty_slot_img = 'https://www.isdvectis.pl/wp-content/uploads/2025/04/wybor.svg';

// (B) Zapisujemy zmienne z sesji do $cfg – i ewentualnie usuwamy slashe
$cfg = isset($_SESSION['kv_configurator']) ? $_SESSION['kv_configurator'] : [];

// Helper do usuwania slashe, gdyby `\"` wciąż się pojawiały
function maybe_stripslashes($value) {
    if (is_string($value)) {
        return stripslashes($value);
    }
    return $value;
}

// (C) Pobieranie danych z sesji
// Seria
$seria = isset($cfg['seria']) ? maybe_stripslashes($cfg['seria']) : '';

// Kształt
$ksztalt_index = isset($cfg['ksztalt']) ? maybe_stripslashes($cfg['ksztalt']) : 0;
$ksztalt_name = '';
if (isset($ksztalt_options[$ksztalt_index]['name'])) {
    $ksztalt_name = $ksztalt_options[$ksztalt_index]['name'];
}

// Układ
$uklad_index = isset($cfg['uklad']) ? maybe_stripslashes($cfg['uklad']) : 0;
$layoutName  = '';
$uklad_img   = '';
$uklad_code  = '00'; // Domyślna wartość
if (isset($uklad_options[$uklad_index])) {
    $layoutName = $uklad_options[$uklad_index]['name'] ?? '';
    $layoutName = maybe_stripslashes($layoutName);  // usuwamy ewentualne \"
    $uklad_img  = $uklad_options[$uklad_index]['image'] ?? '';
    $uklad_img  = maybe_stripslashes($uklad_img);
    
    // Pobierz kod układu - najpierw sprawdź pole 'code', potem 'snippet', na końcu pierwsze znaki nazwy
    if (isset($uklad_options[$uklad_index]['code']) && !empty($uklad_options[$uklad_index]['code'])) {
        $uklad_code = $uklad_options[$uklad_index]['code'];
        error_log("Pobrano kod układu z pola 'code': " . $uklad_code);
    } elseif (isset($uklad_options[$uklad_index]['snippet']) && !empty($uklad_options[$uklad_index]['snippet'])) {
        $uklad_code = $uklad_options[$uklad_index]['snippet'];
        error_log("Pobrano kod układu z pola 'snippet': " . $uklad_code);
    } else {
        $uklad_code = substr($layoutName, 0, 2);
        error_log("Pobrano kod układu z nazwy układu: " . $uklad_code);
    }
}

// (D) Na podstawie nazwy układu określamy liczbę slotów
$ileSlotow = 1;
if (preg_match('/X(\\d+)/i', $layoutName, $matches)) {
    $ileSlotow = (int) $matches[1];
} elseif (stripos($layoutName, 'pionowy') !== false || stripos($layoutName, 'poziomy') !== false) {
    $ileSlotow = 2;
}

// (D‑1) Określenie klasy CSS orientacji ramki (poziomo/pionowo)
if (stripos($layoutName, 'poziomy') !== false) {
    $orientation_class = 'horizontal';
} elseif (stripos($layoutName, 'pionowy') !== false) {
    $orientation_class = 'vertical';
} elseif ($ileSlotow == 1) {
    $orientation_class = 'horizontal'; // Dla pojedynczego slotu domyślnie poziomo
} else {
    $orientation_class = '';
}

// (E) Kolor ramki
$frame_color_index = isset($cfg['kolor_ramki']) ? maybe_stripslashes($cfg['kolor_ramki']) : '';
$frame_color_name  = '';
$frame_color_img   = '';
$frame_color_code  = '00'; // Domyślna wartość

if ($frame_color_index !== '' && isset($kolor_ramki_options[$frame_color_index])) {
    $frame_color_name = $kolor_ramki_options[$frame_color_index]['name']  ?? '';
    $frame_color_name = maybe_stripslashes($frame_color_name);
    $frame_color_img  = $kolor_ramki_options[$frame_color_index]['image'] ?? '';
    $frame_color_img  = maybe_stripslashes($frame_color_img);
    
    // Pobierz kod koloru ramki - najpierw sprawdź pole 'code', potem 'snippet', na końcu pierwsze znaki nazwy
    if (isset($kolor_ramki_options[$frame_color_index]['code']) && !empty($kolor_ramki_options[$frame_color_index]['code'])) {
        $frame_color_code = $kolor_ramki_options[$frame_color_index]['code'];
        error_log("Pobrano kod koloru ramki z pola 'code': " . $frame_color_code);
    } elseif (isset($kolor_ramki_options[$frame_color_index]['snippet']) && !empty($kolor_ramki_options[$frame_color_index]['snippet'])) {
        $frame_color_code = $kolor_ramki_options[$frame_color_index]['snippet'];
        error_log("Pobrano kod koloru ramki z pola 'snippet': " . $frame_color_code);
    } else {
        $frame_color_code = substr($frame_color_name, 0, 2);
        error_log("Pobrano kod koloru ramki z nazwy koloru: " . $frame_color_code);
    }
}

// (F) Pobieramy dane slotów i składamy je w czytelną tablicę $slots
$slots = [];
$mech_code = '';

for ($i = 0; $i < $ileSlotow; $i++) {
    $mechID = isset($cfg['mechanizm_'.$i]) ? maybe_stripslashes($cfg['mechanizm_'.$i]) : '';
    
    // Debug - wypisz ID mechanizmu dla każdego slotu
    error_log("PODSUMOWANIE: Slot {$i} - mechanizm ID: " . $mechID);
    
    $mech_name = 'Brak nazwy';
    $mech_img = ''; // Domyślnie pusty obrazek
    $tech_name = '';
    $tech_price = 0;
    $colorVal = isset($cfg['kolor_mechanizmu_'.$i]) ? maybe_stripslashes($cfg['kolor_mechanizmu_'.$i]) : '';
    $techID = isset($cfg['technologia_'.$i]) ? maybe_stripslashes($cfg['technologia_'.$i]) : '';

    if (isset($mechID) && $mechID !== '' && isset($mechanizm_options[$mechID])) {
        $mech_data = $mechanizm_options[$mechID];
        $mech_name = $mech_data['name'] ?? 'Brak nazwy';

        // Priorytet dla frame_image, fallback do image
        if (!empty($mechanizm_options[$mechID]['frame_image'])) {
            $mech_img = $mechanizm_options[$mechID]['frame_image'];
        } elseif (!empty($mechanizm_options[$mechID]['image'])) {
            $mech_img = $mechanizm_options[$mechID]['image'];
        }
        // Jeśli oba są puste, $mech_img pozostanie pusty

        // Debug: Sprawdź pobrany obrazek
        error_log("PODSUMOWANIE: Slot {$i} - Obrazek mechanizmu ({$mechID}): " . $mech_img);

        // Technologia i cena
        if (!empty($techID) && isset($technologia_options[$techID])) {
            $tech_data = $technologia_options[$techID];
            
            // Sprawdź, czy technologia jest powiązana z tym mechanizmem
            if (($tech_data['group'] ?? -1) == $mechID) {
                $tech_name = $tech_data['technology'] ?? '';
                $found_exact_color_match = false;
                
                // Zapisz pierwotną technologię przed szukaniem alternatyw
                $original_tech_data = $tech_data;
                $original_tech_name = $tech_name;
                
                // Sprawdź zgodność koloru i szukaj najlepszego dopasowania
                if (!empty($colorVal)) {
                    error_log("PODSUMOWANIE: Slot {$i} - Szukam technologii dla mechanizmu {$mechID} i koloru {$colorVal}");
                    
                    // Przeszukaj technologie w poszukiwaniu idealnego dopasowania (mechanizm + kolor)
                    foreach ($technologia_options as $tech_id => $tech) {
                        if (isset($tech['group']) && $tech['group'] == $mechID &&
                            isset($tech['color']) && $tech['color'] == $colorVal) {
                            
                            // Znaleziono DOKŁADNE dopasowanie - użyj tej technologii do ceny
                            $tech_data = $tech;
                            $tech_name = $tech['technology'] ?? '';
                            $found_exact_color_match = true;
                            error_log("PODSUMOWANIE: Slot {$i} - Znaleziono DOKŁADNE dopasowanie koloru w technologii {$tech_id}");
                            break;
                        }
                    }
                    
                    if (!$found_exact_color_match && isset($tech_data['color']) && $tech_data['color'] == $colorVal) {
                        // Jeśli nie znaleźliśmy innej technologii, ale wybrana technologia ma pasujący kolor
                        $found_exact_color_match = true;
                        error_log("PODSUMOWANIE: Slot {$i} - Wybrana technologia {$techID} ma pasujący kolor {$colorVal}");
                    }
                    
                    if (!$found_exact_color_match) {
                        error_log("PODSUMOWANIE: Slot {$i} - Brak technologii z dokładnym dopasowaniem koloru {$colorVal}, używam technologii {$techID}");
                        // Wróć do pierwotnie wybranej technologii
                        $tech_data = $original_tech_data;
                        $tech_name = $original_tech_name;
                    }
                } else {
                    // Brak określonego koloru, używamy wybranej technologii
                    $found_exact_color_match = true;
                    error_log("PODSUMOWANIE: Slot {$i} - Brak określonego koloru, używam technologii {$techID}");
                }
                
                // Upewnij się, że cena jest liczbą - poprawnie pobieraj cenę dla dopasowanej technologii i koloru
                $tech_price = isset($tech_data['price']) ? floatval(str_replace(',', '.', $tech_data['price'])) : 0;
                error_log("PODSUMOWANIE: Slot {$i} - Ustawiono cenę {$tech_price} dla " . 
                         ($found_exact_color_match ? "dokładnie dopasowanej" : "najbliżej dopasowanej") . 
                         " technologii " . ($found_exact_color_match ? "{$techID}" : "") .
                         (!empty($colorVal) ? " i koloru {$colorVal}" : " (brak koloru)"));
            } else {
                error_log("PODSUMOWANIE: Slot {$i} - Niezgodność technologii ({$techID}) z mechanizmem ({$mechID})");
            }
        } else {
            error_log("PODSUMOWANIE: Slot {$i} - Nie znaleziono technologii o ID: " . $techID);
        }
    } else {
         error_log("PODSUMOWANIE: Slot {$i} - Nie znaleziono mechanizmu o ID: " . $mechID);
    }

    // Kolor mechanizmu
    $colorName = '';
    if ($colorVal !== '' && isset($kolor_mechanizmu_options[$colorVal]['name'])) {
        $colorName = $kolor_mechanizmu_options[$colorVal]['name'];
    }

    // Zapisz dane do tablicy $slots używanej w kolumnie "Mechanizmy"
    $slots[] = [
        'mechanizm_id'   => $mechID, // Dodaj ID dla łatwiejszego dostępu później
        'mechanizm_name' => $mech_name,
        'mechanizm_img'  => $mech_img, // Użyj poprawionej zmiennej $mech_img
        'technologia_id' => $techID,   // Dodajemy ID technologii
        'technologia'    => $tech_name,
        'kolor_mech_id'  => $colorVal, // Dodajemy ID koloru mechanizmu
        'kolor_mech'     => $colorName,
        'cena'           => $tech_price
    ];

    // Zapisz dane do tablicy $slotData używanej przy wyświetlaniu ramek
    $slotData[$i] = [
        'mechanizm' => $mechID // Przekazujemy tylko ID mechanizmu
    ];
}

// Inicjalizacja tablicy $slotData dla bieżącej konfiguracji (używana w wyświetlaniu ramki)
$slotData = [];
for ($i = 0; $i < $ileSlotow; $i++) {
    $mechID = isset($cfg['mechanizm_'.$i]) ? maybe_stripslashes($cfg['mechanizm_'.$i]) : '';
    $techID = isset($cfg['technologia_'.$i]) ? maybe_stripslashes($cfg['technologia_'.$i]) : '';
    $colorVal = isset($cfg['kolor_mechanizmu_'.$i]) ? maybe_stripslashes($cfg['kolor_mechanizmu_'.$i]) : '';
    
    $slotData[$i] = [
        'mechanizm' => $mechID,
        'technologia' => $techID,
        'kolor_mechanizmu' => $colorVal
    ];
}

// Generowanie kodu produktu
// Format: Wybrana seria (kod) + Wybrany kształt (kod) + 0 (liczba kontrolna) - wybrany mechanizm (kod) - wybrany układ (kod) - kolor ramki (kod)
// Przykład: ISDR0-12345-11P2

// Kod serii - pobieramy z pola 'fragment' w zapisanej serii
$seria_name = isset($cfg['seria']) ? maybe_stripslashes($cfg['seria']) : '';
$seria_code = 'IS'; // Domyślna wartość

// Poszukaj serii w tablicy opcji i pobierz jej fragment
foreach ($seria_options as $seria_option) {
    if ($seria_option['name'] === $seria_name && isset($seria_option['fragment'])) {
        $seria_code = $seria_option['fragment'];
        break;
    }
}

// Kod kształtu - pobieramy z pola 'snippet' w bazie danych
$ksztalt_code = isset($ksztalt_options[$ksztalt_index]['snippet']) && !empty($ksztalt_options[$ksztalt_index]['snippet']) 
    ? $ksztalt_options[$ksztalt_index]['snippet'] 
    : '0'; // Domyślna wartość, jeśli snippet nie istnieje

// Pobieramy cząstki kodu (snippet) ze wszystkich mechanizmów w slotach i łączymy je
$mech_code = '';

// Dodatkowe debugowanie - sprawdzmy co faktycznie jest w tablicy $cfg
error_log("Liczba slotów: " . $ileSlotow);
error_log("Zawartość tablicy cfg związana ze slotami:");
for ($i = 0; $i < 5; $i++) {
    $key = 'mechanizm_' . $i;
    if (isset($cfg[$key])) {
        error_log("$key: " . $cfg[$key]);
    } else {
        error_log("$key: nie istnieje");
    }
}

// Dodatkowa diagnostyka - sprawdź wszystkie mechanizmy i ich snippety
error_log("==== DIAGNOSTYKA SNIPPETÓW MECHANIZMÓW ====");
foreach ($mechanizm_options as $m_idx => $mech_opt) {
    $snippet_val = isset($mech_opt['snippet']) ? $mech_opt['snippet'] : 'BRAK';
    error_log("Mechanizm ID {$m_idx}: snippet = {$snippet_val}, nazwa = " . ($mech_opt['name'] ?? 'BRAK NAZWY'));
}

// Teraz poprawione generowanie kodu
for ($i = 0; $i < $ileSlotow; $i++) {
    $mechID = isset($cfg['mechanizm_'.$i]) ? maybe_stripslashes($cfg['mechanizm_'.$i]) : '';
    
    // Debug: Zapisz informację o przetwarzanym slocie
    error_log("Przetwarzanie Slot {$i}: MechID = {$mechID}, Typ: " . gettype($mechID));
    
    // Pobierz cząstkę kodu mechanizmu (snippet)
    $slot_mech_code = '';
    
    // POPRAWKA: zamiast !empty($mechID) używamy $mechID !== ''
    if ($mechID !== '' && isset($mechanizm_options[$mechID]['snippet'])) {
        $slot_mech_code = $mechanizm_options[$mechID]['snippet'];
        error_log("Slot {$i}: Znaleziono snippet = {$slot_mech_code}");
    } else {
        // Rozszerzona diagnostyka
        if ($mechID === '') {
            error_log("Slot {$i}: MechID jest pusty");
        } else if (!isset($mechanizm_options[$mechID])) {
            error_log("Slot {$i}: Nie znaleziono mechanizmu o ID: {$mechID}");
            // Spróbuj sprawdzić z numeryczną konwersją indeksu
            $numeric_mechID = intval($mechID); 
            if (isset($mechanizm_options[$numeric_mechID])) {
                error_log("Slot {$i}: Znaleziono mechanizm pod numerycznym ID: {$numeric_mechID}");
                $slot_mech_code = $mechanizm_options[$numeric_mechID]['snippet'] ?? '';
                error_log("Slot {$i}: Użyto snippetu z numerycznego ID: {$slot_mech_code}");
            }
        } else if (!isset($mechanizm_options[$mechID]['snippet'])) {
            error_log("Slot {$i}: Mechanizm {$mechID} nie ma ustawionego snippetu");
        }
    }
    
    // Dodaj cząstkę kodu mechanizmu do łącznego kodu
    $mech_code .= $slot_mech_code;
    
    // Debug: Pokaż aktualny stan kodu mechanizmu
    error_log("Po slocie {$i}: mech_code = {$mech_code}");
}

// Uzupełnij zerami do 5 znaków
$mech_code = str_pad($mech_code, 5, '0');
error_log("Finalny kod mechanizmu: {$mech_code}");

// Łączymy kody w określonym formacie: XXYR0-ZZZZZ-AABB
// gdzie XX = kod serii, Y = kod kształtu, ZZZZZ = kod mechanizmu (teraz ze snippetów), AA = kod układu, BB = kod koloru ramki

// Dodatkowe debugowanie kodów przed łączeniem
error_log("Podsumowanie (główny kod): Składanie kodu produktu z następujących elementów:");
error_log("Kod serii: " . $seria_code);
error_log("Kod kształtu: " . $ksztalt_code);
error_log("Kod mechanizmu: " . $mech_code);
error_log("Kod układu: " . $uklad_code);
error_log("Kod koloru ramki: " . $frame_color_code);

// Upewnij się, że kody układu i koloru ramki mają długość 2 znaków
$uklad_code = str_pad(substr($uklad_code, 0, 2), 2, '0');
$frame_color_code = str_pad(substr($frame_color_code, 0, 2), 2, '0');

error_log("Po normalizacji - Kod układu: " . $uklad_code);
error_log("Po normalizacji - Kod koloru ramki: " . $frame_color_code);

$product_code = strtoupper($seria_code . $ksztalt_code . "0-" . $mech_code . "-" . $uklad_code . $frame_color_code);

// (G) Ustalanie ilości, jeśli zapisana w sesji
$quantity = isset($cfg['quantity']) ? (int) $cfg['quantity'] : 1;

// (H) Obliczanie cen
$cena_jednostkowa = 0;

// Dodanie ceny ramki do ceny jednostkowej
$cena_ramki = 0;

// Wybieramy odpowiednią cenę ramki w zależności od liczby slotów
$frame_price_key = 'price_x' . $ileSlotow;
$frame_price = '';

if (isset($kolor_ramki_options[$frame_color_index][$frame_price_key]) && !empty($kolor_ramki_options[$frame_color_index][$frame_price_key])) {
    // Jeśli istnieje cena dla konkretnego układu
    $frame_price = $kolor_ramki_options[$frame_color_index][$frame_price_key];
    error_log("Użyto ceny dla układu X{$ileSlotow}: {$frame_price}");
} elseif (isset($kolor_ramki_options[$frame_color_index]['price']) && !empty($kolor_ramki_options[$frame_color_index]['price'])) {
    // Jeśli brak ceny dla konkretnego układu, użyj ceny ogólnej (kompatybilność wsteczna)
    $frame_price = $kolor_ramki_options[$frame_color_index]['price'];
    error_log("Użyto ogólnej ceny ramki: {$frame_price} (brak ceny dla X{$ileSlotow})");
}

if (!empty($frame_price)) {
    $cena_ramki = floatval(str_replace(',', '.', $frame_price));
}

// Suma cen wszystkich technologii w slotach
foreach ($slots as $slot) {
    $cena_jednostkowa += $slot['cena'];
}

// Dodajemy cenę ramki do ceny jednostkowej
$cena_jednostkowa += $cena_ramki;

// Jeśli cena jest zerowa, ustawiamy domyślnie 1
$cena_jednostkowa = ($cena_jednostkowa > 0) ? $cena_jednostkowa : 1;

$cena_calkowita = $cena_jednostkowa * $quantity;

// Obsługa zmiany ilości
if (isset($_POST['update_quantity']) && isset($_POST['quantity'])) {
    $quantity = max(1, intval($_POST['quantity']));
    $_SESSION['kv_configurator']['quantity'] = $quantity;
    $cena_calkowita = $cena_jednostkowa * $quantity;
    
    // Zapisz również numer zamówienia klienta, jeśli został podany
    if (isset($_POST['customer_order_number'])) {
        $_SESSION['kv_configurator']['customer_order_number'] = sanitize_text_field($_POST['customer_order_number']);
    }
    
    // Zapisz również uwagi do zamówienia, jeśli zostały podane
    if (isset($_POST['order_notes'])) {
        $_SESSION['kv_configurator']['order_notes'] = sanitize_textarea_field($_POST['order_notes']);
    }
}

// Funkcja pomocnicza do renderowania wiersza pozycji w tabeli
function render_item_row($row_number, $item_data, $uklad_options, $kolor_ramki_options, $mechanizm_options, $technologia_options, $is_current = false, $item_index = null) {
    // WALIDACJA: TYLKO w pełni skonfigurowane pozycje (seria I kształt I układ)
    // UWAGA: ksztalt i uklad są intami, więc sprawdzamy isset i != '' zamiast empty()
    if (!isset($item_data['seria']) || empty($item_data['seria']) ||
        !isset($item_data['ksztalt']) || $item_data['ksztalt'] === '' || $item_data['ksztalt'] === null ||
        !isset($item_data['uklad']) || $item_data['uklad'] === '' || $item_data['uklad'] === null) {
        
        error_log("render_item_row: Pominięto renderowanie - niepełna konfiguracja:");
        error_log("  - seria: " . (isset($item_data['seria']) ? '"'.$item_data['seria'].'"' : 'BRAK'));
        error_log("  - ksztalt: " . (isset($item_data['ksztalt']) ? '"'.$item_data['ksztalt'].'" (typ: '.gettype($item_data['ksztalt']).')' : 'BRAK'));
        error_log("  - uklad: " . (isset($item_data['uklad']) ? '"'.$item_data['uklad'].'" (typ: '.gettype($item_data['uklad']).')' : 'BRAK'));
        error_log("  - PEŁNE DANE: " . print_r($item_data, true));
        
        return 0; // Zwróć 0 jako cenę, bo nie renderujemy wiersza
    }
    
    // Pobierz serie i kształty
    $seria_options = get_option('kv_seria_options', []);
    $ksztalt_options = get_option('kv_ksztalt_options', []);
    $kolor_mechanizmu_options = get_option('kv_kolor_mechanizmu_options', []);
    // Zdefiniuj zmienną $empty_slot_img
    $empty_slot_img = 'https://www.isdvectis.pl/wp-content/uploads/2025/04/wybor.svg';
    
    // POPRAWKA: Dodaj zabezpieczenia dla niepełnych danych
    // Pobranie danych z zapisanej konfiguracji z zabezpieczeniami
    $uklad_index = isset($item_data['uklad']) && !empty($item_data['uklad']) ? $item_data['uklad'] : 0;
    $layoutName = '';
    $uklad_img = '';
    
    // Sprawdź czy układ istnieje, jeśli nie - ustaw domyślne wartości
    if (isset($uklad_options[$uklad_index]) && !empty($uklad_options[$uklad_index]['name'])) {
        $layoutName = $uklad_options[$uklad_index]['name'];
        $uklad_img = $uklad_options[$uklad_index]['image'] ?? '';
    } else {
        $layoutName = 'Układ nie został wybrany';
        $uklad_img = '';
    }
    
    // Pobierz indeks kształtu z danych pozycji
    $ksztalt_index = isset($item_data['ksztalt']) ? $item_data['ksztalt'] : 0;
    // Pobierz kod układu - zabezpieczenia dla pustych wartości
    $uklad_code = '';
    if (!empty($layoutName) && isset($uklad_options[$uklad_index])) {
        if (isset($uklad_options[$uklad_index]['code']) && !empty($uklad_options[$uklad_index]['code'])) {
            $uklad_code = $uklad_options[$uklad_index]['code'];
            error_log("render_item_row: Pobrano kod układu z pola 'code': " . $uklad_code);
        } elseif (isset($uklad_options[$uklad_index]['snippet']) && !empty($uklad_options[$uklad_index]['snippet'])) {
            $uklad_code = $uklad_options[$uklad_index]['snippet'];
            error_log("render_item_row: Pobrano kod układu z pola 'snippet': " . $uklad_code);
        } else {
            $uklad_code = substr($layoutName, 0, 2);
            error_log("render_item_row: Pobrano kod układu z nazwy układu: " . $uklad_code);
        }
    } else {
        $uklad_code = '00'; // Domyślny kod dla nieznanego układu
    }
    
    $frame_color_index = isset($item_data['kolor_ramki']) ? $item_data['kolor_ramki'] : '';
    $frame_color_name = '';
    $frame_color_img = '';
    $frame_color_code = '';
    if ($frame_color_index !== '' && isset($kolor_ramki_options[$frame_color_index])) {
        $frame_color_name = $kolor_ramki_options[$frame_color_index]['name'] ?? '';
        $frame_color_img = $kolor_ramki_options[$frame_color_index]['image'] ?? '';
        // Pobierz kod koloru ramki - najpierw sprawdź pole 'code', potem 'snippet', na końcu pierwsze znaki nazwy
        if (isset($kolor_ramki_options[$frame_color_index]['code']) && !empty($kolor_ramki_options[$frame_color_index]['code'])) {
            $frame_color_code = $kolor_ramki_options[$frame_color_index]['code'];
            error_log("render_item_row: Pobrano kod koloru ramki z pola 'code': " . $frame_color_code);
        } elseif (isset($kolor_ramki_options[$frame_color_index]['snippet']) && !empty($kolor_ramki_options[$frame_color_index]['snippet'])) {
            $frame_color_code = $kolor_ramki_options[$frame_color_index]['snippet'];
            error_log("render_item_row: Pobrano kod koloru ramki z pola 'snippet': " . $frame_color_code);
        } else {
            $frame_color_code = substr($frame_color_name, 0, 2);
            error_log("render_item_row: Pobrano kod koloru ramki z nazwy koloru: " . $frame_color_code);
        }
    } else {
        $frame_color_name = 'Kolor ramki nie został wybrany';
        $frame_color_code = '00';
    }
    
    // Określenie liczby slotów z zabezpieczeniami
    $ileSlotow = 1; // Domyślnie 1 slot
    if (!empty($layoutName)) {
        if (preg_match('/X(\\d+)/i', $layoutName, $matches)) {
            $ileSlotow = (int) $matches[1];
        } elseif (stripos($layoutName, 'pionowy') !== false || stripos($layoutName, 'poziomy') !== false) {
            $ileSlotow = 2;
        }
    }
    
    // Dodaj orientację z zabezpieczeniami
    $orientation_class = 'horizontal'; // Domyślnie poziomy
    if (!empty($layoutName)) {
        if (stripos($layoutName, 'poziomy') !== false) {
            $orientation_class = 'horizontal';
        } elseif (stripos($layoutName, 'pionowy') !== false) {
            $orientation_class = 'vertical';
        } elseif ($ileSlotow == 1) {
            $orientation_class = 'horizontal'; // Dla pojedynczego slotu domyślnie poziomo
        }
    }
    
    // Inicjalizuj $slotData jako tablicę przed pierwszym użyciem
    $slotData = [];
    
    // Wypełnij $slotData danymi z $item_data
    for ($i = 0; $i < $ileSlotow; $i++) {
        $mechID = isset($item_data['mechanizm_'.$i]) ? $item_data['mechanizm_'.$i] : '';
        $slotData[$i] = [
            'mechanizm' => $mechID
        ];
    }
    
    // Pobieranie danych slotów
    $slots = [];
    for ($i = 0; $i < $ileSlotow; $i++) {
        $mechID = isset($item_data['mechanizm_'.$i]) ? $item_data['mechanizm_'.$i] : '';
        
        // Dane mechanizmu
        $mech_name = 'Brak nazwy';
        $mech_img = '';
        if (isset($mechID) && $mechID !== '' && isset($mechanizm_options[$mechID])) {
            $mech_name = $mechanizm_options[$mechID]['name'] ?? 'Brak nazwy';
            // Priorytet dla frame_image, fallback do image
            if (!empty($mechanizm_options[$mechID]['frame_image'])) {
                $mech_img = $mechanizm_options[$mechID]['frame_image'];
            } elseif (!empty($mechanizm_options[$mechID]['image'])) {
                $mech_img = $mechanizm_options[$mechID]['image']; // Fallback
            }
        }
        
        $techID = isset($item_data['technologia_'.$i]) ? $item_data['technologia_'.$i] : '';
        $colorVal = isset($item_data['kolor_mechanizmu_'.$i]) ? $item_data['kolor_mechanizmu_'.$i] : '';
        
        // Dane technologii
        $tech_name = '';
        $tech_price = 0;
        if ($techID !== '' && isset($technologia_options[$techID])) {
            $tech_data = $technologia_options[$techID];
            
            // Sprawdź, czy technologia jest powiązana z tym mechanizmem
            if (isset($tech_data['group']) && $tech_data['group'] == $mechID) {
                $tech_name = $tech_data['technology'] ?? '';
                $found_matching_tech = false;
                
                // Sprawdź zgodność koloru tylko jeśli oba kolory są określone
                $color_matched = true; // Domyślnie zakładamy zgodność koloru (lub jego brak)
                
                // Najpierw szukamy dokładnego dopasowania dla mechanizmu i koloru
                $found_exact_match = false;
                
                // Sprawdź, czy wybrany kolor został określony
                if (!empty($colorVal)) {
                    // Zapisz pierwotną technologię przed szukaniem alternatyw
                    $original_tech_data = $tech_data;
                    $original_tech_name = $tech_name;
                    
                    error_log("Slot {$i} - Szukam technologii dla mechanizmu {$mechID} i koloru {$colorVal}");
                    
                    // Przeszukaj technologie - priorytet dla technologii z pasującym kolorem
                    foreach ($technologia_options as $tech_id => $tech) {
                        if (isset($tech['group']) && $tech['group'] == $mechID &&
                            isset($tech['color']) && $tech['color'] == $colorVal) {
                            
                            // Znaleziono DOKŁADNE dopasowanie - użyj tej technologii zamiast pierwotnie wybranej
                            $tech_data = $tech;
                            $tech_name = $tech['technology'] ?? '';
                            $found_exact_match = true;
                            
                            error_log("Slot {$i} - DOKŁADNE dopasowanie koloru w technologii {$tech_id} - będzie użyta do ceny");
                            break;
                        }
                    }
                    
                    if (!$found_exact_match && isset($tech_data['color']) && $tech_data['color'] == $colorVal) {
                        // Jeśli nie znaleźliśmy innej technologii, ale wybrana technologia ma pasujący kolor
                        $found_exact_match = true;
                        error_log("Slot {$i} - Wybrana technologia {$techID} ma pasujący kolor {$colorVal}");
                    }
                    
                    if (!$found_exact_match) {
                        // Jeśli nie znaleziono dokładnego dopasowania, sprawdź czy pierwotna technologia ma kolor
                        error_log("Slot {$i} - Brak dokładnego dopasowania koloru dla mechanizmu {$mechID} i koloru {$colorVal}");
                        
                        // Wróć do pierwotnie wybranej technologii jeśli nie znaleziono lepszego dopasowania
                        $tech_data = $original_tech_data;
                        $tech_name = $original_tech_name;
                    }
                } else {
                    // Brak określonego koloru, używamy wybranej technologii
                    $found_exact_match = true;
                    error_log("Slot {$i} - Brak określonego koloru, używam technologii {$techID}");
                }
                
                // Upewnij się, że cena jest liczbą
                $tech_price = isset($tech_data['price']) ? floatval(str_replace(',', '.', $tech_data['price'])) : 0;
                error_log("Slot {$i} - Ustawiono cenę {$tech_price} dla " . 
                         ($found_exact_match ? "dokładnie dopasowanej" : "najbliżej dopasowanej") . 
                         " technologii i koloru " . 
                         (!empty($colorVal) ? "{$colorVal}" : "brak koloru"));
            } else {
                error_log("Slot {$i} - Niezgodność technologii ({$techID}) z mechanizmem ({$mechID})");
            }
        }
        
        // Nazwa koloru (może być potrzebna dla wyświetlenia)
        $colorName = '';
        if ($colorVal !== '' && isset($kolor_mechanizmu_options[$colorVal]['name'])) {
            $colorName = $kolor_mechanizmu_options[$colorVal]['name'];
        }
        
        $slots[$i] = [
            'mechanizm_id' => $mechID,
            'mechanizm_name' => $mech_name,
            'mechanizm_img' => $mech_img, // Dodaj obraz mechanizmu
            'technologia_id' => $techID,
            'technologia' => $tech_name,
            'kolor_mech_id' => $colorVal, // Dodajemy ID koloru mechanizmu
            'kolor_mech' => $colorName,   // Dodajemy nazwę koloru mechanizmu
            'cena' => $tech_price
        ];
    }
    
    // Obliczanie ceny jednostkowej z zabezpieczeniami
    $cena_jednostkowa = 0;
    
    // Dodanie ceny ramki do ceny jednostkowej
    $cena_ramki = 0;
    
    // Wybieramy odpowiednią cenę ramki w zależności od liczby slotów (z zabezpieczeniami)
    $frame_price_key = 'price_x' . $ileSlotow;
    $frame_price = '';
    
    // Sprawdź czy kolor ramki został wybrany
    if ($frame_color_index !== '' && isset($kolor_ramki_options[$frame_color_index])) {
        if (isset($kolor_ramki_options[$frame_color_index][$frame_price_key]) && !empty($kolor_ramki_options[$frame_color_index][$frame_price_key])) {
            // Jeśli istnieje cena dla konkretnego układu
            $frame_price = $kolor_ramki_options[$frame_color_index][$frame_price_key];
            error_log("render_item_row: Użyto ceny dla układu X{$ileSlotow}: {$frame_price}");
        } elseif (isset($kolor_ramki_options[$frame_color_index]['price']) && !empty($kolor_ramki_options[$frame_color_index]['price'])) {
            // Jeśli brak ceny dla konkretnego układu, użyj ceny ogólnej (kompatybilność wsteczna)
            $frame_price = $kolor_ramki_options[$frame_color_index]['price'];
            error_log("render_item_row: Użyto ogólnej ceny ramki: {$frame_price} (brak ceny dla X{$ileSlotow})");
        }
    }
    
    if (!empty($frame_price)) {
        $cena_ramki = floatval(str_replace(',', '.', $frame_price));
    }
    
    // Suma cen wszystkich mechanizmów
    foreach ($slots as $slot) {
        $cena_jednostkowa += $slot['cena'];
    }
    
    // Dodajemy cenę ramki do ceny jednostkowej
    $cena_jednostkowa += $cena_ramki;
    
    // Jeśli cena jest zerowa, ustawiamy domyślnie 1
    $cena_jednostkowa = ($cena_jednostkowa > 0) ? $cena_jednostkowa : 1;
    
    // Ilość
    $quantity = isset($item_data['quantity']) ? (int) $item_data['quantity'] : 1;
    $cena_calkowita = $cena_jednostkowa * $quantity;
    
    // Kod produktu (generowanie z zabezpieczeniami dla niepełnych danych)
    
    // Generowanie kodu produktu z zabezpieczeniami
    // Format: Wybrana seria (kod) + Wybrany kształt (kod) + 0 (liczba kontrolna) - wybrany mechanizm (kod) - wybrany układ (kod) - kolor ramki (kod)
    // Przykład: ISDR0-12345-11P2

    // Kod serii - pobieramy z pola 'fragment' w zapisanej serii
    $seria_name = isset($item_data['seria']) ? $item_data['seria'] : '';
    $seria_code = 'IS'; // Domyślna wartość

    // Poszukaj serii w tablicy opcji i pobierz jej fragment (tylko jeśli seria została wybrana)
    if (!empty($seria_name)) {
        foreach ($seria_options as $seria_option) {
            if (isset($seria_option['name']) && $seria_option['name'] === $seria_name && isset($seria_option['fragment'])) {
                $seria_code = $seria_option['fragment'];
                break;
            }
        }
    }

    // Kod kształtu - pobieramy z pola 'snippet' w bazie danych
    $ksztalt_code = '0'; // Domyślna wartość
    if (isset($ksztalt_options[$ksztalt_index]['snippet']) && !empty($ksztalt_options[$ksztalt_index]['snippet'])) {
        $ksztalt_code = $ksztalt_options[$ksztalt_index]['snippet'];
    }

    // Pobieramy cząstki kodu (snippet) ze wszystkich mechanizmów w slotach i łączymy je
    $mech_code = '';
    for ($i = 0; $i < $ileSlotow; $i++) {
        $mechID = isset($item_data['mechanizm_'.$i]) ? $item_data['mechanizm_'.$i] : '';
        
        // Pobierz cząstkę kodu mechanizmu (snippet)
        $slot_mech_code = '';
        
        // Sprawdź czy mechanizm został wybrany i ma snippet
        if ($mechID !== '' && isset($mechanizm_options[$mechID]['snippet'])) {
            $slot_mech_code = $mechanizm_options[$mechID]['snippet'];
        }
        else if (!empty($mechID) && isset($mechanizm_options[(int)$mechID]['snippet'])) {
            $slot_mech_code = $mechanizm_options[(int)$mechID]['snippet'];
        }
        else if (!empty($mechID) && isset($mechanizm_options[(string)$mechID]['snippet'])) {
            $slot_mech_code = $mechanizm_options[(string)$mechID]['snippet'];
        }
        else if (!empty($mechID)) {
            foreach ($mechanizm_options as $mech_key => $mech_value) {
                if ((string)$mech_key === (string)$mechID || (int)$mech_key === (int)$mechID) {
                    if (isset($mech_value['snippet'])) {
                        $slot_mech_code = $mech_value['snippet'];
                        break;
                    }
                }
            }
        }
        
        // Jeśli brak snippet dla mechanizmu, użyj zero jako placeholder
        if (empty($slot_mech_code)) {
            $slot_mech_code = '0';
        }
        
        // Dodaj cząstkę kodu mechanizmu do łącznego kodu
        $mech_code .= $slot_mech_code;
    }

    // Uzupełnij zerami do 5 znaków
    if (empty($mech_code)) {
        $mech_code = '00000';
    } else {
        $mech_code = str_pad($mech_code, 5, '0');
    }

    // Składanie końcowego kodu produktu
    $product_code = strtoupper($seria_code . $ksztalt_code . "0-" . $mech_code . "-" . $uklad_code . $frame_color_code);

    // Wyświetlenie wiersza
    ?>
    <tr>
        <!-- L.P. -->
        <td><?php echo $row_number; ?></td>
        
        <!-- RAMKA -->
        <td>
            <!-- dla zapisanych wcześniej pozycji -->
                         <div class="ramka-slots <?php echo esc_attr($orientation_class); ?>" data-slots="<?php echo esc_attr($ileSlotow); ?>">
                <div class="ramka-image-container">
                    <?php for ($i = 0; $i < $ileSlotow; $i++):
                        $mechID = isset($slotData[$i]['mechanizm']) ? $slotData[$i]['mechanizm'] : '';
                        $slotImg = '';
                        if (isset($mechID) && $mechID !== '' && isset($mechanizm_options[$mechID])) {
                            if (!empty($mechanizm_options[$mechID]['frame_image'])) {
                                $slotImg = $mechanizm_options[$mechID]['frame_image'];
                            } elseif (!empty($mechanizm_options[$mechID]['image'])) {
                                $slotImg = $mechanizm_options[$mechID]['image'];
                            }
                        }
                    ?>
                        <div class="slot">
                            <?php if ($slotImg): ?>
                                <img src="<?php echo esc_url($slotImg); ?>" alt="Slot <?php echo $i+1; ?>">
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Informacje o ramce i kodzie produktu pod obrazkiem -->
            <div style="margin-top:10px;">
                <strong>
                    <?php
                    // Wyświetl "Nazwa w podsumowaniu" jeśli istnieje, w przeciwnym razie zwykłą nazwę układu
                    if (isset($uklad_options[$uklad_index]['summary_name']) && !empty($uklad_options[$uklad_index]['summary_name'])) {
                        echo esc_html($uklad_options[$uklad_index]['summary_name']);
                    } else {
                        echo esc_html($layoutName);
                    }
                    ?>
                </strong><br>
                <?php if ($frame_color_name): ?>
                    <span>Kolor ramki: <?php echo esc_html($frame_color_name); ?></span>
                    <?php if ($frame_color_img): ?>
                        <div style="margin-top: 5px;">
                            <img src="<?php echo esc_url($frame_color_img); ?>" alt="<?php echo esc_attr($frame_color_name); ?>" style="max-width: 50px; height: auto; border: 1px solid #ccc;">
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                      
                <!-- Edytor koloru ramki -->
                <div class="edytuj-kolor-ramki">
                    <strong>Edytuj kolor ramki:</strong><br>
                    <form method="post" style="margin-top: 10px;">
                        <?php wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); ?>
                        
                        <select name="<?php echo $is_current ? 'frame_color_current' : 'frame_color_' . $item_index; ?>" 
                                id="frame-color-select-<?php echo $is_current ? 'current' : $item_index; ?>" 
                                style="width: 100%; margin-bottom: 10px;" 
                                onchange="updateFrameColorPreview(this, '<?php echo $is_current ? 'current' : $item_index; ?>')">
                            <option value="">Wybierz kolor ramki</option>
                            <?php foreach ($kolor_ramki_options as $k_index => $k_item): ?>
                                <option value="<?php echo esc_attr($k_index); ?>" 
                                        data-image="<?php echo esc_attr($k_item['image'] ?? ''); ?>"
                                        <?php selected($frame_color_index, $k_index); ?>>
                                    <?php echo esc_html($k_item['name'] ?? 'Bez nazwy'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        
                        <button type="submit" 
                                name="<?php echo $is_current ? 'update_frame_color_current' : 'update_frame_color_' . $item_index; ?>" 
                                class="button button-small" 
                                style="width: 100%; background: #0073aa; color: white; border: none; padding: 8px; border-radius: 3px; cursor: pointer;">
                          Aktualizuj
                        </button>
                    </form>
                <!-- Kod produktu -->
                <div class="kod-produktu">
                    <strong>Kod produktu:</strong><br>
                    <?php echo esc_html($product_code); ?>
                    <?php 
                    // Wybieramy odpowiednią cenę ramki w zależności od liczby slotów (z zabezpieczeniami)
                    $frame_price_key = 'price_x' . $ileSlotow;
                    $frame_price = '';
                    
                    // Sprawdź czy kolor ramki został wybrany
                    if ($frame_color_index !== '' && isset($kolor_ramki_options[$frame_color_index])) {
                        if (isset($kolor_ramki_options[$frame_color_index][$frame_price_key]) && !empty($kolor_ramki_options[$frame_color_index][$frame_price_key])) {
                            // Jeśli istnieje cena dla konkretnego układu
                            $frame_price = $kolor_ramki_options[$frame_color_index][$frame_price_key];
                        } elseif (isset($kolor_ramki_options[$frame_color_index]['price']) && !empty($kolor_ramki_options[$frame_color_index]['price'])) {
                            // Jeśli brak ceny dla konkretnego układu, użyj ceny ogólnej (kompatybilność wsteczna)
                            $frame_price = $kolor_ramki_options[$frame_color_index]['price'];
                        }
                    }
                    
                    if (!empty($frame_price)): 
                    ?>
                        <br><br><strong>Cena ramki (<?php echo $ileSlotow; ?> s.<?php echo $ileSlotow !== 1 ? '' : ''; ?>):</strong> <?php echo esc_html($frame_price); ?> zł
                    <?php endif; ?>
                </div>
          

                </div>
            </div>
        </td>
        
        <!-- MECHANIZMY -->
        <td>
            <?php foreach ($slots as $slot): ?>
                <div style="margin-bottom:10px;border-bottom:1px dotted #ccc;padding-bottom:5px;">
                    <!-- Nazwa mechanizmu -->
                    <strong><?php echo esc_html($slot['mechanizm_name']); ?></strong><br>
                    
                    <!-- Technologia -->
                    <?php if (!empty($slot['technologia'])): ?>
                        Technologia: <?php echo esc_html($slot['technologia']); ?><br>
                    <?php endif; ?>
                    
                    <!-- Kolor mechanizmu -->
                    <?php if (!empty($slot['kolor_mech'])): ?>
                        Kolor: <?php echo esc_html($slot['kolor_mech']); ?><br>
                    <?php endif; ?>
                    
                    <!-- Cena mechanizmu -->
                    <?php if (isset($slot['cena']) && $slot['cena'] > 0): ?>
                        <div class="cena-mechanizmu"><strong>Cena mechanizmu:</strong> <?php echo number_format($slot['cena'], 2, ',', ' '); ?> zł</div>
                    <?php endif; ?>
                    
                    <!-- Kod mechanizmu (jeśli jest dostępny) -->
                    <?php
                    // Pobierz dane mechanizmu, technologii i koloru
                    $mechID = $slot['mechanizm_id'];
                    $techID = $slot['technologia_id'];
                    $colorID = isset($slot['kolor_mech_id']) ? $slot['kolor_mech_id'] : '';
                    $tech_name = isset($slot['technologia']) ? $slot['technologia'] : '';
                    $tech_code = '';
                    
                    // Pobierz wszystkie technologie
                    $technologie = kv_get_items('kv_technologia_options');
                    
                    // NAPRAWKA: Szukaj technologii po kombinacji mechanizm + technologia + kolor
                    if (!empty($technologie) && $mechID !== '') {
                        // Przeszukaj wszystkie technologie szukając dokładnego dopasowania
                        foreach ($technologie as $tech_id => $tech_item) {
                            // Sprawdź czy technologia należy do właściwego mechanizmu
                            if (isset($tech_item['group']) && $tech_item['group'] == $mechID) {
                                // Sprawdź czy nazwa technologii się zgadza (jeśli jest dostępna)
                                $tech_matches_name = true;
                                if (!empty($tech_name) && isset($tech_item['technology'])) {
                                    $tech_matches_name = ($tech_item['technology'] === $tech_name);
                                }
                                
                                // Sprawdź czy kolor się zgadza (jeśli był wybrany)
                                $color_matches = true;
                                if (!empty($colorID) && isset($tech_item['color'])) {
                                    $color_matches = ($tech_item['color'] == $colorID);
                                }
                                
                                // Jeśli wszystko się zgadza (mechanizm, technologia, kolor) - użyj tego kodu
                                if ($tech_matches_name && $color_matches && isset($tech_item['code'])) {
                                    $tech_code = $tech_item['code'];
                                    error_log("PODSUMOWANIE: Znaleziono kod technologii dla mechID={$mechID}, colorID={$colorID}, tech={$tech_name}: {$tech_code}");
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Wyświetl kod mechanizmu, jeśli istnieje
                    if (!empty($tech_code)):
                    ?>
                       <div class="product-code" style="margin-top:10px; padding:5px; background:#f8f8f8; border:1px solid #ddd;"><?php echo esc_html($tech_code); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </td>
        
        <!-- ILOŚĆ -->
        <td>
            <?php if ($is_current): ?>
                <form method="post" style="display: inline-block; margin: 0;">
                    <?php wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); ?>
                    <input type="hidden" name="current_item_index" value="current">
                    <input type="number" name="current_quantity" value="<?php echo esc_attr($quantity); ?>" min="1" style="width:60px;">
                    <button type="submit" name="update_current_quantity" class="button-small">Aktualizuj</button>
                </form>
            <?php else: ?>
                <form method="post" style="display: inline-block; margin: 0;">
                    <?php wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); ?>
                    <input type="hidden" name="saved_item_index_<?php echo $item_index; ?>" value="<?php echo $item_index; ?>">
                    <input type="number" name="saved_quantity_<?php echo $item_index; ?>" value="<?php echo esc_attr($quantity); ?>" min="1" style="width:60px;">
                    <button type="submit" name="update_saved_item_quantity_<?php echo $item_index; ?>" class="button-small">Aktualizuj</button>
                </form>
            <?php endif; ?>
        </td>
        
        <!-- CENA JEDNOSTKOWA -->
        <td title="Suma ceny ramki i wszystkich mechanizmów"><?php echo number_format($cena_jednostkowa, 2, ',', ' '); ?> zł</td>
        
        <!-- CENA CAŁOŚĆ -->
        <td><?php echo number_format($cena_calkowita, 2, ',', ' '); ?> zł</td>
        
        <!-- AKCJE -->
        <td class="summary-akcje">
            <div style="display: flex; flex-direction: column; gap: 5px; align-items: center;">
                <?php if ($is_current): ?>
                    <!-- Przyciski dla bieżącej konfiguracji -->
                    <form method="post" style="margin: 0;" onsubmit="return confirm('Czy na pewno chcesz wyczyścić bieżącą konfigurację?');">
                        <?php wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); ?>
                        <button type="submit" name="clear_current_config" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;" title="Wyczyść pozycję">
                            🗑️ Usuń
                        </button>
                    </form>
                    <form method="post" style="margin: 0;">
                        <?php wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); ?>
                        <input type="hidden" name="edit_current_config" value="1">
                        <button type="submit" style="background: #007cba; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;" title="Edytuj pozycję">
                            ✏️ Edytuj
                        </button>
                    </form>
                <?php else: ?>
                    <!-- Przyciski dla zapisanych pozycji -->
                    <form method="post" style="margin: 0;" onsubmit="return confirm('Czy na pewno chcesz wyczyścić tę pozycję?');">
                        <?php wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); ?>
                        <input type="hidden" name="delete_item" value="<?php echo $item_index; ?>">
                        <button type="submit" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;" title="Wyczyść pozycję">
                            🗑️ Usuń
                        </button>
                    </form>
                    <form method="post" style="margin: 0;">
                        <?php wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); ?>
                        <input type="hidden" name="edit_item" value="<?php echo $item_index; ?>">
                        <button type="submit" style="background: #007cba; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;" title="Edytuj pozycję">
                            ✏️ Edytuj
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php
    
    return $cena_calkowita;
}
?>

<div class="step-content">
    <?php
    // Sprawdź czy jesteśmy w trybie edycji zamówienia
    if (isset($_SESSION['kv_configurator']['editing_order_id'])) {
        $editing_order_id = $_SESSION['kv_configurator']['editing_order_id'];
        $editing_order_number = $_SESSION['kv_configurator']['editing_order_number'];
        echo '<div class="notice notice-info" style="padding: 15px; margin: 20px 0; background: #e1f5fe; border: 1px solid #81d4fa; border-radius: 5px; color: #01579b;">';
        echo '<h3 style="margin: 0 0 10px 0;">🔧 Tryb edycji zamówienia</h3>';
        echo '<p style="margin: 0;">Edytujesz zamówienie: <strong>' . esc_html($editing_order_number) . '</strong> (ID: ' . esc_html($editing_order_id) . ')</p>';
        echo '<p style="margin: 10px 0 0 0;"><small>Po zapisaniu zmiany zostaną zastosowane do istniejącego zamówienia.</small></p>';
        echo '</div>';
    }
    ?>
    <h2>Krok 5: Podsumowanie</h2>

    <form method="post" action="" id="update-quantity-form">
        <?php wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); ?>
        <!-- Ukryte pole do przechowywania numeru zamówienia klienta -->
        <input type="hidden" name="customer_order_number" id="hidden_customer_order_number" 
               value="<?php echo isset($_SESSION['kv_configurator']['customer_order_number']) ? esc_attr($_SESSION['kv_configurator']['customer_order_number']) : ''; ?>">
        <!-- Ukryte pole do przechowywania uwag do zamówienia -->
        <input type="hidden" name="order_notes" id="hidden_order_notes" 
               value="<?php echo isset($_SESSION['kv_configurator']['order_notes']) ? esc_attr($_SESSION['kv_configurator']['order_notes']) : ''; ?>">
               
        <table class="summary-table">
            <thead>
                <tr>
                    <th class="summary-lp">Lp.</th>
                    <th class="summary-ramka" data-slots="<?php echo $ileSlotow; ?>">Ramka</th>
                    <th class="summary-mechanizmy">Mechanizmy</th>
                    <th class="summary-ilosc">Ilość</th>
                    <th class="summary-cena-jedn" title="Suma ceny ramki i wszystkich mechanizmów">Cena jedn.</th>
                    <th class="summary-cena-calosc">Cena całość</th>
                    <th class="summary-akcje">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Wyświetlanie zapisanych wcześniej pozycji
                $total_price = 0;
                $row_number = 1; // Dodana zmienna do numeracji kolejnej
                if (isset($_SESSION['kv_configurator']['items']) && !empty($_SESSION['kv_configurator']['items'])) {
                    foreach ($_SESSION['kv_configurator']['items'] as $item_index => $item_data) {
                        $total_price += render_item_row($row_number, $item_data, $uklad_options, $kolor_ramki_options, $mechanizm_options, $technologia_options, false, $item_index);
                        $row_number++; // Zwiększ numer wiersza
                    }
                }
                
                // Ustalamy numer bieżącej pozycji
                $current_position = isset($_SESSION['kv_configurator']['items']) ? count($_SESSION['kv_configurator']['items']) : 0;
                
                // Przygotuj dane bieżącej konfiguracji dla funkcji render_item_row
                $current_config_data = $_SESSION['kv_configurator'];
                
                // Sprawdź czy bieżąca konfiguracja zawiera wystarczające dane do wyświetlenia
                // ZAOSTRZONE WARUNKI: Wymaga co najmniej serii, kształtu I układu, aby wyświetlić bieżącą konfigurację
                $has_valid_current_config = false;
                
                // JEDYNY WARUNEK: Seria I kształt I układ - w pełni skonfigurowana pozycja
                // UWAGA: ksztalt i uklad są intami, więc sprawdzamy isset i != '' zamiast empty()
                if ((isset($current_config_data['seria']) && !empty($current_config_data['seria'])) &&
                    (isset($current_config_data['ksztalt']) && $current_config_data['ksztalt'] !== '' && $current_config_data['ksztalt'] !== null) &&
                    (isset($current_config_data['uklad']) && $current_config_data['uklad'] !== '' && $current_config_data['uklad'] !== null)) {
                    $has_valid_current_config = true;
                }
                
                // DEBUG: Log warunków sprawdzenia
                error_log("PODSUMOWANIE - Sprawdzanie bieżącej konfiguracji:");
                error_log("  - seria: " . (isset($current_config_data['seria']) ? '"'.$current_config_data['seria'].'"' : 'BRAK'));
                error_log("  - ksztalt: " . (isset($current_config_data['ksztalt']) ? '"'.$current_config_data['ksztalt'].'"' : 'BRAK'));
                error_log("  - uklad: " . (isset($current_config_data['uklad']) ? '"'.$current_config_data['uklad'].'"' : 'BRAK'));
                error_log("  - has_valid_current_config: " . ($has_valid_current_config ? 'TAK' : 'NIE'));
                error_log("  - CAŁA SESJA: " . print_r($current_config_data, true));
                
                // Renderuj bieżącą konfigurację jeśli ma jakiekolwiek dane
                if ($has_valid_current_config) {
                    $total_price += render_item_row($row_number, $current_config_data, $uklad_options, $kolor_ramki_options, $mechanizm_options, $technologia_options, true, null);
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5"></td>
                    <td><strong>Razem:</strong></td>
                    <td><strong id="suma_calosc"><?php echo number_format($total_price, 2, ',', ' '); ?> zł</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </form>

<!-- Pole na własny numer zamówienia klienta zostało przeniesione do głównego formularza -->

<!-- Uwaga: Przyciski akcji, pola numeru zamówienia i formularz zostały przeniesione do buttons_row.php -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aktualizacja ceny po zmianie ilości bez przesyłania formularza
    const quantityInput = document.getElementById('quantity');
    
    quantityInput.addEventListener('change', function() {
        const quantity = Math.max(1, parseInt(this.value) || 1);
        // Pobieramy aktualną cenę jednostkową (już zawierającą cenę ramki + ceny mechanizmów)
        const cenaJednostkowa = <?php echo $cena_jednostkowa; ?>;
        const cenaCalosc = quantity * cenaJednostkowa;
        
        // Formatowanie liczby z dwoma miejscami po przecinku
        const formattedPrice = new Intl.NumberFormat('pl-PL', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(cenaCalosc);
        
        // Aktualizacja wyświetlanej ceny
        document.getElementById('cena_calkowita').textContent = formattedPrice + ' zł';
        
        // Zaktualizuj sumę całkowitą
        const currentTotal = <?php echo $total_price; ?>;
        const newTotal = currentTotal + cenaCalosc;
        document.getElementById('suma_calosc').textContent = 
            new Intl.NumberFormat('pl-PL', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(newTotal) + ' zł';
    });
    
    // Kod synchronizacji pola numeru zamówienia został przeniesiony do buttons_row.php
});

// Funkcja do czyszczenia bieżącej konfiguracji
function clearCurrentConfiguration() {
    if (confirm('Czy na pewno chcesz wyczyścić bieżącą konfigurację?')) {
        // Wysłij zapytanie AJAX do serwera aby wyczyścić sesję
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const nonceField = document.createElement('input');
        nonceField.type = 'hidden';
        nonceField.name = 'kv_configurator_nonce';
        nonceField.value = '<?php echo wp_create_nonce('kv_configurator_submit'); ?>';
        
        const actionField = document.createElement('input');
        actionField.type = 'hidden';
        actionField.name = 'clear_current_config';
        actionField.value = '1';
        
        form.appendChild(nonceField);
        form.appendChild(actionField);
        document.body.appendChild(form);
        form.submit();
    }
}

// Funkcja do aktualizacji podglądu koloru ramki
function updateFrameColorPreview(selectElement, itemId) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const imageUrl = selectedOption.getAttribute('data-image');
    const previewContainer = document.getElementById('frame-color-preview-' + itemId);
    
    if (imageUrl && imageUrl.trim() !== '') {
        previewContainer.innerHTML = '<img src="' + imageUrl + '" alt="Podgląd koloru" style="max-width: 80px; height: auto; border: 1px solid #ccc; border-radius: 3px;">';
    } else {
        previewContainer.innerHTML = '<div style="padding: 20px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 3px; text-align: center; color: #666;">Brak podglądu</div>';
    }
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funkcja do dodawania klasy kształtu do kontenera ramki
    function applyShapeClass() {
        // Pobierz nazwę kształtu z sessionStorage
        const shapeName = sessionStorage.getItem('selected_shape_name');
        if (!shapeName) return;
        
        // Konwertuj nazwę na małe litery i usuwamy białe znaki
        const shapeClass = shapeName.toLowerCase().trim();
        
        // Pobierz wszystkie kontenery ramek
        const imageContainers = document.querySelectorAll('.ramka-image-container');
        imageContainers.forEach(container => {
            // Usuń wszystkie potencjalne klasy kształtów
            container.classList.remove('square', 'round', 'cube');
            
            // Dodaj odpowiednią klasę zależnie od nazwy kształtu
            container.classList.add(shapeClass);
        });
    }
    
    // Wywołaj funkcję przy ładowaniu strony
    applyShapeClass();
});
</script>