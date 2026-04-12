<?php
// krok2.php
?>
<div class="step-content">
    <h2>Wybierz kształt produktu</h2>
    
    <div class="shape-container">
        <?php
        // Pobieramy dostępne opcje kształtów z bazy
        $ksztalt_options = get_option('kv_ksztalt_options', array());
        $selected_ksztalt = isset($_SESSION['kv_configurator']['ksztalt']) ? $_SESSION['kv_configurator']['ksztalt'] : '';
        
        // Pobieramy wybraną serię z sesji
        $selected_seria = isset($_SESSION['kv_configurator']['seria']) ? $_SESSION['kv_configurator']['seria'] : '';
        
        if (!empty($ksztalt_options)) {
            foreach ($ksztalt_options as $k_index => $k_item) {
                // Filtruje kształty - pokazuj tylko te przypisane do wybranej serii
                $k_seria = isset($k_item['seria']) ? $k_item['seria'] : '';
                
                // Jeśli seria jest wybrana, pokazuj tylko kształty przypisane do tej serii
                // Jeśli seria nie jest wybrana, nie pokazuj żadnych kształtów
                if (empty($selected_seria) || $k_seria !== $selected_seria) {
                    continue;
                }
                
                $is_selected = ($selected_ksztalt == $k_index) ? 'selected' : '';
                // Pobierz nazwę kształtu do danych atrybutu (będzie używane przez JavaScript)
                $shape_name = isset($k_item['name']) ? $k_item['name'] : '';
                ?>
                <div class="shape-item <?php echo $is_selected; ?>" data-value="<?php echo esc_attr($k_index); ?>" data-shape-name="<?php echo esc_attr($shape_name); ?>">
                    <?php if (!empty($k_item['image'])): ?>
                        <div class="shape-image">
                            <img src="<?php echo esc_url($k_item['image']); ?>" alt="<?php echo esc_attr($k_item['name']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="shape-name"><?php echo esc_html($k_item['name']); ?></div>
                    
                    <!-- Ukryte pole radio, które będzie zaznaczane automatycznie -->
                    <input type="radio" name="ksztalt" value="<?php echo esc_attr($k_index); ?>" <?php checked($selected_ksztalt, $k_index); ?> style="display:none;">
                    <!-- Ukryte pole przechowujące nazwę kształtu -->
                    <input type="hidden" name="ksztalt_name" value="<?php echo esc_attr($shape_name); ?>">
                </div>
                <?php
            }
            
            // Jeśli żaden kształt nie spełnia kryteriów, wyświetl komunikat
            if (empty($selected_seria)) {
                echo "<p><strong>Najpierw wybierz serię w poprzednim kroku.</strong></p>";
            } elseif (empty($ksztalt_options) || !in_array($selected_seria, array_column(array_filter($ksztalt_options, function($item) { return isset($item['seria']) && !empty($item['seria']); }), 'seria'))) {
                echo "<p>Brak kształtów dostępnych dla wybranej serii.</p>";
            }
        } else {
            echo "<p>Brak dostępnych kształtów.</p>";
        }
        ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Obsługa kliknięcia w element kształtu
    const shapeItems = document.querySelectorAll('.shape-item');
    
    shapeItems.forEach(item => {
        item.addEventListener('click', function() {
            // Usuń klasę 'selected' z wszystkich elementów
            shapeItems.forEach(el => el.classList.remove('selected'));
            
            // Dodaj klasę 'selected' do klikniętego elementu
            this.classList.add('selected');
            
            // Zaznacz ukryte pole radio w tym elemencie
            const radioInput = this.querySelector('input[type="radio"]');
            radioInput.checked = true;
            
            // Zapisz nazwę kształtu do sessionStorage - będzie można użyć w kroku 4 i podsumowaniu
            const shapeName = this.getAttribute('data-shape-name');
            sessionStorage.setItem('selected_shape_name', shapeName);
            
            // Opcjonalnie możemy wyzwolić zdarzenie change
            const event = new Event('change');
            radioInput.dispatchEvent(event);
        });
    });

    // Przy załadowaniu strony, jeśli już wybrano jakiś kształt, zapisz jego nazwę do sessionStorage
    const selectedShape = document.querySelector('.shape-item.selected');
    if (selectedShape) {
        const shapeName = selectedShape.getAttribute('data-shape-name');
        sessionStorage.setItem('selected_shape_name', shapeName);
    }
});
</script>
