# Statusy zamówień - Konfigurator Vectis

## 📋 **Nowe statusy zamówień**

System zamówień został zaktualizowany o nowe statusy, które lepiej odzwierciedlają rzeczywisty proces realizacji:

### **Statusy:**

1. **🟡 Wersja robocza** (`draft`)
   - Zamówienie w trakcie tworzenia
   - Klient może edytować
   - Nie wysyłane powiadomienia

2. **🔵 Wysłane** (`submitted`)  
   - Zamówienie zostało oficjalnie złożone
   - Przekazane do biura/handlowca
   - **Email:** "Twoje zamówienie zostało wysłane do realizacji"

3. **🟠 W realizacji** (`processing`)
   - Zamówienie jest aktualnie przetwarzane
   - Zespół pracuje nad realizacją  
   - **Email:** "Twoje zamówienie jest w realizacji"

4. **🟣 Częściowo zrealizowane** (`partially_completed`)
   - Część zamówienia została ukończona
   - Reszta w trakcie przygotowania
   - **Email:** "Część Twojego zamówienia została zrealizowana"

5. **🟢 Zrealizowane** (`completed`)
   - Zamówienie w pełni ukończone
   - Gotowe do odbioru/dostawy
   - **Email:** "Twoje zamówienie zostało w pełni zrealizowane"

6. **🔴 Niezrealizowane** (`cancelled`) 
   - Zamówienie anulowane/odrzucone
   - **Email:** "Twoje zamówienie zostało oznaczone jako niezrealizowane"

## 🎨 **Kolory statusów**

### **Panel administracyjny:**
- **Wersja robocza:** Żółty (#fff3cd / #856404)
- **Wysłane:** Zielony (#d4edda / #155724)  
- **W realizacji:** Żółty (#fff3cd / #856404)
- **Częściowo zrealizowane:** Pomarańczowy (#ffeaa7 / #d63384)
- **Zrealizowane:** Zielony (#d4edda / #155724)
- **Niezrealizowane:** Czerwony (#f8d7da / #721c24)

### **Frontend (strona klienta):**
- **Wersja robocza:** Żółty (#fff3cd / #856404)
- **Wysłane:** Niebieski (#d1ecf1 / #0c5460)
- **W realizacji:** Żółty (#fff3cd / #856404) 
- **Częściowo zrealizowane:** Pomarańczowy (#ffeaa7 / #d63384)
- **Zrealizowane:** Zielony (#d4edda / #155724)
- **Niezrealizowane:** Czerwony (#f8d7da / #721c24)

### **Email powiadomienia:**
- **Wysłane:** Cyan (#17a2b8) z ikoną 📤
- **W realizacji:** Żółty (#ffc107) z ikoną ⚙️
- **Częściowo zrealizowane:** Pomarańczowy (#fd7e14) z ikoną 🔄
- **Zrealizowane:** Zielony (#28a745) z ikoną ✅  
- **Niezrealizowane:** Czerwony (#dc3545) z ikoną ❌

## 🔧 **Gdzie zostało zaktualizowane:**

### **1. System powiadomień** (`includes/notifications.php`)
- ✅ Zaktualizowane etykiety statusów
- ✅ Nowe wiadomości email dla każdego statusu
- ✅ Nowe kolory i ikony w template'ach

### **2. Panel administracyjny** (`includes/zamowienia/admin.php`)
- ✅ Dropdown z nowymi statusami
- ✅ Validacja AJAX dla nowego statusu  
- ✅ Style CSS dla wszystkich statusów
- ✅ Filtrowanie zamówień dla roli Biuro

### **3. Frontend klienta** (`includes/zamowienia/frontend.php`)
- ✅ Style CSS dla nowych statusów
- ✅ Wyświetlanie prawidłowych etykiet

## 📧 **Przykładowe wiadomości email:**

### **Status: Wysłane**
> 📤 Twoje zamówienie zostało oficjalnie wysłane do realizacji. Nasze biuro rozpocznie jego przetwarzanie.

### **Status: W realizacji**  
> ⚙️ Twoje zamówienie jest obecnie w realizacji. Skontaktujemy się z Tobą w razie potrzeby dodatkowych informacji.

### **Status: Częściowo zrealizowane**
> 🔄 Część Twojego zamówienia została zrealizowana. Reszta jest w trakcie przygotowania. Skontaktujemy się z Tobą wkrótce.

### **Status: Zrealizowane**
> 🎉 Gratulacje! Twoje zamówienie zostało w pełni zrealizowane. Skontaktuj się z nami, aby ustalić szczegóły odbioru lub dostawy.

### **Status: Niezrealizowane**  
> ❌ Twoje zamówienie zostało oznaczone jako niezrealizowane. Jeśli masz pytania, skontaktuj się z naszym biurem obsługi klienta.

## 🔄 **Flow statusów**

Zalecany przepływ statusów zamówienia:

```
Wersja robocza → Wysłane → W realizacji → [Częściowo zrealizowane] → Zrealizowane
                    ↓
                Niezrealizowane (w dowolnym momencie)
```

## 🎯 **Role i uprawnienia**

- **Klient:** Widzi wszystkie swoje zamówienia z odpowiednimi statusami
- **Biuro/Handlowiec/Admin:** Może zmieniać statusy przez dropdown w panelu
- **System:** Automatycznie wysyła powiadomienia o każdej zmianie statusu

Statusy są teraz bardziej precyzyjne i lepiej odzwierciedlają rzeczywisty proces realizacji zamówień! 🚀