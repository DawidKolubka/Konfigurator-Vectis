# System ról w Konfiguratorze Vectis

## Opis
Konfigurator Vectis wykorzystuje domyślne role WordPress, mapując je na role specyficzne dla aplikacji.

## Mapowanie ról

| Rola WordPress | Rola w Konfiguratorze | Opis |
|----------------|----------------------|------|
| Administrator  | Administrator        | Pełny dostęp do wszystkich funkcji |
| Editor         | Handlowiec          | Zarządzanie zamówieniami klientów |
| Author         | Biuro               | Przetwarzanie zamówień |
| Subscriber     | Klient              | Tworzenie własnych zamówień |

## Hierarchia uprawnień

1. **Administrator** (najwyższe uprawnienia)
   - Zarządzanie wszystkimi zamówieniami
   - Przeglądanie wszystkich zamówień
   - Edycja konfiguratora
   - Zarządzanie użytkownikami

2. **Handlowiec**
   - Zarządzanie zamówieniami klientów
   - Przeglądanie zamówień klientów
   - Tworzenie zamówień dla klientów

3. **Biuro**
   - Przetwarzanie zamówień
   - Przeglądanie wysłanych zamówień
   - Edycja statusu zamówień

4. **Klient** (najniższe uprawnienia)
   - Tworzenie własnych zamówień
   - Przeglądanie własnych zamówień
   - Edycja własnych wersji roboczych

## Funkcje pomocnicze

### `kv_get_user_configurator_role($user_id)`
Pobiera rolę użytkownika w kontekście konfiguratora.

```php
$user_role = kv_get_user_configurator_role();
echo "Twoja rola: " . $user_role;
```

### `kv_user_has_role($required_role, $user_id)`
Sprawdza czy użytkownik ma wymaganą rolę lub wyższą.

```php
if (kv_user_has_role('biuro')) {
    // Użytkownik ma rolę Biuro, Handlowiec lub Administrator
    echo "Dostęp granted";
}
```

### `kv_user_can($capability, $user_id)`
Sprawdza czy użytkownik ma określone uprawnienie.

```php
if (kv_user_can('kv_manage_all_orders')) {
    // Użytkownik może zarządzać wszystkimi zamówieniami
}
```

### `kv_get_role_display_name($role)`
Pobiera nazwę roli do wyświetlenia.

```php
$display_name = kv_get_role_display_name('handlowiec');
// Zwraca: "Handlowiec"
```

### `kv_get_users_by_configurator_role($role)`
Pobiera listę użytkowników dla określonej roli.

```php
$handlowcy = kv_get_users_by_configurator_role('handlowiec');
foreach ($handlowcy as $user) {
    echo $user->display_name;
}
```

## Zmiana nazw ról WordPress

System automatycznie zmienia nazwy domyślnych ról WordPress w interfejsie:

- Subscriber → Klient
- Author → Biuro
- Editor → Handlowiec
- Administrator → Administrator

## Uprawnienia niestandardowe

System dodaje następujące niestandardowe uprawnienia:

### Administrator
- `kv_manage_all_orders` - Zarządzanie wszystkimi zamówieniami
- `kv_view_all_orders` - Przeglądanie wszystkich zamówień
- `kv_edit_configurator` - Edycja konfiguratora
- `kv_manage_users` - Zarządzanie użytkownikami

### Handlowiec (Editor)
- `kv_manage_client_orders` - Zarządzanie zamówieniami klientów
- `kv_view_client_orders` - Przeglądanie zamówień klientów
- `kv_create_orders_for_clients` - Tworzenie zamówień dla klientów

### Biuro (Author)
- `kv_process_orders` - Przetwarzanie zamówień
- `kv_view_submitted_orders` - Przeglądanie wysłanych zamówień
- `kv_edit_order_status` - Edycja statusu zamówień

### Klient (Subscriber)
- `kv_create_orders` - Tworzenie zamówień
- `kv_view_own_orders` - Przeglądanie własnych zamówień
- `kv_edit_own_draft_orders` - Edycja własnych wersji roboczych

## Przykłady użycia

### Wyświetlanie zamówień w zależności od roli

```php
function display_orders_for_user() {
    $user_role = kv_get_user_configurator_role();
    
    switch ($user_role) {
        case 'administrator':
            $orders = kv_get_orders();
            break;
        case 'handlowiec':
            $orders = kv_get_orders(); // Można dodać filtrowanie
            break;
        case 'biuro':
            $orders = kv_get_orders_by_status(['submitted', 'processing']);
            break;
        case 'klient':
        default:
            $orders = kv_get_user_orders(get_current_user_id());
            break;
    }
    
    foreach ($orders as $order) {
        echo "Zamówienie: " . $order['order_number'];
    }
}
```

### Sprawdzanie uprawnień w panelu administracyjnym

```php
function admin_panel() {
    if (!kv_user_has_role('biuro')) {
        echo "Nie masz uprawnień do tej strony";
        return;
    }
    
    $user_role = kv_get_user_configurator_role();
    echo "Panel administracyjny - " . kv_get_role_display_name($user_role);
}
```

## Aktywacja uprawnień

Uprawnienia są automatycznie dodawane przy aktywacji wtyczki przez funkcję `kv_init_roles()`.

Jeśli potrzebujesz ręcznie dodać uprawnienia, możesz wywołać:

```php
kv_add_custom_capabilities();
```

## Uwagi

1. System wykorzystuje istniejące role WordPress - nie tworzy nowych ról
2. Zachowuje kompatybilność z innymi wtyczkami
3. Nazwy ról są zmieniane tylko w kontekście wyświetlania
4. Hierarchia uprawnień jest zachowana (administrator > handlowiec > biuro > klient)