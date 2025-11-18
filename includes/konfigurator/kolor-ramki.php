<?php
defined('ABSPATH') or die('Brak dostępu');

// Ładujemy funkcję submit_button, jeśli nie jest załadowana
if ( ! function_exists('submit_button') ) {
    require_once( ABSPATH . 'wp-admin/includes/template.php' );
}

// Dołącz plik z funkcjami CRUD (kv_get_items, kv_add_item, kv_update_item, kv_delete_item)
require_once __DIR__ . '/crud.php';

/**
 * Funkcja wyświetlająca panel administracyjny dla modułu "Kolor Ramki".
 */
if ( ! function_exists('kv_admin_kolor_ramki_page') ) {
    function kv_admin_kolor_ramki_page() {
        $option_key = 'kv_kolor_ramki_options';

        // --- Obsługa duplikacji ---
        if ( isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $new_id = kv_duplicate_item( $option_key, $id );
            if ( $new_id !== false ) {
                echo '<div class="notice notice-success is-dismissible"><p>Kolor ramki został zduplikowany.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nie udało się zduplikować koloru ramki.</p></div>';
            }
        }

        // --- Obsługa usuwania elementu ---
        if ( isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            if ( isset($_GET['kv_kolor_ramki_nonce']) && wp_verify_nonce($_GET['kv_kolor_ramki_nonce'], 'kv_delete_kolor_ramki_' . $id) ) {
                kv_delete_item( $option_key, $id );
                echo '<div class="notice notice-success is-dismissible"><p>Kolor ramki został usunięty.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Brak uprawnień do usunięcia koloru ramki.</p></div>';
            }
        }

        // --- Obsługa edycji ---
        if ( isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $items = kv_get_items( $option_key );
            if ( isset($items[$id]) ) {
                $current = $items[$id];
                if ( isset($_POST['kv_edit_kolor_ramki_nonce']) && wp_verify_nonce($_POST['kv_edit_kolor_ramki_nonce'], 'kv_edit_kolor_ramki_' . $id) ) {
                    $edited_name  = sanitize_text_field( $_POST['edit_kolor_ramki'] );
                    $edited_image = esc_url_raw( $_POST['edit_kolor_ramki_image'] );
                    
                    // Pobierz ceny dla poszczególnych układów
                    $edited_price_x1 = isset($_POST['edit_kolor_ramki_price_x1']) ? sanitize_text_field($_POST['edit_kolor_ramki_price_x1']) : '';
                    $edited_price_x2 = isset($_POST['edit_kolor_ramki_price_x2']) ? sanitize_text_field($_POST['edit_kolor_ramki_price_x2']) : '';
                    $edited_price_x3 = isset($_POST['edit_kolor_ramki_price_x3']) ? sanitize_text_field($_POST['edit_kolor_ramki_price_x3']) : '';
                    $edited_price_x4 = isset($_POST['edit_kolor_ramki_price_x4']) ? sanitize_text_field($_POST['edit_kolor_ramki_price_x4']) : '';
                    $edited_price_x5 = isset($_POST['edit_kolor_ramki_price_x5']) ? sanitize_text_field($_POST['edit_kolor_ramki_price_x5']) : '';
                    
                    // Zachowujemy kompatybilność wsteczną - cena ogólna
                    $edited_price = isset($_POST['edit_kolor_ramki_price']) ? sanitize_text_field($_POST['edit_kolor_ramki_price']) : '';
                    
                    $edited_snippet = isset($_POST['edit_kolor_ramki_snippet']) ? sanitize_text_field($_POST['edit_kolor_ramki_snippet']) : '';
                    kv_update_item( $option_key, $id, array(
                        'name'      => $edited_name,
                        'image'     => $edited_image,
                        'price'     => $edited_price,
                        'price_x1'  => $edited_price_x1,
                        'price_x2'  => $edited_price_x2,
                        'price_x3'  => $edited_price_x3,
                        'price_x4'  => $edited_price_x4,
                        'price_x5'  => $edited_price_x5,
                        'snippet'   => $edited_snippet,
                    ) );
                    echo '<div class="notice notice-success is-dismissible"><p>Kolor ramki został zaktualizowany.</p></div>';
                    echo '<a href="' . esc_url( remove_query_arg( array('action','id','kv_kolor_ramki_nonce') ) ) . '" class="button">Powrót</a>';
                    return;
                }
                ?>
                <div class="wrap">
                    <h1>Edytuj kolor ramki</h1>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'kv_edit_kolor_ramki_' . $id, 'kv_edit_kolor_ramki_nonce' ); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit_kolor_ramki">Nazwa koloru ramki</label></th>
                                <td>
                                    <input type="text" id="edit_kolor_ramki" name="edit_kolor_ramki" class="regular-text" value="<?php echo esc_attr( $current['name'] ); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_kolor_ramki_image">Obrazek koloru ramki</label></th>
                                <td>
                                    <input type="text" id="edit_kolor_ramki_image" name="edit_kolor_ramki_image" class="regular-text" value="<?php echo esc_url( $current['image'] ); ?>">
                                    <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_kolor_ramki_price_x1">Ceny ramki dla układów</label></th>
                                <td>
                                    <input type="text" id="edit_kolor_ramki_price_x1" name="edit_kolor_ramki_price_x1" class="regular-text" value="<?php echo isset($current['price_x1']) ? esc_attr($current['price_x1']) : ''; ?>" placeholder="0.00">
                                    <p class="description">Cena ramki dla układu X1 (np. 10.00)</p>
                                    
                                    <input type="text" id="edit_kolor_ramki_price_x2" name="edit_kolor_ramki_price_x2" class="regular-text" value="<?php echo isset($current['price_x2']) ? esc_attr($current['price_x2']) : ''; ?>" placeholder="0.00" style="margin-top: 5px;">
                                    <p class="description">Cena ramki dla układu X2 (np. 15.00)</p>
                                    
                                    <input type="text" id="edit_kolor_ramki_price_x3" name="edit_kolor_ramki_price_x3" class="regular-text" value="<?php echo isset($current['price_x3']) ? esc_attr($current['price_x3']) : ''; ?>" placeholder="0.00" style="margin-top: 5px;">
                                    <p class="description">Cena ramki dla układu X3 (np. 20.00)</p>
                                    
                                    <input type="text" id="edit_kolor_ramki_price_x4" name="edit_kolor_ramki_price_x4" class="regular-text" value="<?php echo isset($current['price_x4']) ? esc_attr($current['price_x4']) : ''; ?>" placeholder="0.00" style="margin-top: 5px;">
                                    <p class="description">Cena ramki dla układu X4 (np. 25.00)</p>
                                    
                                    <input type="text" id="edit_kolor_ramki_price_x5" name="edit_kolor_ramki_price_x5" class="regular-text" value="<?php echo isset($current['price_x5']) ? esc_attr($current['price_x5']) : ''; ?>" placeholder="0.00" style="margin-top: 5px;">
                                    <p class="description">Cena ramki dla układu X5 (np. 30.00)</p>
                                    
                                    <!-- Zachowanie kompatybilności wstecznej -->
                                    <input type="hidden" id="edit_kolor_ramki_price" name="edit_kolor_ramki_price" value="<?php echo isset($current['price']) ? esc_attr($current['price']) : ''; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_kolor_ramki_snippet">Cząstka kodu</label></th>
                                <td>
                                    <input type="text" id="edit_kolor_ramki_snippet" name="edit_kolor_ramki_snippet" class="regular-text" value="<?php echo isset($current['snippet']) ? esc_attr($current['snippet']) : ''; ?>" placeholder="Opcjonalnie">
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Zapisz zmiany'); ?>
                    </form>
                    <script>
                    jQuery(document).ready(function($) {
                        $('.kv-upload-image-button').on('click', function(e) {
                            e.preventDefault();
                            var custom_uploader = wp.media({
                                title: 'Wybierz obrazek',
                                library: { type: 'image' },
                                button: { text: 'Wybierz obrazek' },
                                multiple: false
                            }).on('select', function(){
                                var attachment = custom_uploader.state().get('selection').first().toJSON();
                                $('#edit_kolor_ramki_image').val(attachment.url);
                            }).open();
                        });
                    });
                    </script>
                </div>
                <?php
                return;
            }
        }

        // Obsługa dodawania nowego koloru ramki
        if ( isset($_POST['kv_kolor_ramki_nonce']) && wp_verify_nonce($_POST['kv_kolor_ramki_nonce'], 'kv_save_kolor_ramki') ) {
            $new_color = sanitize_text_field( $_POST['new_kolor_ramki'] );
            $color_image = esc_url_raw( $_POST['kolor_ramki_image'] );
            
            // Pobierz ceny dla poszczególnych układów
            $color_price_x1 = isset($_POST['kolor_ramki_price_x1']) ? sanitize_text_field($_POST['kolor_ramki_price_x1']) : '';
            $color_price_x2 = isset($_POST['kolor_ramki_price_x2']) ? sanitize_text_field($_POST['kolor_ramki_price_x2']) : '';
            $color_price_x3 = isset($_POST['kolor_ramki_price_x3']) ? sanitize_text_field($_POST['kolor_ramki_price_x3']) : '';
            $color_price_x4 = isset($_POST['kolor_ramki_price_x4']) ? sanitize_text_field($_POST['kolor_ramki_price_x4']) : '';
            $color_price_x5 = isset($_POST['kolor_ramki_price_x5']) ? sanitize_text_field($_POST['kolor_ramki_price_x5']) : '';
            
            // Zachowujemy kompatybilność wsteczną
            $color_price = isset($_POST['kolor_ramki_price']) ? sanitize_text_field($_POST['kolor_ramki_price']) : '';
            // Jeśli nie ustawiono ceny X1, a jest cena ogólna, użyjmy jej jako domyślnej
            if (empty($color_price_x1) && !empty($color_price)) {
                $color_price_x1 = $color_price;
            }
            
            $color_snippet = isset($_POST['kolor_ramki_snippet']) ? sanitize_text_field($_POST['kolor_ramki_snippet']) : '';
            if ( ! empty($new_color) ) {
                kv_add_item( $option_key, array(
                    'name'      => $new_color,
                    'image'     => $color_image,
                    'price'     => $color_price,
                    'price_x1'  => $color_price_x1,
                    'price_x2'  => $color_price_x2, 
                    'price_x3'  => $color_price_x3,
                    'price_x4'  => $color_price_x4,
                    'price_x5'  => $color_price_x5,
                    'snippet'   => $color_snippet,
                ) );
                echo '<div class="notice notice-success is-dismissible"><p>Nowy kolor ramki został dodany.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nazwa koloru ramki jest wymagana.</p></div>';
            }
        }

        // Pobierz aktualne kolory ramki
        $color_options = kv_get_items( $option_key );
        ?>
        <div class="wrap">
            <h1>Konfigurator – Kolor Ramki</h1>
            <!-- Formularz dodawania nowego koloru ramki -->
            <form method="post" action="">
                <?php wp_nonce_field('kv_save_kolor_ramki', 'kv_kolor_ramki_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="new_kolor_ramki">Nazwa koloru ramki</label></th>
                        <td>
                            <input type="text" id="new_kolor_ramki" name="new_kolor_ramki" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kolor_ramki_image">Obrazek koloru ramki</label></th>
                        <td>
                            <input type="text" id="kolor_ramki_image" name="kolor_ramki_image" class="regular-text">
                            <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek">
                        </td>
                    </tr>
                    <!-- Dodajemy pola cen ramki dla różnych układów -->
                    <tr>
                        <th scope="row"><label for="kolor_ramki_price_x1">Ceny ramki dla układów</label></th>
                        <td>
                            <input type="text" id="kolor_ramki_price_x1" name="kolor_ramki_price_x1" class="regular-text" placeholder="0.00">
                            <p class="description">Cena ramki dla układu X1 (np. 10.00)</p>
                            
                            <input type="text" id="kolor_ramki_price_x2" name="kolor_ramki_price_x2" class="regular-text" placeholder="0.00" style="margin-top: 5px;">
                            <p class="description">Cena ramki dla układu X2 (np. 15.00)</p>
                            
                            <input type="text" id="kolor_ramki_price_x3" name="kolor_ramki_price_x3" class="regular-text" placeholder="0.00" style="margin-top: 5px;">
                            <p class="description">Cena ramki dla układu X3 (np. 20.00)</p>
                            
                            <input type="text" id="kolor_ramki_price_x4" name="kolor_ramki_price_x4" class="regular-text" placeholder="0.00" style="margin-top: 5px;">
                            <p class="description">Cena ramki dla układu X4 (np. 25.00)</p>
                            
                            <input type="text" id="kolor_ramki_price_x5" name="kolor_ramki_price_x5" class="regular-text" placeholder="0.00" style="margin-top: 5px;">
                            <p class="description">Cena ramki dla układu X5 (np. 30.00)</p>
                            
                            <!-- Zachowanie kompatybilności wstecznej -->
                            <input type="hidden" id="kolor_ramki_price" name="kolor_ramki_price" class="regular-text" placeholder="0.00">
                        </td>
                    </tr>
                    <!-- Dodajemy pole "Cząstka kodu" -->
                    <tr>
                        <th scope="row"><label for="kolor_ramki_snippet">Cząstka kodu</label></th>
                        <td>
                            <input type="text" id="kolor_ramki_snippet" name="kolor_ramki_snippet" class="regular-text" placeholder="Opcjonalnie">
                        </td>
                    </tr>
                </table>
                <?php submit_button('Dodaj nowy kolor ramki'); ?>
            </form>

            <h2>Lista dostępnych kolorów ramki</h2>
            <?php if ( ! empty($color_options) ) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Obrazek</th>
                            <th>Cena X1</th>
                            <th>Cena X2</th>
                            <th>Cena X3</th>
                            <th>Cena X4</th>
                            <th>Cena X5</th>
                            <th>Cząstka kodu</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $color_options as $index => $color ) : ?>
                            <tr<?php echo ($index === 0) ? ' class="kv-placeholder-row" style="background-color:#ffffcc;"' : ''; ?>>
                                <td>
                                    <?php 
                                    echo esc_html( $color['name'] );
                                    if ($index === 0) {
                                        echo ' <span style="color:red;">(placeholder - nie wyświetla się na froncie)</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ( !empty($color['image']) ) : ?>
                                        <img src="<?php echo esc_url( $color['image'] ); ?>" style="max-width:80px;height:auto;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($color['price_x1']) ? esc_html($color['price_x1']) : (isset($color['price']) ? esc_html($color['price']) : ''); ?></td>
                                <td><?php echo isset($color['price_x2']) ? esc_html($color['price_x2']) : ''; ?></td>
                                <td><?php echo isset($color['price_x3']) ? esc_html($color['price_x3']) : ''; ?></td>
                                <td><?php echo isset($color['price_x4']) ? esc_html($color['price_x4']) : ''; ?></td>
                                <td><?php echo isset($color['price_x5']) ? esc_html($color['price_x5']) : ''; ?></td>
                                <td><?php echo isset($color['snippet']) ? esc_html($color['snippet']) : ''; ?></td>
                                <td>
                                    <?php
                                    $edit_url = add_query_arg( array(
                                        'page'   => 'kv-kolor-ramki',
                                        'action' => 'edit',
                                        'id'     => $index,
                                    ), admin_url('admin.php') );
                                    $delete_url = add_query_arg( array(
                                        'page'            => 'kv-kolor-ramki',
                                        'action'          => 'delete',
                                        'id'              => $index,
                                        'kv_kolor_ramki_nonce' => wp_create_nonce( 'kv_delete_kolor_ramki_' . $index ),
                                    ), admin_url('admin.php') );
                                    // Dodaj link do duplikowania
                                    $duplicate_url = add_query_arg( array(
                                        'page'   => 'kv-kolor-ramki',
                                        'action' => 'duplicate',
                                        'id'     => $index,
                                    ), admin_url('admin.php') );
                                    ?>
                                    <a href="<?php echo esc_url($edit_url); ?>">Edytuj</a> |
                                    <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Czy na pewno usunąć ten kolor?');">Usuń</a> |
                                    <a href="<?php echo esc_url($duplicate_url); ?>">Duplikuj</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Brak dodanych kolorów ramki.</p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($){
            // Uploader dla formularza dodawania nowego koloru ramki
            $('.kv-upload-image-button').on('click', function(e){
                e.preventDefault();
                var custom_uploader = wp.media({
                    title: 'Wybierz obrazek',
                    library: { type: 'image' },
                    button: { text: 'Wybierz obrazek' },
                    multiple: false
                }).on('select', function(){
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    $(e.target).siblings('input[type="text"]').first().val(attachment.url);
                }).open();
            });
        });
        </script>
        <?php
    }
}
