<?php
defined('ABSPATH') or die('Brak dostępu');

/**
 * Pobiera elementy przechowywane w opcji.
 *
 * @param string $option_key Klucz opcji, np. 'kv_seria_options'
 * @return array Tablica elementów.
 */
function kv_get_items( $option_key ) {
    return get_option( $option_key, array() );
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
