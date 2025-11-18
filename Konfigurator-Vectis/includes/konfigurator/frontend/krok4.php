<?php
// krok4.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['kv_validation_errors']) && !empty($_SESSION['kv_validation_errors'])) {
    echo '<div class="validation-errors" style="color:red; margin-bottom:15px;">';
    foreach ($_SESSION['kv_validation_errors'] as $error) {
        echo '<div>' . esc_html($error) . '</div>';
    }
    echo '</div>';
    unset($_SESSION['kv_validation_errors']);
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

    $message = 'Zmiany zostały zapisane';
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
    // Upewnij się, że wszystkie ID są liczbami całkowitymi
    $groupID = isset($tech['group']) ? intval($tech['group']) : 0;
    $colorID = isset($tech['color']) ? intval($tech['color']) : 0;
    $colorName = isset($kolor_mechanizmu_options[$colorID]['name']) ? $kolor_mechanizmu_options[$colorID]['name'] : '';
    
    $technologie_json[] = [
        'ID' => intval($tech_index),  // Kluczowy fix - zamiana na liczbę
        'group' => $groupID,          // group to ID mechanizmu
        'nazwa' => isset($tech['technology']) ? $tech['technology'] : '',
        'color' => $colorID,
        'colorName' => $colorName,
        'code' => isset($tech['code']) ? $tech['code'] : '',
        'price' => isset($tech['price']) ? floatval($tech['price']) : 0
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
let kolorMechanizmuOptions = <?php echo json_encode($kolor_mechanizmu_options); ?>;
// Filtrujemy elementy o ID=0 (placeholdery)
if (kolorMechanizmuOptions && kolorMechanizmuOptions.length > 0 && kolorMechanizmuOptions[0]) {
    // Usuwamy placeholder z globalnej zmiennej JS
    delete kolorMechanizmuOptions[0];
}
window.kolor_mechanizmu_options = kolorMechanizmuOptions;
</script>

<script>
// Globalne obiekty danych - dodane debugowanie
console.log('Inicjalizacja danych globalnych w kroku 4');
window.mechanizmyData = <?php echo json_encode($mechanizmy_json); ?>;
window.technologieData = <?php echo json_encode($technologie_json); ?>;
window.mechanizmKolorMap = <?php echo $js_mechanizm_kolor_map; ?>;

// Filtrujemy elementy o ID=0 (placeholdery) dla drugiej instancji
let kolorMechanizmuOptions2 = <?php echo json_encode($kolor_mechanizmu_options); ?>;
if (kolorMechanizmuOptions2 && kolorMechanizmuOptions2.length > 0 && kolorMechanizmuOptions2[0]) {
    // Usuwamy placeholder z globalnej zmiennej JS
    delete kolorMechanizmuOptions2[0];
}
window.kolor_mechanizmu_options = kolorMechanizmuOptions2;

// Debug technologii
console.log('Technologie zainicjalizowane:', window.technologieData ? window.technologieData.length : 'BRAK');
if (window.technologieData && window.technologieData.length > 0) {
    // Wypisz relacje technologii z mechanizmami
    console.table(window.technologieData.map(tech => ({
        'Tech ID': tech.ID,
        'Nazwa': tech.nazwa,
        'Grupa/Mechanizm ID': tech.group,
        'Typ Group': typeof tech.group
    })));
}
</script>

<script>
// Definiuję updateSlotEditPanel jako funkcję globalną
window.updateSlotEditPanel = function(slotIndex, mechID) {
    console.log(`Aktualizuję panel ustawień dla slotu ${slotIndex}, mechanizm: ${mechID}`);
    
    // 1. Upewnij się, że ukryte pole mechanizm_X istnieje i ma poprawną wartość
    const mechField = document.getElementById(`mechanizm_${slotIndex}`);
    if (mechField) {
        mechField.value = mechID;
        console.log(`Slot ${slotIndex}: Zaktualizowano pole mechanizm_${slotIndex} na wartość ${mechID}`);
    } else {
        // Jeśli pole nie istnieje, utwórz je
        const mechInput = document.createElement('input');
        mechInput.type = 'hidden';
        mechInput.id = `mechanizm_${slotIndex}`;
        mechInput.name = `mechanizm_${slotIndex}`;
        mechInput.value = mechID;
        document.getElementById('konfigurator-form').appendChild(mechInput);
        console.log(`Slot ${slotIndex}: Utworzono pole mechanizm_${slotIndex} z wartością ${mechID}`);
    }
    
    // 2. Upewnij się, że ukryte pola dla technologii i koloru istnieją
    ensureHiddenFields(slotIndex);
    
    // 3. Aktualizuj panel ustawień
    const panel = document.getElementById(`slot-settings-panel-${slotIndex}`);
    if (!panel) {
        console.error(`Panel dla slotu ${slotIndex} nie znaleziony`);
        return;
    }
    
    const panelContent = panel.querySelector('.edit-panel-content');
    if (!panelContent) {
        console.error(`Nie znaleziono zawartości panelu dla slotu ${slotIndex}`);
        return;
    }
    
    // 4. Pobierz dane mechanizmu
    const mechData = window.mechanizmyData.find(m => String(m.ID) === String(mechID));
    if (!mechData) {
        console.error(`Nie znaleziono danych mechanizmu o ID ${mechID}`);
        return;
    }
    
    // 5. Tworzenie HTML dla technologii
    let techHtml = '<div class="tech-section"><p>Wybierz technologię</p><select id="tech-select-' + slotIndex + '" class="tech-select" data-slot="' + slotIndex + '">';
    techHtml += '<option value="">Wybierz</option>';
    
    // WAŻNA ZMIANA: Porównuj jako liczby, a nie jako ciągi znaków
    const mechIDNum = parseInt(mechID);
    
    // Filtrujemy tylko te technologie, które mają grupę równą mechID (numerycznie)
   const relevantTechs = window.technologieData.filter(t => {
    console.log(`Porównuję technologię: group=${t.group} (${typeof t.group}) z mechID=${mechIDNum} (${typeof mechIDNum})`);
    return String(t.group) === String(mechIDNum);
});
console.log(`Znaleziono ${relevantTechs.length} technologii dla mechanizmu ${mechID}`, relevantTechs);
    console.log(`Slot ${slotIndex}: Znaleziono ${relevantTechs.length} technologii dla mechanizmu ${mechID}`);
    
    // NOWA FUNKCJONALNOŚĆ: Grupuj technologie o tych samych nazwach, pokazuj tylko unikalne nazwy
    const uniqueTechs = [];
    const techNameMap = {}; // Mapa służąca do śledzenia, które technologie już dodaliśmy
    const techNamesToIdsMap = {}; // Mapa przechowująca wszystkie ID technologii o danej nazwie
    
    relevantTechs.forEach(tech => {
        const techName = tech.nazwa;
        if (!techNameMap[techName]) {
            // Jeśli nie dodaliśmy jeszcze technologii o tej nazwie, dodajemy ją do unikalnych
            techNameMap[techName] = tech.ID;
            uniqueTechs.push(tech);
            // Inicjalizacja tablicy dla tej nazwy technologii
            techNamesToIdsMap[techName] = [tech.ID];
        } else {
            // Jeśli już mamy taką nazwę, dodajmy ID do listy powiązań
            techNamesToIdsMap[techName].push(tech.ID);
        }
    });
    
    // Zapisujemy powiązania nazw technologii z ID dla późniejszego użycia
    window.techNamesMap = techNamesToIdsMap;
    
    console.log(`Slot ${slotIndex}: Po usunięciu duplikatów mamy ${uniqueTechs.length} unikalnych technologii`, uniqueTechs);
    console.log('Mapa nazw technologii do ID:', techNamesToIdsMap);
    
    // KLUCZOWA POPRAWKA: Jeśli jest tylko jedna technologia, automatycznie ją wybierz
    if (uniqueTechs.length === 1) {
        const autoTech = uniqueTechs[0];
        const autoTechID = autoTech.ID;
        const techField = document.getElementById(`technologia_${slotIndex}`);
        if (techField) {
            techField.value = autoTechID;
            console.log(`Slot ${slotIndex}: Automatycznie wybrano jedyną dostępną technologię ${autoTechID} (${autoTech.nazwa})`);
            
            // Dodatkowo ustaw kolor mechanizmu na podstawie wybranej technologii
            const originalTech = relevantTechs.find(t => String(t.ID) === String(autoTechID));
            if (originalTech && originalTech.color) {
                const colorField = document.getElementById(`kolor_mechanizmu_${slotIndex}`);
                if (colorField && !colorField.value) {
                    colorField.value = originalTech.color;
                    console.log(`Slot ${slotIndex}: Automatycznie ustawiono kolor ${originalTech.color} na podstawie technologii ${autoTechID}`);
                }
            }
        }
    }
    
    // Sprawdź aktualnie wybraną technologię
    const currentTechID = document.getElementById(`technologia_${slotIndex}`)?.value || '';
    
    uniqueTechs.forEach(tech => {
        // Porównuj jako liczby
        const selected = parseInt(currentTechID) === parseInt(tech.ID) ? 'selected' : '';
        techHtml += `<option value="${tech.ID}" ${selected}>${tech.nazwa}</option>`;
    });
    techHtml += '</select></div>';
    
    // 6. Tworzenie HTML dla kolorów - podobnie
    let colorHtml = '<div class="color-section"><p>Wybierz kolor</p><select id="color-select-' + slotIndex + '" class="color-select" data-slot="' + slotIndex + '">';
    colorHtml += '<option value="">Wybierz</option>';
    
    // Pobierz aktualnie wybrany kolor
    const currentColorID = document.getElementById(`kolor_mechanizmu_${slotIndex}`)?.value || '';
    
    // Pobierz dostępne kolory dla tego mechanizmu (używając mechanizmKolorMap)
    const availableColors = window.mechanizmKolorMap[mechIDNum] || [];
    console.log(`Slot ${slotIndex}: Znaleziono ${availableColors.length} kolorów dla mechanizmu ${mechID}`);
    
    // Określ, czy mamy automatycznie wybrać kolor na podstawie technologii
    let autoSelectedColor = '';
    const selectedTechID = document.getElementById(`technologia_${slotIndex}`)?.value || '';
    
    if (selectedTechID) {
        // Znajdź technologię, aby określić jej kolor
        const selectedTech = window.technologieData.find(t => String(t.ID) === String(selectedTechID));
        if (selectedTech && selectedTech.color) {
            autoSelectedColor = selectedTech.color;
            console.log(`Slot ${slotIndex}: Automatycznie wybieram kolor ${autoSelectedColor} na podstawie technologii ${selectedTechID}`);
        }
    }
    
    // Jeśli nie ma zachowanego wyboru koloru, ale mamy autoSelectedColor, użyj go
    if (!currentColorID && autoSelectedColor) {
        document.getElementById(`kolor_mechanizmu_${slotIndex}`).value = autoSelectedColor;
    }
    
    availableColors.forEach(colorID => {
        const colorInfo = window.kolor_mechanizmu_options[colorID] || {};
        // Użyj autoSelectedColor do określenia, czy ten kolor powinien być wybrany
        const selected = (currentColorID === String(colorID) || (!currentColorID && autoSelectedColor === String(colorID))) ? 'selected' : '';
        const colorName = colorInfo.name || `Kolor ${colorID}`;
        colorHtml += `<option value="${colorID}" ${selected}>${colorName}</option>`;
    });
    colorHtml += '</select></div>';

    // 7. Wstaw nową treść do panelu
    panelContent.innerHTML = techHtml + colorHtml;
    
    // 8. Pokaż panel
    panel.style.display = 'block';
    
    // 9. Dodaj obsługę zdarzeń dla nowo utworzonych selectów
    const techSelect = document.getElementById(`tech-select-${slotIndex}`);
    if (techSelect) {
        // OSTATECZNA POPRAWKA: Jeśli mamy tylko jedną opcję technologii (poza pustą), wybierz ją automatycznie
        if (techSelect.options.length === 2) { // Jedna pusta + jedna opcja
            techSelect.selectedIndex = 1; // Wybierz pierwszą niepustą opcję
            const selectedValue = techSelect.value;
            
            // Natychmiast aktualizuj ukryte pole
            const techField = document.getElementById(`technologia_${slotIndex}`);
            if (techField) {
                techField.value = selectedValue;
                console.log(`Slot ${slotIndex}: Automatycznie wybrano jedyną technologię ${selectedValue}`);
            }
        }
        
        techSelect.addEventListener('change', function() {
            const techValue = this.value;
            const techName = this.options[this.selectedIndex].text;
            const techField = document.getElementById(`technologia_${slotIndex}`);
            
            if (techField) {
                techField.value = techValue;
                console.log(`Slot ${slotIndex}: Zaktualizowano pole technologia_${slotIndex} na wartość ${techValue}`);
                
                // Automatyczny wybór koloru na podstawie technologii
                if (techValue) {
                    // Znajdź technologię, aby określić jej kolor
                    const selectedTech = relevantTechs.find(t => String(t.ID) === String(techValue));
                    if (selectedTech && selectedTech.color) {
                        // Mamy kolor z technologii - ustaw go w ukrytym polu
                        const colorField = document.getElementById(`kolor_mechanizmu_${slotIndex}`);
                        if (colorField) {
                            colorField.value = selectedTech.color;
                            console.log(`Slot ${slotIndex}: Automatycznie ustawiono kolor ${selectedTech.color} na podstawie technologii ${techValue}`);
                            
                            // Aktualizuj również dropdown koloru, jeśli istnieje
                            const colorSelect = document.getElementById(`color-select-${slotIndex}`);
                            if (colorSelect) {
                                for (let i = 0; i < colorSelect.options.length; i++) {
                                    if (String(colorSelect.options[i].value) === String(selectedTech.color)) {
                                        colorSelect.selectedIndex = i;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            if (typeof window.updateSlotSummaries === 'function') {
                window.updateSlotSummaries();
            }
        });
    }
    
    const colorSelect = document.getElementById(`color-select-${slotIndex}`);
    if (colorSelect) {
        colorSelect.addEventListener('change', function() {
            const colorValue = this.value;
            const colorField = document.getElementById(`kolor_mechanizmu_${slotIndex}`);
            if (colorField) {
                colorField.value = colorValue;
                console.log(`Slot ${slotIndex}: Zaktualizowano pole kolor_mechanizmu_${slotIndex} na wartość ${colorValue}`);
            }
            if (typeof window.updateSlotSummaries === 'function') {
                window.updateSlotSummaries();
            }
        });
    }
    
    // 10. Synchronizuj początkowe wartości selectów z ukrytymi polami
    if (techSelect && techSelect.value) {
        const techField = document.getElementById(`technologia_${slotIndex}`);
        if (techField) {
            techField.value = techSelect.value;
        }
    }
    
    if (colorSelect && colorSelect.value) {
        const colorField = document.getElementById(`kolor_mechanizmu_${slotIndex}`);
        if (colorField) {
            colorField.value = colorSelect.value;
        }
    }
};

// Funkcja zapewniająca istnienie ukrytych pól formularza
function ensureHiddenFields(slotIndex) {
    const form = document.getElementById('konfigurator-form');
    if (!form) {
        console.error('Nie znaleziono formularza #konfigurator-form');
        return;
    }
    
    // Sprawdź pole technologii
    let techField = document.getElementById(`technologia_${slotIndex}`);
    if (!techField) {
        techField = document.createElement('input');
        techField.type = 'hidden';
        techField.id = `technologia_${slotIndex}`;
        techField.name = `technologia_${slotIndex}`;
        techField.value = '';
        form.appendChild(techField);
        console.log(`Slot ${slotIndex}: Utworzono pole technologia_${slotIndex}`);
    }
    
    // Sprawdź pole koloru mechanizmu
    let colorField = document.getElementById(`kolor_mechanizmu_${slotIndex}`);
    if (!colorField) {
        colorField = document.createElement('input');
        colorField.type = 'hidden';
        colorField.id = `kolor_mechanizmu_${slotIndex}`;
        colorField.name = `kolor_mechanizmu_${slotIndex}`;
        colorField.value = '';
        form.appendChild(colorField);
        console.log(`Slot ${slotIndex}: Utworzono pole kolor_mechanizmu_${slotIndex}`);
    }
}
</script>

<div class="step-content">
    <?php
    // Sprawdź czy jesteśmy w trybie edycji pozycji (znacznik ustawiany przy kliknięciu "Edytuj")
    $from_edit = isset($_SESSION['kv_configurator']['editing_mode']) && $_SESSION['kv_configurator']['editing_mode'] === true;
    
    if ($from_edit): ?>
        <div class="notice notice-info" style="padding: 15px; margin: 20px 0; background: #e1f5fe; border: 1px solid #81d4fa; border-radius: 5px; color: #01579b;">
            <h3 style="margin: 0 0 10px 0;">✏️ Edycja pozycji</h3>
            <p style="margin: 0;">Edytujesz pozycję z tabeli podsumowania. Możesz zmieniać tylko mechanizmy i kolory w tym kroku.</p>
            <p style="margin: 10px 0 0 0;"><small>Po zakończeniu edycji, pozycja zostanie zaktualizowana w tabeli podsumowania.</small></p>
        </div>
    <?php endif; ?>
    
    <h2>Wybierz mechanizmy i kolory ramki</h2>

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
                        data-img="<?php echo esc_attr($colorData['image'] ?? ''); ?>"
                        data-ramka-id="<?php echo esc_attr($colorIndex); ?>">
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
        <!-- Kontener ramki z dodatkowym data-atrybutem dla kształtu -->
        <div class="ramka-image-container">
            <?php for ($i = 0; $i < $ileSlotow; $i++): 
                $mechID = $slotData[$i]['mechanizm'];
                $techID = $slotData[$i]['technologia'];
                $colorID = $slotData[$i]['kolor_mechanizmu'];

                // Obrazek mechanizmu (jeśli wybrany)
                $slotImg = $empty_slot_img;
                if (!empty($mechID) && isset($mechanizm_options[$mechID]['frame_image'])) {
                    $slotImg = $mechanizm_options[$mechID]['frame_image'];
                }
            ?>
                <div class="slot-wrapper">
                    <div class="slot" data-slot="<?php echo $i; ?>">
                        <img src="<?php echo esc_url($slotImg); ?>" alt="Slot <?php echo $i+1; ?>">
                        <!-- Ukryte pola przechowujące dane slotu -->
                        <input type="hidden" name="mechanizm_<?php echo $i; ?>" id="mechanizm_<?php echo $i; ?>" value="<?php echo esc_attr($mechID); ?>">
                        <input type="hidden" name="technologia_<?php echo $i; ?>" id="technologia_<?php echo $i; ?>" value="<?php echo esc_attr($techID); ?>">
                        <input type="hidden" name="kolor_mechanizmu_<?php echo $i; ?>" id="kolor_mechanizmu_<?php echo $i; ?>" value="<?php echo esc_attr($colorID); ?>">
                    </div>
                    <!-- Panel edycji dla każdego slotu (początkowo ukryty) -->
                    <div id="slot-settings-panel-<?php echo $i; ?>" class="slot-settings-panel" style="display:none;">
                        <h4>Slot <?php echo $i+1; ?></h4>
                        <div class="edit-panel-content">
                            <?php if (empty($mechID)): ?>
                                <p>Wybierz mechanizm</p>
                            <?php else: ?>
                                <p>Wybierz technologię</p>
                                <select id="tech-select-<?php echo $i; ?>" class="tech-select" data-slot="<?php echo $i; ?>">
                                    <option value="">Wybierz</option>
                                    <?php 
                                    // Filtruj technologie dla wybranego mechanizmu i usuń duplikaty nazw
                                    $unique_technologies = [];
                                    $tech_name_map = []; // Mapa do śledzenia unikalnych nazw technologii
                                    
                                    // Krok 1: Zbierz wszystkie technologie dla tego mechanizmu
                                    $relevant_techs = [];
                                    foreach ($technologia_options as $tech_index => $tech_item):
                                        if (isset($tech_item['group']) && (int)$tech_item['group'] === (int)$mechID):
                                            $relevant_techs[$tech_index] = $tech_item;
                                        endif;
                                    endforeach;
                                    
                                    // Krok 2: Wybierz unikalne technologie na podstawie nazw
                                    foreach ($relevant_techs as $tech_index => $tech_item):
                                        $tech_name = $tech_item['technology'] ?? '';
                                        if (!isset($tech_name_map[$tech_name])):
                                            $tech_name_map[$tech_name] = $tech_index;
                                            $unique_technologies[$tech_index] = $tech_item;
                                        endif;
                                    endforeach;
                                    
                                    // Krok 3: Wyświetl unikalne technologie
                                    foreach ($unique_technologies as $tech_index => $tech_item):
                                        $selected = ($techID == $tech_index) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo esc_attr($tech_index); ?>" <?php echo $selected; ?>>
                                            <?php echo esc_html($tech_item['technology'] ?? ''); ?>
                                        </option>
                                    <?php 
                                    endforeach; 
                                    ?>
                                </select>
                                
                                <p>Wybierz kolor</p>
                                <select id="color-select-<?php echo $i; ?>" class="color-select" data-slot="<?php echo $i; ?>">
                                    <option value="">Wybierz</option>
                                    <?php 
                                    // Filtruj kolory dla wybranego mechanizmu
                                    if (!empty($mechID) && isset($mechanizm_options[$mechID]['selected_colors'])):
                                        // Jeśli mamy wybraną technologię, pobierz jej kolor z bazy
                                        $auto_selected_color = '';
                                        // WIĘCEJ NIECO BARDZIEJ AGRESYWNE USTAWIANIE KOLORU
                                        if (!empty($techID) && isset($technologia_options[$techID]['color'])):
                                            $auto_selected_color = $technologia_options[$techID]['color'];
                                            // Wymuszamy kolor z technologii, jeśli nie ma wyraźnie wybranego koloru
                                            if (empty($colorID)) {
                                                $colorID = $auto_selected_color;
                                                // Od razu aktualizujemy dane w sesji
                                                $_SESSION['kv_configurator']['kolor_mechanizmu_' . $i] = $colorID;
                                            }
                                        endif;
                                        
                                        foreach ($mechanizm_options[$mechID]['selected_colors'] as $color_index):
                                            if (isset($kolor_mechanizmu_options[$color_index])):
                                                // Jeśli mamy zapisany kolor, użyj go, w przeciwnym razie użyj koloru z technologii
                                                $selected = ($colorID == $color_index) || 
                                                           (empty($colorID) && $auto_selected_color == $color_index) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo esc_attr($color_index); ?>" <?php echo $selected; ?> data-kolor-id="<?php echo esc_attr($color_index); ?>">
                                            <?php echo esc_html($kolor_mechanizmu_options[$color_index]['name'] ?? ''); ?>
                                        </option>
                                    <?php 
                                            endif;
                                        endforeach;
                                    endif;
                                    ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>



    <!-- (D) Panel mechanizmów – zawsze widoczny na dole -->
    <div class="mechanizmy"><h3>Mechanizmy</h3>
    <div id="mechanism-list" class="mechanism-list">
        <?php foreach ($mechanizm_options as $m_index => $mech): 
            $mIkona = $mech['image'] ?? $empty_slot_img;
        ?>
            <div class="mechanizm-item" data-mech-id="<?php echo esc_attr($m_index); ?>">
                <img src="<?php echo esc_url($mIkona); ?>" alt="<?php echo esc_attr($mech['name'] ?? ''); ?>">
                <div class="name"><?php echo esc_html($mech['name'] ?? ''); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
        </div>
</div>

<script>
// Dane z PHP
const mechanizmyData = <?php echo json_encode($mechanizmy_json); ?>;
const technologieData = <?php echo json_encode($technologie_json); ?>;
const mechanizmKolorMap = <?php echo $js_mechanizm_kolor_map; ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Funkcja do synchronizacji kolorów z technologiami dla wszystkich slotów
    function synchronizeColorsWithTechnologies() {
        for (let i = 0; i < <?php echo $ileSlotow; ?>; i++) {
            const techField = document.getElementById(`technologia_${i}`);
            const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
            const mechField = document.getElementById(`mechanizm_${i}`);
            
            if (techField && techField.value && colorField && !colorField.value && mechField && mechField.value) {
                const techID = techField.value;
                const mechID = mechField.value;
                
                // Znajdź technologię w danych
                const selectedTech = window.technologieData.find(t => String(t.ID) === String(techID));
                
                if (selectedTech && selectedTech.color) {
                    // Mamy kolor z technologii - ustaw go w ukrytym polu
                    colorField.value = selectedTech.color;
                    console.log(`Slot ${i}: Automatycznie ustawiono kolor ${selectedTech.color} na podstawie technologii ${techID}`);
                    
                    // Zaktualizuj również dropdown koloru, jeśli istnieje
                    const colorSelect = document.getElementById(`color-select-${i}`);
                    if (colorSelect) {
                        for (let j = 0; j < colorSelect.options.length; j++) {
                            if (String(colorSelect.options[j].value) === String(selectedTech.color)) {
                                colorSelect.selectedIndex = j;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Uruchom synchronizację po załadowaniu strony
    setTimeout(synchronizeColorsWithTechnologies, 500);
    
    // Podgląd koloru ramki
    const selectKolorRamki = document.getElementById('kolor_ramki');
    if (selectKolorRamki) {
        selectKolorRamki.addEventListener('change', function() {
            const opt = selectKolorRamki.options[selectKolorRamki.selectedIndex];
            const imgSrc = opt.getAttribute('data-img');
            const previewImg = document.getElementById('preview-img');
            if (imgSrc && previewImg) {
                previewImg.src = imgSrc;
                previewImg.style.display = 'inline';
            }
        });
    }

    // Obsługa kliknięcia w slot - pokazuje panel edycji pod klikniętym slotem
    const slots = document.querySelectorAll('.slot');
    slots.forEach(slot => {
        slot.addEventListener('click', function() {
            const slotIndex = this.getAttribute('data-slot');
            
            // Ukryj wszystkie panele edycji
            document.querySelectorAll('.slot-settings-panel').forEach(panel => {
                panel.style.display = 'none';
            });
            
            // Pokaż panel dla klikniętego slotu
            const panel = document.getElementById(`slot-settings-panel-${slotIndex}`);
            if (panel) {
                panel.style.display = 'block';
            }
        });
    });

    // Obsługa zmiany technologii
    document.querySelectorAll('.tech-select').forEach(select => {
        select.addEventListener('change', function() {
            const slotIndex = this.getAttribute('data-slot');
            const selectedTechID = this.value;
            const selectedTechName = this.options[this.selectedIndex].text;
            
            // Zapisz wybraną technologię
            document.getElementById(`technologia_${slotIndex}`).value = selectedTechID;
            console.log(`Slot ${slotIndex}: Wybrano technologię ${selectedTechID} (${selectedTechName})`);
            
            if (selectedTechID) {
                // Znajdź wybrany mechanizm
                const mechID = document.getElementById(`mechanizm_${slotIndex}`).value;
                
                // Znajdź wszystkie technologie o tej samej nazwie dla tego mechanizmu
                const selectedTech = technologieData.find(t => String(t.ID) === String(selectedTechID));
                
                if (selectedTech) {
                    const techName = selectedTech.nazwa;
                    
                    // Znajdź wszystkie technologie o tej samej nazwie
                    const relatedTechs = technologieData.filter(t => 
                        String(t.group) === String(mechID) && t.nazwa === techName
                    );
                    
                    console.log(`Slot ${slotIndex}: Znaleziono ${relatedTechs.length} technologii o nazwie "${techName}" dla mechanizmu ${mechID}`, relatedTechs);
                    
                    // Automatycznie wybierz kolor na podstawie wybranej technologii
                    if (selectedTech && selectedTech.color) {
                        const selectedColor = selectedTech.color;
                        // Aktualizuj ukryte pole koloru
                        document.getElementById(`kolor_mechanizmu_${slotIndex}`).value = selectedColor;
                        console.log(`Slot ${slotIndex}: Automatycznie ustawiono kolor ${selectedColor} dla technologii ${selectedTechID}`);
                        
                        // Aktualizuj dropdown koloru
                        const colorSelect = document.getElementById(`color-select-${slotIndex}`);
                        if (colorSelect) {
                            for (let i = 0; i < colorSelect.options.length; i++) {
                                if (String(colorSelect.options[i].value) === String(selectedColor)) {
                                    colorSelect.selectedIndex = i;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            
            updateSlotSummaries();
        });
    });

    // Obsługa zmiany koloru mechanizmu
    document.querySelectorAll('.color-select').forEach(select => {
        select.addEventListener('change', function() {
            const slotIndex = this.getAttribute('data-slot');
            const selectedValue = this.value;
            document.getElementById(`kolor_mechanizmu_${slotIndex}`).value = selectedValue;
            updateSlotSummaries();
        });
    });

    // Obsługa kliknięcia w mechanizm z listy
    const mechanismItems = document.querySelectorAll('.mechanizm-item');
    mechanismItems.forEach(item => {
        item.addEventListener('click', function() {
            // Sprawdź, czy jest aktywny slot
            const activeSlots = document.querySelectorAll('.slot.active');
            if (activeSlots.length > 0) {
                const activeSlot = activeSlots[0];
                const slotIndex = activeSlot.getAttribute('data-slot');
                const mechID = this.getAttribute('data-mech-id');
                
                // Aktualizuj ukryte pole mechanizmu
                document.getElementById(`mechanizm_${slotIndex}`).value = mechID;
                // Resetuj technologię i kolor po zmianie mechanizmu
                document.getElementById(`technologia_${slotIndex}`).value = '';
                document.getElementById(`kolor_mechanizmu_${slotIndex}`).value = '';
                
                // Zaktualizuj obrazek slotu
                const mechData = window.mechanizmyData.find(m => String(m.ID) === String(mechID));
                if (mechData && mechData.ikona) {
                    activeSlot.querySelector('img').src = mechData.ikona;
                }
                
                console.log('Przed wywołaniem updateSlotEditPanel', slotIndex, mechID);
                
                // KLUCZOWA POPRAWKA: Utwórz/zaktualizuj ukryte pole mechanizmu przed wywołaniem updateSlotEditPanel
                const mechField = document.getElementById(`mechanizm_${slotIndex}`);
                if (mechField) {
                    mechField.value = mechID;
                    console.log(`Slot ${slotIndex}: Bezpośrednio ustawiono wartość mechanizmu na ${mechID}`);
                } else {
                    console.error(`Nie można znaleźć ukrytego pola mechanizmu mechanizm_${slotIndex}`);
                }
                
                if (typeof window.updateSlotEditPanel === 'function') {
                    // Dodaj niewielkie opóźnienie, aby zapewnić poprawną inicjalizację
                    setTimeout(() => {
                        window.updateSlotEditPanel(slotIndex, mechID);
                    }, 50);
                } else {
                    console.error('Funkcja updateSlotEditPanel nie jest dostępna!');
                    
                    // Fallback: wypełnij panel ręcznie
                    const panel = document.getElementById(`slot-settings-panel-${slotIndex}`);
                    if (panel) {
                        const panelContent = panel.querySelector('.edit-panel-content');
                        if (panelContent) {
                            // Pełny fallback z mechanizmami i kolorami
                            let techHtml = '<p>Wybierz technologię</p><select id="tech-select-' + slotIndex + '" class="tech-select" data-slot="' + slotIndex + '">';
                            techHtml += '<option value="">Wybierz</option>';
                            
                            // Filtruj technologie dla wybranego mechanizmu
                            const relevantTechs = technologieData.filter(t => t.group == mechID);
                            relevantTechs.forEach(tech => {
                                techHtml += `<option value="${tech.ID}">${tech.nazwa}</option>`;
                            });
                            techHtml += '</select>';
                            
                            // Dodaj select dla koloru
                            let colorHtml = '<p>Wybierz kolor</p><select id="color-select-' + slotIndex + '" class="color-select" data-slot="' + slotIndex + '">';
                            colorHtml += '<option value="">Wybierz</option>';
                            
                            // Filtruj kolory dla wybranego mechanizmu
                            const availableColors = mechanizmKolorMap[mechID] || [];
                            availableColors.forEach(colorID => {
                                const colorInfo = window.kolor_mechanizmu_options[colorID] || {};
                                const colorName = colorInfo.name || `Kolor ${colorID}`;
                                colorHtml += `<option value="${colorID}">${colorName}</option>`;
                            });
                            colorHtml += '</select>';
                            
                            panelContent.innerHTML = techHtml + colorHtml;
                            
                            // Dodaj event listenery
                            setTimeout(() => {
                                const techSelect = document.getElementById(`tech-select-${slotIndex}`);
                                if (techSelect) {
                                    techSelect.addEventListener('change', function() {
                                        document.getElementById(`technologia_${slotIndex}`).value = this.value;
                                        updateSlotSummaries();
                                    });
                                }
                                
                                const colorSelect = document.getElementById(`color-select-${slotIndex}`);
                                if (colorSelect) {
                                    colorSelect.addEventListener('change', function() {
                                        document.getElementById(`kolor_mechanizmu_${slotIndex}`).value = this.value;
                                        updateSlotSummaries();
                                    });
                                }
                            }, 100);
                        }
                        panel.style.display = 'block';
                    }
                }
            } else {
                alert('Najpierw wybierz slot, a następnie mechanizm');
            }
        });
    });

    // Dodaj klasę "active" do klikniętego slotu i usuń z pozostałych
    slots.forEach(slot => {
        slot.addEventListener('click', function() {
            const wasActive = this.classList.contains('active');
            
            // Usuń klasę active ze wszystkich slotów
            document.querySelectorAll('.slot').forEach(s => {
                s.classList.remove('active');
            });
            
            // Dodaj klasę active do klikniętego slotu, chyba że już był aktywny
            if (!wasActive) {
                this.classList.add('active');
            }
        });
    });

    // Funkcja aktualizująca podsumowania slotów
    function updateSlotSummaries() {
        for (let i = 0; i < <?php echo $ileSlotow; ?>; i++) {
            const mechField = document.getElementById(`mechanizm_${i}`);
            const techField = document.getElementById(`technologia_${i}`);
            const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
            
            if (!mechField || !techField || !colorField) continue;
            
            const mechVal = mechField.value;
            const techVal = techField.value;
            const colorVal = colorField.value;
            
            // Jeśli mamy technologię, ale nie mamy wybranego koloru, spróbuj ustawić automatycznie
            if (techVal && !colorVal && mechVal) {
                const selectedTech = window.technologieData.find(t => String(t.ID) === String(techVal));
                
                if (selectedTech && selectedTech.color) {
                    // Mamy kolor z technologii - ustaw go w ukrytym polu
                    colorField.value = selectedTech.color;
                    console.log(`Slot ${i}: Automatycznie ustawiono kolor ${selectedTech.color} na podstawie technologii ${techVal}`);
                }
            }
            
            // Aktualizuj zawartość podsumowania slotu
            const summary = document.querySelector(`#slot-summary-${i}`);
            if (summary) {
                if (mechVal !== undefined && mechVal !== null && mechVal !== '') {
                    // Pobierz nazwy wybranych opcji
                    const mechName = mechanizmyData.find(m => String(m.ID) === String(mechVal))?.nazwa || '—';
                    const techName = technologieData.find(t => String(t.ID) === String(techVal))?.nazwa || '—';
                    const colorName = window.kolor_mechanizmu_options[colorVal]?.name || '—';
                    
                    // Aktualizuj podsumowanie
                    summary.querySelector('#slot-mech-name-'+i).textContent = mechName;
                    summary.querySelector('#slot-tech-summary-'+i).textContent = techName;
                    summary.querySelector('#slot-color-summary-'+i).textContent = colorName;
                    
                    // Pokaż podsumowanie
                    summary.classList.add('filled');
                } else {
                    // Ukryj podsumowanie, jeśli nie wybrano mechanizmu
                    summary.classList.remove('filled');
                }
            }
        }
    }

    // Funkcja wypełniająca istniejące obrazy slotów
    function fillExistingSlotImages() {
        for (let i = 0; i < <?php echo $ileSlotow; ?>; i++) {
            const mechField = document.getElementById(`mechanizm_${i}`);
            if (!mechField) continue;
            
            const mechVal = mechField.value;
            if (mechVal !== undefined && mechVal !== null && mechVal !== '') {
                const mechData = mechanizmyData.find(m => String(m.ID) === String(mechVal));
                if (mechData && mechData.ikona) {
                    const slotImg = document.querySelector(`.slot[data-slot="${i}"] img`);
                    if (slotImg) {
                        slotImg.src = mechData.ikona;
                    }
                }
            }
        }
    }
    
    // Wywołaj funkcje inicjalizujące
    fillExistingSlotImages();
    updateSlotSummaries();
    
    // Funkcja do dodawania klasy kształtu do kontenera ramki
    function applyShapeClass() {
        // Pobierz nazwę kształtu z sessionStorage
        const shapeName = sessionStorage.getItem('selected_shape_name');
        if (!shapeName) return;
        
        // Konwertuj nazwę na małe litery i usuwamy białe znaki
        const shapeClass = shapeName.toLowerCase().trim();
        
        // Pobierz kontener ramki
        const imageContainer = document.querySelector('.ramka-image-container');
        if (imageContainer) {
            // Usuń wszystkie potencjalne klasy kształtów
            imageContainer.classList.remove('square', 'round', 'cube');
            
            // Dodaj odpowiednią klasę zależnie od nazwy kształtu
            imageContainer.classList.add(shapeClass);
        }
    }
    
    // Wywołaj funkcję przy ładowaniu strony
    applyShapeClass();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wybierz panel ustawień slotu
    const panel = document.getElementById('slot-settings-panel');
    if (panel) {
        // Upewnij się, że panel jest widoczny
        panel.style.display = 'block';
        
        // Dodaj obserwator, który będzie przywracał widoczność, jeśli jakiś skrypt spróbuje ukryć panel
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'style' && panel.style.display === 'none') {
                    panel.style.display = 'block';
                }
            });
        });
        
        observer.observe(panel, { attributes: true });
    } 
});
</script>

<script>

document.addEventListener('DOMContentLoaded', function() {
    // Funkcja do pozycjonowania panelu ustawień slotu w zależności od układu
    function positionSettingsPanel() {
        // Sprawdź czy mamy układ pionowy
        const isVertical = document.querySelector('.ramka-slots.vertical') !== null;
        
        // Pobierz wszystkie panele ustawień
        const panels = document.querySelectorAll('.slot-settings-panel');
        
        panels.forEach((panel, index) => {
            const slot = document.querySelector(`.slot[data-slot="${index}"]`);
            if (!slot) return;
            
            if (isVertical) {
                // Dla układu pionowego umieszczamy panel obok slotu
                panel.style.position = 'relative';
                panel.style.margin = '0 0 0 20px';
                
                // Znajdź odpowiedni slot-wrapper i dodaj panel obok
                const slotWrapper = slot.closest('.slot-wrapper');
                if (slotWrapper) {
                    // Stwórz kontener flex, jeśli nie istnieje
                    if (!slotWrapper.classList.contains('vertical-slot-container')) {
                        slotWrapper.classList.add('vertical-slot-container');
                        slotWrapper.style.display = 'flex';
                        slotWrapper.style.flexDirection = 'row';
                        slotWrapper.style.alignItems = 'center';
                    }
                    
                    // Przenieś panel do kontenera slotu
                    slotWrapper.appendChild(panel);
                }
            } else {
                // Dla układu poziomego zostawiamy domyślne pozycjonowanie
                panel.style.position = 'absolute';
                panel.style.margin = '65px 0';
            }
        });
    }
    
    // Wywołaj funkcję po załadowaniu strony
    positionSettingsPanel();
    
    // Ponownie wywołaj funkcję po kliknięciu w slot
    document.querySelectorAll('.slot').forEach(slot => {
        slot.addEventListener('click', function() {
            // Krótkie opóźnienie, aby panel zdążył się utworzyć/zaktualizować
            setTimeout(positionSettingsPanel, 100);
        });
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('konfigurator-form');
    if (form) {
        // Dodaj funkcję, która przy wysłaniu formularza sprawdzi i zsynchronizuje wszystkie pola
        form.addEventListener('submit', function(e) {
            // Jeśli to przycisk "Dalej"
            if (e.submitter && e.submitter.name === 'go_next') {
                // Pokaż wszystkie ukryte pola mechanizmów, technologii i kolorów
                console.log('Sprawdzam ukryte pola przed wysłaniem formularza:');
                
                // Ustal liczbę slotów
                const ramkaSlots = document.querySelector('.ramka-slots');
                const ileSlotow = parseInt(ramkaSlots.getAttribute('data-slots') || '1') : 1;
                
                console.log(`Liczba slotów: ${ileSlotow}`);
                
                // Przejdź przez każdy slot
                for (let i = 0; i < ileSlotow; i++) {
                    // Pobierz wartości z selectów
                    const mechField = document.getElementById(`mechanizm_${i}`);
                    const techField = document.getElementById(`technologia_${i}`);
                    const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
                    
                    console.log(`Slot ${i}:`);
                    console.log(`  - mechanizm: ${mechField ? mechField.value : 'BRAK POLA'}`);
                    console.log(`  - technologia: ${techField ? techField.value : 'BRAK POLA'}`);
                    console.log(`  - kolor: ${colorField ? colorField.value : 'BRAK POLA'}`);
                }
            }
        });
    }
});
</script>

<script>
// Dodaj tę funkcję przed obsługą zdarzenia submit
function fullSynchronizeFormValues() {
    const slotsContainer = document.querySelector('.ramka-slots');
    if (!slotsContainer) return;
    
    const ileSlotow = parseInt(slotsContainer.getAttribute('data-slots') || '1', 10);
    console.log(`Pełna synchronizacja ${ileSlotow} slotów`);
    
    for (let i = 0; i < ileSlotow; i++) {
        const techSelect = document.getElementById(`tech-select-${i}`);
        const colorSelect = document.getElementById(`color-select-${i}`);
        const mechField = document.getElementById(`mechanizm_${i}`);
        const techField = document.getElementById(`technologia_${i}`);
        const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
        
        // Najpierw synchronizuj wartości z selectów do ukrytych pól
        if (techSelect && techField) techField.value = techSelect.value;
        if (colorSelect && colorField) colorField.value = colorSelect.value;
        
        // Jeśli mamy technologię, ale nie mamy wybranego koloru, spróbuj ustawić automatycznie
        if (techField && techField.value && colorField && !colorField.value && mechField && mechField.value) {
            const techID = techField.value;
            const mechID = mechField.value;
            
            // Znajdź technologię w danych
            const selectedTech = window.technologieData.find(t => String(t.ID) === String(techID));
            
            if (selectedTech && selectedTech.color) {
                // Mamy kolor z technologii - ustaw go w ukrytym polu
                colorField.value = selectedTech.color;
                console.log(`Slot ${i}: Automatycznie ustawiono kolor ${selectedTech.color} na podstawie technologii ${techID}`);
                
                // Dodatkowo ustaw selektor, jeśli istnieje
                if (colorSelect) {
                    for (let opt = 0; opt < colorSelect.options.length; opt++) {
                        if (String(colorSelect.options[opt].value) === String(selectedTech.color)) {
                            colorSelect.selectedIndex = opt;
                            break;
                        }
                    }
                }
            }
        }
    }
    
    // Debug - wyświetl wszystkie wartości
    for (let i = 0; i < ileSlotow; i++) {
        console.log(`Slot ${i} po synchronizacji:`, {
            mech: document.getElementById(`mechanizm_${i}`)?.value || 'brak',
            tech: document.getElementById(`technologia_${i}`)?.value || 'brak',
            color: document.getElementById(`kolor_mechanizmu_${i}`)?.value || 'brak'
        });
    }
}

// Używaj tej funkcji przed wysłaniem formularza
document.getElementById('konfigurator-form').addEventListener('submit', function(e) {
    if (document.querySelector('input[name="kv_step"]').value === '4' && 
        e.submitter && e.submitter.name === 'go_next') {
        fullSynchronizeFormValues();
        // Reszta kodu walidacji...
    }
});
</script>

<script>
// Dodaj na początku sekcji JavaScript

// Pełna synchronizacja formularza przed wysłaniem
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('konfigurator-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Tylko dla kroku 4
            if (document.querySelector('input[name="kv_step"]').value === '4') {
                console.log('Synchronizuję wszystkie wartości formularza przed wysłaniem');
                
                // Pobierz liczbę slotów
                const slotsContainer = document.querySelector('.ramka-slots');
                const ileSlotow = parseInt(slotsContainer.getAttribute('data-slots') || '1', 10);
                
                // Synchronizacja dla każdego slotu
                for (let i = 0; i < ileSlotow; i++) {
                    // Pobierz aktualne wartości z selectów
                    const techSelect = document.getElementById(`tech-select-${i}`);
                    const colorSelect = document.getElementById(`color-select-${i}`);
                    
                    // Synchronizuj z ukrytymi polami formularza
                    if (techSelect) {
                        const techField = document.getElementById(`technologia_${i}`);
                        if (techField) {
                            techField.value = techSelect.value;
                            console.log(`Slot ${i}: technologia = ${techField.value}`);
                        }
                    }
                    
                    if (colorSelect) {
                        const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
                        if (colorField) {
                            colorField.value = colorSelect.value;
                            console.log(`Slot ${i}: kolor = ${colorField.value}`);
                        }
                    }
                }
            }
        });
    }
});
</script>
