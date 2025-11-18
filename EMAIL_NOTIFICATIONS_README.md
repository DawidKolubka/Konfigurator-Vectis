# System powiadomień email - Konfigurator Vectis

## 📧 **Co zostało zaimplementowane:**

### **1. Powiadomienia dla administratorów/biura**
- ✅ **Automatyczne powiadomienie** o każdym nowym zamówieniu
- ✅ **Professional email template** z logo i szczegółami
- ✅ **Wysyłka do wszystkich użytkowników** z rolami: Administrator, Handlowiec, Biuro
- ✅ **Link do panelu administracyjnego** w mailu

### **2. Potwierdzenie dla klienta**
- ✅ **Automatyczne potwierdzenie** po złożeniu zamówienia
- ✅ **Estetyczny template** z informacjami o zamówieniu
- ✅ **Instrukcje co dalej** - informacja o kontakcie w 24h
- ✅ **Linki do konta klienta** i nowego zamówienia

### **3. Powiadomienia o zmianie statusu**
- ✅ **Automatyczne powiadomienia** przy każdej zmianie statusu
- ✅ **Różne kolory i ikony** dla różnych statusów
- ✅ **Specjalne wiadomości** dopasowane do statusu
- ✅ **Historia zmian** w jednym mailu

### **4. Panel administracyjny**
- ✅ **Dropdown do zmiany statusu** w tabeli zamówień
- ✅ **AJAX - bez przeładowania strony**
- ✅ **Potwierdzenie przed zmianą** z informacją o powiadomieniu klienta
- ✅ **Kontrola uprawnień** - tylko Biuro i wyżej

## ⚙️ **Konfiguracja**

### **1. Adresy email**
System automatycznie wysyła powiadomienia do:
- Email głównego administratora (`admin_email`)
- Wszystkich użytkowników z rolami: Administrator, Editor (Handlowiec), Author (Biuro)

### **2. Personalizacja adresów** (opcjonalna)
```php
// Dodaj do functions.php lub pliku wtyczki
add_filter('kv_admin_notification_emails', function($emails) {
    // Dodaj dodatkowy email
    $emails[] = 'biuro@twojafirma.pl';
    
    // Usuń niepotrzebny email
    $key = array_search('niechciany@email.pl', $emails);
    if ($key !== false) {
        unset($emails[$key]);
    }
    
    return $emails;
});
```

### **3. Dostosowanie linków**
Zaktualizuj URL-e w pliku `includes/notifications.php`:
```php
$my_account_url = site_url('/moje-konto/'); // Zmień na właściwy URL
```

## 🎨 **Template'y email**

### **Kolory i style**
- **Nowe zamówienie (Admin):** Niebieski (#0073aa)
- **Potwierdzenie (Klient):** Zielony (#28a745)  
- **Status "Przesłane":** Cyan (#17a2b8)
- **Status "W realizacji":** Żółty (#ffc107)
- **Status "Ukończone":** Zielony (#28a745)
- **Status "Anulowane":** Czerwony (#dc3545)

### **Dostosowanie template'ów**
Możesz edytować funkcje w `includes/notifications.php`:
- `kv_get_new_order_admin_email_template()` - email dla administratorów
- `kv_get_order_confirmation_email_template()` - potwierdzenie dla klienta  
- `kv_get_status_change_email_template()` - zmiana statusu

## 🔧 **Funkcje API**

### **Wysyłanie powiadomień**
```php
// Nowe zamówienie (automatyczne)
kv_send_new_order_notification($order_id, $order_data);

// Zmiana statusu (automatyczne przez panel admin)
kv_send_order_status_notification($order_id, $old_status, $new_status);

// Aktualizacja statusu z powiadomieniem
kv_update_order_status_with_notification($order_id, $new_status);
```

### **Pobranie adresów email**
```php
$admin_emails = kv_get_admin_notification_emails();
```

### **Statusy zamówień**
```php
$label = kv_get_status_label('submitted'); // "Przesłane"
```

## 📱 **Jak to działa**

### **1. Nowe zamówienie**
1. Klient kończy konfigurator i klika "Zapisz"
2. Zamówienie zapisuje się w bazie danych
3. **Automatycznie wysyłane są 2 maile:**
   - **Do administratorów/biura** - powiadomienie o nowym zamówieniu
   - **Do klienta** - potwierdzenie zamówienia

### **2. Zmiana statusu**
1. Pracownik biura/administrator zmienia status w panelu
2. **Automatycznie wysyłany jest mail do klienta** z informacją o zmianie
3. Mail zawiera odpowiednią wiadomość dopasowaną do nowego statusu

### **3. Bezpieczeństwo**
- ✅ Sprawdzanie uprawnień użytkownika
- ✅ Nonce verification dla AJAX
- ✅ Sanityzacja danych wejściowych
- ✅ Validation statusów

## 🐛 **Debug i testowanie**

### **Sprawdź czy maile są wysyłane**
```php
// Dodaj do wp-config.php do testów
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Sprawdź logi w /wp-content/debug.log
```

### **Test wysyłki email**
```php
// Dodaj tymczasowo do functions.php
add_action('init', function() {
    if (isset($_GET['test_email'])) {
        $result = wp_mail('test@example.com', 'Test', 'Test message');
        var_dump($result);
        exit;
    }
});
// Następnie idź na: yoursite.com/?test_email
```

### **SMTP konfiguracja** (zalecane)
Zainstaluj wtyczkę SMTP (np. "Easy WP SMTP") dla pewniejszej dostawy maili.

## ✨ **Przykład maila**

### **Admin - Nowe zamówienie:**
```
🆕 Nowe zamówienie w konfiguratorze
[Logo firmy]

Witaj!
Otrzymaliśmy nowe zamówienie w konfiguratorze Vectis.

📋 Szczegóły zamówienia
Numer zamówienia: KV-2024-123456
Klient: Jan Kowalski
Data utworzenia: 18.11.2024 14:30
Status: Wersja robocza

[Zobacz zamówienie w panelu] -> Link do admin panelu
```

### **Klient - Potwierdzenie:**
```
✅ Potwierdzenie zamówienia
[Logo firmy]

Witaj Jan Kowalski!
Dziękujemy za złożenie zamówienia w naszym konfiguratorze.

📋 Twoje zamówienie
Numer zamówienia: KV-2024-123456
Data złożenia: 18.11.2024 14:30

📧 Co dalej?
Nasze biuro skontaktuje się z Tobą w ciągu 24 godzin...

[Moje konto] [Nowe zamówienie]
```

## 🚀 **Następne kroki**

System jest gotowy do użycia! Możesz teraz:

1. **Przetestować wysyłkę** - złóż testowe zamówienie
2. **Dostosować template'y** - zmień kolory, tekst, logo
3. **Skonfigurować SMTP** - dla lepszej dostawy
4. **Dodać więcej statusów** - jeśli potrzebujesz
5. **Zintegrować z CRM** - jeśli masz zewnętrzny system

Wszystkie maile są responsywne i wyglądają dobrze na telefonach! 📱✨