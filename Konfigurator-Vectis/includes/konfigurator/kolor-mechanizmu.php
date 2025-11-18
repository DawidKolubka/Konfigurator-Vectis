<?php
defined('ABSPATH') or die('Brak dostępu');

// Ładujemy funkcję submit_button, jeśli nie jest załadowana
if ( ! function_exists('submit_button') ) {
    require_once( ABSPATH . 'wp-admin/includes/template.php' );
}

// Dołącz plik z funkcjami CRUD (kv_get_items, kv_add_item, kv_update_item, kv_delete_item)
require_once __DIR__ . '/crud.php';

/**
 * Funkcja wyświetlająca panel administracyjny dla modułu "Kolor Mechanizmu".
 */
if ( ! function_exists('kv_admin_kolor_mechanizmu_page') ) {
    function kv_admin_kolor_mechanizmu_page() {
        $option_key = 'kv_kolor_mechanizmu_options';

        // --- Obsługa duplikacji ---
        if ( isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $new_id = kv_duplicate_item( $option_key, $id );
            if ( $new_id !== false ) {
                echo '<div class="notice notice-success is-dismissible"><p>Kolor mechanizmu został zduplikowany.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nie udało się zduplikować koloru mechanizmu.</p></div>';
            }
        }

        // --- Obsługa usuwania elementu ---
        if ( isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            if ( isset($_GET['kv_kolor_mechanizmu_nonce']) && wp_verify_nonce($_GET['kv_kolor_mechanizmu_nonce'], 'kv_delete_kolor_mechanizmu_' . $id) ) {
                kv_delete_item( $option_key, $id );
                echo '<div class="notice notice-success is-dismissible"><p>Kolor mechanizmu został usunięty.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Brak uprawnień do usunięcia koloru mechanizmu.</p></div>';
            }
        }

        // --- Obsługa edycji ---
        if ( isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $items = kv_get_items( $option_key );
            if ( isset($items[$id]) ) {
                $current = $items[$id];
                if ( isset($_POST['kv_edit_kolor_mechanizmu_nonce']) && wp_verify_nonce($_POST['kv_edit_kolor_mechanizmu_nonce'], 'kv_edit_kolor_mechanizmu_' . $id) ) {
                    $edited_name  = sanitize_text_field( $_POST['edit_kolor_mechanizmu'] );
                    $edited_image = esc_url_raw( $_POST['edit_kolor_mechanizmu_image'] );
                    $edited_snippet = isset($_POST['edit_kolor_mechanizmu_snippet']) ? sanitize_text_field($_POST['edit_kolor_mechanizmu_snippet']) : '';
                    kv_update_item( $option_key, $id, array(
                        'name'    => $edited_name,
                        'image'   => $edited_image,
                        'snippet' => $edited_snippet,
                    ) );
                    echo '<div class="notice notice-success is-dismissible"><p>Kolor mechanizmu został zaktualizowany.</p></div>';
                    echo '<a href="' . esc_url( remove_query_arg( array('action','id','kv_kolor_mechanizmu_nonce') ) ) . '" class="button">Powrót</a>';
                    return;
                }
                ?>
                <div class="wrap">
                    <h1>Edytuj kolor mechanizmu</h1>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'kv_edit_kolor_mechanizmu_' . $id, 'kv_edit_kolor_mechanizmu_nonce' ); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit_kolor_mechanizmu">Nazwa koloru mechanizmu</label></th>
                                <td>
                                    <input type="text" id="edit_kolor_mechanizmu" name="edit_kolor_mechanizmu" class="regular-text" value="<?php echo esc_attr($current['name']); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_kolor_mechanizmu_image">Ikona koloru</label></th>
                                <td>
                                    <input type="text" id="edit_kolor_mechanizmu_image" name="edit_kolor_mechanizmu_image" class="regular-text" value="<?php echo esc_url( $current['image'] ); ?>">
                                    <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_kolor_mechanizmu_snippet">Cząstka kodu</label></th>
                                <td>
                                    <input type="text" id="edit_kolor_mechanizmu_snippet" name="edit_kolor_mechanizmu_snippet" class="regular-text" value="<?php echo isset($current['snippet']) ? esc_attr($current['snippet']) : ''; ?>" placeholder="Opcjonalnie">
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
                                $('#edit_kolor_mechanizmu_image').val(attachment.url);
                            }).open();
                        });
                    });
                    </script>
                </div>
                <?php
                return;
            }
        }

        // Obsługa dodawania nowego koloru mechanizmu
        if ( isset($_POST['kv_kolor_mechanizmu_nonce']) && wp_verify_nonce($_POST['kv_kolor_mechanizmu_nonce'], 'kv_save_kolor_mechanizmu') ) {
            $new_color = sanitize_text_field( $_POST['new_kolor_mechanizmu'] );
            $color_image = esc_url_raw( $_POST['kolor_mechanizmu_image'] );
            $color_snippet = isset($_POST['kolor_mechanizmu_snippet']) ? sanitize_text_field($_POST['kolor_mechanizmu_snippet']) : '';
            if ( ! empty($new_color) ) {
                kv_add_item( $option_key, array(
                    'name'    => $new_color,
                    'image'   => $color_image,
                    'snippet' => $color_snippet,
                ) );
                echo '<div class="notice notice-success is-dismissible"><p>Nowy kolor mechanizmu został dodany.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nazwa koloru mechanizmu jest wymagana.</p></div>';
            }
        }

        // Pobierz aktualne kolory mechanizmu
        $color_options = kv_get_items( $option_key );
        ?>
        <div class="wrap">
            <h1>Konfigurator – Kolor Mechanizmu</h1>
            <!-- Formularz dodawania nowego koloru mechanizmu -->
            <form method="post" action="">
                <?php wp_nonce_field('kv_save_kolor_mechanizmu', 'kv_kolor_mechanizmu_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="new_kolor_mechanizmu">Nazwa koloru mechanizmu</label></th>
                        <td>
                            <input type="text" id="new_kolor_mechanizmu" name="new_kolor_mechanizmu" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kolor_mechanizmu_image">Ikona koloru</label></th>
                        <td>
                            <input type="text" id="kolor_mechanizmu_image" name="kolor_mechanizmu_image" class="regular-text">
                            <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek">
                        </td>
                    </tr>
                    <!-- Dodajemy pole "Cząstka kodu" -->
                    <tr>
                        <th scope="row"><label for="kolor_mechanizmu_snippet">Cząstka kodu</label></th>
                        <td>
                            <input type="text" id="kolor_mechanizmu_snippet" name="kolor_mechanizmu_snippet" class="regular-text" placeholder="Opcjonalnie">
                        </td>
                    </tr>
                </table>
                <?php submit_button('Dodaj nowy kolor mechanizmu'); ?>
            </form>

            <h2>Lista dostępnych kolorów mechanizmu</h2>
            <?php if ( ! empty($color_options) ) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Ikona koloru</th>
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
                                <td><?php echo isset($color['snippet']) ? esc_html($color['snippet']) : ''; ?></td>
                                <td>
                                    <?php
                                   $edit_url = add_query_arg( array(
                                    'page'   => 'kv-kolor-mechanizmu', 
                                    'action' => 'edit',
                                    'id'     => $index,
                                ), admin_url('admin.php') );
                                
                                $delete_url = add_query_arg( array(
                                    'page'            => 'kv-kolor-mechanizmu', 
                                    'action'          => 'delete',
                                    'id'              => $index,
                                    'kv_kolor_mechanizmu_nonce' => wp_create_nonce('kv_delete_kolor_mechanizmu_' . $index)
                                ), admin_url('admin.php') );
                                
                                $duplicate_url = add_query_arg( array(
                                    'page'   => 'kv-kolor-mechanizmu', 
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
                <p>Brak dodanych kolorów mechanizmu.</p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($){
            // Uploader dla formularza dodawania nowego koloru mechanizmu
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
