# System komentarzy do zamówień - Konfigurator Vectis

## 📝 **Co zostało zaimplementowane:**

### **1. Tabela komentarzy w bazie danych**
- ✅ Utworzona nowa tabela `wp_vectis_order_comments`
- ✅ Struktura tabeli:
  - `id` - ID komentarza
  - `order_id` - ID zamówienia
  - `user_id` - ID użytkownika (autor komentarza)
  - `comment_text` - Treść komentarza
  - `created_at` - Data i godzina dodania komentarza
- ✅ Indeksy dla wydajności: `order_id_idx`, `user_id_idx`

### **2. Funkcje CRUD dla komentarzy** (`includes/zamowienia/orders.php`)
- ✅ `kv_add_order_comment($order_id, $comment_text, $user_id)` - dodaje komentarz
- ✅ `kv_get_order_comments($order_id)` - pobiera wszystkie komentarze dla zamówienia
- ✅ `kv_get_order_comments_count($order_id)` - zlicza komentarze
- ✅ `kv_delete_order_comment($comment_id)` - usuwa komentarz (dla adminów)

### **3. Panel administracyjny** (`includes/zamowienia/admin.php`)
- ✅ Nowa kolumna "Komentarze" w tabeli zamówień
- ✅ Przycisk "Dodaj komentarz" w kolumnie Akcje
- ✅ Modal do dodawania komentarzy z polem tekstowym
- ✅ Wyświetlanie liczby komentarzy
- ✅ Rozwijana lista komentarzy (przycisk "Pokaż")
- ✅ AJAX - dodawanie komentarzy bez przeładowania strony

### **4. Funkcjonalność wyświetlania**
- ✅ Komentarze są zwijane/rozwijane za pomocą przycisku
- ✅ Każdy komentarz zawiera:
  - Autora (nazwa użytkownika)
  - Datę i godzinę dodania
  - Treść komentarza (z obsługą wieloliniowego tekstu)
- ✅ Stylizacja zgodna z panelem WordPress

### **5. System uprawnień**
- ✅ Dodawanie komentarzy: rola **Biuro** i wyżej
- ✅ Przeglądanie komentarzy: zgodnie z uprawnieniami do zamówień
- ✅ Bezpieczeństwo: weryfikacja nonce przy AJAX

## 🎨 **Wygląd i UX**

### **Kolumna Komentarze:**
- Jeśli brak komentarzy: "Brak komentarzy" (szary tekst)
- Jeśli są komentarze: Przycisk "💬 Pokaż (X)" gdzie X to liczba komentarzy

### **Przycisk Dodaj komentarz:**
- Fioletowy kolor (theme: #8e44ad)
- Ikona: 💬
- Pozycja: w kolumnie Akcje, po przycisku "Edytuj dane"

### **Lista komentarzy:**
- Wyświetlana po kliknięciu "Pokaż"
- Maksymalna wysokość: 300px (z scrollem)
- Każdy komentarz w osobnym bloku
- Border po lewej stronie w kolorze fioletowym

### **Modal dodawania komentarza:**
- Wyśrodkowany na ekranie
- Tło z overlay (półprzezroczyste czarne)
- Pole tekstowe (textarea) 4 wiersze
- Przyciski: "Dodaj komentarz" (primary) i "Anuluj"

## 🔧 **Implementacja techniczna**

### **Struktura bazy danych:**
```sql
CREATE TABLE wp_vectis_order_comments (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    order_id mediumint(9) NOT NULL,
    user_id bigint(20) unsigned NOT NULL,
    comment_text text NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    INDEX order_id_idx (order_id),
    INDEX user_id_idx (user_id)
)
```

### **AJAX Handler:**
```php
add_action('wp_ajax_kv_add_order_comment', 'kv_add_order_comment_ajax');
```

### **JavaScript funkcje:**
- `openCommentModal(orderId)` - otwiera modal
- `closeCommentModal()` - zamyka modal
- `saveComment()` - zapisuje komentarz przez AJAX
- `toggleComments(orderId)` - rozwija/zwija listę komentarzy

### **Aktualizacja struktury bazy:**
- Wersja bazy danych zwiększona do **1.1**
- Automatyczne tworzenie tabeli przy pierwszym załadowaniu

## 📋 **Jak używać:**

1. **Dodawanie komentarza:**
   - W panelu administracyjnym (Zamówienia)
   - Kliknij "💬 Dodaj komentarz" przy wybranym zamówieniu
   - Wpisz treść komentarza w modal
   - Kliknij "Dodaj komentarz"

2. **Przeglądanie komentarzy:**
   - W kolumnie "Komentarze" kliknij "💬 Pokaż (X)"
   - Lista komentarzy rozwinie się poniżej
   - Ponowne kliknięcie zwija listę

3. **Informacje w komentarzu:**
   - Autor - pogrubiony, fioletowy
   - Data - format: dd.mm.YYYY HH:MM
   - Treść - z obsługą nowych linii

## 🚀 **Zalety rozwiązania:**

✅ **Nie zajmuje dużo miejsca** - komentarze są zwinięte domyślnie
✅ **Wiele komentarzy** - można dodawać nieograniczoną liczbę
✅ **Historia komunikacji** - wszystkie komentarze z datami i autorami
✅ **Szybkie dodawanie** - AJAX bez przeładowania strony
✅ **Bezpieczne** - sprawdzanie uprawnień i nonce
✅ **Przejrzyste** - czytelny interfejs zgodny z WordPress

## 🔄 **Automatyczna migracja:**

Przy pierwszym załadowaniu wtyczki po aktualizacji:
- Automatycznie zostanie utworzona tabela `wp_vectis_order_comments`
- Nie ma potrzeby ręcznej aktywacji czy migracji
- Działa dla wszystkich istniejących instalacji

## 📝 **Uwagi techniczne:**

- Komentarze są **trwale zapisywane** w osobnej tabeli
- Usunięcie zamówienia **nie usuwa** komentarzy (można dodać CASCADE jeśli potrzeba)
- Data jest zapisywana w czasie serwera WordPress (`current_time('mysql')`)
- Komentarze są sortowane od najnowszych
- Tekst jest sanityzowany (`sanitize_textarea_field`)
- Wyświetlanie z `nl2br()` dla obsługi nowych linii

System komentarzy jest w pełni funkcjonalny i gotowy do użycia! 🎉
