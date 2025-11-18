/**
 * Funkcja aktualizująca podsumowania slotów
 * Synchronizuje selekty z ukrytymi polami i aktualizuje wyświetlane podsumowania
 */
window.updateSlotSummaries = function() {
    console.log("Aktualizuję podsumowania slotów i synchronizuję ukryte pola");
    
    // Pobierz liczbę slotów z data-atrybutu
    const container = document.querySelector('.ramka-slots');
    if (!container) return;
    
    const ileSlotow = parseInt(container.getAttribute('data-slots') || '1', 10);
    
    // KLUCZOWA POPRAWKA: Najpierw upewnij się, że wszystkie selekty są zsynchronizowane z ukrytymi polami
    for (let i = 0; i < ileSlotow; i++) {
        // Synchronizacja technologii
        const techSelect = document.getElementById(`tech-select-${i}`);
        if (techSelect && (techSelect.value !== undefined && techSelect.value !== null && techSelect.value !== '')) {
            const techField = document.getElementById(`technologia_${i}`);
            if (techField) {
                techField.value = techSelect.value;
                console.log(`Slot ${i}: Zsynchronizowano pole technologia_${i} z wartością ${techSelect.value}`);
            }
        }
        
        // Synchronizacja koloru mechanizmu
        const colorSelect = document.getElementById(`color-select-${i}`);
        if (colorSelect && (colorSelect.value !== undefined && colorSelect.value !== null && colorSelect.value !== '')) {
            const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
            if (colorField) {
                colorField.value = colorSelect.value;
                console.log(`Slot ${i}: Zsynchronizowano pole kolor_mechanizmu_${i} z wartością ${colorSelect.value}`);
            }
        }
    }
    
    // Automatyczne uzupełnienie kolorów na podstawie technologii
    for (let i = 0; i < ileSlotow; i++) {
        const mechField = document.getElementById(`mechanizm_${i}`);
        const techField = document.getElementById(`technologia_${i}`);
        const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
        
        // Jeśli mamy technologię ale nie mamy koloru, ustaw automatycznie kolor pasujący do technologii
        if (techField && techField.value && colorField && !colorField.value && mechField && mechField.value) {
            const techID = techField.value;
            const mechID = mechField.value;
            
            // Znajdź technologię w danych
            const selectedTech = window.technologieData.find(t => String(t.ID) === String(techID));
            
            if (selectedTech && selectedTech.color) {
                // Mamy kolor z technologii - ustaw go w ukrytym polu
                colorField.value = selectedTech.color;
                console.log(`Slot ${i}: Automatycznie ustawiono kolor ${selectedTech.color} na podstawie technologii ${techID}`);
                
                // Aktualizuj również dropdown koloru
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
    
    for (let i = 0; i < ileSlotow; i++) {
        const mechField = document.getElementById(`mechanizm_${i}`);
        const techField = document.getElementById(`technologia_${i}`);
        const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
        
        if (!mechField || !techField || !colorField) continue;
        
        const mechVal = mechField.value;
        const techVal = techField.value;
        const colorVal = colorField.value;
        
        // Aktualizuj zawartość podsumowania slotu
        const mechName = document.getElementById(`slot-mech-name-${i}`);
        const techSummary = document.getElementById(`slot-tech-summary-${i}`);
        const colorSummary = document.getElementById(`slot-color-summary-${i}`);
        
        // Jeśli nie ma podsumowania - musimy być w innym widoku
        if (!mechName || !techSummary || !colorSummary) continue;
        
        // Pobierz nazwy wybranych opcji
        if (mechName && (mechVal !== undefined && mechVal !== null && mechVal !== '')) {
            const mechData = window.mechanizmyData.find(m => String(m.ID) === String(mechVal));
            mechName.textContent = mechData ? mechData.nazwa : '—';
        }
        
        if (techSummary && (techVal !== undefined && techVal !== null && techVal !== '')) {
            const techData = window.technologieData.find(t => String(t.ID) === String(techVal));
            techSummary.textContent = techData ? techData.nazwa : '—';
        }
        
        if (colorSummary && (colorVal !== undefined && colorVal !== null && colorVal !== '')) {
            const colorData = window.kolor_mechanizmu_options[colorVal];
            colorSummary.textContent = colorData ? colorData.name : '—';
        }
        
        // Dodaj klasę 'filled' do podsumowania slotu
        const summary = document.querySelector(`.slot-summary[data-slot="${i}"]`);
        if (summary) {
            if ((mechVal !== undefined && mechVal !== null && mechVal !== '') && 
                (techVal !== undefined && techVal !== null && techVal !== '') && 
                (colorVal !== undefined && colorVal !== null && colorVal !== '')) {
                summary.classList.add('filled');
            } else {
                summary.classList.remove('filled');
            }
        }
    }
};

/**
 * Funkcja do czyszczenia i przeładowania selecta technologii
 * Użyteczna, gdy chcemy wyfiltrować unikalne technologie dla mechanizmu
 * 
 * @param {number} slotIndex indeks slotu
 * @param {Array} technologies tablica technologii do wyświetlenia
 * @param {string} currentTechID aktualnie wybrana technologia ID
 */
function refreshTechnologySelect(slotIndex, technologies, currentTechID = '') {
    const techSelect = document.getElementById(`tech-select-${slotIndex}`);
    if (!techSelect) return;
    
    // Wyczyść select
    while (techSelect.options.length > 0) {
        techSelect.remove(0);
    }
    
    // Dodaj pustą opcję
    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = 'Wybierz';
    techSelect.appendChild(emptyOption);
    
    // Grupuj technologie o tych samych nazwach
    const uniqueTechs = [];
    const techNameMap = {}; // Mapa do śledzenia unikalnych nazw
    
    technologies.forEach(tech => {
        const techName = tech.nazwa;
        if (!techNameMap[techName]) {
            // Jeśli nie dodaliśmy jeszcze technologii o tej nazwie
            techNameMap[techName] = tech.ID;
            uniqueTechs.push(tech);
        }
    });
    
    // Dodaj opcje do selecta
    uniqueTechs.forEach(tech => {
        const option = document.createElement('option');
        option.value = tech.ID;
        option.textContent = tech.nazwa;
        if (String(currentTechID) === String(tech.ID)) {
            option.selected = true;
        }
        techSelect.appendChild(option);
    });
    
    // Jeśli jest tylko jedna opcja, automatycznie ją wybierz
    if (techSelect.options.length === 2) {
        techSelect.selectedIndex = 1;
    }
}
