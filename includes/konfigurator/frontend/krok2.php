<?php
// krok2.php
?>
<div class="step-content">
    <h2>Krok 2: Wybierz kształt</h2>
    <?php
    $ksztalt_options = get_option('kv_ksztalt_options', array());
    if (!empty($ksztalt_options)) {
        foreach ($ksztalt_options as $k_index => $k_item) {
            ?>
            <div class="option-container">
                <label class="option-label">
                    <input type="radio" name="ksztalt" value="<?php echo esc_attr($k_index); ?>">
                    <span class="option-name"><?php echo esc_html($k_item['name']); ?></span>
                </label>
                <?php if (!empty($k_item['image'])): ?>
                    <div class="option-image">
                        <img src="<?php echo esc_url($k_item['image']); ?>" alt="">
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
    } else {
        echo "<p>Brak dostępnych kształtów.</p>";
    }
    ?>
</div>
