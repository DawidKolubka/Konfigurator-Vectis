<?php
// krok3.php

// Pobierz aktualny wybór z sesji (jeśli istnieje)
$current_choice = isset($_SESSION['configurator']['krok3']) ? $_SESSION['configurator']['krok3'] : 'Nie wybrano';

// Dodaj debugowanie zmiennych sesji
error_log('KONFIGURATOR krok3: Aktualny wybór z sesji: "' . $current_choice . '"');
error_log('KONFIGURATOR krok3: Zawartość $_SESSION[\'configurator\']: ' . print_r($_SESSION['configurator'] ?? [], true));

// Funkcja do analizy układu i wydobycia liczby slotów
function analyze_layout_info($layout_name) {
    // Określ liczbę slotów
    $slots_count = 1; // Domyślnie dla X1
    if (preg_match('/X(\d+)/', $layout_name, $matches)) {
        $slots_count = intval($matches[1]);
    }
    
    // Określ orientację
    $is_vertical = (stripos($layout_name, 'PIONOWY') !== false);
    $orientation = $is_vertical ? 'pionowy' : 'poziomy';
    
    return [
        'slots' => $slots_count,
        'orientation' => $orientation
    ];
}

// Tworzenie panelu debugującego, który będzie ukryty domyślnie
// i pokazany po wybraniu opcji
?>

<style>
.layout-debug-panel {
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    padding: 10px;
    margin: 10px 0;
    border-radius: 5px;
    font-family: monospace;
    display: none; /* Ukryte domyślnie */
}
.layout-debug-panel.active {
    display: block;
}

.step3-debug-panel {
    background-color: #e8f4fb;
    border: 1px solid #b8e0f7;
    padding: 12px;
    margin: 15px 0 20px 0;
    border-radius: 5px;
    font-family: monospace;
    line-height: 1.5;
}
.step3-debug-panel h4 {
    margin-top: 0;
    color: #0078bf;
    border-bottom: 1px solid #b8e0f7;
    padding-bottom: 5px;
}
.step3-debug-status {
    font-style: italic;
    font-size: 0.9em;
    color: #666;
}
</style>

<!-- Panel debugowania - umieść przed generowaniem opcji układów -->
<div class="layout-debug-panel" id="debug-panel">
    <h4 style="margin-top: 0;">Informacje debugowania:</h4>
    <div id="debug-content">Wybierz układ, aby zobaczyć szczegóły.</div>
</div>

<!-- Panel debugowania dla kroku 3 -->
<div class="step3-debug-panel">
    <h4>Debug Kroku 3 - Wybór Układu</h4>
    <div id="step3-current-choice">
        <p>Wybór w sesji: <strong><?php echo htmlspecialchars($current_choice); ?></strong></p>
        <p>Aktualny wybór w interfejsie: <strong id="ui-selection">Nie zaznaczono</strong></p>
    </div>
    <div id="step3-layout-analysis">
        <p>Analiza wybranego układu:</p>
        <pre id="step3-analysis-details">Wybierz układ, aby zobaczyć analizę</pre>
    </div>
    <div class="step3-debug-status" id="debug-save-status"></div>
</div>

<script>
jQuery(document).ready(function($) {
    // Funkcja analizująca wybrany układ
    function analyzeSelectedLayout() {
        var selectedLayout = $('input[name="krok3"]:checked').val();
        if (!selectedLayout) return;
        
        // Wyciągnij liczbę slotów z nazwy
        var slotsMatch = selectedLayout.match(/X(\d+)/);
        var slotsCount = slotsMatch ? parseInt(slotsMatch[1]) : 1;
        
        // Sprawdź orientację
        var isVertical = selectedLayout.toUpperCase().includes('PIONOWY');
        var orientation = isVertical ? 'pionowy' : 'poziomy';
        
        // Aktualizuj panel debugowania
        var debugInfo = '<strong>Wybrany układ:</strong> ' + selectedLayout + '<br>' +
                        '<strong>Liczba slotów:</strong> ' + slotsCount + '<br>' +
                        '<strong>Orientacja:</strong> ' + orientation + '<br>' +
                        '<strong>Klasa CSS:</strong> ' + (isVertical ? 'vertical' : 'horizontal');
        
        $('#debug-content').html(debugInfo);
        $('#debug-panel').addClass('active');
        
        // Zapisz do localStorage dla kroku 4
        localStorage.setItem('debug_layout_info', JSON.stringify({
            name: selectedLayout,
            slots: slotsCount,
            orientation: orientation,
            cssClass: isVertical ? 'vertical' : 'horizontal'
        }));
    }
    
    function analyzeLayoutChoice() {
        var selectedInput = $('input[name="krok3"]:checked');
        if (selectedInput.length === 0) {
            $('#ui-selection').text('Nie zaznaczono');
            $('#step3-analysis-details').text('Wybierz układ, aby zobaczyć analizę');
            return;
        }
        
        var selectedValue = selectedInput.val();
        $('#ui-selection').text(selectedValue);
        
        // Analizuj układ
        var layoutMatch = selectedValue.match(/X(\d+)/);
        var slotsCount = layoutMatch ? parseInt(layoutMatch[1]) : 1;
        var isVertical = selectedValue.toUpperCase().includes('PIONOWY');
        
        // Pokaż analizę
        var analysisText = 'Układ: ' + selectedValue + '\n' +
                          'Liczba slotów: ' + slotsCount + '\n' +
                          'Orientacja: ' + (isVertical ? 'PIONOWY' : 'POZIOMY') + '\n' +
                          'Przewidywana klasa CSS: ' + (isVertical ? 'vertical' : 'horizontal');
        
        $('#step3-analysis-details').text(analysisText);
        
        // Zapisz natychmiast do sesji przez AJAX
        $('#debug-save-status').text('Zapisywanie wyboru...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_configurator_step',
                step: 'krok3',
                value: selectedValue
            },
            success: function(response) {
                console.log('Natychmiastowy zapis wyboru:', response);
                $('#debug-save-status').text('Wybór zapisany do sesji (' + new Date().toLocaleTimeString() + ')');
                
                // Odśwież panel debugowania, aby pokazywał aktualną wartość sesji
                setTimeout(function() {
                    // Pobierz aktualną wartość z sesji
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_configurator_step',
                            step: 'krok3'
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                $('#step3-current-choice p:first-child strong').text(response.data);
                            }
                        }
                    });
                }, 500);
            },
            error: function(xhr, status, error) {
                $('#debug-save-status').text('Błąd zapisu: ' + error);
                console.error('Błąd zapisu wyboru:', error);
            }
        });
    }
    
    // Nasłuchuj zmiany wyboru
    $('input[name="krok3"]').on('change', function() {
        analyzeSelectedLayout();
        analyzeLayoutChoice();
        
        // Wyślij do konsoli dla celów diagnostycznych
        console.log('Zmieniono wybór na:', $(this).val());
    });
    
    // Sprawdź czy jest już coś wybrane
    analyzeSelectedLayout();
    analyzeLayoutChoice();
});
</script>

<?php
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