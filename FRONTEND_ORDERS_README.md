# Strona zarządzania zamówieniami - Frontend

## Instrukcja użycia

### 1. Utworzenie strony

Utwórz nową stronę w WordPress z shortcode:
```
[moje-zamowienia]
```

### 2. Funkcjonalności

#### Dla użytkowników niezalogowanych:
- Komunikat z linkiem do logowania

#### Dla zalogowanych użytkowników:
- Lista wszystkich zamówień użytkownika
- Tabela z kolumnami:
  - Nr zamówienia
  - Nr zamówienia klienta
  - Data utworzenia
  - Status zamówienia
  - Szczegóły (przycisk rozwijający)
  - Akcje

#### Statusy zamówień:
- **draft** (Wersja robocza) - można edytować i anulować
- **submitted** (Wysłane) - można tylko anulować
- **cancelled** (Anulowane) - tylko do podglądu
- **completed** (Zakończone) - tylko do podglądu

#### Dostępne akcje:
- **Edytuj** - dostępne tylko dla statusu 'draft'
- **Anuluj** - dostępne dla statusów 'draft' i 'submitted'

### 3. Bezpieczeństwo

- Każdy użytkownik widzi tylko swoje zamówienia
- Sprawdzanie uprawnień przy każdej akcji
- Zabezpieczenia CSRF (nonce)

### 4. Integracja z konfiguratorem

- Przycisk "Edytuj" przekierowuje do konfiguratora z załadowanymi danymi zamówienia
- Przycisk "Utwórz nowe zamówienie" przekierowuje do czystego konfiguratora
- Po edycji użytkownik może powrócić do listy zamówień

### 5. Responsywność

- Tabela dostosowuje się do różnych rozmiarów ekranu
- Na urządzeniach mobilnych zmniejszony font i padding

### 6. Wymagane funkcje w bazie danych

Upewnij się, że tabela `wp_vectis_orders` ma kolumny:
- `user_id` - ID użytkownika
- `status` - status zamówienia  
- `customer_order_number` - numer zamówienia klienta
- `order_notes` - uwagi do zamówienia
- `updated_at` - data ostatniej aktualizacji

### 7. Przykład użycia

1. Utwórz stronę "Moje zamówienia"
2. Dodaj shortcode `[moje-zamowienia]`
3. Opublikuj stronę
4. Użytkownicy będą mogli zarządzać swoimi zamówieniami
