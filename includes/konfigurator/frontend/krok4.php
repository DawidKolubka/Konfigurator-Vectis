<?php
// krok4.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Dodaj tę funkcję tuż przed początkiem głównego kodu
function debug_mechanizm_options() {
    $mechanizm_options = get_option('kv_mechanizm_options', []);
    error_log('DEBUGOWANIE MECHANIZMÓW:');
    
    foreach ($mechanizm_options as $id => $mech) {
        error_log("Mechanizm #{$id}: " . print_r([
            'name' => $mech['name'] ?? 'BRAK',
            'frame_image' => $mech['frame_image'] ?? 'BRAK',
            'snippet' => $mech['snippet'] ?? 'BRAK',
            'selected_colors' => !empty($mech['selected_colors']) ? count($mech['selected_colors']) : 0,
        ], true));
    }
}

// Wywołaj funkcję diagnostyczną
debug_mechanizm_options();

// Helper do bezpiecznego pozbycia się slashes
if (!function_exists('kv_strip_and_sanitize')) {
    function kv_strip_and_sanitize($value) {
        // Usuwa ewentualne backslashe (np. magic_quotes) i potem używa sanitize_text_field
        if (is_array($value)) {
            return array_map('kv_strip_and_sanitize', $value);
        }
        // Jeżeli to w ogóle nie jest string, nie modyfikuj
        if (!is_string($value)) {
            return $value;
        }
        // stripslash i sanitize
        $value = stripslashes($value);
        // WordPressowa funkcja do usuwania potencjalnie niebezpiecznych znaków
        // (działa głównie na polach tekstowych)
        $value = sanitize_text_field($value);
        return $value;
    }
}

// Pobieramy dane z bazy – analogicznie do kroku 3
$uklad_options            = get_option('kv_uklad_options', []);
$kolor_ramki_options      = get_option('kv_kolor_ramki_options', []);
$mechanizm_options        = get_option('kv_mechanizm_options', []);
$technologia_options      = get_option('kv_technologia_options', []);
$kolor_mechanizmu_options = get_option('kv_kolor_mechanizmu_options', []);

// Obsługa przesłania formularza (przyciski „Wstecz"/„Dalej" są w głównym pliku)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['kv_configurator_nonce']) || !wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')) {
        die('Błąd: nieprawidłowy nonce.');
    }

    // Zapis koloru ramki
    if (isset($_POST['kolor_ramki'])) {
        $_SESSION['kv_configurator']['kolor_ramki'] = kv_strip_and_sanitize($_POST['kolor_ramki']);
    }

    // Ustalamy liczbę slotów na podstawie wybranego układu
    $uklad_index = isset($_SESSION['kv_configurator']['uklad']) ? kv_strip_and_sanitize($_SESSION['kv_configurator']['uklad']) : 0;
    $layoutName  = isset($uklad_options[$uklad_index]['name']) ? $uklad_options[$uklad_index]['name'] : '';

    $ileSlotow = 1;
    if (preg_match('/X(\d+)/i', $layoutName, $matches)) {
        $ileSlotow = intval($matches[1]);
    } elseif (stripos($layoutName, 'pionowy') !== false || stripos($layoutName, 'poziomy') !== false) {
        $ileSlotow = 2;
    }

    // Zapis danych dla każdego slotu
    for ($i = 0; $i < $ileSlotow; $i++) {
        $mech_key  = 'mechanizm_' . $i;
        $tech_key  = 'technologia_' . $i;
        $color_key = 'kolor_mechanizmu_' . $i;
        if (isset($_POST[$mech_key])) {
            $_SESSION['kv_configurator'][$mech_key] = kv_strip_and_sanitize($_POST[$mech_key]);
        }
        if (isset($_POST[$tech_key])) {
            $_SESSION['kv_configurator'][$tech_key] = kv_strip_and_sanitize($_POST[$tech_key]);
        }
        if (isset($_POST[$color_key])) {
            $_SESSION['kv_configurator'][$color_key] = kv_strip_and_sanitize($_POST[$color_key]);
        }
    }

    $message = 'Zmiany zostały zapisane.';
}

