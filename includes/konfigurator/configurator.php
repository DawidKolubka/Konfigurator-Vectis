<?php
defined('ABSPATH') or die('Brak dostępu');

/**
 * 1. Start sesji, jeśli nie ma
 */
if (!function_exists('kv_start_session')) {
    function kv_start_session() {
        if (!session_id()) {
            session_start();
        }
    }
}
add_action('init', 'kv_start_session', 1);

/**
 * 2. Obsługa globalnych akcji (Zapisz / Anuluj) - brak Wstecz/Dalej tutaj!
 *
 * Zwróć uwagę, że używamy tej samej akcji "kv_configurator_submit"
 * i tego samego pola "kv_configurator_nonce" co w shortcodzie.
 */
add_action('init', 'kv_handle_global_actions');
function kv_handle_global_actions() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['kv_configurator_nonce'])
        && wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')
    ) {
        // Kliknięto „Zapisz" -> zapisz do usermeta
        if (isset($_POST['kv_global_save'])) {
            kv_save_config_state();
            // Możesz dodać tutaj mechanizm przekazania komunikatu np. ?saved=1
            wp_redirect(add_query_arg('saved', 1, $_SERVER['REQUEST_URI']));
            exit;
        }

        // Kliknięto „Anuluj" -> reset sesji i redirect
        if (isset($_POST['kv_global_cancel'])) {
            kv_reset_config_state();
            wp_redirect(home_url('/konfigurator'));
            exit;
        }
    }
}

/**
 * 3. Ładowanie konfiguracji z usermeta przy starcie
 */
add_action('init', 'kv_load_config_state');
function kv_load_config_state() {
    if (!is_user_logged_in()) return;

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $user_id = get_current_user_id();
    $saved   = get_user_meta($user_id, 'kv_saved_configurator', true);
    if (!empty($saved) && is_array($saved)) {
        $_SESSION['kv_configurator'] = $saved;
    }
}

/**
 * 4. Shortcode [konfigurator-vectis] – obsługuje wszystkie kroki w jednym formularzu
 */
