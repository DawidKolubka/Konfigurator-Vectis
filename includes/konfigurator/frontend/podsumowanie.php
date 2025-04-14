<?php
// podsumowanie.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inicjalizacja tablicy pozycji w sesji, jeśli nie istnieje
if (!isset($_SESSION['kv_configurator']['items'])) {
    $_SESSION['kv_configurator']['items'] = [];
}

// Obsługa przycisku "Dodaj kolejną pozycję"
if (isset($_POST['add_item'])) {
    // Najpierw weryfikuj nonce
    if (!isset($_POST['kv_configurator_nonce']) || !wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')) {
        die('Błąd: nieprawidłowy nonce.');
    }
    
    // Reszta kodu bez zmian
    $current_config = $_SESSION['kv_configurator'];
    $items = $current_config['items'] ?? [];
    unset($current_config['items']);
    $items[] = $current_config;
    $_SESSION['kv_configurator']['items'] = $items;
    
    // Przekierowanie na stronę konfiguratora do kroku 1
    wp_redirect(home_url('/konfigurator/?step=1'));
    exit;
}

// Obsługa przycisku "Złóż zamówienie"
if (isset($_POST['final_submit'])) {
    // Dodaj bieżącą konfigurację do pozycji
    $current_config = $_SESSION['kv_configurator'];
    
    // Usuwamy z niej tablicę 'items', aby uniknąć duplikacji
    $items = $current_config['items'] ?? [];
    unset($current_config['items']);
    
    // Dodajemy bieżącą konfigurację jako ostatnią pozycję
    $items[] = $current_config;
    
    // Zapisujemy wszystkie pozycje z powrotem do sesji
    $_SESSION['kv_configurator']['items'] = $items;
    
    // Tutaj kod obsługi złożenia zamówienia (np. zapis do bazy, wysyłka e-maila itp.)
    // ...
    
    // Przekierowanie na stronę potwierdzenia zamówienia
    // wp_redirect(home_url('/konfigurator/zamowienie-przyjete/'));
    // exit;
}

// (A) Pobranie danych z bazy – tak samo, jak w krokach 1-4
$uklad_options            = get_option('kv_uklad_options', []);
$kolor_ramki_options      = get_option('kv_kolor_ramki_options', []);
$mechanizm_options        = get_option('kv_mechanizm_options', []);
$technologia_options      = get_option('kv_technologia_options', []);
$kolor_mechanizmu_options = get_option('kv_kolor_mechanizmu_options', []);
$ksztalt_options          = get_option('kv_ksztalt_options', []);
$seria_options            = get_option('kv_seria_options', []);

// (B) Zapisujemy zmienne z sesji do $cfg – i ewentualnie usuwamy slashe
$cfg = isset($_SESSION['kv_configurator']) ? $_SESSION['kv_configurator'] : [];

// Helper do usuwania slashe, gdyby `\"` wciąż się pojawiały
function maybe_stripslashes($value) {
    if (is_string($value)) {
        return stripslashes($value);
    }
    return $value;
}

// (C) Pobieranie danych z sesji
// Seria
$seria = isset($cfg['seria']) ? maybe_stripslashes($cfg['seria']) : '';

// Kształt
$ksztalt_index = isset($cfg['ksztalt']) ? maybe_stripslashes($cfg['ksztalt']) : 0;
$ksztalt_name = '';
if (isset($ksztalt_options[$ksztalt_index]['name'])) {
    $ksztalt_name = $ksztalt_options[$ksztalt_index]['name'];
}

// Układ
$uklad_index = isset($cfg['uklad']) ? maybe_stripslashes($cfg['uklad']) : 0;
$layoutName  = '';
$uklad_img   = '';
if (isset($uklad_options[$uklad_index])) {
    $layoutName = $uklad_options[$uklad_index]['name'] ?? '';
    $layoutName = maybe_stripslashes($layoutName);  // usuwamy ewentualne \"
    $uklad_img  = $uklad_options[$uklad_index]['image'] ?? '';
    $uklad_img  = maybe_stripslashes($uklad_img);
    $uklad_code = isset($uklad_options[$uklad_index]['code']) ? $uklad_options[$uklad_index]['code'] : substr($layoutName, 0, 2);
}

