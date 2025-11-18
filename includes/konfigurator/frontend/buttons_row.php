<?php
defined('ABSPATH') or die('Brak dostępu');
/**
 * Wspólny plik z przyciskami dla wszystkich kroków konfiguratora
 */
?>
<div class="navigation-buttons-row">
    <?php if ($step === 5): ?>
    <!-- Pole na numer zamówienia klienta tylko w kroku 5 (podsumowanie) -->
    <div class="custom-order-number">
        <label for="customer_order_number">Twój numer zamówienia (opcjonalnie):</label>
        <input type="text" name="customer_order_number" id="customer_order_number" maxlength="150" 
               placeholder="Wpisz swój wewnętrzny numer zamówienia (max. 150 znaków)"
               value="<?php echo isset($_SESSION['kv_configurator']['customer_order_number']) ? esc_attr($_SESSION['kv_configurator']['customer_order_number']) : ''; ?>">
        <p class="description">
            Pole opcjonalne. Możesz tu wpisać swój wewnętrzny numer zamówienia.
        </p>
    </div>
    
    <!-- Pole na uwagi do zamówienia -->
    <div class="order-notes">
        <label for="order_notes">Uwagi do zamówienia:</label>
        <textarea name="order_notes" id="order_notes" rows="4" cols="50" maxlength="500" 
                  placeholder="Dodatkowe uwagi lub informacje dotyczące zamówienia (max. 500 znaków)"><?php echo isset($_SESSION['kv_configurator']['order_notes']) ? esc_textarea($_SESSION['kv_configurator']['order_notes']) : ''; ?></textarea>
        <p class="description">
            Pole opcjonalne. Możesz tu wpisać dodatkowe uwagi lub informacje dotyczące zamówienia.
        </p>
    </div>
    <?php endif; ?>
    
    <div class="nav-buttons-container">
        <!-- Grupa przycisków akcji (Zapisz/Anuluj) -->
        <div class="action-buttons">
            <button type="submit" name="kv_global_save" class="btn-save">Wyślij zamówienie</button>
            <?php if ($step === 5): ?>
            <button type="submit" name="kv_global_save_draft" class="btn-save-draft">Zapisz wersję roboczą</button>
            <?php endif; ?>
            <button type="submit" name="kv_global_cancel" class="btn-cancel">Anuluj</button>
        </div>

        <!-- Grupa przycisków nawigacyjnych (Wstecz/Dalej) -->
        <div class="nav-buttons">
            <?php if ($step > 1): ?>
                <button type="submit" name="go_back" value="1" class="btn-prev">← Wstecz</button>
            <?php endif; ?>
            
            <?php if ($step < 5): ?>
                <button type="submit" name="go_next" value="1" class="btn-next">Dalej →</button>
            <?php endif; ?>
        </div>
        
        <!-- Grupa przycisków tylko dla podsumowania -->
        <?php if ($step === 5): ?>
            <div class="summary-buttons">
                <button type="submit" name="add_item" class="btn-add-item">Dodaj kolejną pozycję</button>
                <button type="submit" name="kv_global_save" class="btn-order">Złóż zamówienie</button>
            </div>
        <?php endif; ?>    </div>
</div>

<?php if ($step === 5): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Obsługa pola numeru zamówienia klienta
    const customerOrderInput = document.getElementById('customer_order_number');
    
    if (customerOrderInput) {
        // Aktualizuj ukryte pole przy zmianie w polu numeru zamówienia
        customerOrderInput.addEventListener('input', function() {
            const hiddenOrderInput = document.getElementById('hidden_customer_order_number');
            if (hiddenOrderInput) {
                hiddenOrderInput.value = this.value;
            }
        });
    }
    
    // Obsługa pola uwag do zamówienia
    const orderNotesInput = document.getElementById('order_notes');
    
    if (orderNotesInput) {
        // Aktualizuj ukryte pole przy zmianie w polu uwag
        orderNotesInput.addEventListener('input', function() {
            const hiddenNotesInput = document.getElementById('hidden_order_notes');
            if (hiddenNotesInput) {
                hiddenNotesInput.value = this.value;
            }
        });
    }
});
</script>
<?php endif; ?>

<style>
/* Style dla rzędu przycisków */
.navigation-buttons-row {
    margin-top: 30px;
    padding: 15px 0;
    border-top: 1px solid #ddd;
    clear: both;
}

.nav-buttons-container {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

.nav-buttons, .action-buttons, .summary-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* Style dla przycisków */
.navigation-buttons-row button {
    padding: 12px 20px;
    border-radius: 5px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    white-space: nowrap;
    text-align: center;
    min-width: 120px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Wstecz/Dalej */
.btn-prev, .btn-next {
    background-color: #f3f3f3;
    color: #444;
    border: 1px solid #ddd;
}

.btn-prev:hover, .btn-next:hover {
    background-color: #e0e0e0;
    transform: translateY(-2px);
}

/* Zapisz/Anuluj */
.btn-save {
    background-color: #4CAF50;
    color: white;
    border: 1px solid #45a049;
}

.btn-save:hover {
    background-color: #45a049;
    transform: translateY(-2px);
}

.btn-save-draft {
    background-color: #2196F3;
    color: white;
    border: 1px solid #0b7dda;
}

.btn-save-draft:hover {
    background-color: #0b7dda;
    transform: translateY(-2px);
}

.btn-cancel {
    background-color: #f44336;
    color: white;
    border: 1px solid #d32f2f;
}

.btn-cancel:hover {
    background-color: #d32f2f;
    transform: translateY(-2px);
}

/* Dodaj kolejną pozycję / Złóż zamówienie */
.btn-add-item {
    background-color: #2196F3;
    color: white;
    border: 1px solid #0b7dda;
}

.btn-add-item:hover {
    background-color: #0b7dda;
    transform: translateY(-2px);
}

.btn-order {
    background-color: #ff9800;
    color: white;
    border: 1px solid #e68a00;
    font-weight: 600;
}

.btn-order:hover {
    background-color: #e68a00;
    transform: translateY(-2px);
}

/* Style dla pola numeru zamówienia */
.custom-order-number {
    margin-bottom: 20px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.custom-order-number label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.custom-order-number input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.custom-order-number .description {
    margin-top: 5px;
    font-size: 0.9em;
    color: #666;
}

/* Responsywność */
@media (max-width: 990px) {
    .nav-buttons-container {
        flex-direction: row;
        justify-content: center;
    }
    
    .nav-buttons, .action-buttons, .summary-buttons {
        margin: 5px 0;
    }
    
    .custom-order-number {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .nav-buttons-container {
        flex-direction: column;
        align-items: center;
    }
    
    .nav-buttons, .action-buttons, .summary-buttons {
        justify-content: center;
        width: 100%;
    }
    
    .navigation-buttons-row button {
        width: 100%;
        margin: 5px 0;
    }
}
</style>