function kv_configurator_shortcode() {
    ob_start();

    // Inicjuj tablicę w sesji, jeśli brak
    if (!isset($_SESSION['kv_configurator'])) {
        $_SESSION['kv_configurator'] = [];
    }

    // Ustal aktualny krok (domyślnie 1)
    $step = 1;
    if (isset($_REQUEST['kv_step'])) {
        $step = intval($_REQUEST['kv_step']);
    }
    if ($step < 1) $step = 1;
    if ($step > 5) $step = 5;

    // Obsługa POST (np. wciśnięto Wstecz, Dalej, itp.)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sprawdź nonce (ten sam co globalny, 'kv_configurator_submit')
        if (!isset($_POST['kv_configurator_nonce']) 
            || !wp_verify_nonce($_POST['kv_configurator_nonce'], 'kv_configurator_submit')
        ) {
            echo "<div class='error'>Błąd: nieprawidłowy nonce.</div>";
            return ob_get_clean();
        }

        // Obsługa Wstecz
        if (isset($_POST['go_back'])) {
            $step = max(1, intval($_POST['kv_step']) - 1);
        }
        // Obsługa Dalej
        elseif (isset($_POST['go_next'])) {
            // Najpierw zapisz dane z bieżącego kroku
            switch (intval($_POST['kv_step'])) {
                case 1:
                    if (isset($_POST['seria'])) {
                        $_SESSION['kv_configurator']['seria'] = sanitize_text_field($_POST['seria']);
                    }
                    $step = 2;
                    break;
                case 2:
                    if (isset($_POST['ksztalt'])) {
                        $_SESSION['kv_configurator']['ksztalt'] = intval($_POST['ksztalt']);
                    }
                    $step = 3;
                    break;
                case 3:
                    if (isset($_POST['uklad'])) {
                        $_SESSION['kv_configurator']['uklad'] = intval($_POST['uklad']);
                    }
                    $step = 4;
                    break;
                case 4:
                    // Zapisz kolor ramki
                    if (isset($_POST['kolor_ramki'])) {
                        $_SESSION['kv_configurator']['kolor_ramki'] = sanitize_text_field($_POST['kolor_ramki']);
                    }
                    
                    // Ustal liczbę slotów
                    $uklad_index = isset($_SESSION['kv_configurator']['uklad']) ? intval($_SESSION['kv_configurator']['uklad']) : 0;
                    $uklad_options = get_option('kv_uklad_options', []);
                    $layoutName = isset($uklad_options[$uklad_index]['name']) ? $uklad_options[$uklad_index]['name'] : '';
                    
                    $ileSlotow = 1;
                    if (preg_match('/X(\d+)/i', $layoutName, $matches)) {
                        $ileSlotow = intval($matches[1]);
                    } elseif (stripos($layoutName, 'pionowy') !== false || stripos($layoutName, 'poziomy') !== false) {
                        $ileSlotow = 2;
                    }
                    
                    // Zapisz dane slotów
                    for ($i = 0; $i < $ileSlotow; $i++) {
                        if (isset($_POST['mechanizm_' . $i])) {
                            $_SESSION['kv_configurator']['mechanizm_' . $i] = sanitize_text_field($_POST['mechanizm_' . $i]);
                        }
                        if (isset($_POST['technologia_' . $i])) {
                            $_SESSION['kv_configurator']['technologia_' . $i] = sanitize_text_field($_POST['technologia_' . $i]);
                        }
                        if (isset($_POST['kolor_mechanizmu_' . $i])) {
                            $_SESSION['kv_configurator']['kolor_mechanizmu_' . $i] = sanitize_text_field($_POST['kolor_mechanizmu_' . $i]);
                        }
                    }
                    
                    $step = 5;
                    break;
                default:
                    // jeżeli coś dziwnego
                    $step = 5;
                    break;
            }
        }

    } // koniec obsługi $_SERVER['REQUEST_METHOD'] === 'POST'

    // Pasek postępu (opcjonalnie)
    ?>
    <div id="konfigurator-wrapper">
        <div id="progress-bar">
            <div class="step <?php echo ($step >= 1) ? 'active' : ''; ?>" data-step="1">Seria</div>
            <div class="step <?php echo ($step >= 2) ? 'active' : ''; ?>" data-step="2">Kształt</div>
            <div class="step <?php echo ($step >= 3) ? 'active' : ''; ?>" data-step="3">Układ</div>
            <div class="step <?php echo ($step >= 4) ? 'active' : ''; ?>" data-step="4">Mechanizmy</div>
            <div class="step <?php echo ($step >= 5) ? 'active' : ''; ?>" data-step="5">Podsumowanie</div>
        </div>

        <!-- Główny formularz - W TYM JEDNYM MIEJSCU -->
        <form method="post" action="" id="konfigurator-form">
            <?php 
            // Nonce (TE SAME wartości)
            wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); 
            ?>
            <input type="hidden" name="kv_step" value="<?php echo esc_attr($step); ?>">

            <?php
            // Ładujemy odpowiedni plik kroku
            $frontend_dir = plugin_dir_path(__FILE__) . 'frontend/';
            switch ($step) {
                case 1: include $frontend_dir . 'krok1.php'; break;
                case 2: include $frontend_dir . 'krok2.php'; break;
                case 3: include $frontend_dir . 'krok3.php'; break;
                case 4: include $frontend_dir . 'krok4.php'; break;
                case 5: include $frontend_dir . 'podsumowanie.php'; break;
            }
            ?>

            <div class="navigation-buttons" style="margin-top: 20px;">
                <!-- Wstecz: od kroku 2 w górę -->
                <?php if ($step > 1 && $step <= 5): ?>
                    <button type="submit" name="go_back" value="1" class="btn-prev">← Wstecz</button>
                <?php endif; ?>

                <!-- Dalej: do kroku 4 włącznie -->
                <?php if ($step < 5): ?>
                    <button type="submit" name="go_next" value="1" class="btn-next">Dalej →</button>
                <?php endif; ?>
            </div>

            <!-- Jeśli chcesz dołączyć przyciski globalne Zapisz/Anuluj w TYM SAMYM formularzu -->
            <?php 
            $global_buttons_path = plugin_dir_path(__FILE__) . 'frontend/global_buttons.php';
            if (file_exists($global_buttons_path)) {
                include $global_buttons_path;
            }
            ?>

        </form>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('konfigurator-vectis', 'kv_configurator_shortcode');

/**
 * 5. Funkcje pomocnicze
 */

// Zapisywanie do usermeta
function kv_save_config_state() {
    if (!is_user_logged_in()) return;
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $user_id = get_current_user_id();
    if (isset($_SESSION['kv_configurator'])) {
        update_user_meta($user_id, 'kv_saved_configurator', $_SESSION['kv_configurator']);
    }
}

// Reset konfiguracji
function kv_reset_config_state() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Usuń dane z sesji
    unset($_SESSION['kv_configurator']);
    
    // Usuń również zapisane dane z usermeta dla zalogowanych użytkowników
    if (is_user_logged_in()) {
        delete_user_meta(get_current_user_id(), 'kv_saved_configurator');
    }
}


// Przykładowe zapisywanie zamówienia w bazie
function kv_add_order($order_number, $all_items) {
    // Twój kod: np. wstaw do tabeli $wpdb->prefix . 'orders'
    // Poniższy kod to placeholder
    // global $wpdb;
    // ...
    // return $wpdb->insert_id;
    return rand(1000, 9999);
}
