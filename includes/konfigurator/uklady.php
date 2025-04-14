<?php
defined('ABSPATH') or die('Brak dostępu');

// Upewniamy się, że funkcja submit_button jest dostępna
if ( ! function_exists('submit_button') ) {
    require_once ABSPATH . 'wp-admin/includes/template.php';
}

// Dołącz plik z funkcjami CRUD
require_once __DIR__ . '/crud.php';

/**
 * Funkcja wyświetlająca panel administracyjny "Układy".
 */
if ( ! function_exists('kv_admin_uklady_page') ) {
    function kv_admin_uklady_page() {
        $option_key = 'kv_uklad_options';

        // Pobieramy listę kształtów, żeby wyświetlić w dropdown
        $ksztalt_options = get_option('kv_ksztalt_options', array());

        // --- Obsługa duplikacji (action=duplicate) ---
        if ( isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $new_id = kv_duplicate_item( $option_key, $id );
            if ( $new_id !== false ) {
                echo '<div class="notice notice-success is-dismissible"><p>Układ został zduplikowany.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nie udało się zduplikować układu.</p></div>';
            }
        }

        // --- Obsługa usuwania (action=delete) ---
        if ( isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            if ( isset($_GET['kv_uklad_nonce']) && wp_verify_nonce($_GET['kv_uklad_nonce'], 'kv_delete_uklad_' . $id) ) {
                kv_delete_item( $option_key, $id );
                echo '<div class="notice notice-success is-dismissible"><p>Układ został usunięty.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Brak uprawnień do usunięcia układu.</p></div>';
            }
        }

        // --- Obsługa edycji (action=edit) ---
        if ( isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $items = kv_get_items( $option_key );
            if ( isset($items[$id]) ) {
                $current = $items[$id];
                // Jeśli formularz edycji został przesłany
                if ( isset($_POST['kv_edit_uklad_nonce']) && wp_verify_nonce($_POST['kv_edit_uklad_nonce'], 'kv_edit_uklad_' . $id) ) {
                    $edited_name    = sanitize_text_field( $_POST['edit_uklad'] );
                    $edited_image   = esc_url_raw( $_POST['edit_uklad_image'] );
                    $edited_ksztalt = absint( $_POST['edit_ksztalt_id'] );
                    $edited_snippet = isset($_POST['edit_uklad_snippet']) ? sanitize_text_field($_POST['edit_uklad_snippet']) : '';
                    kv_update_item( $option_key, $id, array(
                        'name'       => $edited_name,
                        'image'      => $edited_image,
                        'ksztalt_id' => $edited_ksztalt,
                        'snippet'    => $edited_snippet,
                    ) );
                    echo '<div class="notice notice-success is-dismissible"><p>Układ został zaktualizowany.</p></div>';
                    echo '<a href="' . esc_url( remove_query_arg( array('action','id','kv_uklad_nonce') ) ) . '" class="button">Powrót</a>';
                    return;
                }
                // Formularz edycji
                ?>
                <div class="wrap">
                    <h1>Edytuj układ</h1>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'kv_edit_uklad_' . $id, 'kv_edit_uklad_nonce' ); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit_uklad">Nazwa układu</label></th>
                                <td>
                                    <input type="text" id="edit_uklad" name="edit_uklad" class="regular-text" value="<?php echo esc_attr( $current['name'] ); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_uklad_image">Obrazek układu</label></th>
                                <td>
                                    <input type="text" id="edit_uklad_image" name="edit_uklad_image" class="regular-text" value="<?php echo esc_url( $current['image'] ); ?>" />
                                    <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek" />
                                </td>
                            </tr>
                            <!-- Nowa linia: Cząstka kodu -->
                            <tr>
                                <th scope="row"><label for="edit_uklad_snippet">Cząstka kodu</label></th>
                                <td>
                                    <input type="text" id="edit_uklad_snippet" name="edit_uklad_snippet" class="regular-text" value="<?php echo isset($current['snippet']) ? esc_attr($current['snippet']) : ''; ?>" placeholder="Opcjonalnie" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_ksztalt_id">Powiązany kształt</label></th>
                                <td>
                                    <select name="edit_ksztalt_id" id="edit_ksztalt_id">
                                        <option value="0">Wybierz kształt</option>
                                        <?php foreach ( $ksztalt_options as $k_index => $k_item ) : ?>
                                            <option value="<?php echo $k_index; ?>" <?php selected( $current['ksztalt_id'], $k_index ); ?>>
                                                <?php echo esc_html( $k_item['name'] ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
                                $('#edit_uklad_image').val(attachment.url);
                            }).open();
                        });
                    });
                    </script>
                </div>
                <?php
                return;
            }
        }

        // --- Obsługa dodawania nowego układu ---
        if ( isset($_POST['kv_uklad_nonce']) && wp_verify_nonce($_POST['kv_uklad_nonce'], 'kv_save_uklad') ) {
            $new_uklad   = sanitize_text_field( $_POST['new_uklad'] );
            $uklad_image = esc_url_raw( $_POST['uklad_image'] );
            $ksztalt_id  = absint( $_POST['ksztalt_id'] );
            $uklad_snippet = isset($_POST['uklad_snippet']) ? sanitize_text_field($_POST['uklad_snippet']) : '';
            if ( ! empty( $new_uklad ) ) {
                kv_add_item( $option_key, array(
                    'name'       => $new_uklad,
                    'image'      => $uklad_image,
                    'ksztalt_id' => $ksztalt_id,
                    'snippet'    => $uklad_snippet,
                ) );
                echo '<div class="notice notice-success is-dismissible"><p>Nowy układ został dodany.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nazwa układu jest wymagana.</p></div>';
            }
        }

        // Pobierz wszystkie układy
        $uklad_options = kv_get_items( $option_key );
        ?>
        <div class="wrap">
            <h1>Konfigurator – Układy</h1>
            <!-- Formularz dodawania nowego układu -->
            <form method="post" action="">
                <?php wp_nonce_field('kv_save_uklad', 'kv_uklad_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="new_uklad">Nazwa układu</label></th>
                        <td>
                            <input type="text" id="new_uklad" name="new_uklad" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="uklad_image">Obrazek układu</label></th>
                        <td>
                            <input type="text" id="uklad_image" name="uklad_image" class="regular-text" />
                            <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ksztalt_id">Powiązany kształt</label></th>
                        <td>
                            <select name="ksztalt_id" id="ksztalt_id">
                                <option value="0">Wybierz kształt</option>
                                <?php foreach ( $ksztalt_options as $k_index => $k_item ) : ?>
                                    <option value="<?php echo $k_index; ?>">
                                        <?php echo esc_html( $k_item['name'] ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <!-- Dodajemy pole "Cząstka kodu" -->
                    <tr>
                        <th scope="row"><label for="uklad_snippet">Cząstka kodu</label></th>
                        <td>
                            <input type="text" id="uklad_snippet" name="uklad_snippet" class="regular-text" placeholder="Opcjonalnie" />
                        </td>
                    </tr>
                </table>
                <?php submit_button('Dodaj nowy układ'); ?>
            </form>

            <h2>Lista dostępnych układów</h2>
            <?php if ( ! empty($uklad_options) ) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Kształt</th>
                            <th>Obrazek</th>
                            <th>Cząstka kodu</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $uklad_options as $index => $uklad ) : ?>
                            <tr>
                                <td><?php echo esc_html( $uklad['name'] ); ?></td>
                                <td>
                                    <?php
                                    if ( isset($uklad['ksztalt_id'], $ksztalt_options[$uklad['ksztalt_id']]) ) {
                                        echo esc_html($ksztalt_options[$uklad['ksztalt_id']]['name']);
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ( !empty($uklad['image']) ) : ?>
                                        <img src="<?php echo esc_url($uklad['image']); ?>" style="max-width: 80px; height:auto;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($uklad['snippet']) ? esc_html($uklad['snippet']) : ''; ?></td>
                                <td>
                                    <?php
                                    $edit_url = add_query_arg( array(
                                        'page'   => 'kv-uklady',
                                        'action' => 'edit',
                                        'id'     => $index,
                                    ), admin_url('admin.php') );
                                    $delete_url = add_query_arg( array(
                                        'page' => 'kv-uklady',
                                        'action' => 'delete',
                                        'id' => $index,
                                        'kv_uklad_nonce' => wp_create_nonce( 'kv_delete_uklad_' . $index ),
                                    ), admin_url('admin.php') );
                                    // Link do duplikowania
                                    $duplicate_url = add_query_arg( array(
                                        'page'   => 'kv-uklady',
                                        'action' => 'duplicate',
                                        'id'     => $index,
                                    ), admin_url('admin.php') );
                                    ?>
                                    <a href="<?php echo esc_url($edit_url); ?>">Edytuj</a> |
                                    <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Na pewno usunąć ten układ?');">Usuń</a> |
                                    <a href="<?php echo esc_url($duplicate_url); ?>">Duplikuj</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>Brak zdefiniowanych układów.</p>
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
                    $('#uklad_image').val(attachment.url);
                }).open();
            });
        });
        </script>
        <?php
    }
}
