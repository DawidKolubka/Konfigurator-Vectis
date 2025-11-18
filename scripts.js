// Inicjalizacja pliku scripts.js


jQuery(document).ready(function($) {

  // Sprawdzamy, czy jesteśmy na kroku 4 (czy istnieje #step4-container)
  if ($('#step4-container').length) {
    initStep4();
  }

  function initStep4() {
    // 1. Wczytujemy dane z hidden inputów
    const ukladOptions    = JSON.parse($('#uklad_options_data').val() || '[]');
    const kolorRamkiData  = JSON.parse($('#kolor_ramki_data').val() || '[]');
    const mechanizmData   = JSON.parse($('#mechanizm_data').val() || '[]');
    const technologiaData = JSON.parse($('#technologia_data').val() || '[]');
    const layoutChoice    = parseInt($('#layout_choice').val() || '0', 10);

    // 2. Czyścimy kontener
    const $container = $('#step4-container');
    $container.empty();

    // 3. Ustalamy, jak się nazywa wybrany układ
    let layoutName = '';
    if (ukladOptions[layoutChoice] && ukladOptions[layoutChoice].name) {
      layoutName = ukladOptions[layoutChoice].name; // np. "X1", "X2 POZIOMY", "PIONOWY", ...
    }

    // 4. Wyświetlamy sekcję "Wybierz kolor ramki"
    renderKolorRamki(kolorRamkiData, $container);

    // 5. Sprawdzamy, ile slotów/grafik wstawić w zależności od layoutName
    //    Przykład: jeśli nazwa zawiera "X2" -> 2 sloty, 
    //              jeśli "PIONOWY" -> generujemy np. 2 sloty w pionie, itd.
    //    Możesz zmodyfikować warunki według własnego klucza.
    let orientacja = 'vertical'; // Domyślna orientacja
    
    if (layoutName.match(/X(\d+)/i)) {
      // Szukamy liczby po literze X
      const match = layoutName.match(/X(\d+)/i); // np. "X2 POZIOMY" -> ["X2","2"]
      const ile = match ? parseInt(match[1], 10) : 1; // domyślnie 1
      
      // Sprawdzamy orientację
      if (layoutName.toUpperCase().includes('POZIOM')) {
        orientacja = 'horizontal';
      }
      
      // Rysujemy sloty z odpowiednią orientacją
      renderWyboroweGrafiki(ile, $container, mechanizmData, technologiaData, orientacja);

    } else if (layoutName.toUpperCase().includes('PIONOWY')) {
      orientacja = 'vertical';
      renderGrafikiPionowe(2, $container, mechanizmData, technologiaData);

    } else if (layoutName.toUpperCase().includes('POZIOMY')) {
      orientacja = 'horizontal';
      renderGrafikiPoziome(2, $container, mechanizmData, technologiaData);

    } else {
      // Inny layout, nie rozpoznany
      $container.append('<p>Nie rozpoznano layoutu: '+layoutName+'</p>');
    }
    
    // Ustawienie klasy dla kontenera
    $('.slots-container').removeClass('vertical horizontal').addClass(orientacja);
  }

  /** Funkcja do wyświetlenia listy kolorów ramki */
  function renderKolorRamki(kolorRamkiData, $container) {
    const $section = $('<div style="margin-bottom:20px;"></div>');
    $section.append('<h3>Wybierz kolor ramki</h3>');

    $.each(kolorRamkiData, function(i, item) {
      // item np. {id:1, nazwa:'Biały'}
      const $label = $('<label style="display:block;"></label>');
      const $radio = $('<input type="radio" name="kolor_ramki">').val(item.id);
      $label.append($radio);
      $label.append(' ' + (item.nazwa || 'Kolor bez nazwy'));
      $section.append($label);
    });

    $container.append($section);
  }

  /** Funkcja rysująca "ile" slotów z grafiką wybor.svg w rzędzie */
  function renderWyboroweGrafiki(ile, $container, mechanizmData, technologiaData, orientacja = 'vertical') {
    // Sprawdź najpierw, czy przypadkiem orientacja nie jest już ustawiona przez PHP
    var phpOrientation = $('.ramka-slots').hasClass('horizontal') ? 'horizontal' : 'vertical';
    orientacja = phpOrientation; // Użyj orientacji z PHP
    
    const $wrapper = $('<div class="slots-container ' + orientacja + '" style="display:flex; gap:20px; flex-wrap:wrap;"></div>');

    for (let i = 0; i < ile; i++) {
      const $slot = $('<div class="slot" data-slot="' + i + '" style="text-align:center; cursor:pointer;"></div>');
      const $img = $('<img>')
        .attr('src', 'https://www.isdvectis.pl/wp-content/uploads/2025/04/wybor.svg')
        .attr('id', 'slot-img-' + i)
        .css({ width:'100px' });
      
      // Dodajemy opis pod obrazkiem
      const $summary = $('<div class="slot-summary"></div>');
      $summary.html(
        '<div><strong>Slot ' + (i+1) + '</strong></div>' +
        '<div>Mechanizm: <span id="slot-mech-name-' + i + '">—</span></div>' +
        '<div>Technologia: <span id="slot-tech-summary-' + i + '">—</span></div>' +
        '<div>Kolor: <span id="slot-color-summary-' + i + '">—</span>'
      );

      // Ukryte pola formularza
      const $hiddenFields = $(
        '<input type="hidden" name="mechanizm_' + i + '" id="mechanizm_' + i + '" value="">' +
        '<input type="hidden" name="technologia_' + i + '" id="technologia_' + i + '" value="">' +
        '<input type="hidden" name="kolor_mechanizmu_' + i + '" id="kolor_mechanizmu_' + i + '" value="">'
      );

      $slot.append($img).append($summary).append($hiddenFields);
      $wrapper.append($slot);
    }

    $container.append($wrapper);
  }

  // Poniższe dwie funkcje są tylko wrapperami na renderWyboroweGrafiki
  // z określonym parametrem orientacji
  
  /** Pomocnicza - pionowy układ */
  function renderGrafikiPionowe(ile, $container, mechanizmData, technologiaData) {
    renderWyboroweGrafiki(ile, $container, mechanizmData, technologiaData, 'vertical');
  }

  /** Pomocnicza - poziomy układ */
  function renderGrafikiPoziome(ile, $container, mechanizmData, technologiaData) {
    renderWyboroweGrafiki(ile, $container, mechanizmData, technologiaData, 'horizontal');
  }

  /**
   * Po kliknięciu w slot (wybor.svg) wyświetlamy listę mechanizmów
   * (ikony z mechanizm_data). Po wyborze mechanizmu podmieniamy grafikę
   * i wyświetlamy "Wybór technologii" + "Kolor mechanizmu".
   */
  function showMechanizmOptions($slot, mechanizmData, technologiaData) {
    // Usuwamy starą sekcję, jeśli była
    $slot.find('.mechanizm-details').remove();

    const $details = $('<div class="mechanizm-details" style="margin-top:10px;"></div>');
    $details.append('<h4>Mechanizm</h4>');

    const $iconsWrapper = $('<div style="display:flex; gap:10px; flex-wrap:wrap;"></div>');

    $.each(mechanizmData, function(i, mech) {
      // mech np. { id:11, nazwa:'M. A', ikona_grupy:'...', ikona_do_ramki:'...' }
      const $mechDiv = $('<div style="text-align:center; cursor:pointer;"></div>');

      const $icon = $('<img>')
        .attr('src', mech.ikona_grupy)
        .css({ width:'50px' });

      const $label = $('<div></div>').text(mech.nazwa || 'Bez nazwy');

      // Klik w ikonkę mechanizmu
      $icon.on('click', function() {
        // Podmieniamy główny obrazek
        const $mainImg = $slot.find('img').first();
        if ($mainImg && mech.ikona_do_ramki) {
          $mainImg.attr('src', mech.ikona_do_ramki);
        }
        // Wyświetlamy wybór technologii + koloru mechanizmu
        showTechnologiaAndColor($details, mech, technologiaData);
      });

      $mechDiv.append($icon).append($label);
      $iconsWrapper.append($mechDiv);
    });

    $details.append($iconsWrapper);
    $slot.append($details);
  }

  /**
   * Po wyborze mechanizmu - pokazujemy "Wybór technologii" i "Kolor mechanizmu".
   * Dane pobieramy z `technologiaData`, filtrując obiekty pasujące do 
   * `selectedMechanizm.id` (np. `mechanizm_id` = 11).
   */
  function showTechnologiaAndColor($parent, selectedMechanizm, technologiaData) {
    // Usuwamy poprzednią sekcję .technologia-section, jeśli istniała
    $parent.find('.technologia-section').remove();

    const $techSection = $('<div class="technologia-section" style="margin-top:10px;"></div>');

    // Wybór technologii
    $techSection.append('<h4>Wybór technologii</h4>');

    // Filtrujemy tylko te technologie, które mają mechanizm_id == selectedMechanizm.id
    const relevantTech = technologiaData.filter(t => parseInt(t.mechanizm_id, 10) === selectedMechanizm.id);

    if (relevantTech.length) {
      $.each(relevantTech, function(i, tech) {
        // tech np. { id:101, nazwa:'Tech 1', mechanizm_id:11, kolory_mechanizmu:[...] }
        const $techDiv = $('<div style="margin-bottom:5px;"></div>')
          .text(tech.nazwa || 'Technologia bez nazwy');
        $techSection.append($techDiv);
        
        // Możesz tu dodać klik/checkbox dla wyboru konkretnej technologii
        // i np. pokazywać kolory zależne od technologii.
      });
    } else {
      $techSection.append('<div>Brak przypisanych technologii dla wybranego mechanizmu.</div>');
    }

    // Kolor mechanizmu
    $techSection.append('<h4>Kolor mechanizmu</h4>');
    
    // Załóżmy, że kolory mechanizmu są w polu selectedMechanizm.kolory_mechanizmu
    // lub w poszczególnej technologii. Zależnie od Twojej struktury:
    if (Array.isArray(selectedMechanizm.kolory_mechanizmu)) {
      selectedMechanizm.kolory_mechanizmu.forEach(function(col) {
        $techSection.append('<div>'+col+'</div>');
      });
    } else {
      $techSection.append('<div>Brak zdefiniowanych kolorów dla tego mechanizmu.</div>');
    }

    // Dodajemy sekcję do DOM
    $parent.append($techSection);
  }

  // Zmodyfikowana funkcja sprawdzająca typ układu - bazując na orientacji z PHP
  function checkLayoutOrientation() {
    // Odczytaj orientację ustawioną przez PHP
    var phpOrientation = $('.ramka-slots').hasClass('horizontal') ? 'horizontal' : 'vertical';
    
    // Jeśli mamy div z klasą slots-container, upewnijmy się, że ma tę samą orientację
    $('.slots-container').removeClass('horizontal vertical').addClass(phpOrientation);
    
    // Zapamiętaj w localStorage dla diagnostyki
    localStorage.setItem('krok4_orientation', phpOrientation);
  }
  
  // Funkcja inicjalizująca układ na podstawie ukrytego pola
  function initLayoutFromHiddenField() {
    var layoutType = $('#selected_layout_type').val();
    
    if (layoutType === 'horizontal') {
      $('.slots-container').removeClass('vertical').addClass('horizontal');
    } else {
      $('.slots-container').removeClass('horizontal').addClass('vertical');
    }
  }
  
  // Funkcja do synchronizacji informacji o układzie - łączy wszystkie operacje na layoutach
  function synchronizeLayoutInfo() {
    // Sprawdź, czy jesteśmy w kroku 4
    if (!$('#krok4').is(':visible')) return;
    
    // Pobierz informacje z localStorage (dla diagnostyki)
    var storedLayout = localStorage.getItem('krok3_selected_layout') || '';
    var storedSlots = localStorage.getItem('krok3_slots_count') || '1';
    var storedIsVertical = localStorage.getItem('krok3_is_vertical') === '1';
    
    // Pobierz aktualne ustawienia kontenera
    var container = $('.slots-container');
    if (container.length) {
        var currentClass = container.attr('class');
        
        // Sprawdź czy klasa jest zgodna z orientacją
        var hasVertical = currentClass.includes('vertical');
        var hasHorizontal = currentClass.includes('horizontal');
        
        if (storedIsVertical && !hasVertical) {
            container.removeClass('horizontal').addClass('vertical');
        } else if (!storedIsVertical && !hasHorizontal) {
            container.removeClass('vertical').addClass('horizontal');
        }
    }
    
    // Dodatkowo sprawdź orientację ustawioną przez PHP
    checkLayoutOrientation();
  }
  
  // Funkcja inicjalizująca wszystkie potrzebne elementy w kroku 4
  function initializeStep4Components() {
    synchronizeLayoutInfo();
    initializeFormValuesFromSlots();
  }
  
  // Wywołanie funkcji po załadowaniu strony
  setTimeout(initializeStep4Components, 500);
  
  // Wywołanie funkcji przy zmianie kroków - jeden handler
  $(document).on('configurator_step_loaded', function(e, step) {
    if (step === 4) {
      setTimeout(initializeStep4Components, 500);
    }
  });

  // Uruchamiamy przy zmianie wyboru w kroku 3
  $(document).on('change', 'input[name="krok3"]', function() {
    // Zapisz wybór do ukrytego pola
    $('#temp_selected_option').val($(this).val());
    
    var selectedValue = $(this).val();
    var selectedLabel = $('label[for="' + $(this).attr('id') + '"]').text();
    
    // Zapisz wybór do localStorage dla debugowania
    localStorage.setItem('debug_krok3_value', selectedValue);
    localStorage.setItem('debug_krok3_label', selectedLabel);
  });
  
  // Sprawdź layout jeśli jesteśmy już w kroku 4
  if ($('.configurator-step#krok4').length > 0 || 
      $('#step4-container').length || 
      $('.slots-container').length) {
    setTimeout(synchronizeLayoutInfo, 500);
  }
  
  // Inicjalizacja z ukrytego pola jeśli istnieje
  if ($('#selected_layout_type').length) {
    initLayoutFromHiddenField();
  }
  
  // Sprawdź czy jest już coś wybrane przy załadowaniu strony
  if ($('#krok3').is(':visible')) {
    var selectedOption = $('input[name="krok3"]:checked');
    if (selectedOption.length) {
      var selectedValue = selectedOption.val();
      var selectedLabel = $('label[for="' + selectedOption.attr('id') + '"]').text();
    }
  }

  // Znajdź kod obsługujący przycisk "Dalej"
  $(document).on('click', '.next-step', function() {
    var currentStep = parseInt($(this).data('step')) - 1;
    var nextStep = $(this).data('step');
    
    // Jeśli przechodzimy z kroku 3 do 4, upewnij się, że wybór jest zapisany
    if (currentStep === 3) {
        var selectedLayout = $('input[name="krok3"]:checked').val();
        
        if (selectedLayout) {
            // Analiza układu
            var layoutMatch = selectedLayout.match(/X(\d+)/);
            var slotsCount = layoutMatch ? parseInt(layoutMatch[1]) : 1;
            var isVertical = selectedLayout.toUpperCase().includes('PIONOWY');
            
            // Zapisz do sesji przez AJAX przed przejściem
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                async: false, // Ważne - wykonaj synchronicznie przed przejściem
                data: {
                    action: 'save_configurator_step',
                    step: 'krok3',
                    value: selectedLayout
                },
                success: function(response) {
                }
            });
        }
    }
  });

});

