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
    
    console.log("Sprawdzam układ - typ:", layoutType, "opcja:", selectedOption);
    
    // Sprawdź na podstawie nazwy opcji
    var isHorizontal = false;
    
    if (layoutType === 'horizontal' || (selectedOption && selectedOption.toUpperCase().indexOf('POZIOMY') !== -1)) {
      isHorizontal = true;
    }
    
    // Ustaw odpowiednią klasę
    if (isHorizontal) {
      console.log("Ustawiam układ POZIOMY");
      $('.slots-container').removeClass('vertical').addClass('horizontal');
    } else {
      console.log("Ustawiam układ PIONOWY");
      $('.slots-container').removeClass('horizontal').addClass('vertical');
    }
    
    // Sprawdź czy zmiany faktycznie zostały zastosowane
    setTimeout(function() {
      console.log("Klasy po zmianie:", $('.slots-container').attr('class'));
    }, 50);
  }
  
  // Uruchamiamy przy zmianie wyboru w kroku 3
  $(document).on('change', 'input[name="krok3"]', function() {
    console.log("Zmieniono wybór w kroku 3:", $(this).val());
    // Zapisz wybór do ukrytego pola
    $('#temp_selected_option').val($(this).val());
  });
  
  // Sprawdź układ po załadowaniu kroku 4
  $(document).on('configurator_step_loaded', function(e, stepId) {
    console.log("Załadowano krok:", stepId);
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
    console.log("Wykryto kontener kroku 4 - uruchamiam sprawdzenie układu");
    setTimeout(checkLayoutOrientation, 500); // Dajemy trochę czasu na załadowanie
  }

  // Dodatkowa funkcja inicjalizująca układ na podstawie ukrytego pola
  function initLayoutFromHiddenField() {
    var layoutType = $('#selected_layout_type').val();
    console.log("Znaleziono ukryty typ układu:", layoutType);
    
    if (layoutType === 'horizontal') {
      $('.slots-container').removeClass('vertical').addClass('horizontal');
      console.log("Ustawiono układ poziomy na podstawie ukrytego pola");
    } else {
      $('.slots-container').removeClass('horizontal').addClass('vertical');
      console.log("Ustawiono układ pionowy na podstawie ukrytego pola");
    }
  }
  
  // Wywołaj funkcję po załadowaniu dokumentu
  if ($('#selected_layout_type').length) {
    initLayoutFromHiddenField();
  }

});
