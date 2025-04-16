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
    if (layoutName.match(/X(\d+)/i)) {
      // Szukamy liczby po literze X
      const match = layoutName.match(/X(\d+)/i); // np. "X2 POZIOMY" -> ["X2","2"]
      const ile = match ? parseInt(match[1], 10) : 1; // domyślnie 1
      // Rysujemy sloty w poziomie
      renderWyboroweGrafiki(ile, $container, mechanizmData, technologiaData);

    } else if (layoutName.toUpperCase().includes('PIONOWY')) {
      // Rysujemy 2 sloty w pionie (przykład)
      renderGrafikiPionowe(2, $container, mechanizmData, technologiaData);

    } else if (layoutName.toUpperCase().includes('POZIOMY')) {
      // Rysujemy 2 sloty w poziomie (przykład)
      renderGrafikiPoziome(2, $container, mechanizmData, technologiaData);

    } else {
      // Inny layout, nie rozpoznany
      $container.append('<p>Nie rozpoznano layoutu: '+layoutName+'</p>');
    }
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
  function renderWyboroweGrafiki(ile, $container, mechanizmData, technologiaData) {
    const $wrapper = $('<div style="display:flex; gap:20px; flex-wrap:wrap;"></div>');

    for (let i = 0; i < ile; i++) {
      const $slot = $('<div style="text-align:center;"></div>');
      const $img = $('<img>')
        .attr('src', 'http://konfigurator-vectis.local/wp-content/uploads/2025/02/wybor.svg')
        .css({ width:'100px', cursor:'pointer' });
      
      // Klik -> pokazujemy mechanizmy
      $img.on('click', function() {
        showMechanizmOptions($slot, mechanizmData, technologiaData);
      });

      $slot.append($img);
      $wrapper.append($slot);
    }

    $container.append($wrapper);
  }

  /** Podobnie - pionowy układ */
  function renderGrafikiPionowe(ile, $container, mechanizmData, technologiaData) {
    const $wrapper = $('<div style="display:flex; flex-direction:column; align-items:center; gap:20px;"></div>');
    
    for (let i = 0; i < ile; i++) {
      const $slot = $('<div style="text-align:center;"></div>');
      const $img = $('<img>')
        .attr('src', 'http://konfigurator-vectis.local/wp-content/uploads/2025/02/wybor.svg')
        .css({ width:'100px', cursor:'pointer' });

      $img.on('click', function() {
        showMechanizmOptions($slot, mechanizmData, technologiaData);
      });

      $slot.append($img);
      $wrapper.append($slot);
    }

    $container.append($wrapper);
  }

  /** Podobnie - poziomy układ */
  function renderGrafikiPoziome(ile, $container, mechanizmData, technologiaData) {
    const $wrapper = $('<div style="display:flex; flex-direction:row; gap:20px;"></div>');

    for (let i = 0; i < ile; i++) {
      const $slot = $('<div style="text-align:center;"></div>');
      const $img = $('<img>')
        .attr('src', 'http://konfigurator-vectis.local/wp-content/uploads/2025/02/wybor.svg')
        .css({ width:'100px', cursor:'pointer' });

      $img.on('click', function() {
        showMechanizmOptions($slot, mechanizmData, technologiaData);
      });

      $slot.append($img);
      $wrapper.append($slot);
    }

    $container.append($wrapper);
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
    const relevantTech = technologiaData.filter(t => t.mechanizm_id === selectedMechanizm.id);

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

  // Zmodyfikowana funkcja sprawdzająca typ układu
  function checkLayoutOrientation() {
    // Sprawdź ukrytą wartość z PHP
    var layoutType = $('#selected_layout_type').val();
    var selectedOption = $('#selected_layout_option').val();
    
    // Sprawdź na podstawie nazwy opcji
    var isHorizontal = false;
    
    if (layoutType === 'horizontal' || (selectedOption && selectedOption.toUpperCase().indexOf('POZIOMY') !== -1)) {
      isHorizontal = true;
    }
    
    // Ustaw odpowiednią klasę
    if (isHorizontal) {
      $('.slots-container').removeClass('vertical').addClass('horizontal');
    } else {
      $('.slots-container').removeClass('horizontal').addClass('vertical');
    }
    
    // Sprawdź czy zmiany faktycznie zostały zastosowane
    setTimeout(function() {
    }, 50);
  }
  
  // Uruchamiamy przy zmianie wyboru w kroku 3
  $(document).on('change', 'input[name="krok3"]', function() {
    // Zapisz wybór do ukrytego pola
    $('#temp_selected_option').val($(this).val());
  });
  
  // Sprawdź układ po załadowaniu kroku 4
  $(document).on('configurator_step_loaded', function(e, stepId) {
    if (stepId === 4) {
      setTimeout(checkLayoutOrientation, 100);
    }
  });
  
  // Jeśli już jesteśmy w kroku 4, sprawdź układ
  if ($('.configurator-step#krok4').length > 0) {
    setTimeout(checkLayoutOrientation, 100);
  }

  // Dodatkowe bezpośrednie sprawdzenie po załadowaniu dokumentu
  if ($('#step4-container').length || $('.slots-container').length) {
    setTimeout(checkLayoutOrientation, 500); // Dajemy trochę czasu na załadowanie
  }

  // Dodatkowa funkcja inicjalizująca układ na podstawie ukrytego pola
  function initLayoutFromHiddenField() {
    var layoutType = $('#selected_layout_type').val();
    
    if (layoutType === 'horizontal') {
      $('.slots-container').removeClass('vertical').addClass('horizontal');
    } else {
      $('.slots-container').removeClass('horizontal').addClass('vertical');
    }
  }
  
  // Wywołaj funkcję po załadowaniu dokumentu
  if ($('#selected_layout_type').length) {
    initLayoutFromHiddenField();
  }

  // Dodaj nasłuchiwanie zmiany wyboru układu w kroku 3
  $(document).on('change', 'input[name="krok3"]', function() {
    var selectedValue = $(this).val();
    var selectedLabel = $('label[for="' + $(this).attr('id') + '"]').text();
    
    // Zapisz wybór do localStorage dla debugowania
    localStorage.setItem('debug_krok3_value', selectedValue);
    localStorage.setItem('debug_krok3_label', selectedLabel);
  });
  
  // Sprawdź czy jest już coś wybrane przy załadowaniu strony
  if ($('#krok3').is(':visible')) {
    var selectedOption = $('input[name="krok3"]:checked');
    if (selectedOption.length) {
      var selectedValue = selectedOption.val();
      var selectedLabel = $('label[for="' + selectedOption.attr('id') + '"]').text();
    }
  }

  // Funkcja poprawiająca klasy układu po załadowaniu kroku 4
  function ensureCorrectLayoutClasses() {
    if ($('#krok4').is(':visible')) {
      var selectedLayout = $('#selected-layout').val() || '';
      var isVertical = selectedLayout.toUpperCase().indexOf('PIONOWY') !== -1;
      
      // Ustaw odpowiednie klasy kontenera slotów
      var container = $('.slots-container');
      if (container.length) {
        if (isVertical) {
          container.addClass('vertical').removeClass('horizontal');
        } else {
          container.addClass('horizontal').removeClass('vertical');
        }
      }
    }
  }

  // Wywołaj funkcję po załadowaniu strony
  setTimeout(ensureCorrectLayoutClasses, 300);

  // Wywołaj funkcję po załadowaniu kroku 4
  $(document).on('configurator_step_loaded', function(e, step) {
    if (step === 4) {
      setTimeout(ensureCorrectLayoutClasses, 300);
    }
  });

  // Funkcja do synchronizacji informacji o układzie
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
  }
  
  // Wywołaj funkcję przy zmianie kroków
  $(document).on('configurator_step_loaded', function(e, step) {
    if (step === 4) {
        setTimeout(synchronizeLayoutInfo, 300);
    }
  });

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
    
    // Istniejący kod przejścia do następnego kroku
    // ...
  });

});
