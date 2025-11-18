<?php
defined('ABSPATH') or die('Brak dostępu');

/**
 * Funkcja naprawiająca ID mechanizmów i technologii w bazie danych.
 * Zwiększa wszystkie ID o 1, aby uniknąć problemów z ID 0.
 */
function kv_repair_zero_ids() {
    $options_to_repair = array(
        'kv_mechanizm_options',
        'kv_technologia_options',
        'kv_kolor_ramki_options',
        'kv_kolor_mechanizmu_options'
    );

    foreach ($options_to_repair as $option_key) {
        $items = get_option($option_key, array());
        $repaired_items = array();
          // Przesunięcie ID o 1 - pusta wartość na indeksie 0 ale z uzupełnionymi kluczami
        if ($option_key == 'kv_mechanizm_options') {
            $repaired_items[0] = array(
                'name' => 'Placeholder', 
                'image' => '', 
                'frame_image' => '', 
                'frame_number' => '', 
                'visibility' => '0'
            );
        } elseif ($option_key == 'kv_technologia_options') {
            $repaired_items[0] = array(
                'group' => '',
                'technology' => 'Placeholder',
                'visibility' => '0'
            );
        } elseif ($option_key == 'kv_kolor_ramki_options') {
            $repaired_items[0] = array(
                'name' => 'Placeholder Kolor Ramki',
                'image' => '',
                'snippet' => 'placeholder',
                'visibility' => '0'
            );
        } elseif ($option_key == 'kv_kolor_mechanizmu_options') {
            $repaired_items[0] = array(
                'name' => 'Placeholder Kolor Mechanizmu',
                'image' => '',
                'snippet' => 'placeholder',
                'visibility' => '0'
            );
        } else {
            $repaired_items[0] = array('name' => 'Placeholder');
        }
        
        // Mapowanie starych ID na nowe
        $id_mapping = array();
          // Najpierw dodajemy wszystkie elementy z ID zwiększonym o 1
        foreach ($items as $old_id => $item) {
            if ($item) { // Ignoruj puste wartości
                $new_id = $old_id + 1;
                
                // Upewnij się, że element ma wszystkie wymagane pola
                if ($option_key == 'kv_mechanizm_options') {
                    if (!isset($item['name']) || empty($item['name'])) {
                        $item['name'] = 'Mechanizm ' . $old_id;
                    }
                    
                    if (!isset($item['frame_image'])) {
                        $item['frame_image'] = '';
                    }
                    
                    if (!isset($item['visibility'])) {
                        $item['visibility'] = '1';
                    }
                } elseif ($option_key == 'kv_technologia_options') {
                    if (!isset($item['technology']) || empty($item['technology'])) {
                        $item['technology'] = 'Technologia ' . $old_id;
                    }
                    
                    if (!isset($item['visibility'])) {
                        $item['visibility'] = '1';
                    }
                }
                
                $repaired_items[$new_id] = $item;
                $id_mapping[$old_id] = $new_id;
                
                // Log dla admina
                error_log("$option_key: Przesuwam ID z $old_id na $new_id");
            }
        }
        
        // Aktualizacja w bazie danych
        update_option($option_key, $repaired_items);
        
        // Zapisz mapowanie dla późniejszych aktualizacji relacji
        update_option("{$option_key}_id_mapping", $id_mapping);
    }
      // Aktualizacja relacji między technologiami a mechanizmami
    $tech_options = get_option('kv_technologia_options', array());
    $mech_id_mapping = get_option('kv_mechanizm_options_id_mapping', array());
    
    // Upewnij się, że technologie mają wszystkie wymagane pola
    foreach ($tech_options as $tech_id => &$tech_item) {
        // Jeśli element jest nullem lub nie jest tablicą, utwórz pusty element
        if (!is_array($tech_item)) {
            $tech_item = array(
                'group' => '',
                'technology' => 'Placeholder ' . $tech_id,
                'visibility' => '0'
            );
            error_log("Technologia ID $tech_id: Naprawiam uszkodzony rekord, tworzę placeholder");
            continue;
        }
        
        // Dodaj brakujące pola
        if (!isset($tech_item['technology'])) {
            $tech_item['technology'] = 'Technologia ' . $tech_id;
        }
        
        if (!isset($tech_item['visibility'])) {
            $tech_item['visibility'] = '1';
        }
        
        // Aktualizacja powiązań
        if (isset($tech_item['group'])) {
            $old_group_id = $tech_item['group'];
            if (isset($mech_id_mapping[$old_group_id])) {
                $new_group_id = $mech_id_mapping[$old_group_id];
                $tech_item['group'] = $new_group_id;
                error_log("Technologia ID $tech_id: Aktualizuję powiązanie z mechanizmu $old_group_id na $new_group_id");
            }
        } else {
            // Jeśli brak powiązania, ustaw puste
            $tech_item['group'] = '';
            error_log("Technologia ID $tech_id: Brak powiązania z mechanizmem, ustawiam puste");
        }
    }        // Zapisz zaktualizowane technologie
    update_option('kv_technologia_options', $tech_options);
    
    // Zaktualizuj też dane sesji dla użytkowników konfigurujących produkty
    if (isset($_SESSION['kv_configurator'])) {
        $cfg = &$_SESSION['kv_configurator'];
        
        // Aktualizacja mapowań ID dla kolorów
        $kolor_ramki_id_mapping = get_option('kv_kolor_ramki_options_id_mapping', array());
        $kolor_mechanizmu_id_mapping = get_option('kv_kolor_mechanizmu_options_id_mapping', array());
        
        // Znajdź wszystkie klucze rozpoczynające się od "mechanizm_", "technologia_", "kolor_ramki" i "kolor_mechanizmu_"
        foreach ($cfg as $key => $value) {
            if (strpos($key, 'mechanizm_') === 0 && $value !== '' && isset($mech_id_mapping[$value])) {
                $cfg[$key] = $mech_id_mapping[$value];
                error_log("Sesja: Aktualizuję $key z wartości $value na {$mech_id_mapping[$value]}");
            }
            else if (strpos($key, 'technologia_') === 0 && $value !== '') {
                $tech_id_mapping = get_option('kv_technologia_options_id_mapping', array());
                if (isset($tech_id_mapping[$value])) {
                    $cfg[$key] = $tech_id_mapping[$value];
                    error_log("Sesja: Aktualizuję $key z wartości $value na {$tech_id_mapping[$value]}");
                }
            }
            else if ($key === 'kolor_ramki' && $value !== '' && isset($kolor_ramki_id_mapping[$value])) {
                $cfg[$key] = $kolor_ramki_id_mapping[$value];
                error_log("Sesja: Aktualizuję $key z wartości $value na {$kolor_ramki_id_mapping[$value]}");
            }
            else if (strpos($key, 'kolor_mechanizmu_') === 0 && $value !== '') {
                if (isset($kolor_mechanizmu_id_mapping[$value])) {
                    $cfg[$key] = $kolor_mechanizmu_id_mapping[$value];
                    error_log("Sesja: Aktualizuję $key z wartości $value na {$kolor_mechanizmu_id_mapping[$value]}");
                }
            }
        }
    }
    
    return true;
}

