jQuery(document).ready(function($) {

    // Obsługa przycisku uploadera obrazków
    $('.kv-upload-image-button').on('click', function(e) {
        e.preventDefault();
        var custom_uploader = wp.media({
            title: 'Wybierz obrazek',
            library: { type: 'image' },
            button: { text: 'Wybierz obrazek' },
            multiple: false
        }).on('select', function() {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $(e.target).siblings('input[type="text"]').first().val(attachment.url);
        }).open();
    });
    
    // Obsługa zmiany układu w kroku "Układ"
    $(document).on('click', '.layout-option', function() {
        var layoutType = $(this).data('layout-type') || '';
        
        // Zapisujemy wybrany typ układu w sessionStorage
        sessionStorage.setItem('selectedLayoutType', layoutType);
        
        console.log('Zapisano układ: ' + layoutType);
    });
    
    // Funkcja sprawdzająca układ przy ładowaniu strony
    function checkLayoutAndApplyStyles() {
        // Sprawdzamy czy jesteśmy w kroku Mechanizmy
        if ($('#konfigurator-step-mechanizmy').length || $('.mechanizm-container').length) {
            var selectedLayout = sessionStorage.getItem('selectedLayoutType') || '';
            console.log('Odczytano układ: ' + selectedLayout);
            
            // Sprawdzamy czy wybrany został układ pionowy
            if (selectedLayout.toLowerCase().includes('pionowy')) {
                // Zastosuj styl dla układu pionowego
                $('.mechanizm-container').removeClass('horizontal-layout').addClass('vertical-layout');
                console.log('Zastosowano układ pionowy');
            } else {
                // Domyślnie układ poziomy
                $('.mechanizm-container').removeClass('vertical-layout').addClass('horizontal-layout');
                console.log('Zastosowano układ poziomy');
            }
        }
    }
    
    // Wykonaj sprawdzenie po załadowaniu strony
    checkLayoutAndApplyStyles();
    
    // Nasłuchuj na zdarzenia nawigacji konfiguratora
    $(document).on('click', '.btn-next, .btn-prev', function() {
        // Opóźnienie, aby dać czas na załadowanie nowego kroku
        setTimeout(function() {
            checkLayoutAndApplyStyles();
        }, 300);
    });

    // Sprawdzanie typu układu i dostosowanie wyglądu slotów w kroku Mechanizmy
    function adjustSlotsLayout() {
        // Sprawdzamy, czy jesteśmy w kroku Mechanizmy
        if ($('.slots-container').length) {
            // Pobieramy informację o wybranym układzie z localStorage lub sesji
            var selectedLayoutType = localStorage.getItem('selected_layout_type') || '';
            
            // Jeśli wybrany jest układ pionowy
            if (selectedLayoutType.toLowerCase().includes('pionowy')) {
                $('.slots-container').removeClass('horizontal-slots').addClass('vertical-slots');
            } else {
                // Domyślnie układ poziomy
                $('.slots-container').removeClass('vertical-slots').addClass('horizontal-slots');
            }
        }
    }
    
    // Zapisywanie wybranego układu przy przejściu do następnego kroku
    $(document).on('click', '.layout-option', function() {
        var layoutType = $(this).data('layout-type') || '';
        localStorage.setItem('selected_layout_type', layoutType);
    });
    
    // Uruchom funkcję dostosowującą po załadowaniu strony
    adjustSlotsLayout();
    
    // Dodatkowe wywołanie przy zmianie kroku konfiguratora
    $(document).on('konfigurator_step_change', function() {
        adjustSlotsLayout();
    });

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
  
    // Funkcja zapisująca wybór układu
    function selectLayout(layoutType) {
        // Zapisz w sesji przeglądarki
        sessionStorage.setItem('selectedLayoutType', layoutType);
        
        // Zaktualizuj również dane sesji PHP (poprzez AJAX)
        jQuery.ajax({
            url: ajaxurl || kv_ajax_object.ajax_url, // Preferuj zdefiniowaną zmienną ajaxurl lub obiekt konfiguracyjny
            type: 'POST',
            data: {
                action: 'save_selected_layout', // Nazwa akcji używanej w WordPress
                layout: layoutType,
                security: kv_ajax_object.nonce || '' // Jeśli używasz nonce dla bezpieczeństwa
            },
            success: function(response) {
                console.log('Zapisano układ: ' + layoutType);
            },
            error: function(error) {
                console.log('Błąd zapisywania układu: ', error);
            }
        });
        
        // Dodaj dodatkowe działania wyboru układu, jeśli są potrzebne
        // ...
    }
    
    // Sprawdzanie układu przy ładowaniu strony
    jQuery(document).ready(function($) {
        // Debugowanie - wyświetl informacje o zapisanym układzie
        var currentLayout = sessionStorage.getItem('selectedLayoutType');
        console.log('Inicjalizacja konfiguratora, zapisany układ: ' + currentLayout);
        
        // Funkcja do sprawdzania i aplikowania stylów układu
        function checkLayoutAndApplyStyles() {
            if ($('#konfigurator-step-mechanizmy').length || $('.mechanizm-container').length) {
                var selectedLayout = sessionStorage.getItem('selectedLayoutType') || '';
                console.log('Odczytano układ: ' + selectedLayout);
                
                // Sprawdzamy czy wybrany został układ pionowy
                if (selectedLayout.toLowerCase().includes('pionowy')) {
                    // Zastosuj styl dla układu pionowego
                    $('.mechanizm-container').removeClass('horizontal-layout').addClass('vertical-layout');
                    console.log('Zastosowano układ pionowy');
                } else {
                    // Domyślnie układ poziomy
                    $('.mechanizm-container').removeClass('vertical-layout').addClass('horizontal-layout');
                    console.log('Zastosowano układ poziomy');
                }
            }
        }
        
        // Wykonaj sprawdzenie po załadowaniu strony
        checkLayoutAndApplyStyles();
        
        // Sprawdź układ po przejściu do następnego kroku
        $(document).on('click', '.btn-next, .btn-prev', function() {
            console.log('Nawigacja, aktualny układ: ' + sessionStorage.getItem('selectedLayoutType'));
            
            // Opóźnienie dla załadowania nowego kroku
            setTimeout(function() {
                checkLayoutAndApplyStyles();
            }, 300);
        });
    });
});