// (D) Na podstawie nazwy układu określamy liczbę slotów
$ileSlotow = 1;
if (preg_match('/X(\\d+)/i', $layoutName, $matches)) {
    $ileSlotow = (int) $matches[1];
} elseif (stripos($layoutName, 'pionowy') !== false || stripos($layoutName, 'poziomy') !== false) {
    $ileSlotow = 2;
}

// (E) Kolor ramki
$frame_color_index = isset($cfg['kolor_ramki']) ? maybe_stripslashes($cfg['kolor_ramki']) : '';
$frame_color_name  = '';
$frame_color_img   = '';
$frame_color_code  = '';

if ($frame_color_index !== '' && isset($kolor_ramki_options[$frame_color_index])) {
    $frame_color_name = $kolor_ramki_options[$frame_color_index]['name']  ?? '';
    $frame_color_name = maybe_stripslashes($frame_color_name);
    $frame_color_img  = $kolor_ramki_options[$frame_color_index]['image'] ?? '';
    $frame_color_img  = maybe_stripslashes($frame_color_img);
    $frame_color_code = isset($kolor_ramki_options[$frame_color_index]['code']) ? $kolor_ramki_options[$frame_color_index]['code'] : substr($frame_color_name, 0, 2);
}

// (F) Pobieramy dane slotów i składamy je w czytelną tablicę $slots
$slots = [];
$mech_code = '';
for ($i = 0; $i < $ileSlotow; $i++) {
    // odczyt z sesji
    $mechID   = isset($cfg['mechanizm_'.$i])       ? maybe_stripslashes($cfg['mechanizm_'.$i])       : '';
    $techID   = isset($cfg['technologia_'.$i])     ? maybe_stripslashes($cfg['technologia_'.$i])     : '';
    $colorVal = isset($cfg['kolor_mechanizmu_'.$i])? maybe_stripslashes($cfg['kolor_mechanizmu_'.$i]) : '';

    // dodatkowe dane o mechanizmie
    $mech_name = 'Brak nazwy';
    $mech_img  = '';
    if ($mechID !== '' && isset($mechanizm_options[$mechID])) {
        $mech_name = $mechanizm_options[$mechID]['name'] ?? 'Brak nazwy';
        $mech_img  = $mechanizm_options[$mechID]['frame_image'] ?? '';
        $mech_name = maybe_stripslashes($mech_name);
        $mech_img  = maybe_stripslashes($mech_img);
        
        // Pobieramy kod mechanizmu (lub generujemy z nazwy jeśli kod nie istnieje)
        $mech_code = isset($mechanizm_options[$mechID]['snippet']) ? $mechanizm_options[$mechID]['snippet'] : preg_replace('/[^A-Z0-9]/i', '', substr($mech_name, 0, 5));
    }

    // dodatkowe dane o technologii
    $tech_name = '';
    $tech_price = 0;
    if ($techID !== '' && isset($technologia_options[$techID])) {
        $tech_name = $technologia_options[$techID]['technology'] ?? '';
        $tech_name = maybe_stripslashes($tech_name);
        $tech_price = isset($technologia_options[$techID]['price']) ? floatval($technologia_options[$techID]['price']) : 0;
    }

    $slots[$i] = [
        'mechanizm_id'   => $mechID,
        'mechanizm_name' => $mech_name,
        'mechanizm_img'  => $mech_img,
        'technologia_id' => $techID,
        'technologia'    => $tech_name,
        'kolor_mech'     => $colorVal,
        'cena'           => $tech_price
    ];
}

// Generowanie kodu produktu
// Format: Wybrana seria (kod) + Wybrany kształt (kod) + 0 (liczba kontrolna) - wybrany mechanizm (kod) - wybrany układ (kod) - kolor ramki (kod)
// Przykład: ISDR0-12345-11P2

// Kod serii - pobieramy z pola 'fragment' w zapisanej serii
$seria_name = isset($cfg['seria']) ? maybe_stripslashes($cfg['seria']) : '';
$seria_code = 'IS'; // Domyślna wartość

// Poszukaj serii w tablicy opcji i pobierz jej fragment
foreach ($seria_options as $seria_option) {
    if ($seria_option['name'] === $seria_name && isset($seria_option['fragment'])) {
        $seria_code = $seria_option['fragment'];
        break;
    }
}

