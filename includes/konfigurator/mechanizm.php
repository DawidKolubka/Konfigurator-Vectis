<?php
defined('ABSPATH') or die('Brak dostępu');

// Ładujemy funkcję submit_button, jeśli nie jest załadowana
if ( ! function_exists('submit_button') ) {
    require_once( ABSPATH . 'wp-admin/includes/template.php' );
}

// Dołącz plik z funkcjami CRUD (kv_get_items, kv_add_item, kv_update_item, kv_delete_item)
require_once __DIR__ . '/crud.php';

/**
 * Funkcja wyświetlająca panel administracyjny dla modułu "Mechanizm".
 * Każdy mechanizm ma: nazwę, ikonę grupy, ikonę grupy do ramki, oraz cząstkę kodu.
 */
if ( ! function_exists('kv_admin_mechanizm_page') ) {
    function kv_admin_mechanizm_page() {
        $option_key = 'kv_mechanizm_options';

        // --- Obsługa duplikacji ---
        if ( isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $new_id = kv_duplicate_item( $option_key, $id );
            if ( $new_id !== false ) {
                echo '<div class="notice notice-success is-dismissible"><p>Mechanizm został zduplikowany.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nie udało się zduplikować mechanizmu.</p></div>';
            }
        }

        // --- Obsługa usuwania elementu ---
        if ( isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            if ( isset($_GET['kv_mechanizm_nonce']) && wp_verify_nonce($_GET['kv_mechanizm_nonce'], 'kv_delete_mechanizm_' . $id) ) {
                kv_delete_item( $option_key, $id );
                echo '<div class="notice notice-success is-dismissible"><p>Mechanizm został usunięty.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Brak uprawnień do usunięcia mechanizmu.</p></div>';
            }
        }

        // --- Obsługa edycji ---
        if ( isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $items = kv_get_items( $option_key );
            if ( isset($items[$id]) ) {
                $current = $items[$id];
                if ( isset($_POST['kv_edit_mechanizm_nonce']) && wp_verify_nonce($_POST['kv_edit_mechanizm_nonce'], 'kv_edit_mechanizm_' . $id) ) {
                    $edited_name        = sanitize_text_field( $_POST['edit_mechanizm'] );
                    $edited_image       = esc_url_raw( $_POST['edit_mechanizm_image'] );
                    $edited_frame_image = esc_url_raw( $_POST['edit_mechanizm_frame_image'] );
                    // Jeśli pole nie jest wypełnione, ustaw pusty string:
                    $edited_snippet     = isset($_POST['edit_mechanizm_snippet']) ? sanitize_text_field($_POST['edit_mechanizm_snippet']) : '';
                    
                    kv_update_item( $option_key, $id, array(
                        'name'        => $edited_name,
                        'image'       => $edited_image,
                        'frame_image' => $edited_frame_image,
                        'snippet'     => $edited_snippet,
                    ) );

                    echo '<div class="notice notice-success is-dismissible"><p>Mechanizm został zaktualizowany.</p></div>';
                    echo '<a href="' . esc_url( remove_query_arg( array('action','id','kv_mechanizm_nonce') ) ) . '" class="button">Powrót</a>';
                    return;
                }
                ?>
                <div class="wrap">
                    <h1>Edytuj Mechanizm</h1>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'kv_edit_mechanizm_' . $id, 'kv_edit_mechanizm_nonce' ); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit_mechanizm">Nazwa mechanizmu</label></th>
                                <td>
                                    <input type="text" id="edit_mechanizm" name="edit_mechanizm" class="regular-text" value="<?php echo esc_attr($current['name']); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_mechanizm_image">Ikona grupy</label></th>
                                <td>
                                    <input type="text" id="edit_mechanizm_image" name="edit_mechanizm_image" class="regular-text" value="<?php echo esc_url($current['image']); ?>">
                                    <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_mechanizm_frame_image">Ikona grupy do ramki</label></th>
                                <td>
                                    <input type="text" id="edit_mechanizm_frame_image" name="edit_mechanizm_frame_image" class="regular-text" value="<?php echo isset($current['frame_image']) ? esc_url($current['frame_image']) : ''; ?>">
                                    <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_mechanizm_snippet">Cząstka kodu</label></th>
                                <td>
                                    <input type="text" id="edit_mechanizm_snippet" name="edit_mechanizm_snippet" class="regular-text" 
                                           value="<?php echo isset($current['snippet']) ? esc_attr($current['snippet']) : ''; ?>" placeholder="Opcjonalnie">
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Zapisz zmiany'); ?>
                    </form>
                    <script>
                    jQuery(document).ready(function($){
                        $('.kv-upload-image-button').on('click', function(e){
                            e.preventDefault();
                            var custom_uploader = wp.media({
                                title: 'Wybierz obrazek',
                                library: { type: 'image' },
                                button: { text: 'Wybierz obrazek' },
                                multiple: false
                            }).on('select', function(){
                                var attachment = custom_uploader.state().get('selection').first().toJSON();
                                // Dla pola ikony grupy lub do ramki - sprawdzamy, który input aktualnie jest docelowy
                                if ($(e.target).siblings('#edit_mechanizm_image').length) {
                                    $('#edit_mechanizm_image').val(attachment.url);
                                } else if ($(e.target).siblings('#edit_mechanizm_frame_image').length) {
                                    $('#edit_mechanizm_frame_image').val(attachment.url);
                                }
                            }).open();
                        });
                    });
                    </script>
                </div>
                <?php
                return;
            }
        }

        // --- Obsługa dodawania nowego mechanizmu ---
        if ( isset($_POST['kv_mechanizm_nonce']) && wp_verify_nonce($_POST['kv_mechanizm_nonce'], 'kv_save_mechanizm') ) {
            $new_mechanizm       = sanitize_text_field( $_POST['new_mechanizm'] );
            $mechanizm_image     = esc_url_raw( $_POST['mechanizm_image'] );
            $mechanizm_frame_image = isset($_POST['mechanizm_frame_image']) ? esc_url_raw($_POST['mechanizm_frame_image']) : '';

            // Tutaj odczytujemy pole 'mechanizm_snippet', jeśli zostało przesłane:
            $mechanizm_snippet = isset($_POST['mechanizm_snippet'])
                ? sanitize_text_field($_POST['mechanizm_snippet'])
                : '';

            if ( ! empty($new_mechanizm) ) {
                kv_add_item( $option_key, array(
                    'name'        => $new_mechanizm,
                    'image'       => $mechanizm_image,
                    'frame_image' => $mechanizm_frame_image,
                    'snippet'     => $mechanizm_snippet,  // Zapisujemy snippet do bazy
                ) );
                echo '<div class="notice notice-success is-dismissible"><p>Nowy mechanizm został dodany.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nazwa mechanizmu jest wymagana.</p></div>';
            }
        }

        // Pobierz aktualne mechanizmy
        $mechanizm_options = kv_get_items( $option_key );
        ?>
        <div class="wrap">
            <h1>Konfigurator – Mechanizm</h1>
            <!-- Formularz dodawania nowego mechanizmu -->
            <form method="post" action="">
                <?php wp_nonce_field('kv_save_mechanizm', 'kv_mechanizm_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="new_mechanizm">Nazwa mechanizmu</label></th>
                        <td>
                            <input type="text" id="new_mechanizm" name="new_mechanizm" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mechanizm_image">Ikonka grupy</label></th>
                        <td>
                            <input type="text" id="mechanizm_image" name="mechanizm_image" class="regular-text">
                            <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mechanizm_frame_image">Ikonka grupy do ramki</label></th>
                        <td>
                            <input type="text" id="mechanizm_frame_image" name="mechanizm_frame_image" class="regular-text">
                            <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek">
                        </td>
                    </tr>
                    <!-- Dodajemy pole "Cząstka kodu" -->
                    <tr>
                        <th scope="row"><label for="mechanizm_snippet">Cząstka kodu</label></th>
                        <td>
                            <input type="text" id="mechanizm_snippet" name="mechanizm_snippet" class="regular-text" placeholder="Opcjonalnie">
                        </td>
                    </tr>
                </table>
                <?php submit_button('Dodaj nowy mechanizm'); ?>
            </form>

            <h2>Lista dostępnych mechanizmów</h2>
            <?php if ( ! empty($mechanizm_options) ) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Nazwa grupy</th>
                            <th>Ikonka grupy</th>
                            <th>Ikonka do ramki</th>
                            <th>Cząstka kodu</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $mechanizm_options as $index => $mechanizm ) : ?>
                            <tr>
                                <td><?php echo esc_html( $mechanizm['name'] ); ?></td>
                                <td>
                                    <?php if ( ! empty($mechanizm['image']) ) : ?>
                                        <img src="<?php echo esc_url($mechanizm['image']); ?>" style="max-width:80px;height:auto;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( ! empty($mechanizm['frame_image']) ) : ?>
                                        <img src="<?php echo esc_url($mechanizm['frame_image']); ?>" style="max-width:80px;height:auto;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($mechanizm['snippet']) ? esc_html($mechanizm['snippet']) : ''; ?></td>
                                <td>
                                    <?php
                                    $edit_url = add_query_arg( array(
                                        'page'   => 'kv-mechanizm',
                                        'action' => 'edit',
                                        'id'     => $index,
                                    ), admin_url('admin.php') );
                                    $delete_url = add_query_arg( array(
                                        'page'                  => 'kv-mechanizm',
                                        'action'                => 'delete',
                                        'id'                    => $index,
                                        'kv_mechanizm_nonce'    => wp_create_nonce('kv_delete_mechanizm_' . $index)
                                    ), admin_url('admin.php') );
                                    // Link do duplikowania
                                    $duplicate_url = add_query_arg( array(
                                        'page'   => 'kv-mechanizm',
                                        'action' => 'duplicate',
                                        'id'     => $index,
                                    ), admin_url('admin.php') );
                                    ?>
                                    <a href="<?php echo esc_url($edit_url); ?>">Edytuj</a> |
                                    <a href="<?php echo esc_url($delete_url); ?>" 
                                       onclick="return confirm('Czy na pewno usunąć ten mechanizm?');">Usuń</a> |
                                    <a href="<?php echo esc_url($duplicate_url); ?>">Duplikuj</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Brak dodanych mechanizmów.</p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($){
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
