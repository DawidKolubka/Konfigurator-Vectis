<?php
// krok1.php
?>
<div class="step-content">
    <h2>Wybierz serię produktów którą chcesz konfigurować</h2>
    
    <div class="series-container">
        <?php
        // Pobieramy dostępne opcje serii z bazy
        $seria_options = get_option('kv_seria_options', array());
        $selected_seria = isset($_SESSION['kv_configurator']['seria']) ? $_SESSION['kv_configurator']['seria'] : '';
        
        if (!empty($seria_options)) {
            foreach ($seria_options as $index => $serie) {
                $is_selected = ($selected_seria === $serie['name']) ? 'selected' : '';
                ?>
                <div class="series-item <?php echo $is_selected; ?>" data-value="<?php echo esc_attr($serie['name']); ?>">
                    <?php if (!empty($serie['image'])): ?>
                        <div class="series-image">
                            <img src="<?php echo esc_url($serie['image']); ?>" alt="<?php echo esc_attr($serie['name']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="series-name"><?php echo esc_html($serie['name']); ?></div>
                    
                    <!-- Ukryte pole radio, które będzie zaznaczane automatycznie -->
                    <input type="radio" name="seria" value="<?php echo esc_attr($serie['name']); ?>" <?php checked($selected_seria, $serie['name']); ?> style="display:none;">
                </div>
                <?php
            }
        } else {
            echo "<p>Brak dostępnych serii.</p>";
        }
        ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Obsługa kliknięcia w element serii
    const seriesItems = document.querySelectorAll('.series-item');
    
    seriesItems.forEach(item => {
        item.addEventListener('click', function() {
            // Usuń klasę 'selected' z wszystkich elementów
            seriesItems.forEach(el => el.classList.remove('selected'));
            
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