// Kod kształtu - pobieramy z pola 'snippet' w bazie danych
$ksztalt_code = isset($ksztalt_options[$ksztalt_index]['snippet']) && !empty($ksztalt_options[$ksztalt_index]['snippet']) 
    ? $ksztalt_options[$ksztalt_index]['snippet'] 
    : '?'; // Domyślna wartość, jeśli snippet nie istnieje

// Kod mechanizmu - pobieramy z pola 'snippet' w bazie danych mechanizmu
$mech_code = !empty($mechanizm_options[$mechID]['snippet'])
    ? $mechanizm_options[$mechID]['snippet']
    : 'wartość domyślna';
// Nie przypisujemy wartości domyślnej - jeśli snippet jest pusty, $mech_code pozostaje pusty

// Kod układu - np. 11
// Jeśli nie ma kodu układu, wygeneruj z nazwy
$uklad_code = (
    isset($uklad_options[$uklad_index]['snippet']) 
    && !empty($uklad_options[$uklad_index]['snippet'])
)
    ? $uklad_options[$uklad_index]['snippet']
    : 'Brak zdefiniowanego snippetu';

// Kod koloru ramki - pobieramy z pola 'snippet' w tablicy kolorów ramki
$frame_color_code = '';
if ($frame_color_index !== '' && isset($kolor_ramki_options[$frame_color_index]['snippet'])) {
    $frame_color_code = $kolor_ramki_options[$frame_color_index]['snippet'];
} else {
    // Jeśli snippet nie istnieje, użyj kodu z wcześniej przypisanej wartości
    $frame_color_code = $frame_color_code ?: 'test';
}

// Łączymy kody w określonym formacie: XXYR0-ZZZZZ-AABB
// gdzie XX = kod serii, Y = kod kształtu, ZZZZZ = kod mechanizmu, AA = kod układu, BB = kod koloru ramki
$product_code = strtoupper($seria_code . $ksztalt_code . "0-" . $mech_code . "-" . $uklad_code . $frame_color_code);

// (G) Ustalanie ilości, jeśli zapisana w sesji
$quantity = isset($cfg['quantity']) ? (int) $cfg['quantity'] : 1;

// (H) Obliczanie cen
$cena_jednostkowa = 0;
// Suma cen wszystkich technologii w slotach
foreach ($slots as $slot) {
    $cena_jednostkowa += $slot['cena'];
}
// Jeśli cena jest zerowa, ustawiamy domyślnie 1
$cena_jednostkowa = ($cena_jednostkowa > 0) ? $cena_jednostkowa : 1;

$cena_calkowita = $cena_jednostkowa * $quantity;

// Obsługa zmiany ilości
if (isset($_POST['update_quantity']) && isset($_POST['quantity'])) {
    $quantity = max(1, intval($_POST['quantity']));
    $_SESSION['kv_configurator']['quantity'] = $quantity;
    $cena_calkowita = $cena_jednostkowa * $quantity;
}

