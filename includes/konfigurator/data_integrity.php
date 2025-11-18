<?php
defined('ABSPATH') or die('Brak dostępu');

/**
 * Funkcje zapewniające integralność danych w konfiguratorze
 */

/**
 * Sprawdza i naprawia opcje konfiguratora, upewniając się, że wszystkie wymagane pola istnieją
 * i mają poprawne wartości.
 * 
 * @return bool True jeśli dokonano naprawy, false jeśli nie było konieczne
 */
function kv_sanitize_all_options() {
    $options_repaired = false;
    
    // Lista opcji do sprawdzenia i wymagane pola
    $options_config = array(
        'kv_mechanizm_options' => array(
            'required_fields' => array(
                'name' => 'Mechanizm',
                'image' => '',
                'frame_image' => '',
                'frame_number' => '',
                'visibility' => '1'
            )
        ),
        'kv_technologia_options' => array(
            'required_fields' => array(
                'group' => '',
                'technology' => 'Technologia',
                'visibility' => '1'
            )
        )
    );
    
    // Sprawdź i napraw każdą opcję
    foreach ($options_config as $option_key => $config) {
        $items = get_option($option_key, array());
        $items_repaired = false;
        
        // Jeśli opcja jest pusta, stwórz pustą tablicę
        if (!is_array($items)) {
            $items = array();
            $items_repaired = true;
        }
        
        // Sprawdź każdy element
        foreach ($items as $id => &$item) {
            // Jeśli element jest null, zastąp go pustym elementem
            if (!is_array($item)) {
                $item = $config['required_fields'];
                $item_name_field = key($config['required_fields']);
                $item[$item_name_field] .= ' ' . $id;
                $items_repaired = true;
                continue;
            }
            
            // Sprawdź, czy element ma wszystkie wymagane pola
            foreach ($config['required_fields'] as $field => $default_value) {
                if (!isset($item[$field])) {
                    $item[$field] = $default_value;
                    if ($field === key($config['required_fields'])) {
                        $item[$field] .= ' ' . $id;
                    }
                    $items_repaired = true;
                }
            }
        }
        
        // Zapisz naprawione elementy
        if ($items_repaired) {
            update_option($option_key, $items);
            $options_repaired = true;
            error_log("Naprawiono opcje $option_key");
        }
    }
    
    return $options_repaired;
}

/**
 * Sanityzuje ID mechanizmu, upewniając się, że jest liczbą lub pustym stringiem
 * 
 * @param mixed $id ID do sanityzacji
 * @return string|int Prawidłowe ID
 */
function kv_sanitize_mech_id($id) {
    // Jeśli ID jest null lub puste, zwróć pusty string
    if ($id === null || $id === '') {
        return '';
    }
    
    // Konwertuj do liczby całkowitej
    return intval($id);
}

/**
 * Wrapper bezpieczeństwa do pobierania mechanizmów
 * 
 * @return array Bezpieczna tablica mechanizmów
 */
function kv_get_safe_mechanizmy() {
    $mechanizmy = get_option('kv_mechanizm_options', array());
    
    // Jeśli nie jest tablicą, zwróć pustą tablicę
    if (!is_array($mechanizmy)) {
        return array();
    }
    
    // Upewnij się, że każdy mechanizm ma wszystkie wymagane pola
    foreach ($mechanizmy as $id => &$mech) {
        if (!is_array($mech)) {
            $mech = array(
                'name' => 'Mechanizm ' . $id,
                'image' => '',
                'frame_image' => '',
                'frame_number' => '',
                'visibility' => '1'
            );
        } else {
            if (!isset($mech['name']) || empty($mech['name'])) {
                $mech['name'] = 'Mechanizm ' . $id;
            }
        }
    }
    
    return $mechanizmy;
}

/**
 * Wrapper bezpieczeństwa do pobierania technologii
 * 
 * @return array Bezpieczna tablica technologii
 */
function kv_get_safe_technologie() {
    $technologie = get_option('kv_technologia_options', array());
    
    // Jeśli nie jest tablicą, zwróć pustą tablicę
    if (!is_array($technologie)) {
        return array();
    }
    
    // Upewnij się, że każda technologia ma wszystkie wymagane pola
    foreach ($technologie as $id => &$tech) {
        if (!is_array($tech)) {
            $tech = array(
                'group' => '',
                'technology' => 'Technologia ' . $id,
                'visibility' => '1'
            );
        } else {
            if (!isset($tech['technology']) || empty($tech['technology'])) {
                $tech['technology'] = 'Technologia ' . $id;
            }
            
            if (!isset($tech['group'])) {
                $tech['group'] = '';
            }
        }
    }
    
    return $technologie;
}

// Uruchamiamy sanityzację przy ładowaniu pliku
add_action('admin_init', 'kv_sanitize_all_options');