// Ustalamy wybrany układ oraz liczbę slotów
$uklad_index = isset($_SESSION['kv_configurator']['uklad']) ? kv_strip_and_sanitize($_SESSION['kv_configurator']['uklad']) : 0;
$layoutName  = isset($uklad_options[$uklad_index]['name']) ? $uklad_options[$uklad_index]['name'] : '';
$uklad_image = isset($uklad_options[$uklad_index]['image']) ? $uklad_options[$uklad_index]['image'] : '';

// Definicja domyślnego obrazka dla pustego slotu
$empty_slot_img = 'https://www.isdvectis.pl/wp-content/uploads/2025/04/wybor.svg';

$ileSlotow   = 1;
if (preg_match('/X(\d+)/i', $layoutName, $matches)) {
    $ileSlotow = intval($matches[1]);
} elseif (stripos($layoutName, 'pionowy') !== false || stripos($layoutName, 'poziomy') !== false) {
    $ileSlotow = 2;
}

// --- NOWA LOGIKA: Określenie klasy orientacji ---
$orientation_class = 'vertical'; // Domyślnie pionowy

// Sprawdź najpierw wzorzec X1, X2, itd. - te są domyślnie poziome (horizontal)
if (preg_match('/X\d+/i', $layoutName)) {
    $orientation_class = 'horizontal'; // Dla wszystkich układów X1, X2, X3 itd.
}

// Następnie sprawdź jawne określenie PIONOWY/POZIOMY - to nadpisuje domyślną regułę
if (stripos($layoutName, 'PIONOWY') !== false) {
    $orientation_class = 'vertical';
} elseif (stripos($layoutName, 'POZIOMY') !== false) {
    $orientation_class = 'horizontal';
}
// --- KONIEC NOWEJ LOGIKI ---

// Tuż po określeniu zmiennej $ileSlotow i $orientacja, a przed linią 169
$slots = [];
for ($i = 0; $i < $ileSlotow; $i++) {
    $mechanizm_value = isset($_SESSION['kv_configurator']['mechanizm_' . $i]) ? $_SESSION['kv_configurator']['mechanizm_' . $i] : '';
    $technologia_value = isset($_SESSION['kv_configurator']['technologia_' . $i]) ? $_SESSION['kv_configurator']['technologia_' . $i] : '';
    
    $slots[] = [
        'slot_index' => $i,
        'mechanizm' => $mechanizm_value,
        'technologia' => $technologia_value
    ];
}

// Kolor ramki
$selected_color_index = isset($_SESSION['kv_configurator']['kolor_ramki']) ? kv_strip_and_sanitize($_SESSION['kv_configurator']['kolor_ramki']) : '';
$initial_img = ($selected_color_index !== '' && isset($kolor_ramki_options[$selected_color_index]['image']))
    ? $kolor_ramki_options[$selected_color_index]['image'] : '';

// Dane slotów – zapisane wybory (mechanizm, technologia, kolor)
$slotData = [];
for ($i = 0; $i < $ileSlotow; $i++) {
    $slotData[$i] = [
        'mechanizm'        => isset($_SESSION['kv_configurator']['mechanizm_' . $i]) ? kv_strip_and_sanitize($_SESSION['kv_configurator']['mechanizm_' . $i]) : '',
        'technologia'      => isset($_SESSION['kv_configurator']['technologia_' . $i]) ? kv_strip_and_sanitize($_SESSION['kv_configurator']['technologia_' . $i]) : '',
        'kolor_mechanizmu' => isset($_SESSION['kv_configurator']['kolor_mechanizmu_' . $i]) ? kv_strip_and_sanitize($_SESSION['kv_configurator']['kolor_mechanizmu_' . $i]) : '',
    ];
}

// Dane dla JavaScript – mechanizmy i technologie
$mechanizmy_json = [];
foreach ($mechanizm_options as $m_index => $mech) {
    $mechanizmy_json[] = [
        'ID'    => $m_index,
        'nazwa' => $mech['name'] ?? '',
        'ikona' => $mech['frame_image'] ?? '',
    ];
}

// Budujemy strukturę technologii dopasowaną do struktury w technologia.php
$technologie_json = [];
foreach ($technologia_options as $tech_index => $tech) {
    $colorID = $tech['color'] ?? 0;
    $colorName = isset($kolor_mechanizmu_options[$colorID]['name']) ? $kolor_mechanizmu_options[$colorID]['name'] : '';
    $technologie_json[] = [
        'ID'        => $tech_index, // Index technologii jako ID
        'group'     => $tech['group'] ?? 0,  // Powiązanie z mechanizmem (indeks)
        'nazwa'     => $tech['technology'] ?? '',
        'color'     => $colorID,
        'colorName' => $colorName
    ];
}

