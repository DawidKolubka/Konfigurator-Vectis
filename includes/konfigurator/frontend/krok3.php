<?php
// krok3.php
?>
<div class="step-content">
    <h2>Krok 3: Wybierz układ</h2>
    <?php
    $selected_ksztalt_id = isset($_SESSION['kv_configurator']['ksztalt']) ? $_SESSION['kv_configurator']['ksztalt'] : 0;
    $all_uklady = get_option('kv_uklad_options', array());

    // Filtrowanie dostępnych układów zależnie od wybranego kształtu
    $available_uklady = array_filter($all_uklady, function($u) use ($selected_ksztalt_id) {
        return (isset($u['ksztalt_id']) && $u['ksztalt_id'] == $selected_ksztalt_id);
    });

    // Grupowanie (X1, POZIOMY, PIONOWY, INNY)
    $groupedLayouts = array(
        'X1'       => array(),
        'POZIOMY'  => array(),
        'PIONOWY'  => array(),
        'INNY'     => array()
    );

    foreach ($available_uklady as $u_index => $u_item) {
        $name = trim($u_item['name']);
        if (strcasecmp($name, 'X1') === 0) {
            $groupedLayouts['X1'][$u_index] = $u_item;
        } elseif (stripos($name, 'POZIOMY') !== false) {
            $groupedLayouts['POZIOMY'][$u_index] = $u_item;
        } elseif (stripos($name, 'PIONOWY') !== false) {
            $groupedLayouts['PIONOWY'][$u_index] = $u_item;
        } else {
            $groupedLayouts['INNY'][$u_index] = $u_item;
        }
    }

    // Wyświetlanie w grupach
    foreach (['X1', 'POZIOMY', 'PIONOWY', 'INNY'] as $groupKey) {
        if (!empty($groupedLayouts[$groupKey])) {
            // Etykieta grupy
            $groupLabel = ($groupKey === 'X1') ? 'X1' : (
                ($groupKey === 'POZIOMY') ? 'X – POZIOMY' : (
                    ($groupKey === 'PIONOWY') ? 'X – PIONOWY' : 'Inne'
                )
            );
            echo '<div class="layout-group">';
            echo '<h3>' . esc_html($groupLabel) . '</h3>';

            foreach ($groupedLayouts[$groupKey] as $u_index => $u_item) {
                ?>
                <div class="option-container">
                    <label class="option-label" style="cursor:pointer;">
                        <input type="radio" name="uklad" value="<?php echo esc_attr($u_index); ?>">
                        <span class="option-name"><?php echo esc_html($u_item['name']); ?></span>
                    </label>
                    <?php if (!empty($u_item['image'])): ?>
                        <div class="option-image">
                            <img src="<?php echo esc_url($u_item['image']); ?>" alt="">
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }

            echo '</div>';
        }
    }

    if (empty($available_uklady)) {
        echo "<p>Brak dostępnych układów dla wybranego kształtu.</p>";
    }
    ?>
</div>
