<?php
defined('ABSPATH') or die('Brak dostępu');

/**
 * 1. Start sesji, jeśli nie ma
 */
if (!function_exists('kv_start_session')) {
    function kv_start_session() {
        if (!headers_sent() && !session_id()) {
            @session_start();
        }
    }
}
add_action('init', 'kv_start_session', 1);

/**
 * 2. Obsługa globalnych akcji (Zapisz / Anuluj) - brak Wstecz/Dalej tutaj!
 *
 * Zwróć uwagę, że używamy tej samej akcji "kv_configurator_submit"
 * i tego samego pola "kv_configurator_nonce" co w shortcodzie.
 */
add_action('init', 'kv_handle_global_actions');
function kv_handle_global_actions() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['kv_configurator_nonce'])
        && wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')
    ) {
        // Kliknięto „Zapisz" lub „Zapisz jako wersję roboczą" -> zapisz zamówienie do bazy danych
        if (isset($_POST['kv_global_save']) || isset($_POST['kv_global_save_draft'])) {
            // Sprawdź czy jesteśmy w kroku 5 (podsumowanie)
            $current_step = isset($_REQUEST['step']) ? intval($_REQUEST['step']) : 1;
            
            if ($current_step === 5) {
                // Zapisz numer zamówienia klienta do sesji, jeśli został podany
                if (isset($_POST['customer_order_number'])) {
                    $_SESSION['kv_configurator']['customer_order_number'] = sanitize_text_field($_POST['customer_order_number']);
                }
                
                // Zapisz uwagi do zamówienia do sesji, jeśli zostały podane
                if (isset($_POST['order_notes'])) {
                    $_SESSION['kv_configurator']['order_notes'] = sanitize_textarea_field($_POST['order_notes']);
                }
                
                // Sprawdź czy jest jakiekolwiek zamówienie do zapisania
                $order_data = $_SESSION['kv_configurator'] ?? [];
                $has_items = isset($order_data['items']) && !empty($order_data['items']);
                
                // Sprawdź czy jest bieżąca konfiguracja
                $current_config = $order_data;
                unset($current_config['items']); // Usuń items z bieżącej konfiguracji
                
                $has_current_config = false;
                foreach (['seria', 'ksztalt', 'uklad'] as $key) {
                    if (isset($current_config[$key]) && !empty($current_config[$key])) {
                        $has_current_config = true;
                        break;
                    }
                }
                
                // Jeśli nie ma ani zapisanych pozycji ani bieżącej konfiguracji, nie zapisuj
                if (!$has_items && !$has_current_config) {
                    wp_redirect(add_query_arg([
                        'save_error' => 2, // kod błędu: brak danych do zapisu
                        'step' => 5
                    ], home_url('/konfigurator/')));
                    exit;
                }
                
                // Przygotuj dane zamówienia
                if ($has_current_config) {
                    if (!isset($order_data['items'])) {
                        $order_data['items'] = [];
                    }
                    $order_data['items'][] = $current_config;
                }
                
                // Ustaw status zamówienia na podstawie przycisku
                if (isset($_POST['kv_global_save_draft'])) {
                    $order_data['status'] = 'draft';
                } else {
                    $order_data['status'] = 'submitted';
                }
                
                // Zapisz zamówienie do bazy danych
                error_log("Zapisywanie zamówienia do bazy - dane do zapisania: " . print_r($order_data, true));
                
                // Sprawdź czy edytujemy istniejące zamówienie
                if (isset($order_data['editing_order_id'])) {
                    $editing_order_id = $order_data['editing_order_id'];
                    $editing_order_number = $order_data['editing_order_number'];
                    
                    // Usuń metadane dotyczące edycji z danych zamówienia
                    unset($order_data['editing_order_id'], $order_data['editing_order_number']);
                    
                    // Zaktualizuj istniejące zamówienie
                    $order_id = kv_update_existing_order($editing_order_id, $order_data);
                    error_log("Aktualizacja istniejącego zamówienia - ID: " . ($order_id ? $editing_order_id : 'FALSE'));
                    
                    if ($order_id) {
                        // Wyczyść sesję po zapisaniu
                        unset($_SESSION['kv_configurator']);
                        error_log("Zamówienie zaktualizowane pomyślnie, ID: " . $editing_order_id . ", przekierowywanie z komunikatem sukcesu");
                        
                        // Przekieruj z komunikatem o sukcesie aktualizacji
                        wp_redirect(add_query_arg([
                            'saved' => 1,
                            'order_updated' => 1,
                            'step' => 5
                        ], home_url('/konfigurator/')));
                        exit;
                    } else {
                        error_log("Błąd podczas aktualizacji zamówienia ID: " . $editing_order_id);
                        // Błąd aktualizacji - przekieruj z komunikatem błędu
                        wp_redirect(add_query_arg([
                            'save_error' => 3, // nowy kod błędu dla aktualizacji
                            'step' => 5
                        ], home_url('/konfigurator/')));
                        exit;
                    }
                } else {
                    // Tworzenie nowego zamówienia (standardowa logika)
                    $order_id = kv_save_configurator_order($order_data);
                    error_log("Wynik zapisywania nowego zamówienia - ID: " . ($order_id ? $order_id : 'FALSE'));
                    
                    if ($order_id) {
                        // Wyczyść sesję po zapisaniu
                        unset($_SESSION['kv_configurator']);
                        error_log("Zamówienie zapisane pomyślnie, ID: " . $order_id . ", przekierowywanie z komunikatem sukcesu");
                        
                        // Przekieruj z komunikatem o sukcesie
                        wp_redirect(add_query_arg([
                            'saved' => 1,
                            'order_saved' => 1,
                            'step' => 5
                        ], home_url('/konfigurator/')));
                        exit;
                    } else {
                        error_log("Błąd podczas zapisywania nowego zamówienia do bazy danych");
                        // Błąd zapisywania - przekieruj z komunikatem błędu
                        wp_redirect(add_query_arg([
                            'save_error' => 1,
                            'step' => 5
                        ], home_url('/konfigurator/')));
                        exit;
                    }
                }
            } else {
                // Dla innych kroków - standardowe zapisywanie do usermeta
                kv_save_config_state();
                wp_redirect(add_query_arg('saved', 1, $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // Kliknięto „Anuluj" -> reset sesji i redirect
        if (isset($_POST['kv_global_cancel'])) {
            kv_reset_config_state();
            wp_redirect(add_query_arg('step', 1, home_url('/konfigurator/')));
            exit;
        }
    }
}

/**
 * 3. Ładowanie konfiguracji z usermeta przy starcie
 */
add_action('init', 'kv_load_config_state');
function kv_load_config_state() {
    if (!is_user_logged_in()) return;

    if (!headers_sent() && !session_id()) {
        @session_start();
    }
    $user_id = get_current_user_id();
    $saved   = get_user_meta($user_id, 'kv_saved_configurator', true);
    if (!empty($saved) && is_array($saved)) {
        $_SESSION['kv_configurator'] = $saved;
    }
}

/**
 * 4. Shortcode [konfigurator-vectis] – obsługuje wszystkie kroki w jednym formularzu
 */
function kv_configurator_shortcode() {
    ob_start();

    // Obsługa parametru new_order - wyczyść sesję konfiguratora dla nowego zamówienia
    if (isset($_GET['new_order']) && $_GET['new_order'] == '1') {
        kv_reset_config_state();
        wp_redirect(remove_query_arg(['new_order', 'order_saved', 'step']));
        exit;
    }

    // Inicjuj tablicę w sesji, jeśli brak
    if (!isset($_SESSION['kv_configurator'])) {
        $_SESSION['kv_configurator'] = [];
    }

    // NOWA FUNKCJONALNOŚĆ: Ładowanie zamówienia do edycji
    if (isset($_GET['edit_order']) && !empty($_GET['edit_order'])) {
        $order_id = intval($_GET['edit_order']);
        $order_data = kv_get_order_for_edit($order_id);
        
        if ($order_data) {
            // Załaduj dane zamówienia do sesji konfiguratora
            $loaded_order_data = $order_data['order_data'];
            
            // WAŻNE: Wyczyść sesję i zachowaj tylko meta-dane oraz items
            $_SESSION['kv_configurator'] = array(
                'items' => isset($loaded_order_data['items']) ? $loaded_order_data['items'] : array(),
                'customer_order_number' => isset($loaded_order_data['customer_order_number']) ? $loaded_order_data['customer_order_number'] : '',
                'order_notes' => isset($loaded_order_data['order_notes']) ? $loaded_order_data['order_notes'] : '',
                'editing_order_id' => $order_id,
                'editing_order_number' => $order_data['order_number']
            );
            
            error_log("Załadowano zamówienie do edycji - ID: " . $order_id . ", Numer: " . $order_data['order_number'] . ", Pozycji: " . count($_SESSION['kv_configurator']['items']));
        } else {
            echo '<div class="notice notice-error"><p>Nie znaleziono zamówienia o podanym ID lub nie masz uprawnień do jego edycji.</p></div>';
        }
    }

    // Ustal aktualny krok (domyślnie 1)
    $step = 1;
    $has_step_param = false;
    
    if (isset($_REQUEST['kv_step'])) {
        $step = intval($_REQUEST['kv_step']);
        $has_step_param = true;
    } elseif (isset($_REQUEST['step'])) {
        // Obsługa parametru 'step' z URL (np. /konfigurator/?step=5)
        $step = intval($_REQUEST['step']);
        $has_step_param = true;
    }
    
    if ($step < 1) $step = 1;
    if ($step > 5) $step = 5;
    
    // Jeśli nie ma parametru step w URL i to nie jest POST request, przekieruj na step=1
    if (!$has_step_param && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        wp_redirect(add_query_arg('step', 1, remove_query_arg('step')));
        exit;
    }

    // Obsługa POST (np. wciśnięto Wstecz, Dalej, itp.)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sprawdź nonce (ten sam co globalny, 'kv_configurator_submit')
        if (!isset($_POST['kv_configurator_nonce']) 
            || !wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')
        ) {
            echo "<div class='error'>Błąd: nieprawidłowy nonce.</div>";
            return ob_get_clean();
        }

        // Obsługa Wstecz
        if (isset($_POST['go_back'])) {
            $new_step = max(1, intval($_POST['kv_step']) - 1);
            
            // Usuń znacznik trybu edycji przy cofaniu się z kroku 4
            if (intval($_POST['kv_step']) == 4 && isset($_SESSION['kv_configurator']['editing_mode'])) {
                unset($_SESSION['kv_configurator']['editing_mode']);
            }
            
            // Przekieruj z aktualizacją URL
            $current_url = remove_query_arg('step');
            $redirect_url = add_query_arg('step', $new_step, $current_url);
            wp_redirect($redirect_url);
            exit;
        }
        // Obsługa Dalej
        elseif (isset($_POST['go_next'])) {
            // Dodaj logowanie
            error_log('Przechodzę z kroku ' . $step . ' do kroku ' . ($step + 1));
            error_log('Dane przesłane w formularzu: ' . print_r($_POST, true));
            
            // Najpierw zapisz dane z bieżącego kroku
            $new_step = intval($_POST['kv_step']) + 1;
            switch (intval($_POST['kv_step'])) {
                case 1:
                    if (isset($_POST['seria'])) {
                        $_SESSION['kv_configurator']['seria'] = sanitize_text_field($_POST['seria']);
                    }
                    $new_step = 2;
                    break;
                case 2:
                    if (isset($_POST['ksztalt'])) {
                        $_SESSION['kv_configurator']['ksztalt'] = intval($_POST['ksztalt']);
                    }
                    $new_step = 3;
                    break;
                case 3:
                    if (isset($_POST['uklad'])) {
                        $_SESSION['kv_configurator']['uklad'] = intval($_POST['uklad']);
                    }
                    $new_step = 4;
                    break;
                case 4:
                    // Walidacja - sprawdź czy wszystkie pola są wypełnione
                    $valid = true;
                    $validation_errors = [];

                    // Zaraportuj wszystkie przesłane dane dla diagnozy - SZCZEGÓŁOWO
                    error_log('KROK 4 - WSZYSTKIE DANE POST: ' . print_r($_POST, true));
                    error_log('KROK 4 - WSZYSTKIE DANE SESJI przed zapisem: ' . print_r($_SESSION['kv_configurator'], true));
                    
                    // Bardziej szczegółowe raportowanie pól technologii
                    if (isset($_POST['kv_step']) && $_POST['kv_step'] == '4') {
                        $uklad_index = isset($_SESSION['kv_configurator']['uklad']) ? intval($_SESSION['kv_configurator']['uklad']) : 0;
                        $layoutName = isset($uklad_options[$uklad_index]['name']) ? $uklad_options[$uklad_index]['name'] : '';
                        
                        $ileSlotow = 1;
                        if (preg_match('/X(\d+)/i', $layoutName, $matches)) {
                            $ileSlotow = intval($matches[1]);
                        } elseif (stripos($layoutName, 'pionowy') !== false || stripos($layoutName, 'poziomy') !== false) {
                            $ileSlotow = 2;
                        }
                        
                        error_log("SZCZEGÓŁOWA ANALIZA PÓL TECHNOLOGII ({$ileSlotow} slotów):");
                        for ($slot_i = 0; $slot_i < $ileSlotow; $slot_i++) {
                            $tech_field_name = "technologia_{$slot_i}";
                            $mech_field_name = "mechanizm_{$slot_i}";
                            $mech_val = isset($_POST[$mech_field_name]) ? $_POST[$mech_field_name] : 'BRAK';
                            $tech_val = isset($_POST[$tech_field_name]) ? $_POST[$tech_field_name] : 'BRAK';
                            
                            error_log("Slot {$slot_i}: mechanizm={$mech_val}, technologia={$tech_val}");
                            error_log(" - Typ danych mechanizm: " . gettype($_POST[$mech_field_name] ?? 'BRAK'));
                            error_log(" - Typ danych technologia: " . gettype($_POST[$tech_field_name] ?? 'BRAK'));
                            
                            // Sprawdź, czy odpowiedni select istnieje w HTML
                            $html_info = "Pola w HTML: ";
                            $html_info .= "tech-select-{$slot_i}=" . (isset($_POST["tech-select-{$slot_i}"]) ? 'TAK' : 'NIE');
                            error_log($html_info);
                        }
                    }
                    
                    // Zawsze najpierw zapisz wszystko co zostało przesłane
                    foreach ($_POST as $key => $value) {
                        if (strpos($key, 'mechanizm_') === 0 || 
                            strpos($key, 'technologia_') === 0 || 
                            strpos($key, 'kolor_mechanizmu_') === 0 || 
                            $key === 'kolor_ramki') {
                            $_SESSION['kv_configurator'][$key] = sanitize_text_field($value);
                        }
                    }
                    
                    error_log('KROK 4 - WSZYSTKIE DANE SESJI po zapisie: ' . print_r($_SESSION['kv_configurator'], true));
                    
                    // Ustal liczbę slotów na podstawie wybranego układu
                    $uklad_index = isset($_SESSION['kv_configurator']['uklad']) ? intval($_SESSION['kv_configurator']['uklad']) : 0;
                    $uklad_options = get_option('kv_uklad_options', []);
                    $layoutName = isset($uklad_options[$uklad_index]['name']) ? $uklad_options[$uklad_index]['name'] : '';
                    
                    $ileSlotow = 1;
                    if (preg_match('/X(\d+)/i', $layoutName, $matches)) {
                        $ileSlotow = intval($matches[1]);
                    } elseif (stripos($layoutName, 'pionowy') !== false || stripos($layoutName, 'poziomy') !== false) {
                        $ileSlotow = 2;
                    }
                    
                    error_log("KROK 4 - Wykryto {$ileSlotow} slotów na podstawie układu: {$layoutName}");
                    
                    // WAŻNA POPRAWKA: Załaduj dane technologii PRZED ich użyciem
                    $technologia_options = get_option('kv_technologia_options', []);
                    $mechanizm_options = get_option('kv_mechanizm_options', []);
                    
                    // Debugowanie wszystkich technologii i mechanizmów
                    error_log("==== DEBUGOWANIE TECHNOLOGII I MECHANIZMÓW ====");
                    foreach ($technologia_options as $tech_id => $tech) {
                        $mech_id = isset($tech['group']) ? $tech['group'] : 'BRAK';
                        $tech_name = isset($tech['technology']) ? $tech['technology'] : 'BRAK NAZWY';
                        error_log("Technologia #{$tech_id} ({$tech_name}) -> Mechanizm #{$mech_id}");
                    }

                    // Sprawdź kolor ramki i wszystkie sloty
                    if (!isset($_POST['kolor_ramki']) || empty($_POST['kolor_ramki'])) {
                        $valid = false;
                        $validation_errors[] = 'Kolor ramki jest wymagany.';
                    }
                    
                    for ($i = 0; $i < $ileSlotow; $i++) {
                        $mech_key = 'mechanizm_' . $i;
                        $tech_key = 'technologia_' . $i;
                        $color_key = 'kolor_mechanizmu_' . $i;
                        
                        // Debug: pokaż informacje o wszystkich polach dla każdego slotu
                        error_log("Slot {$i} - sprawdzam pola:");
                        error_log(" - {$mech_key}: " . (isset($_POST[$mech_key]) ? $_POST[$mech_key] : "BRAK") . 
                                  " (typ: " . (isset($_POST[$mech_key]) ? gettype($_POST[$mech_key]) : "N/A") . ")");
                        error_log(" - {$tech_key}: " . (isset($_POST[$tech_key]) ? $_POST[$tech_key] : "BRAK"));
                        error_log(" - {$color_key}: " . (isset($_POST[$color_key]) ? $_POST[$color_key] : "BRAK"));
                        
                        // 1. Sprawdź, czy wybrany mechanizm
                        if (!isset($_POST[$mech_key]) || empty($_POST[$mech_key])) {
                            $valid = false;
                            $validation_errors[] = "Mechanizm dla slotu " . ($i+1) . " jest wymagany.";
                            error_log("BŁĄD: Brak mechanizmu dla slotu {$i}");
                            continue; // Jeśli brak mechanizmu, nie sprawdzaj dalej dla tego slotu
                        }
                        
                        // 2. Pobierz ID wybranego mechanizmu i upewnij się, że jest liczbą
                        $selected_mech_id = intval($_POST[$mech_key]);
                        error_log("Slot {$i}: Wybrany mechanizm ID={$selected_mech_id}");
                        
                        // 3. Dodatkowa diagnostyka - sprawdź czy mechanizm istnieje w bazie
                        if (!isset($mechanizm_options[$selected_mech_id])) {
                            error_log("UWAGA: Mechanizm o ID={$selected_mech_id} nie istnieje w bazie!");
                        } else {
                            error_log("OK: Mechanizm {$selected_mech_id} - {$mechanizm_options[$selected_mech_id]['name']} znaleziony w bazie");
                        }
                        
                        // 4. Sprawdź czy dla tego mechanizmu istnieją jakiekolwiek technologie
                        $has_technologies = false;
                        $tech_count = 0;
                        
                        foreach ($technologia_options as $tech_id => $tech) {
                            if (isset($tech['group']) && (int)$tech['group'] === (int)$selected_mech_id) {
                                $has_technologies = true;
                                $tech_count++;
                                error_log("Slot {$i}: Znaleziono pasującą technologię #{$tech_id} dla mechanizmu {$selected_mech_id}");
                            }
                        }
                        
                        error_log("Slot {$i}: Mechanizm {$selected_mech_id} ma {$tech_count} powiązanych technologii");
                        
                        // 5. Tylko jeśli mechanizm ma technologie, wymagaj jej wybrania
                        if ($has_technologies) {
                            // Szczegółowa diagnostyka walidacji technologii
                            error_log("DIAGNOSTYKA POLA TECHNOLOGII dla slotu {$i}:");
                            error_log(" - Nazwa pola: {$tech_key}");
                            error_log(" - Czy pole istnieje w POST: " . (isset($_POST[$tech_key]) ? 'TAK' : 'NIE'));
                            error_log(" - Wartość pola (jeśli istnieje): " . (isset($_POST[$tech_key]) ? $_POST[$tech_key] : 'BRAK'));
                            error_log(" - Czy pole ma wartość: " . (!empty($_POST[$tech_key]) ? 'TAK' : 'NIE'));
                            error_log(" - Cały POST dla diagnozy: " . json_encode($_POST));
                            
                            // KLUCZOWA POPRAWKA: Dodatkowe sprawdzenie wartości w formularzu przed walidacją
                            // Jeśli mechanizm ma tylko jedną technologię, sprawdźmy czy jest ona ustawiona
                            if ($tech_count === 1) {
                                // Znajdź tę jedyną technologię
                                foreach ($technologia_options as $tech_id => $tech) {
                                    if (isset($tech['group']) && (int)$tech['group'] === (int)$selected_mech_id) {
                                        // Ustaw ją automatycznie w zmiennej POST jeśli jej nie ma, a powinna być
                                        if (!isset($_POST[$tech_key]) || empty($_POST[$tech_key])) {
                                            $_POST[$tech_key] = $tech_id;
                                            error_log("Slot {$i}: Automatycznie ustawiono jedyną dostępną technologię #{$tech_id}");
                                            // Również zaktualizuj sesję
                                            $_SESSION['kv_configurator'][$tech_key] = $tech_id;
                                        }
                                        break;
                                    }
                                }
                            }
                            
                            // Teraz walidacja
                            if (!isset($_POST[$tech_key]) || empty($_POST[$tech_key])) {
                                $valid = false;
                                $validation_errors[] = "Technologia dla slotu " . ($i+1) . " jest wymagana.";
                                error_log("BŁĄD WALIDACJI: Brak technologii dla slotu {$i} (mechanizm {$selected_mech_id})");
                            } else {
                                $selected_tech_id = intval($_POST[$tech_key]);
                                error_log("Slot {$i}: Wybrana technologia ID={$selected_tech_id}");
                                
                                // Dodatkowe sprawdzenie, czy wybrana technologia jest powiązana z mechanizmem
                                $tech_valid = false;
                                foreach ($technologia_options as $tech_id => $tech) {
                                    $tech_id_str = (string)$tech_id;
                                    $selected_tech_id_str = (string)$selected_tech_id;
                                    $tech_group_str = isset($tech['group']) ? (string)$tech['group'] : '';
                                    $selected_mech_id_str = (string)$selected_mech_id;
                                    
                                    // Dodaj debugging
                                    error_log("PORÓWNANIE: tech_id:{$tech_id_str} vs selected_tech_id:{$selected_tech_id_str}, " .
                                             "tech_group:{$tech_group_str} vs selected_mech_id:{$selected_mech_id_str}");
                                    
                                    // Używaj porównania stringów zamiast intów
                                    if ($tech_id_str === $selected_tech_id_str && $tech_group_str === $selected_mech_id_str) {
                                        $tech_valid = true;
                                        error_log("ZGODNOŚĆ: Technologia {$tech_id_str} pasuje do mechanizmu {$selected_mech_id_str}");
                                        break;
                                    }
                                }

                                
                                if (!$tech_valid) {
                                    $valid = false;
                                    $validation_errors[] = "Wybrana technologia dla slotu " . ($i+1) . " nie jest kompatybilna z wybranym mechanizmem.";
                                    error_log("BŁĄD: Technologia {$selected_tech_id} nie jest powiązana z mechanizmem {$selected_mech_id} dla slotu {$i}");
                                }
                            }
                        } else {
                            // Jeśli mechanizm nie ma technologii, oznacz to w logu i przyjmij puste pole
                            error_log("UWAGA: Mechanizm {$selected_mech_id} w slocie {$i} nie ma powiązanych technologii");
                        }
                        
                        // 6. Sprawdź kolor mechanizmu
                        if (!isset($_POST[$color_key]) || empty($_POST[$color_key])) {
                            $valid = false;
                            $validation_errors[] = "Kolor mechanizmu dla slotu " . ($i+1) . " jest wymagany.";
                            error_log("BŁĄD: Brak koloru mechanizmu dla slotu {$i}");
                        } else {
                            error_log("Slot {$i}: Wybrany kolor mechanizmu ID={$_POST[$color_key]}");
                        }
                    }
                    
                    // Raportuj rezultat walidacji
                    if (!$valid) {
                        error_log('KROK 4 - Walidacja nieudana: ' . implode(', ', $validation_errors));
                        $_SESSION['kv_validation_errors'] = $validation_errors;
                        // Nie przechodź do kroku 5 - zostań na kroku 4
                        $new_step = 4;
                    } else {
                        $new_step = 5;
                        // Usuń znacznik trybu edycji po pomyślnym przejściu do podsumowania
                        if (isset($_SESSION['kv_configurator']['editing_mode'])) {
                            unset($_SESSION['kv_configurator']['editing_mode']);
                        }
                        error_log('KROK 4 - Walidacja udana, przechodzę do kroku 5');
                    }
                    break;
                case 5:
                    // Kod dla kroku 5
                    $new_step = 5;
                    break;
            } // zamknięcie switch
            
            // Przekieruj z aktualizacją URL po zapisaniu danych
            $current_url = remove_query_arg('step');
            $redirect_url = add_query_arg('step', $new_step, $current_url);
            wp_redirect($redirect_url);
            exit;
        } // zamknięcie if ($_SERVER['REQUEST_METHOD'] === 'POST')

    }

    // Pasek postępu (opcjonalnie)
    ?>
    <div id="konfigurator-wrapper">
        <div id="progress-bar">
            <div class="step <?php echo ($step >= 1) ? 'active' : ''; ?>" data-step="1">Seria</div>
            <div class="step <?php echo ($step >= 2) ? 'active' : ''; ?>" data-step="2">Kształt</div>
            <div class="step <?php echo ($step >= 3) ? 'active' : ''; ?>" data-step="3">Układ</div>
            <div class="step <?php echo ($step >= 4) ? 'active' : ''; ?>" data-step="4">Mechanizmy</div>
            <div class="step <?php echo ($step >= 5) ? 'active' : ''; ?>" data-step="5">Podsumowanie</div>
        </div>

        <!-- Główny formularz - W TYM JEDNYM MIEJSCU -->
        <form method="post" action="" id="konfigurator-form">
            <?php 
            // Nonce (TE SAME wartości)
            wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); 
            ?>
            <input type="hidden" name="kv_step" value="<?php echo esc_attr($step); ?>">

            <?php
            // Ładujemy odpowiedni plik kroku
            $frontend_dir = plugin_dir_path(__FILE__) . 'frontend/';
            switch ($step) {
                case 1: include $frontend_dir . 'krok1.php'; break;
                case 2: include $frontend_dir . 'krok2.php'; break;
                case 3: include $frontend_dir . 'krok3.php'; break;
                case 4: include $frontend_dir . 'krok4.php'; break;
                case 5: include $frontend_dir . 'podsumowanie.php'; break;
            }
            ?>

            <!-- Wspólny rząd przycisków dla wszystkich kroków -->
            <?php 
            $buttons_row_path = plugin_dir_path(__FILE__) . 'frontend/buttons_row.php';
            if (file_exists($buttons_row_path)) {
                include $buttons_row_path;
            }
            ?>

        </form>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('konfigurator-vectis', 'kv_configurator_shortcode');

/**
 * 5. Funkcje pomocnicze
 */

// Zapisywanie do usermeta
function kv_save_config_state() {
    if (!is_user_logged_in()) return;
    if (!headers_sent() && !session_id()) {
        @session_start();
    }
    $user_id = get_current_user_id();
    if (isset($_SESSION['kv_configurator'])) {
        update_user_meta($user_id, 'kv_saved_configurator', $_SESSION['kv_configurator']);
    }
}

// Reset konfiguracji
function kv_reset_config_state() {
    if (!headers_sent() && !session_id()) {
        @session_start();
    }
    
    // Usuń dane z sesji
    unset($_SESSION['kv_configurator']);
    
    // Usuń również zapisane dane z usermeta dla zalogowanych użytkowników
    if (is_user_logged_in()) {
        delete_user_meta(get_current_user_id(), 'kv_saved_configurator');
    }
}


// Przykładowe zapisywanie zamówienia w bazie
function kv_add_order($order_number, $all_items) {
    // Twój kod: np. wstaw do tabeli $wpdb->prefix . 'orders'
    // Poniższy kod to placeholder
    // global $wpdb;
    // ...
    // return $wpdb->insert_id;
    return rand(1000, 9999);
}

// Funkcja do obsługi zapisu wybranego układu do sesji
function kv_save_selected_layout() {
    if (isset($_POST['layout'])) {
        $_SESSION['selected_layout'] = sanitize_text_field($_POST['layout']);
        wp_send_json_success('Layout saved');
    }
    wp_send_json_error('No layout provided');
}
add_action('wp_ajax_save_selected_layout', 'kv_save_selected_layout');
add_action('wp_ajax_nopriv_save_selected_layout', 'kv_save_selected_layout');