/**
 * Dodaje link do panelu administracyjnego, który uruchamia naprawę ID.
 */
function kv_add_repair_ids_button() {
    if (current_user_can('manage_options')) {
        // Sprawdź, czy przycisk został naciśnięty
        if (isset($_GET['kv_repair_ids']) && $_GET['kv_repair_ids'] == 1) {
            // Wykonaj naprawę ID
            $result = kv_repair_zero_ids();
            
            // Przekieruj z powrotem do strony głównej pluginu
            wp_redirect(admin_url('admin.php?page=kv-kreator&repair_done=' . ($result ? '1' : '0')));
            exit;
        }
        
        // Dodaj informację o naprawie
        if (isset($_GET['repair_done'])) {
            if ($_GET['repair_done'] == '1') {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>Naprawa ID została przeprowadzona pomyślnie!</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>Wystąpił błąd podczas naprawy ID.</p></div>';
                });
            }
        }
    }
}
add_action('admin_init', 'kv_add_repair_ids_button');

/**
 * Dodaje link "Napraw ID" do menu administratora.
 */
function kv_add_repair_link_to_menu($links) {
    if (current_user_can('manage_options')) {
        // Dodaj link do naprawy ID na stronie głównej kreatora
        add_action('kv_kreator_page_after', function() {
            echo '<div class="repair-section" style="margin-top: 20px; padding: 15px; background: #f8f8f8; border: 1px solid #ddd; border-radius: 4px;">';
            echo '<h2>Naprawa ID</h2>';
            echo '<p>Jeśli masz problemy z walidacją mechanizmów, technologii, kolorów ramki lub kolorów mechanizmu z ID 0, ta funkcja przesunie wszystkie ID o 1, aby uniknąć problemów.</p>';
            echo '<p><strong>Uwaga!</strong> Przed wykonaniem tej operacji zalecane jest wykonanie kopii zapasowej bazy danych.</p>';
            echo '<a href="' . admin_url('admin.php?page=kv-kreator&kv_repair_ids=1') . '" class="button button-primary" onclick="return confirm(\'Czy na pewno chcesz przeprowadzić naprawę ID? Ta operacja jest nieodwracalna.\');">Napraw ID mechanizmów, technologii i kolorów</a>';
            echo '</div>';
        });
    }
    
    return $links;
}
add_filter('plugin_action_links_konfigurator-vectis/konfigurator-vectis.php', 'kv_add_repair_link_to_menu');