// Funkcja pomocnicza do renderowania wiersza pozycji w tabeli
function render_item_row($item_index, $item_data, $uklad_options, $kolor_ramki_options, $mechanizm_options, $technologia_options) {
    // Pobranie danych z zapisanej konfiguracji
    $uklad_index = isset($item_data['uklad']) ? $item_data['uklad'] : 0;
    $layoutName = isset($uklad_options[$uklad_index]['name']) ? $uklad_options[$uklad_index]['name'] : '';
    $uklad_img = isset($uklad_options[$uklad_index]['image']) ? $uklad_options[$uklad_index]['image'] : '';
    
    $frame_color_index = isset($item_data['kolor_ramki']) ? $item_data['kolor_ramki'] : '';
    $frame_color_name = '';
    $frame_color_img = '';
    if ($frame_color_index !== '' && isset($kolor_ramki_options[$frame_color_index])) {
        $frame_color_name = $kolor_ramki_options[$frame_color_index]['name'] ?? '';
        $frame_color_img = $kolor_ramki_options[$frame_color_index]['image'] ?? '';
    }
    
    // Określenie liczby slotów
    $ileSlotow = 1;
    if (preg_match('/X(\\d+)/i', $layoutName, $matches)) {
        $ileSlotow = (int) $matches[1];
    } elseif (stripos($layoutName, 'pionowy') !== false || stripos($layoutName, 'poziomy') !== false) {
        $ileSlotow = 2;
    }
    
    // Pobieranie danych slotów
    $slots = [];
    for ($i = 0; $i < $ileSlotow; $i++) {
        $mechID = isset($item_data['mechanizm_'.$i]) ? $item_data['mechanizm_'.$i] : '';
        $techID = isset($item_data['technologia_'.$i]) ? $item_data['technologia_'.$i] : '';
        $colorVal = isset($item_data['kolor_mechanizmu_'.$i]) ? $item_data['kolor_mechanizmu_'.$i] : '';
        
        // Dane mechanizmu
        $mech_name = 'Brak nazwy';
        $mech_img = '';
        if ($mechID !== '' && isset($mechanizm_options[$mechID])) {
            $mech_name = $mechanizm_options[$mechID]['name'] ?? 'Brak nazwy';
            $mech_img = $mechanizm_options[$mechID]['frame_image'] ?? '';
        }
        
        // Dane technologii
        $tech_name = '';
        $tech_price = 0;
        if ($techID !== '' && isset($technologia_options[$techID])) {
            $tech_name = $technologia_options[$techID]['technology'] ?? '';
            $tech_price = isset($technologia_options[$techID]['price']) ? floatval($technologia_options[$techID]['price']) : 0;
        }
        
        $slots[$i] = [
            'mechanizm_id' => $mechID,
            'mechanizm_name' => $mech_name,
            'mechanizm_img' => $mech_img,
            'technologia_id' => $techID,
            'technologia' => $tech_name,
            'kolor_mech' => $colorVal,
            'cena' => $tech_price
        ];
    }
    
    // Obliczanie ceny jednostkowej
    $cena_jednostkowa = 0;
    foreach ($slots as $slot) {
        $cena_jednostkowa += $slot['cena'];
    }
    $cena_jednostkowa = ($cena_jednostkowa > 0) ? $cena_jednostkowa : 1;
    
    // Ilość
    $quantity = isset($item_data['quantity']) ? (int) $item_data['quantity'] : 1;
    $cena_calkowita = $cena_jednostkowa * $quantity;
    
    // Kod produktu (pełny kod powinien być już zapisany w konfiguracji)
    $product_code = isset($item_data['product_code']) ? $item_data['product_code'] : 'Brak kodu';
    
    // Wyświetlenie wiersza
    ?>
    <tr>
        <!-- L.P. -->
        <td><?php echo ($item_index + 1); ?></td>
        
        <!-- RAMKA -->
        <td>
            <strong><?php echo esc_html($layoutName); ?></strong><br>
            <?php if ($uklad_img): ?>
                <img src="<?php echo esc_url($uklad_img); ?>" alt="<?php echo esc_attr($layoutName); ?>" style="max-width:80px; display:block;margin-bottom:5px;">
            <?php endif; ?>
            
            <?php if ($frame_color_img): ?>
                <img src="<?php echo esc_url($frame_color_img); ?>" alt="<?php echo esc_attr($frame_color_name); ?>" style="max-width:40px; vertical-align:middle;">
            <?php endif; ?>
            <?php echo esc_html($frame_color_name); ?>
            
            <!-- Kod produktu -->
            <div class="product-code" style="margin-top:10px; padding:5px; background:#f8f8f8; border:1px solid #ddd;">
                <strong>Kod produktu:</strong><br>
                <?php echo esc_html($product_code); ?>
            </div>
        </td>
        
        <!-- MECHANIZMY -->
        <td>
            <?php if (!empty($slots)): ?>
                <?php foreach ($slots as $index => $slot): ?>
                    <div style="margin-bottom:10px;border-bottom:1px dotted #ccc;padding-bottom:5px;">
                        <!-- Ikona mechanizmu -->
                        <?php if (!empty($slot['mechanizm_img'])): ?>
                            <img src="<?php echo esc_url($slot['mechanizm_img']); ?>" alt="<?php echo esc_attr($slot['mechanizm_name']); ?>" style="max-width:30px;vertical-align:middle;margin-right:5px;">
                        <?php endif; ?>
                        
                        <!-- Nazwa mechanizmu -->
                        <strong><?php echo esc_html($slot['mechanizm_name']); ?></strong><br>
                        
                        <!-- Technologia -->
                        <?php if (!empty($slot['technologia'])): ?>
                            Technologia: <?php echo esc_html($slot['technologia']); ?><br>
                        <?php endif; ?>
                        
                        <!-- Kolor mechanizmu -->
                        <?php if (!empty($slot['kolor_mech'])): ?>
                            Kolor: <?php echo esc_html($slot['kolor_mech']); ?>
                        <?php endif; ?>
                        
                        <!-- Kod mechanizmu - pobieramy z technologii -->
                        <?php 
                        $tech_id = $slot['technologia_id'];
                        if (!empty($tech_id) && isset($technologia_options[$tech_id]) && isset($technologia_options[$tech_id]['code'])): 
                        ?>
                            <div class="mechanizm-code" style="margin-top:5px; padding:5px; background:#f8f8f8; border:1px solid #ddd;">
                                <strong>Kod mechanizmu:</strong><br>
                                <?php echo esc_html($technologia_options[$tech_id]['code']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                Brak mechanizmów
            <?php endif; ?>
        </td>
        
        <!-- ILOŚĆ -->
        <td><?php echo $quantity; ?></td>
        
        <!-- CENA JEDNOSTKOWA -->
        <td><?php echo number_format($cena_jednostkowa, 2, ',', ' '); ?> zł</td>
        
        <!-- CENA CAŁOŚĆ -->
        <td><?php echo number_format($cena_calkowita, 2, ',', ' '); ?> zł</td>
    </tr>
    <?php
    
    return $cena_calkowita;
}
?>

<div class="step-content">
    <h2>Krok 5: Podsumowanie</h2>

    <form method="post" action="" id="update-quantity-form">
        <table class="summary-table">
            <thead>
                <tr>
                    <th>L.P.</th>
                    <th>Ramka</th>
                    <th>Mechanizmy</th>
                    <th>Ilość</th>
                    <th>Cena jednostkowa</th>
                    <th>Cena całość</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Wyświetlanie zapisanych wcześniej pozycji
                $total_price = 0;
                if (isset($_SESSION['kv_configurator']['items']) && !empty($_SESSION['kv_configurator']['items'])) {
                    foreach ($_SESSION['kv_configurator']['items'] as $item_index => $item_data) {
                        $total_price += render_item_row($item_index, $item_data, $uklad_options, $kolor_ramki_options, $mechanizm_options, $technologia_options);
                    }
                }
                
                // Ustalamy numer bieżącej pozycji
                $current_position = isset($_SESSION['kv_configurator']['items']) ? count($_SESSION['kv_configurator']['items']) : 0;
                ?>
                
                <!-- Bieżąca konfiguracja -->
                <tr>
                    <!-- L.P. -->
                    <td><?php echo $current_position + 1; ?></td>

                    <!-- RAMKA -->
                    <td>
                        <strong><?php echo esc_html($layoutName); ?></strong><br>
                        <?php if ($uklad_img): ?>
                            <img src="<?php echo esc_url($uklad_img); ?>" alt="<?php echo esc_attr($layoutName); ?>" style="max-width:80px; display:block;margin-bottom:5px;">
                        <?php endif; ?>

                        <?php if ($frame_color_img): ?>
                            <img src="<?php echo esc_url($frame_color_img); ?>" alt="<?php echo esc_attr($frame_color_name); ?>" style="max-width:40px; vertical-align:middle;">
                        <?php endif; ?>
                        <?php echo esc_html($frame_color_name); ?>
                        
                        <!-- Kod produktu -->
                        <div class="product-code" style="margin-top:10px; padding:5px; background:#f8f8f8; border:1px solid #ddd;">
                            <strong>Kod produktu:</strong><br>
                            <?php echo esc_html($product_code); ?>
                        </div>
                    </td>

                    <!-- MECHANIZMY -->
                    <td>
                        <?php if (!empty($slots)): ?>
                            <?php foreach ($slots as $index => $slot): ?>
                                <div style="margin-bottom:10px;border-bottom:1px dotted #ccc;padding-bottom:5px;">
                                    <!-- Ikona mechanizmu -->
                                    <?php if (!empty($slot['mechanizm_img'])): ?>
                                        <img src="<?php echo esc_url($slot['mechanizm_img']); ?>" alt="<?php echo esc_attr($slot['mechanizm_name']); ?>" style="max-width:30px;vertical-align:middle;margin-right:5px;">
                                    <?php endif; ?>

                                    <!-- Nazwa mechanizmu -->
                                    <strong><?php echo esc_html($slot['mechanizm_name']); ?></strong><br>

                                    <!-- Technologia -->
                                    <?php if (!empty($slot['technologia'])): ?>
                                        Technologia: <?php echo esc_html($slot['technologia']); ?><br>
                                    <?php endif; ?>

                                    <!-- Kolor mechanizmu -->
                                    <?php if (!empty($slot['kolor_mech'])): ?>
                                        Kolor: <?php echo esc_html($slot['kolor_mech']); ?>
                                    <?php endif; ?>

                                    <!-- Kod mechanizmu - pobieramy z technologii -->
                                    <?php 
                                    $tech_id = $slot['technologia_id'];
                                    $has_tech_id = !empty($tech_id);
                                    $tech_exists = $has_tech_id && isset($technologia_options[$tech_id]);
                                    $has_tech_code = $tech_exists && isset($technologia_options[$tech_id]['code']);
                                    
                                    if ($has_tech_id && $tech_exists && $has_tech_code): 
                                    ?>
                                        <div class="mechanizm-code" style="margin-top:5px; padding:5px; background:#f8f8f8; border:1px solid #ddd;">
                                            <strong>Kod mechanizmu:</strong><br>
                                            <?php echo esc_html($technologia_options[$tech_id]['code']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            Brak mechanizmów
                        <?php endif; ?>
                    </td>

                    <!-- ILOŚĆ -->
                    <td>
                        <input type="number" name="quantity" id="quantity" value="<?php echo esc_attr($quantity); ?>" min="1" style="width:60px;">
                        <button type="submit" name="update_quantity" class="button-small">Aktualizuj</button>
                    </td>

                    <!-- CENA JEDNOSTKOWA -->
                    <td><?php echo number_format($cena_jednostkowa, 2, ',', ' '); ?> zł</td>

                    <!-- CENA CAŁOŚĆ -->
                    <td id="cena_calkowita"><?php echo number_format($cena_calkowita, 2, ',', ' '); ?> zł</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"></td>
                    <td><strong>Razem:</strong></td>
                    <td><strong id="suma_calosc"><?php echo number_format($total_price + $cena_calkowita, 2, ',', ' '); ?> zł</strong></td>
                </tr>
            </tfoot>
        </table>
    </form>

<div class="action-buttons" style="margin-top: 20px;">
    <!-- Przyciski akcji -->
    <form method="post" id="konfigurator-form">
        <?php wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); ?>
        <button type="submit" name="add_item" class="btn-add-item">Dodaj kolejną pozycję</button>
        <button type="submit" name="final_submit" class="btn-submit-order">Złóż zamówienie</button>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aktualizacja ceny po zmianie ilości bez przesyłania formularza
    const quantityInput = document.getElementById('quantity');
    
    quantityInput.addEventListener('change', function() {
        const quantity = Math.max(1, parseInt(this.value) || 1);
        const cenaJednostkowa = <?php echo $cena_jednostkowa; ?>;
        const cenaCalosc = quantity * cenaJednostkowa;
        
        // Formatowanie liczby z dwoma miejscami po przecinku
        const formattedPrice = new Intl.NumberFormat('pl-PL', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(cenaCalosc);
        
        // Aktualizacja wyświetlanej ceny
        document.getElementById('cena_calkowita').textContent = formattedPrice + ' zł';
        
        // Zaktualizuj sumę całkowitą
        const currentTotal = <?php echo $total_price; ?>;
        const newTotal = currentTotal + cenaCalosc;
        document.getElementById('suma_calosc').textContent = 
            new Intl.NumberFormat('pl-PL', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(newTotal) + ' zł';
    });
});
</script>
