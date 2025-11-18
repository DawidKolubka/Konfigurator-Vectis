<?php
?>

<div class="step-content">
    <h2 style="margin-bottom: 50px;">Wybierz czy chcesz konfigurować produkt poziomy czy pionowy oraz oznacz
wielokrotność ramki którą chcesz stworzyć</h2>
    <?php
    $selected_ksztalt_id = isset($_SESSION['kv_configurator']['ksztalt']) ? $_SESSION['kv_configurator']['ksztalt'] : 0;
    $all_uklady = get_option('kv_uklad_options', array());
    $selected_uklad = isset($_SESSION['kv_configurator']['uklad']) ? $_SESSION['kv_configurator']['uklad'] : '';

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
           
            
            echo '<div class="layout-container">';
            foreach ($groupedLayouts[$groupKey] as $u_index => $u_item) {
                $is_selected = ($selected_uklad == $u_index) ? 'selected' : '';
                ?>
                <div class="layout-item <?php echo $is_selected; ?>" data-value="<?php echo esc_attr($u_index); ?>">
                    <?php if (!empty($u_item['image'])): ?>
                        <div class="layout-image">
                            <img src="<?php echo esc_url($u_item['image']); ?>" alt="<?php echo esc_attr($u_item['name']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="layout-name"><?php echo esc_html($u_item['name']); ?></div>
                    
                    <!-- Ukryte pole radio, które będzie zaznaczane automatycznie -->
                    <input type="radio" name="uklad" value="<?php echo esc_attr($u_index); ?>" <?php checked($selected_uklad, $u_index); ?> style="display:none;">
                </div>
                <?php
            }
            echo '</div></div>';
        }
    }

    if (empty($available_uklady)) {
        echo "<p>Brak dostępnych układów dla wybranego kształtu.</p>";
    }
    ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Obsługa kliknięcia w element układu
    const layoutItems = document.querySelectorAll('.layout-item');
    
    layoutItems.forEach(item => {
        item.addEventListener('click', function() {
            // Usuń klasę 'selected' z wszystkich elementów
            layoutItems.forEach(el => el.classList.remove('selected'));
            
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

<script>
// Funkcja sprawdzająca czy wybrany układ powinien być poziomy
function checkLayoutOrientation() {
  // Sprawdzamy wybór z kroku 3
  var krok3Value = $('input[name="krok3"]:checked').val() || '';
  
  // Sprawdzamy czy zawiera słowo "POZIOMY"
  if (krok3Value.toUpperCase().indexOf('POZIOMY') !== -1) {
      $('.slots-container').removeClass('vertical').addClass('horizontal');
  } else {
      $('.slots-container').removeClass('horizontal').addClass('vertical');
  }
}
</script>