/**
 * Zoptymalizowana funkcja walidująca krok 4 formularza
 * Sprawdza czy wszystkie wymagane pola są wypełnione i wyświetla błędy
 * @returns {boolean} Czy formularz jest poprawnie wypełniony
 */
function validateStep4() {
    console.log('== WALIDACJA KROKU 4 ==');
    let valid = true;
    let errors = [];
    let firstInvalidSlot = -1;

    // 0. Sprawdź dostępność globalnych zmiennych danych
    console.log('Sprawdzam dostępność danych globalnych:', {
        technologieData: window.technologieData ? window.technologieData.length : 'BRAK!',
        mechanizmyData: window.mechanizmyData ? window.mechanizmyData.length : 'BRAK!'
    });

    // 1. Kolor ramki
    const kolorRamki = $('select[name="kolor_ramki"]').val();
    if (!kolorRamki) {
        valid = false;
        errors.push('Wybierz kolor ramki.');
    }

    // 2. Liczba slotów
    const ileSlotow = $('.ramka-slots').data('slots') || 1;
    console.log('Liczba slotów:', ileSlotow);

    // 3. Walidacja każdego slotu
    for (let i = 0; i < ileSlotow; i++) {
        const mech = $('#mechanizm_' + i).val();
        const tech = $('#technologia_' + i).val();
        const color = $('#kolor_mechanizmu_' + i).val();

        console.log(`Slot ${i}: mech=${mech}, tech=${tech}, color=${color}`);

        if (!mech) {
            valid = false;
            errors.push(`Wybierz mechanizm dla slotu ${i + 1}.`);
            if (firstInvalidSlot === -1) firstInvalidSlot = i;
            continue;
        }
        
        // Sprawdź, czy dla tego mechanizmu są technologie
        const mechID = parseInt(mech, 10);
        
        // NOWA WERSJA: Bardziej szczegółowa diagnostyka
        if (!window.technologieData || !Array.isArray(window.technologieData)) {
            console.error(`⛔ KRYTYCZNY BŁĄD: window.technologieData nie jest tablicą!`);
            if (!valid) errors.push(`Problem z danymi technologii (brak danych).`);
            valid = false;
            continue;
        }
        
        // Znajdź wszystkie technologie dla tego mechanizmu
        const techsForMech = window.technologieData.filter(t => 
            t && String(t.group) === String(mechID)
        );
        console.log(`Slot ${i}: Dla mechanizmu ${mechID} znaleziono ${techsForMech.length} technologii`);
        
        // Szczegółowe informacje o znalezionych technologiach
        if (techsForMech.length > 0) {
            console.log('Dostępne technologie:', techsForMech.map(t => ({ id: t.ID, nazwa: t.nazwa })));
        }
        
        // Sprawdź czy mechanizm ma technologie i czy użytkownik wybrał technologię
        const hasTech = techsForMech.length > 0;
        console.log(`Slot ${i}: mechID=${mechID}, hasTech=${hasTech}, tech=${tech || 'BRAK'}`);
        
        if (hasTech && !tech) {
            valid = false;
            errors.push(`Wybierz technologię dla slotu ${i + 1}.`);
            if (firstInvalidSlot === -1) firstInvalidSlot = i;
            continue;
        }
        
        if (!color) {
            valid = false;
            errors.push(`Wybierz kolor mechanizmu dla slotu ${i + 1}.`);
            if (firstInvalidSlot === -1) firstInvalidSlot = i;
            continue;
        }
    }

    // Wyświetl błędy
    $('.validation-errors').remove();
    if (!valid) {
        let html = '<div class="validation-errors" style="color:red; margin-bottom:15px;">';
        errors.forEach(e => html += `<div>${e}</div>`);
        html += '</div>';
        $('#konfigurator-form').prepend(html);
        console.warn('Błędy walidacji:', errors);
        
        // Aktywuj slot z brakującymi danymi jeśli to problem z konkretnym slotem
        if (firstInvalidSlot !== -1) {
            $(`.slot[data-slot="${firstInvalidSlot}"]`).click();
        }
    }
    
    return valid;
}

