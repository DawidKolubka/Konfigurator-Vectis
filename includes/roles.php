<?php
defined('ABSPATH') or die('Brak dostępu');

/**
 * System ról dla konfiguratora Vectis
 * Wykorzystuje domyślne role WordPress
 */

/**
 * Mapowanie ról WordPress na role konfiguratora
 */
function kv_get_user_role_mapping() {
    return array(
        'administrator' => 'administrator',
        'editor'       => 'handlowiec', 
        'author'       => 'biuro',
        'subscriber'   => 'klient'
    );
}

/**
 * Pobiera rolę użytkownika w kontekście konfiguratora
 */
function kv_get_user_configurator_role($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return 'klient'; // Dla niezalogowanych użytkowników
    }
    
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return 'klient';
    }
    
    $mapping = kv_get_user_role_mapping();
    $user_roles = $user->roles;
    
    // Sprawdź role w kolejności ważności (administrator > editor > author > subscriber)
    $priority_roles = array('administrator', 'editor', 'author', 'subscriber');
    
    foreach ($priority_roles as $wp_role) {
        if (in_array($wp_role, $user_roles) && isset($mapping[$wp_role])) {
            return $mapping[$wp_role];
        }
    }
    
    return 'klient'; // Domyślna rola
}

/**
 * Sprawdza czy użytkownik ma określoną rolę w konfiguratorze
 */
function kv_user_has_role($required_role, $user_id = null) {
    $user_role = kv_get_user_configurator_role($user_id);
    
    // Definiuj hierarchię uprawnień
    $role_hierarchy = array(
        'administrator' => 4,
        'handlowiec' => 3,
        'biuro' => 2,
        'klient' => 1
    );
    
    $user_level = isset($role_hierarchy[$user_role]) ? $role_hierarchy[$user_role] : 1;
    $required_level = isset($role_hierarchy[$required_role]) ? $role_hierarchy[$required_role] : 1;
    
    return $user_level >= $required_level;
}

/**
 * Pobiera nazwę roli do wyświetlenia
 */
function kv_get_role_display_name($role) {
    $role_names = array(
        'administrator' => 'Administrator',
        'handlowiec' => 'Handlowiec',
        'biuro' => 'Biuro',
        'klient' => 'Klient'
    );
    
    return isset($role_names[$role]) ? $role_names[$role] : ucfirst($role);
}

/**
 * Zmienia nazwy wyświetlane ról WordPress w interfejsie
 */
function kv_translate_user_roles($translation, $text, $domain) {
    if ($domain === 'default') {
        switch ($text) {
            case 'Subscriber':
                return 'Klient';
            case 'Author':
                return 'Biuro';
            case 'Editor':
                return 'Handlowiec';
            case 'Administrator':
                return 'Administrator';
        }
    }
    return $translation;
}
add_filter('gettext', 'kv_translate_user_roles', 10, 3);

/**
 * Zmienia nazwy ról w dropdown i innych miejscach WordPress
 */
function kv_change_role_names() {
    global $wp_roles;
    
    if (!isset($wp_roles) || !is_object($wp_roles)) {
        return;
    }
    
    if (isset($wp_roles->roles['subscriber']['name'])) {
        $wp_roles->roles['subscriber']['name'] = 'Klient';
    }
    if (isset($wp_roles->roles['author']['name'])) {
        $wp_roles->roles['author']['name'] = 'Biuro';
    }
    if (isset($wp_roles->roles['editor']['name'])) {
        $wp_roles->roles['editor']['name'] = 'Handlowiec';
    }
    if (isset($wp_roles->roles['administrator']['name'])) {
        $wp_roles->roles['administrator']['name'] = 'Administrator';
    }
}
add_action('init', 'kv_change_role_names');

/**
 * Dodaje niestandardowe uprawnienia do ról
 */
function kv_add_custom_capabilities() {
    // Pobierz role
    $admin = get_role('administrator');
    $editor = get_role('editor'); 
    $author = get_role('author');
    $subscriber = get_role('subscriber');
    
    // Dodaj uprawnienia do zarządzania zamówieniami
    if ($admin) {
        $admin->add_cap('kv_manage_all_orders');
        $admin->add_cap('kv_view_all_orders');
        $admin->add_cap('kv_edit_configurator');
        $admin->add_cap('kv_manage_users');
    }
    
    if ($editor) { // Handlowiec
        $editor->add_cap('kv_manage_client_orders');
        $editor->add_cap('kv_view_client_orders');
        $editor->add_cap('kv_create_orders_for_clients');
    }
    
    if ($author) { // Biuro
        $author->add_cap('kv_process_orders');
        $author->add_cap('kv_view_submitted_orders');
        $author->add_cap('kv_edit_order_status');
    }
    
    if ($subscriber) { // Klient
        $subscriber->add_cap('kv_create_orders');
        $subscriber->add_cap('kv_view_own_orders');
        $subscriber->add_cap('kv_edit_own_draft_orders');
    }
}

/**
 * Sprawdza czy użytkownik ma określone uprawnienie
 */
function kv_user_can($capability, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return user_can($user_id, $capability);
}

/**
 * Pobiera listę użytkowników dla określonej roli konfiguratora
 */
function kv_get_users_by_configurator_role($configurator_role) {
    $mapping = array_flip(kv_get_user_role_mapping());
    
    if (!isset($mapping[$configurator_role])) {
        return array();
    }
    
    $wp_role = $mapping[$configurator_role];
    
    return get_users(array(
        'role' => $wp_role,
        'fields' => array('ID', 'display_name', 'user_email')
    ));
}

/**
 * Inicjalizacja uprawnień przy aktywacji wtyczki
 */
function kv_init_roles() {
    kv_add_custom_capabilities();
}
register_activation_hook(__FILE__, 'kv_init_roles');