<?php
// krok1.php
?>
<div class="step-content">
    <h2>Krok 1: Wybierz serię</h2>
    <?php
    // Pobieramy dostępne opcje serii z bazy
    $seria_options = get_option('kv_seria_options', array());
    if (!empty($seria_options)) {
        foreach ($seria_options as $index => $serie) {
            ?>
            <div class="option-container">
                <label class="option-label">
                    <!-- Uwaga: to pole input jest częścią GŁÓWNEGO formularza (w configurator.php) -->
                    <input type="radio" name="seria" value="<?php echo esc_attr($serie['name']); ?>">
                    <span class="option-name"><?php echo esc_html($serie['name']); ?></span>
                </label>
                <?php if (!empty($serie['image'])): ?>
                    <div class="option-image">
                        <img src="<?php echo esc_url($serie['image']); ?>" alt="">
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
    } else {
        echo "<p>Brak dostępnych serii.</p>";
    }
    ?>
</div>