// Funkcja inicjalizująca wartości w formularzu na podstawie slotów
/**
 * Funkcja inicjalizująca wartości formularza na podstawie wybranych slotów
 * Sprawdza obrazki w slotach i ustawia odpowiednie wartości w ukrytych polach
 */
function initializeFormValuesFromSlots() {
  // Sprawdź czy jesteśmy w kroku 4
  if (!$('#step4-container').length) return;
  
  console.log("Inicjalizuję wartości formularza na podstawie slotów");
  
  const $container = $('.ramka-slots');
  const ileSlotow = $container.data('slots') || 1;
  
  for (let i = 0; i < ileSlotow; i++) {
    // Sprawdź czy slot ma wybrany mechanizm (czy jest w nim obrazek inny niż defaultowy)
    const $slot = $(`.slot[data-slot="${i}"]`);
    const $img = $slot.find('img');
    const src = $img.attr('src');
    
    // Jeśli src nie zawiera "wybor.svg", to znaczy że mechanizm został wybrany
    if (src && !src.includes('wybor.svg')) {
      // Pobierz ID mechanizmu z data-atrybutu
      const mechID = $slot.data('mechid');
      
      if (mechID) {
        console.log(`Slot ${i} - znaleziono mechanizm ID: ${mechID}`);
        
        // Sprawdź czy pole hidden już ma wartość
        const $mechInput = $(`#mechanizm_${i}`);
        if (!$mechInput.val() && mechID) {
          $mechInput.val(mechID);
          console.log(`Slot ${i} - zaktualizowano pole mechanizm_${i} na ${mechID}`);
          
          // Znajdź i aktywuj panel ustawień slotu
          updateSlotEditPanel(i, mechID);
        }
      }
    }
  }
}

