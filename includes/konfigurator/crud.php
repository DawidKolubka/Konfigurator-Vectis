<?php
defined('ABSPATH') or die('Brak dostępu');

/**
 * Pobiera elementy przechowywane w opcji.
 *
 * @param string $option_key Klucz opcji, np. 'kv_seria_options'
 * @return array Tablica elementów.
 */
function kv_get_items( $option_key ) {
    // Używaj funkcji bezpieczeństwa dla specyficznych typów danych
    if ($option_key === 'kv_mechanizm_options') {
        return kv_get_safe_mechanizmy();
    } elseif ($option_key === 'kv_technologia_options') {
        return kv_get_safe_technologie();
    } elseif ($option_key === 'kv_kolor_ramki_options') {
        return kv_get_safe_kolor_ramki();
    } elseif ($option_key === 'kv_kolor_mechanizmu_options') {
        return kv_get_safe_kolor_mechanizmu();
    }
    
    // Dla pozostałych typów danych używaj standardowej funkcji
    $items = get_option( $option_key, array() );
    
    // Upewnij się, że zawsze zwracamy tablicę
    return is_array($items) ? $items : array();
}

/**
 * Dodaje nowy element do opcji.
 *
 * @param string $option_key Klucz opcji.
 * @param array  $item       Nowy element (tablica).
 */
function kv_add_item( $option_key, $item ) {
    $items = kv_get_items( $option_key );
    $items[] = $item;
    update_option( $option_key, $items );
}

/**
 * Aktualizuje element o podanym indeksie.
 *
 * @param string $option_key Klucz opcji.
 * @param int    $id         Indeks elementu.
 * @param array  $item       Zaktualizowany element.
 */
function kv_update_item( $option_key, $id, $item ) {
    $items = kv_get_items( $option_key );
    if ( isset( $items[ $id ] ) ) {
        $items[ $id ] = $item;
        update_option( $option_key, $items );
    }
}

/**
 * Usuwa element o podanym indeksie.
 *
 * @param string $option_key Klucz opcji.
 * @param int    $id         Indeks elementu.
 */
function kv_delete_item( $option_key, $id ) {
    $items = kv_get_items( $option_key );
    if ( isset( $items[ $id ] ) ) {
        unset( $items[ $id ] );
        $items = array_values( $items );
        update_option( $option_key, $items );
    }
}

/**
 * Duplikuje element o podanym indeksie.
 *
 * @param string $option_key Klucz opcji, np. 'kv_seria_options'
 * @param int    $id         Indeks elementu do duplikacji.
 * @return int|false Nowy indeks duplikowanego elementu lub false, jeśli element nie istnieje.
 */
function kv_duplicate_item( $option_key, $id ) {
    $items = kv_get_items( $option_key );
    if ( isset( $items[ $id ] ) ) {
        $duplicated_item = $items[ $id ];
        // Możesz też zmodyfikować duplikat, np. dodać do nazwy końcówkę " - kopia"
        $duplicated_item['name'] .= ' - kopia';
        $items[] = $duplicated_item;
        update_option( $option_key, $items );
        return count( $items ) - 1;
    }
    return false;
}

/**
 * Bezpieczne pobieranie kolorów ramki - dodaje placeholder, jeśli to pierwszy element
 *
 * @return array Tablica kolorów ramki z dodanym placeholderem dla id=0 (jeśli brak danych)
 */
function kv_get_safe_kolor_ramki() {
    $items = get_option('kv_kolor_ramki_options', array());
    
    // Upewnij się, że zawsze mamy tablicę
    if (!is_array($items)) {
        $items = array();
    }
    
    // Jeśli brak elementów, utwórz placeholder
    if (empty($items)) {
        $items = array(
            array(
                'name' => 'Placeholder Kolor Ramki',
                'image' => '',
                'snippet' => 'placeholder',
            )
        );
        update_option('kv_kolor_ramki_options', $items);
    }
    
    return $items;
}

/**
 * Bezpieczne pobieranie kolorów mechanizmu - dodaje placeholder, jeśli to pierwszy element
 *
 * @return array Tablica kolorów mechanizmu z dodanym placeholderem dla id=0 (jeśli brak danych)
 */
function kv_get_safe_kolor_mechanizmu() {
    $items = get_option('kv_kolor_mechanizmu_options', array());
    
    // Upewnij się, że zawsze mamy tablicę
    if (!is_array($items)) {
        $items = array();
    }
    
    // Jeśli brak elementów, utwórz placeholder
    if (empty($items)) {
        $items = array(
            array(
                'name' => 'Placeholder Kolor Mechanizmu',
                'image' => '',
                'snippet' => 'placeholder',
            )
        );
        update_option('kv_kolor_mechanizmu_options', $items);
    }
    
    return $items;
}
