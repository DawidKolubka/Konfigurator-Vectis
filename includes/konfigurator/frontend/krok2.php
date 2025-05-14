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
        
        if (!empty($ksztalt_options)) {
            foreach ($ksztalt_options as $k_index => $k_item) {
                $is_selected = ($selected_ksztalt == $k_index) ? 'selected' : '';
                ?>
                <div class="shape-item <?php echo $is_selected; ?>" data-value="<?php echo esc_attr($k_index); ?>">
                    <?php if (!empty($k_item['image'])): ?>
                        <div class="shape-image">
                            <img src="<?php echo esc_url($k_item['image']); ?>" alt="<?php echo esc_attr($k_item['name']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="shape-name"><?php echo esc_html($k_item['name']); ?></div>
                    
                    <!-- Ukryte pole radio, które będzie zaznaczane automatycznie -->
                    <input type="radio" name="ksztalt" value="<?php echo esc_attr($k_index); ?>" <?php checked($selected_ksztalt, $k_index); ?> style="display:none;">
                </div>
                <?php
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
            
            // Opcjonalnie możemy wyzwolić zdarzenie change
            const event = new Event('change');
            radioInput.dispatchEvent(event);
        });
    });
});
</script>