// Wywołaj funkcję po załadowaniu strony
$(document).ready(function() {
  setTimeout(initializeFormValuesFromSlots, 500);
  
    // Handler zostanie przeniesiony do głównego handlera formularza na dole pliku
});

// Funkcja aktualizująca panel edycji slotu po wyborze mechanizmu
window.updateSlotEditPanel = function(slotIndex, mechID) {
    console.log(`Aktualizuję panel ustawień dla slotu ${slotIndex}, mechanizm: ${mechID}`);
    
    const panel = document.getElementById(`slot-settings-panel-${slotIndex}`);
    if (!panel) {
        console.error(`Panel dla slotu ${slotIndex} nie znaleziony`);
        return;
    }
    
    // Sprawdzamy czy mamy dostęp do danych
    if (!window.mechanizmyData || !window.technologieData || !window.mechanizmKolorMap) {
        console.error('Brak dostępu do niezbędnych danych globalnych', {
            mechanizmyData: window.mechanizmyData,
            technologieData: window.technologieData,
            mechanizmKolorMap: window.mechanizmKolorMap
        });
        return;
    }
    
    // Pobierz dane mechanizmu
    const mechData = window.mechanizmyData.find(m => m.ID == mechID);
    if (!mechData) {
        console.error(`Nie znaleziono danych mechanizmu o ID ${mechID}`);
        return;
    }

    // Aktualizuj treść panelu
    const panelContent = panel.querySelector('.edit-panel-content');
    if (!panelContent) {
        console.error(`Nie znaleziono zawartości panelu dla slotu ${slotIndex}`);
        return;
    }
    
    // Utwórz treść dla technologii
    let techHtml = '<div class="tech-section"><p>Wybierz technologię</p><select id="tech-select-' + slotIndex + '" class="tech-select" data-slot="' + slotIndex + '">';
    techHtml += '<option value="">Wybierz</option>';
    
    // POPRAWA: Technologie muszą być porównywane jako stringi, aby uniknąć problemów z typami
    const strMechID = String(mechID);
    console.log(`Szukam technologii dla mechanizmu ${mechID} (jako string: ${strMechID})`);
    
    if (!window.technologieData || !Array.isArray(window.technologieData)) {
        console.error(`Brak poprawnie zainicjalizowanej tablicy technologii!`);
        techHtml += '<option value="">BŁĄD: brak danych technologii</option>';
    } else {
        // Filtruj technologie dla wybranego mechanizmu - porównuj jako stringi
        const relevantTechs = window.technologieData.filter(t => String(t.group) === strMechID);
        console.log(`Znaleziono ${relevantTechs.length} technologii dla mechanizmu ${mechID}`, relevantTechs);

        // Diagnostyka technologieData
        const allGroups = window.technologieData.map(t => t.group);
        console.log(`Wszystkie grupy technologii:`, allGroups);
        
        // Sprawdź aktualnie wybraną technologię
        const currentTechID = document.getElementById(`technologia_${slotIndex}`)?.value || '';
        console.log(`Aktualna technologia dla slotu ${slotIndex}: ${currentTechID}`);
        
        if (relevantTechs.length === 0) {
            techHtml += `<option value="">Brak dostępnych technologii</option>`;
        } else {
            // NOWA FUNKCJONALNOŚĆ: Grupowanie technologii o tych samych nazwach
            const uniqueTechs = [];
            const techNameMap = {}; // Mapa do śledzenia unikalnych nazw
            const techNamesToIdsMap = {}; // Mapa nazw do wszystkich powiązanych ID
            
            relevantTechs.forEach(tech => {
                const techName = tech.nazwa;
                if (!techNameMap[techName]) {
                    // Jeśli nie dodaliśmy jeszcze technologii o tej nazwie
                    techNameMap[techName] = tech.ID;
                    uniqueTechs.push(tech);
                    // Inicjalizacja tablicy dla tej nazwy technologii
                    techNamesToIdsMap[techName] = [tech.ID];
                } else {
                    // Mamy już tę nazwę, dodajemy ID do listy
                    techNamesToIdsMap[techName].push(tech.ID);
                }
            });
            
            // Zapisujemy powiązania do obiektu globalnego
            window.techNamesToIdsMap = techNamesToIdsMap;
            
            console.log(`Slot ${slotIndex}: Po deduplikacji mamy ${uniqueTechs.length} unikalnych technologii`);
            console.log('Mapa technologii:', techNamesToIdsMap);
            
            // Wyświetlamy unikalne technologie
            uniqueTechs.forEach(tech => {
                const selected = String(currentTechID) === String(tech.ID) ? 'selected' : '';
                techHtml += `<option value="${tech.ID}" ${selected}>${tech.nazwa}</option>`;
            });
        }
    }
    techHtml += '</select></div>';
    
    // Utwórz treść dla kolorów
    let colorHtml = '<div class="color-section"><p>Wybierz kolor</p><select id="color-select-' + slotIndex + '" class="color-select" data-slot="' + slotIndex + '">';
    colorHtml += '<option value="">Wybierz</option>';
    
    // Pobierz aktualnie wybrany kolor
    const currentColorID = document.getElementById(`kolor_mechanizmu_${slotIndex}`)?.value || '';
    
    // Filtruj kolory dla wybranego mechanizmu
    const availableColors = window.mechanizmKolorMap[mechID] || [];
    console.log(`Znaleziono ${availableColors.length} kolorów dla mechanizmu ${mechID}`, availableColors);
    
    availableColors.forEach(colorID => {
        const colorInfo = window.kolor_mechanizmu_options[colorID] || {};
        const colorName = colorInfo.name || `Kolor ${colorID}`;
        const selected = currentColorID == colorID ? 'selected' : '';
        
        colorHtml += `<option value="${colorID}" ${selected}>${colorName}</option>`;
    });
    colorHtml += '</select></div>';
    
    // Wstaw nową treść do panelu
    panelContent.innerHTML = techHtml + colorHtml;
    
    // Pokaż panel
    panel.style.display = 'block';
    
    // Dodaj obsługę zdarzeń dla nowo utworzonych selectów
    const techSelect = document.getElementById(`tech-select-${slotIndex}`);
    if (techSelect) {
        // Najpierw sprawdź, czy istnieją jakieś opcje
        if (techSelect.options.length <= 1) {
            console.warn(`⚠️ Select technologii dla slotu ${slotIndex} ma tylko ${techSelect.options.length} opcji!`);
        }
        
        // Jeśli jest tylko jedna opcja (nie licząc pustej), wybierz ją automatycznie
        if (techSelect.options.length == 2) {
            techSelect.selectedIndex = 1; // wybierz pierwszą niepustą opcję
            const autoSelectedValue = techSelect.value;
            console.log(`Slot ${slotIndex} - automatycznie wybrano jedyną dostępną technologię: ${autoSelectedValue}`);
            
            // Zaktualizuj ukryte pole od razu
            const techField = document.getElementById(`technologia_${slotIndex}`);
            if (techField) {
                techField.value = autoSelectedValue;
                console.log(`Slot ${slotIndex} - zaktualizowano ukryte pole technologii na ${autoSelectedValue}`);
            }
        }
        
        // Dodaj obsługę zdarzenia zmiany
        techSelect.addEventListener('change', function() {
            const techID = this.value;
            const techName = this.options[this.selectedIndex].text;
            const techField = document.getElementById(`technologia_${slotIndex}`);
            
            if (techField) {
                techField.value = techID;
                console.log(`Slot ${slotIndex} - wybrano technologię: ${techID} (${techName})`);
                
                // Automatyczny wybór koloru na podstawie technologii
                if (techID) {
                    // Znajdź technologię w danych
                    const selectedTech = window.technologieData.find(t => String(t.ID) === String(techID));
                    
                    if (selectedTech && selectedTech.color) {
                        // Znaleziono kolor dla wybranej technologii
                        const colorField = document.getElementById(`kolor_mechanizmu_${slotIndex}`);
                        if (colorField) {
                            colorField.value = selectedTech.color;
                            console.log(`Slot ${slotIndex} - automatycznie ustawiono kolor ${selectedTech.color} na podstawie technologii ${techID}`);
                            
                            // Aktualizuj również dropdown, jeśli istnieje
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
                
                // Dodatkowy debug wartości
                setTimeout(() => {
                    const currentValue = document.getElementById(`technologia_${slotIndex}`).value;
                    console.log(`Slot ${slotIndex} - wartość technologii po 50ms: ${currentValue}`);
                }, 50);
            } else {
                console.error(`⛔ Nie znaleziono ukrytego pola technologii dla slotu ${slotIndex}!`);
            }
            
            if (typeof updateSlotSummaries === 'function') {
                updateSlotSummaries();
            }
        });
    }
    
    const colorSelect = document.getElementById(`color-select-${slotIndex}`);
    if (colorSelect) {
        // Jeśli jest tylko jedna opcja (nie licząc pustej), wybierz ją automatycznie
        if (colorSelect.options.length == 2) {
            colorSelect.selectedIndex = 1; // wybierz pierwszą niepustą opcję
            const autoSelectedValue = colorSelect.value;
            console.log(`Slot ${slotIndex} - automatycznie wybrano jedyny dostępny kolor: ${autoSelectedValue}`);
            
            // Zaktualizuj ukryte pole od razu
            const colorField = document.getElementById(`kolor_mechanizmu_${slotIndex}`);
            if (colorField) {
                colorField.value = autoSelectedValue;
                console.log(`Slot ${slotIndex} - zaktualizowano ukryte pole koloru na ${autoSelectedValue}`);
            }
        }
        
        // Dodaj obsługę zdarzenia zmiany
        colorSelect.addEventListener('change', function() {
            const colorID = this.value;
            const colorField = document.getElementById(`kolor_mechanizmu_${slotIndex}`);
            if (colorField) {
                colorField.value = colorID;
                console.log(`Slot ${slotIndex} - wybrano kolor: ${colorID}`);
            } else {
                console.error(`⛔ Nie znaleziono ukrytego pola koloru dla slotu ${slotIndex}!`);
            }
            
            if (typeof updateSlotSummaries === 'function') {
                updateSlotSummaries();
            }
        });
    } else {
        console.warn(`⚠️ Nie znaleziono selecta koloru dla slotu ${slotIndex}`);
    }
    
    // Upewnij się, że ukryte pola istnieją
    ensureHiddenFields(slotIndex, mechID);
}

// Funkcja sprawdzająca i tworząca ukryte pola jeśli nie istnieją
function ensureHiddenFields(slotIndex, mechID) {
    const formElement = document.getElementById('konfigurator-form');
    if (!formElement) {
        console.error('Nie znaleziono formularza #konfigurator-form');
        return;
    }
    
    // Sprawdź pole mechanizmu
    let mechField = document.getElementById(`mechanizm_${slotIndex}`);
    if (!mechField) {
        mechField = document.createElement('input');
        mechField.type = 'hidden';
        mechField.id = `mechanizm_${slotIndex}`;
        mechField.name = `mechanizm_${slotIndex}`;
        mechField.value = mechID;
        formElement.appendChild(mechField);
        console.log(`Utworzono ukryte pole mechanizmu #mechanizm_${slotIndex}`);
    } else if (!mechField.value) {
        mechField.value = mechID;
        console.log(`Zaktualizowano ukryte pole mechanizmu #mechanizm_${slotIndex}`);
    }
    
    // Sprawdź pole technologii
    let techField = document.getElementById(`technologia_${slotIndex}`);
    if (!techField) {
        techField = document.createElement('input');
        techField.type = 'hidden';
        techField.id = `technologia_${slotIndex}`;
        techField.name = `technologia_${slotIndex}`;
        techField.value = '';
        formElement.appendChild(techField);
        console.log(`Utworzono ukryte pole technologii #technologia_${slotIndex}`);
    }
    
    // Sprawdź pole koloru mechanizmu
    let colorField = document.getElementById(`kolor_mechanizmu_${slotIndex}`);
    if (!colorField) {
        colorField = document.createElement('input');
        colorField.type = 'hidden';
        colorField.id = `kolor_mechanizmu_${slotIndex}`;
        colorField.name = `kolor_mechanizmu_${slotIndex}`;
        colorField.value = '';
        formElement.appendChild(colorField);
        console.log(`Utworzono ukryte pole koloru #kolor_mechanizmu_${slotIndex}`);
    }
}

// Usunięto niepełną definicję funkcji updateSlotSummaries - patrz scripts-patch.js
    
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
    
    for (let i = 0; i < ileSlotow; i++) {
        const mechField = document.getElementById(`mechanizm_${i}`);
        const techField = document.getElementById(`technologia_${i}`);
        const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
        
        if (!mechField || !techField || !colorField) continue;
        
        const mechVal = mechField.value;
        const techVal = techField.value;
        const colorVal = colorField.value;
        
        console.log(`Slot ${i}: mechanizm=${mechVal}, technologia=${techVal}, kolor=${colorVal}`);
        
        // Aktualizuj zawartość podsumowania slotu
        const mechName = document.getElementById(`slot-mech-name-${i}`);
        const techSummary = document.getElementById(`slot-tech-summary-${i}`);
        const colorSummary = document.getElementById(`slot-color-summary-${i}`);
        
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

// Ujednolicona walidacja formularza
jQuery(document).ready(function($) {
    // Walidacja formularza przed przesłaniem
    $('#konfigurator-form').on('submit', function(e) {
        // Sprawdź, czy przycisk "Dalej" został kliknięty i czy jesteśmy na kroku 4
        if ($('input[name="kv_step"]').val() == '4' && $('button[name="go_next"]').is(':focus')) {
            console.log('Walidacja kroku 4 przed przejściem do podsumowania');
            
            // OSTATECZNA POPRAWKA: Wywołaj funkcję aktualizującą podsumowania i synchronizującą pola
            if (typeof window.updateSlotSummaries === 'function') {
                window.updateSlotSummaries();
                console.log('Wywołano updateSlotSummaries przed walidacją formularza');
            } else {
                console.warn('Funkcja updateSlotSummaries niedostępna - nie można zsynchronizować pól');
            }
            
            // KLUCZOWA POPRAWKA: Najpierw pełna synchronizacja selectów do ukrytych pól
            const ileSlotow = $('.ramka-slots').data('slots') || 1;
            console.log(`Synchronizuję dane dla ${ileSlotow} slotów przed walidacją`);
            
            // Zbiorcze debugowanie wartości wszystkich slotów przed synchronizacją
            let slotsDebug = {};
            for (let i = 0; i < ileSlotow; i++) {
                slotsDebug[`Slot ${i}`] = {
                    mechVal: $(`#mechanizm_${i}`).val() || 'brak',
                    techVal: $(`#technologia_${i}`).val() || 'brak',
                    colorVal: $(`#kolor_mechanizmu_${i}`).val() || 'brak',
                    selectTechVal: $(`#tech-select-${i}`).val() || 'brak selecta',
                    selectColorVal: $(`#color-select-${i}`).val() || 'brak selecta'
                };
            }
            console.table(slotsDebug);
            
            // Synchronizacja wszystkich selectów na raz
            console.log("Synchronizuję wszystkie pola formularza w kroku 4");
            for (let i = 0; i < ileSlotow; i++) {
                const mechValue = $(`#mechanizm_${i}`).val();
                
                // Synchronizacja technologii
                const techSelect = document.getElementById(`tech-select-${i}`);
                if (techSelect) {
                    const techField = document.getElementById(`technologia_${i}`);
                    if (techField) {
                        // Jeśli select nie jest pusty, użyj jego wartości
                        if (techSelect.value !== undefined && techSelect.value !== null && techSelect.value !== '') {
                            techField.value = techSelect.value;
                            console.log(`Slot ${i}: Zaktualizowano technologię na ${techField.value}`);
                        }
                        // Debug
                        else {
                            console.warn(`Slot ${i}: Select technologii jest pusty!`);
                        }
                    }
                }
                
                // Synchronizacja koloru mechanizmu
                const colorSelect = document.getElementById(`color-select-${i}`);
                if (colorSelect) {
                    const colorField = document.getElementById(`kolor_mechanizmu_${i}`);
                    if (colorField) {
                        // Jeśli select nie jest pusty, użyj jego wartości
                        if (colorSelect.value !== undefined && colorSelect.value !== null && colorSelect.value !== '') {
                            colorField.value = colorSelect.value;
                            console.log(`Slot ${i}: Zaktualizowano kolor mechanizmu na ${colorField.value}`);
                        }
                        // Debug
                        else {
                            console.warn(`Slot ${i}: Select koloru jest pusty!`);
                        }
                    }
                }
            }
            
            // Ponownie wypisz wartości po synchronizacji
            console.log("Wartości po synchronizacji:");
            for (let i = 0; i < ileSlotow; i++) {
                console.log(`Slot ${i}: mech=${$(`#mechanizm_${i}`).val()}, tech=${$(`#technologia_${i}`).val()}, color=${$(`#kolor_mechanizmu_${i}`).val()}`);
            }
            
            // Najpierw użyj istniejącej funkcji walidacyjnej
            if (!validateStep4()) {
                e.preventDefault();
                return false;
            }
            
            console.log('Walidacja kroku 4 zakończona sukcesem - wszystkie pola wypełnione');
        }
    });
});

// Usunięto nieużywaną funkcję fullSynchronizeFormValues