<?php
// krok4.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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
$ileSlotow   = 1;
if (preg_match('/X(\d+)/i', $layoutName, $matches)) {
    $ileSlotow = intval($matches[1]);
} elseif (stripos($layoutName, 'pionowy') !== false || stripos($layoutName, 'poziomy') !== false) {
    $ileSlotow = 2;
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
?>

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
    <div class="ramka-slots">
        <?php if ($uklad_image): ?>
            <div class="ramka-image-container">
                <img src="<?php echo esc_url($uklad_image); ?>" alt="Układ" class="uklad-image">
            </div>
            <div class="slots-container">
                <?php
                $empty_slot_img = 'http://konfigurator-vectis.local/wp-content/uploads/2025/02/wybor.svg';
                for ($i = 0; $i < $ileSlotow; $i++):
                    $mechID = $slotData[$i]['mechanizm'];
                    $slotImg = $empty_slot_img;
                    if (!empty($mechID) && isset($mechanizm_options[$mechID]['frame_image'])) {
                        $slotImg = $mechanizm_options[$mechID]['frame_image'];
                    }
                ?>
                    <div class="slot" data-slot="<?php echo $i; ?>">
                        <img id="slot-img-<?php echo $i; ?>" src="<?php echo esc_url($slotImg); ?>" alt="Slot <?php echo ($i+1); ?>">
                        <div class="slot-summary">
                            <div><b>Mechanizm:</b> <span id="slot-mech-name-<?php echo $i; ?>">
                                <?php echo (!empty($mechID) && isset($mechanizm_options[$mechID]['name'])) ? esc_html($mechanizm_options[$mechID]['name']) : 'Brak'; ?>
                            </span></div>
                            <div><b>Technologia:</b> <span id="slot-tech-summary-<?php echo $i; ?>">
                                <?php 
                                $techID = $slotData[$i]['technologia'];
                                $techName = '—';
                                if (!empty($techID) && isset($technologia_options[$techID])) {
                                    $techName = $technologia_options[$techID]['technology'] ?? '—';
                                }
                                echo esc_html($techName);
                                ?>
                            </span></div>
                            <div><b>Kolor:</b> <span id="slot-color-summary-<?php echo $i; ?>">
                                <?php echo ($slotData[$i]['kolor_mechanizmu']) ? esc_html($slotData[$i]['kolor_mechanizmu']) : '—'; ?>
                            </span></div>
                        </div>
                        <!-- Ukryte pola -->
                        <input type="hidden" name="mechanizm_<?php echo $i; ?>" id="mechanizm_<?php echo $i; ?>" value="<?php echo esc_attr($slotData[$i]['mechanizm']); ?>">
                        <input type="hidden" name="technologia_<?php echo $i; ?>" id="technologia_<?php echo $i; ?>" value="<?php echo esc_attr($slotData[$i]['technologia']); ?>">
                        <input type="hidden" name="kolor_mechanizmu_<?php echo $i; ?>" id="kolor_mechanizmu_<?php echo $i; ?>" value="<?php echo esc_attr($slotData[$i]['kolor_mechanizmu']); ?>">
                    </div>
                <?php endfor; ?>
            </div>
        <?php else: ?>
            <div>Brak grafiki układu</div>
        <?php endif; ?>
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
            $mIkona = $mech['frame_image'] ?? $empty_slot_img;
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
            updateSlotBorders();
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
        
        // Dropdown koloru – wyświetlamy wszystkie dostępne kolory
        const colorSelect = document.getElementById('edit-color-select');
        colorSelect.innerHTML = '';
        const allColors = <?php echo json_encode(array_values($kolor_mechanizmu_options)); ?>;
        allColors.forEach(c => {
            if (!c.name) return;
            const opt = document.createElement('option');
            opt.value = c.name;
            opt.text = c.name;
            colorSelect.appendChild(opt);
        });
        
        const currentColor = document.getElementById(`kolor_mechanizmu_${slotIndex}`).value;
        if (currentColor) {
            colorSelect.value = currentColor;
        }
        
        techSelect.onchange = function() {
            const chosenTechID = this.value;
            const techInput = document.getElementById(`technologia_${slotIndex}`);
            techInput.value = chosenTechID;
            
            // Debug - wypisujemy wartość wybranej technologii
            console.log(`Zmieniono technologię dla slotu ${slotIndex}: ID=${chosenTechID}`);
            
            const chosenTech = relTech.find(t => t.ID == chosenTechID);
            const newColor = chosenTech ? chosenTech.colorName : '';
            document.getElementById(`kolor_mechanizmu_${slotIndex}`).value = newColor;
            
            // Aktualizacja podsumowania w bloku slotu
            document.getElementById(`slot-tech-summary-${slotIndex}`).textContent = chosenTech ? chosenTech.nazwa : '—';
            document.getElementById(`slot-color-summary-${slotIndex}`).textContent = newColor ? newColor : '—';
            updateSlotBorders();
        };
        
        colorSelect.onchange = function() {
            const chosenColor = this.value;
            document.getElementById(`kolor_mechanizmu_${slotIndex}`).value = chosenColor;
            document.getElementById(`slot-color-summary-${slotIndex}`).textContent = chosenColor ? chosenColor : '—';
            updateSlotBorders();
        };

        panel.style.display = 'block';
        slotElement.parentNode.insertBefore(panel, slotElement.nextSibling);
        updateSlotBorders();
    }

    // Zamknięcie panelu edycji
    document.getElementById('close-slot-settings').addEventListener('click', () => {
        document.getElementById('slot-settings-panel').style.display = 'none';
        updateSlotBorders();
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
            slotImg.src = newMech ? (newMech.ikona || 'http://konfigurator-vectis.local/wp-content/uploads/2025/02/wybor.svg') : 'http://konfigurator-vectis.local/wp-content/uploads/2025/02/wybor.svg';
            document.getElementById(`slot-mech-name-${activeSlot}`).textContent = newMech ? newMech.nazwa : 'Brak';
            document.getElementById(`slot-tech-summary-${activeSlot}`).textContent = '—';
            document.getElementById(`slot-color-summary-${activeSlot}`).textContent = '—';
            const slotEl = document.querySelector(`.slot[data-slot="${activeSlot}"]`);
            showSlotSettings(activeSlot, slotEl);
            updateSlotBorders();
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
    updateSlotBorders();
    
    // Debug - wypisz zawartość pól na starcie
    console.log("Inicjalne wartości pól:");
    for (let i = 0; i < <?php echo $ileSlotow; ?>; i++) {
        const mechField = document.getElementById(`mechanizm_${i}`);
        const techField = document.getElementById(`technologia_${i}`);
        const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
        console.log(`Slot ${i+1}: mechanizm=${mechField?.value}, technologia=${techField?.value}, kolor=${colorField?.value}`);
    }
});
</script>