// Przygotowanie danych o przypisanych kolorach do mechanizmów
$mechanizm_kolor_map = [];
foreach ($mechanizm_options as $mech_id => $mech) {
    // Pobierz przypisane kolory mechanizmu (pole selected_colors)
    $selected_colors = isset($mech['selected_colors']) && is_array($mech['selected_colors']) ? $mech['selected_colors'] : [];
    
    // Zapisz mapowanie mechanizm => dostępne kolory
    $mechanizm_kolor_map[$mech_id] = $selected_colors;
}

// Przekaż mapę do JavaScript
$js_mechanizm_kolor_map = json_encode($mechanizm_kolor_map);
?>

<script>
// Globalny obiekt z kolorami mechanizmów
window.kolor_mechanizmu_options = <?php echo json_encode($kolor_mechanizmu_options); ?>;
</script>

<div class="step-content">
    <h2>Krok 4: Wybierz mechanizmy i kolor ramki</h2>

    <?php if (!empty($message)): ?>
        <div class="message-box">
            <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>

    <!-- (A) Wybór koloru ramki -->
    <div class="ramka-color-selector">
        <h3>Wybierz kolor ramki</h3>
        <select name="kolor_ramki" id="kolor_ramki">
            <option value="">— Wybierz kolor —</option>
            <?php foreach ($kolor_ramki_options as $colorIndex => $colorData): ?>
                <?php if (!isset($colorData['name'])) continue; ?>
                <?php $sel = ($colorIndex == $selected_color_index) ? 'selected' : ''; ?>
                <option value="<?php echo esc_attr($colorIndex); ?>" <?php echo $sel; ?>
                        data-img="<?php echo esc_attr($colorData['image'] ?? ''); ?>">
                    <?php echo esc_html($colorData['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="preview-img-container">
            <?php if ($initial_img): ?>
                <img id="preview-img" src="<?php echo esc_url($initial_img); ?>" alt="Podgląd koloru">
            <?php else: ?>
                <img id="preview-img" src="" alt="Podgląd koloru" style="display:none;">
            <?php endif; ?>
        </div>
    </div>

    <!-- (B) Ramka z interaktywnymi slotami -->
    <div class="ramka-slots <?php echo esc_attr($orientation_class); ?>" data-slots="<?php echo esc_attr($ileSlotow); ?>">
        <div class="ramka-image-container">
            <?php
            // Pętla generująca sloty
            for ($i = 0; $i < $ileSlotow; $i++):
                $mechID = $slotData[$i]['mechanizm'] ?? '';
                $slotImg = $empty_slot_img; // Domyślny obrazek

                // Sprawdzenie obrazka mechanizmu (tak jak w podsumowaniu)
                if (!empty($mechID) && isset($mechanizm_options[$mechID])) {
                    if (!empty($mechanizm_options[$mechID]['frame_image'])) {
                        $slotImg = $mechanizm_options[$mechID]['frame_image'];
                    } elseif (!empty($mechanizm_options[$mechID]['image'])) {
                        $slotImg = $mechanizm_options[$mechID]['image']; // Fallback
                    }
                }
            ?>
                <div class="slot" data-slot="<?php echo $i; ?>">
                    <img
                        id="slot-img-<?php echo $i; ?>"
                        src="<?php echo esc_url($slotImg); ?>"
                        alt="Slot <?php echo $i + 1; ?>"
                    >

                    <!-- ukryte pola muszą mieć też id, bo JS używa getElementById -->
                    <input
                        type="hidden"
                        id="mechanizm_<?php echo $i; ?>"
                        name="mechanizm_<?php echo $i; ?>"
                        value="<?php echo esc_attr($slotData[$i]['mechanizm']); ?>"
                    >
                    <input
                        type="hidden"
                        id="technologia_<?php echo $i; ?>"
                        name="technologia_<?php echo $i; ?>"
                        value="<?php echo esc_attr($slotData[$i]['technologia']); ?>"
                    >
                    <input
                        type="hidden"
                        id="kolor_mechanizmu_<?php echo $i; ?>"
                        name="kolor_mechanizmu_<?php echo $i; ?>"
                        value="<?php echo esc_attr($slotData[$i]['kolor_mechanizmu']); ?>"
                    >
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Sekcja podsumowań obok slotów -->
    <div class="ramka-slots-summary <?php echo esc_attr($orientation_class); ?>" data-slots="<?php echo esc_attr($ileSlotow); ?>">
        <?php for ($i = 0; $i < $ileSlotow; $i++): 
            $mechID = $slotData[$i]['mechanizm'];
            $techID = $slotData[$i]['technologia'];
            $colorVal = $slotData[$i]['kolor_mechanizmu'];
        ?>
            <div class="slot-summary" data-slot="<?php echo $i; ?>">
                <div><b>Mechanizm:</b> <span id="slot-mech-name-<?php echo $i; ?>">
                    <?php 
                    $mechName = (!empty($mechID) && isset($mechanizm_options[$mechID]['name'])) 
                        ? esc_html($mechanizm_options[$mechID]['name']) 
                        : '—';
                    echo $mechName;
                    ?>
                </span></div>
                <div><b>Technologia:</b> <span id="slot-tech-summary-<?php echo $i; ?>">
                    <?php 
                    $techName = (!empty($techID) && isset($technologia_options[$techID]['technology'])) 
                        ? esc_html($technologia_options[$techID]['technology']) 
                        : '—';
                    echo $techName;
                    ?>
                </span></div>
                <div><b>Kolor:</b> <span id="slot-color-summary-<?php echo $i; ?>">
                    <?php echo !empty($colorVal) ? esc_html($colorVal) : '—'; ?>
                </span></div>
            </div>
        <?php endfor; ?>
    </div>

    <!-- (C) Panel edycji ustawień dla aktywnego slotu (dynamiczny, wstawiany pod klikniętym slotem) -->
    <div id="slot-settings-panel" class="slot-settings-panel" style="display:none;">
        <h4>Edycja slotu <span id="active-slot-number"></span></h4>
        <div class="edit-panel-content">
            <div class="tech-color-edit">
                <p>Wybierz technologię:</p>
                <select id="edit-tech-select" class="edit-tech-select"></select>
                <p>Wybierz kolor mechanizmu:</p>
                <select id="edit-color-select" class="edit-color-select"></select>
            </div>
        </div>
        <button type="button" id="close-slot-settings">Zamknij edycję</button>
    </div>

    <!-- (D) Panel mechanizmów – zawsze widoczny na dole -->
    <h3>Mechanizmy</h3>
    <div id="mechanism-list" class="mechanism-list">
        <?php foreach ($mechanizm_options as $m_index => $mech): 
            $mName  = $mech['name'] ?? 'Bez nazwy';
            $mIkona = $mech['image'] ?? $empty_slot_img;
        ?>
            <div class="mechanizm-item" data-mech-id="<?php echo esc_attr($m_index); ?>">
                <img src="<?php echo esc_url($mIkona); ?>" alt="">
                <div><?php echo esc_html($mName); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Dane z PHP
const mechanizmyData = <?php echo json_encode($mechanizmy_json); ?>;
const technologieData = <?php echo json_encode($technologie_json); ?>;
const mechanizmKolorMap = <?php echo $js_mechanizm_kolor_map; ?>;
let activeSlot = null;

document.addEventListener('DOMContentLoaded', function() {
    // Podgląd koloru ramki
    const selectKolorRamki = document.getElementById('kolor_ramki');
    const previewImg = document.getElementById('preview-img');
    selectKolorRamki.addEventListener('change', () => {
        const opt = selectKolorRamki.options[selectKolorRamki.selectedIndex];
        const imgSrc = opt.getAttribute('data-img');
        if (imgSrc) {
            previewImg.src = imgSrc;
            previewImg.style.display = 'block';
        } else {
            previewImg.src = '';
            previewImg.style.display = 'none';
        }
    });

    // Obsługa kliknięcia w slot – wyświetlamy panel edycji pod klikniętym slotem
    const slots = document.querySelectorAll('.slot');
    slots.forEach(slot => {
        slot.addEventListener('click', () => {
            activeSlot = slot.getAttribute('data-slot');
            updateSlotState(); // Zaktualizowano z updateSlotBorders()
            document.getElementById('active-slot-number').innerText = parseInt(activeSlot) + 1;
            showSlotSettings(activeSlot, slot);
        });
    });

    // Aktualizacja obramowania slotów
    function updateSlotBorders() {
        const slots = document.querySelectorAll('.slot');
        slots.forEach(s => {
            const idx = s.getAttribute('data-slot');
            const mechVal = document.getElementById(`mechanizm_${idx}`).value;
            const techVal = document.getElementById(`technologia_${idx}`).value;
            const colorVal = document.getElementById(`kolor_mechanizmu_${idx}`).value;
            
            // Sprawdzamy czy jest to aktywny slot
            if (idx == activeSlot) {
                if (mechVal && techVal && colorVal) {
                    s.style.border = '2px solid green';
                } else {
                    s.style.border = '2px solid yellow';
                }
            } else {
                if (mechVal && techVal && colorVal) {
                    s.style.border = '2px solid green';
                } else {
                    s.style.border = '1px solid #ccc';
                }
            }
        });
    }

    // Funkcja sprawdzająca czy slot ma wypełnione dane i na tej podstawie pokazująca/ukrywająca podsumowanie
    function updateSlotSummaryVisibility() {
        for (let i = 0; i < <?php echo $ileSlotow; ?>; i++) {
            const mechVal = document.getElementById(`mechanizm_${i}`).value;
            const techVal = document.getElementById(`technologia_${i}`).value; 
            const colorVal = document.getElementById(`kolor_mechanizmu_${i}`).value;
            
            // Znajdujemy podsumowanie dla danego slotu
            const summary = document.querySelector(`.ramka-slots-summary .slot-summary[data-slot="${i}"]`);
            if (!summary) continue;
            
            // Sprawdzamy orientację (horizontal/vertical)
            const isHorizontal = document.querySelector('.ramka-slots').classList.contains('horizontal');
            
            if (isHorizontal) {
                // W układzie poziomym pokazujemy podsumowanie gdy wszystkie pola są wypełnione
                if (mechVal && techVal && colorVal) {
                    summary.classList.add('filled');
                } else {
                    summary.classList.remove('filled');
                }
            } else {
                // W układzie pionowym pokazujemy podsumowanie gdy jest wybrany mechanizm
                if (mechVal) {
                    summary.classList.add('filled');
                } else {
                    summary.classList.remove('filled');
                }
            }
        }
    }

    // Połączona funkcja aktualizująca stan slotu
    function updateSlotState() {
        updateSlotBorders();
        updateSlotSummaryVisibility();
        updateSlotSummaries(); // Dodaj tę linię!
    }

    // Funkcja wypełniająca podsumowania slotów
    function updateSlotSummaries() {
        for (let i = 0; i < <?php echo $ileSlotow; ?>; i++) {
            const mechField = document.getElementById(`mechanizm_${i}`);
            const techField = document.getElementById(`technologia_${i}`);
            const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
            
            if (!mechField || !mechField.value) continue;
            
            // Pobierz elementy podsumowania
            const mechNameSpan = document.getElementById(`slot-mech-name-${i}`);
            const techSummarySpan = document.getElementById(`slot-tech-summary-${i}`);
            const colorSummarySpan = document.getElementById(`slot-color-summary-${i}`);
            const summaryDiv = document.querySelector(`.slot-summary[data-slot="${i}"]`);
            
            if (!summaryDiv) continue;
            
            // Znajdź nazwę mechanizmu
            const mechData = mechanizmyData.find(m => m.ID == mechField.value);
            if (mechNameSpan && mechData) {
                mechNameSpan.textContent = mechData.nazwa;
            }
            
            // Znajdź nazwę technologii
            const techData = technologieData.find(t => t.ID == techField.value);
            if (techSummarySpan && techData) {
                techSummarySpan.textContent = techData.nazwa;
            } else if (techSummarySpan) {
                techSummarySpan.textContent = "—";
            }
            
            // Znajdź nazwę koloru
            if (colorSummarySpan && colorField && colorField.value) {
                const colorData = window.kolor_mechanizmu_options[colorField.value];
                colorSummarySpan.textContent = colorData ? colorData.name : "—";
            } else if (colorSummarySpan) {
                colorSummarySpan.textContent = "—";
            }
            
            // Dodaj klasę filled, jeśli mamy mechanizm
            if (mechField.value) {
                summaryDiv.classList.add('filled');
            } else {
                summaryDiv.classList.remove('filled');
            }
        }
    }

    // Funkcja pokazująca panel edycji dla aktywnego slotu
    function showSlotSettings(slotIndex, slotElement) {
        let panel = document.getElementById('slot-settings-panel');
        panel.querySelector('#active-slot-number').innerText = parseInt(slotIndex) + 1;
        
        const mechVal = document.getElementById(`mechanizm_${slotIndex}`).value;
        if (!mechVal) {
            panel.style.display = 'none';
            return;
        }
        
        // Filtrujemy technologie przypisane do wybranego mechanizmu
        const relTech = technologieData.filter(t => t.group == mechVal);
        const techSelect = document.getElementById('edit-tech-select');
        techSelect.innerHTML = '';
        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.text = 'Wybierz technologię';
        techSelect.appendChild(defaultOpt);
        
        relTech.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.ID;
            opt.text = t.nazwa + (t.colorName ? ' (' + t.colorName + ')' : '');
            techSelect.appendChild(opt);
        });
        
        const currentTech = document.getElementById(`technologia_${slotIndex}`).value;
        if (currentTech) {
            techSelect.value = currentTech;
        }

        // Pobierz dostępne kolory dla wybranego mechanizmu
        const availableColors = mechanizmKolorMap[mechVal] || [];
        
        // Utwórz select dla kolorów mechanizmu
        const colorSelect = document.getElementById('edit-color-select');
        colorSelect.innerHTML = '';
        colorSelect.disabled = availableColors.length === 0; // Wyłącz, jeśli brak dostępnych kolorów
        
        if (availableColors.length > 0) {
            // Dodaj opcję domyślną
            const defaultColorOpt = document.createElement('option');
            defaultColorOpt.value = '';
            defaultColorOpt.text = 'Wybierz kolor mechanizmu';
            colorSelect.appendChild(defaultColorOpt);
            
            // Dodaj dostępne kolory
            availableColors.forEach(colorId => {
                if (colorId && window.kolor_mechanizmu_options && window.kolor_mechanizmu_options[colorId]) {
                    const colorData = window.kolor_mechanizmu_options[colorId];
                    const opt = document.createElement('option');
                    opt.value = colorData.name || '';
                    opt.text = colorData.name || 'Bez nazwy';
                    colorSelect.appendChild(opt);
                }
            });
            
            // Ustaw aktualnie wybrany kolor
            const currentColor = document.getElementById(`kolor_mechanizmu_${slotIndex}`).value;
            if (currentColor) {
                colorSelect.value = currentColor;
            }
        }
        
        // Obsługa zmiany koloru mechanizmu
        colorSelect.onchange = function() {
            const chosenColor = this.value;
            document.getElementById(`kolor_mechanizmu_${slotIndex}`).value = chosenColor;
            document.getElementById(`slot-color-summary-${slotIndex}`).textContent = chosenColor || '—';
            updateSlotState();
        };
        
        // Obsługa zmiany technologii
        techSelect.onchange = function() {
            const chosenTechID = this.value;
            const techInput = document.getElementById(`technologia_${slotIndex}`);
            techInput.value = chosenTechID;
            
            const chosenTech = relTech.find(t => t.ID == chosenTechID);
            
            // Aktualizacja podsumowania w bloku slotu
            document.getElementById(`slot-tech-summary-${slotIndex}`).textContent = chosenTech ? chosenTech.nazwa : '—';
            updateSlotState();
        };
        
        panel.style.display = 'block';
        slotElement.parentNode.insertBefore(panel, slotElement.nextSibling);
        updateSlotState();
    }

    // Zamknięcie panelu edycji
    document.getElementById('close-slot-settings').addEventListener('click', () => {
        document.getElementById('slot-settings-panel').style.display = 'none';
        updateSlotState(); // Zaktualizowano z updateSlotBorders()
    });

    // Panel mechanizmów – kliknięcie w ikonę zmienia mechanizm
    const mechanismItems = document.querySelectorAll('.mechanizm-item');
    mechanismItems.forEach(item => {
        item.addEventListener('click', () => {
            if (activeSlot === null) {
                alert('Najpierw kliknij w slot, który chcesz edytować.');
                return;
            }
            const newMechID = item.getAttribute('data-mech-id');
            document.getElementById(`mechanizm_${activeSlot}`).value = newMechID;
            document.getElementById(`technologia_${activeSlot}`).value = '';
            document.getElementById(`kolor_mechanizmu_${activeSlot}`).value = '';
            const slotImg = document.getElementById(`slot-img-${activeSlot}`);
            const newMech = mechanizmyData.find(m => m.ID == newMechID);
            slotImg.src = newMech ? (newMech.ikona || '<?php echo esc_url($empty_slot_img); ?>') : '<?php echo esc_url($empty_slot_img); ?>';
            document.getElementById(`slot-mech-name-${activeSlot}`).textContent = newMech ? newMech.nazwa : 'Brak';
            document.getElementById(`slot-tech-summary-${activeSlot}`).textContent = '—';
            document.getElementById(`slot-color-summary-${activeSlot}`).textContent = '—';
            const slotEl = document.querySelector(`.slot[data-slot="${activeSlot}"]`);
            showSlotSettings(activeSlot, slotEl);
            updateSlotState();
        });
    });

    // Walidacja formularza – przed wysłaniem
    const form = document.getElementById('konfigurator-form');
    if (form) {
        form.addEventListener('submit', (e) => {
            // Sprawdź czy kliknięto przycisk "go_next" (Dalej)
            const isGoingForward = e.submitter && e.submitter.name === 'go_next';
            
            if (isGoingForward && document.querySelector('input[name="kv_step"]').value === '4') {
                let valid = true;
                let errorMessages = [];
                
                // Sprawdź kolor ramki
                const kolorVal = document.getElementById('kolor_ramki').value;
                if (!kolorVal) {
                    errorMessages.push('Musisz wybrać kolor ramki.');
                    valid = false;
                }
                
                // Sprawdź wszystkie sloty
                for (let i = 0; i < <?php echo $ileSlotow; ?>; i++) {
                    const mechField = document.getElementById(`mechanizm_${i}`);
                    const techField = document.getElementById(`technologia_${i}`);
                    const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
                    
                    // Debug - wypisanie wartości pól przed walidacją
                    console.log(`Walidacja slotu ${i+1}: mechanizm=${mechField?.value}, technologia=${techField?.value}, kolor=${colorField?.value}`);
                    
                    if (!mechField || !mechField.value) {
                        errorMessages.push(`Slot ${i+1} nie ma wybranego mechanizmu.`);
                        valid = false;
                        continue; // Bez mechanizmu nie sprawdzamy dalej
                    }
                    
                    if (!techField || !techField.value) {
                        errorMessages.push(`Slot ${i+1} nie ma wybranej technologii.`);
                        valid = false;
                    }
                    
                    if (!colorField || !colorField.value) {
                        errorMessages.push(`Slot ${i+1} nie ma wybranego koloru mechanizmu.`);
                        valid = false;
                    }
                }
                
                if (!valid) {
                    e.preventDefault();
                    alert(errorMessages.join('\n'));
                    return false;
                }
            }
        });
    }
    
    // Wywołaj na starcie, aby odpowiednio oznaczyć wypełnione sloty
    updateSlotState();
    
    // Debug - wypisz zawartość pól na starcie
    console.log("Inicjalne wartości pól:");
    for (let i = 0; i < <?php echo $ileSlotow; ?>; i++) {
        const mechField = document.getElementById(`mechanizm_${i}`);
        const techField = document.getElementById(`technologia_${i}`);
        const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
        console.log(`Slot ${i+1}: mechanizm=${mechField?.value}, technologia=${techField?.value}, kolor=${colorField?.value}`);
    }
    
    // Wykonaj na końcu, aby upewnić się, że podsumowania są prawidłowo widoczne/ukryte
    updateSlotSummaryVisibility();

    // Funkcja wypełniająca istniejące obrazy slotów
    function fillExistingSlotImages() {
        for (let i = 0; i < <?php echo $ileSlotow; ?>; i++) {
            const mechVal = document.getElementById(`mechanizm_${i}`).value;
            if (!mechVal) continue;
            const img = document.getElementById(`slot-img-${i}`);
            const mechData = mechanizmyData.find(m => m.ID == mechVal);
            if (img && mechData && mechData.ikona) {
                img.src = mechData.ikona;
            }
        }
    }
    fillExistingSlotImages();
    
    // Wywołaj na starcie, aby wypełnić podsumowania
    updateSlotSummaries();
});
</script>
