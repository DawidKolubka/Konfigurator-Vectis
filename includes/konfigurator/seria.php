<?php
defined('ABSPATH') or die('Brak dostępu');

// Ładujemy funkcję submit_button, jeśli nie jest załadowana
if ( ! function_exists('submit_button') ) {
    require_once( ABSPATH . 'wp-admin/includes/template.php' );
}

// Dołącz plik z funkcjami CRUD, jeśli nie jest jeszcze załadowany
require_once __DIR__ . '/crud.php';

/**
 * Funkcja wyświetlająca stronę administracyjną dla elementu "Seria".
 */
if ( ! function_exists('kv_admin_seria_page') ) {
    function kv_admin_seria_page() {
        $option_key = 'kv_seria_options';

        // --- Obsługa duplikacji ---
        if ( isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $new_id = kv_duplicate_item( $option_key, $id );
            if ( $new_id !== false ) {
                echo '<div class="notice notice-success is-dismissible"><p>Seria została zduplikowana.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nie udało się zduplikować serii.</p></div>';
            }
        }

        // Obsługa usuwania elementu
        if ( isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            if ( isset($_GET['kv_seria_nonce']) && wp_verify_nonce($_GET['kv_seria_nonce'], 'kv_delete_seria_' . $id) ) {
                kv_delete_item( $option_key, $id );
                echo '<div class="notice notice-success is-dismissible"><p>Seria została usunięta.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Brak uprawnień do usunięcia serii.</p></div>';
            }
        }

        // Obsługa edycji
        if ( isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $items = kv_get_items( $option_key );
            if ( isset($items[$id]) ) {
                $current = $items[$id];
                // Jeśli formularz edycji został przesłany
                if ( isset($_POST['kv_edit_seria_nonce']) && wp_verify_nonce($_POST['kv_edit_seria_nonce'], 'kv_edit_seria_' . $id) ) {
                    $edited_name     = sanitize_text_field( $_POST['edit_seria'] );
                    $edited_image    = esc_url_raw( $_POST['edit_seria_image'] );
                    $edited_fragment = sanitize_text_field($_POST['edit_seria_fragment']);
                    kv_update_item( $option_key, $id, array(
                        'name'     => $edited_name,
                        'image'    => $edited_image,
                        'fragment' => $edited_fragment,
                    ) );
                    echo '<div class="notice notice-success is-dismissible"><p>Seria została zaktualizowana.</p></div>';
                    echo '<a href="' . esc_url( remove_query_arg( array('action','id','kv_seria_nonce') ) ) . '" class="button">Powrót</a>';
                    return;
                }
                // Formularz edycji
                ?>
                <div class="wrap">
                    <h1>Edytuj serię</h1>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'kv_edit_seria_' . $id, 'kv_edit_seria_nonce' ); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit_seria">Nazwa serii</label></th>
                                <td>
                                    <input type="text" id="edit_seria" name="edit_seria" class="regular-text" value="<?php echo esc_attr( $current['name'] ); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_seria_image">Obrazek serii</label></th>
                                <td>
                                    <input type="text" id="edit_seria_image" name="edit_seria_image" class="regular-text" value="<?php echo esc_url( $current['image'] ); ?>" />
                                    <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_seria_fragment">Cząstka kodu</label></th>
                                <td>
                                    <input type="text" id="edit_seria_fragment" name="edit_seria_fragment" class="regular-text" value="<?php echo isset($current['fragment']) ? esc_attr($current['fragment']) : ''; ?>" />
                                    <p class="description">Opcjonalnie, wpisz cząstkę kodu.</p>
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
                                $('#edit_seria_image').val(attachment.url);
                            }).open();
                        });
                    });
                    </script>
                </div>
                <?php
                return;
            }
        }

        // Obsługa dodawania nowej serii
        if ( isset($_POST['kv_seria_nonce']) && wp_verify_nonce($_POST['kv_seria_nonce'], 'kv_save_seria') ) {
            $new_seria     = sanitize_text_field( $_POST['new_seria'] );
            $seria_image   = esc_url_raw( $_POST['seria_image'] );
            $seria_fragment = sanitize_text_field( $_POST['seria_fragment'] );
            if ( ! empty( $new_seria ) ) {
                kv_add_item( $option_key, array(
                    'name'     => $new_seria,
                    'image'    => $seria_image,
                    'fragment' => $seria_fragment,
                ) );
                echo '<div class="notice notice-success is-dismissible"><p>Nowa seria została dodana.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nazwa serii jest wymagana.</p></div>';
            }
        }

        // Pobierz aktualne elementy "seria"
        $seria_options = kv_get_items( $option_key );
        ?>
        <div class="wrap">
            <h1>Konfigurator – Konfiguracja serii</h1>
            <!-- Formularz dodawania nowej serii -->
            <form method="post" action="">
                <?php wp_nonce_field('kv_save_seria', 'kv_seria_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="new_seria">Nazwa serii</label></th>
                        <td>
                            <input type="text" id="new_seria" name="new_seria" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="seria_image">Obrazek serii</label></th>
                        <td>
                            <input type="text" id="seria_image" name="seria_image" class="regular-text" />
                            <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="seria_fragment">Cząstka kodu</label></th>
                        <td>
                            <input type="text" id="seria_fragment" name="seria_fragment" class="regular-text" />
                            <p class="description">Opcjonalnie, wpisz cząstkę kodu.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Dodaj nową serię'); ?>
            </form>
            <h2>Aktualne serie</h2>
            <?php if ( ! empty( $seria_options ) ) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Nazwa serii</th>
                            <th>Obrazek</th>
                            <th>Cząstka kodu</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $seria_options as $index => $seria ) : ?>
                            <tr>
                                <td><?php echo esc_html( $seria['name'] ); ?></td>
                                <td>
                                    <?php if ( ! empty( $seria['image'] ) ) : ?>
                                        <img src="<?php echo esc_url( $seria['image'] ); ?>" style="max-width:100px;height:auto;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($seria['fragment']) ? esc_html($seria['fragment']) : ''; ?></td>
                                <td>
                                    <?php
                                    $edit_url = add_query_arg(
                                        array(
                                            'page'   => 'kv-seria',
                                            'action' => 'edit',
                                            'id'     => $index,
                                        ),
                                        admin_url('admin.php')
                                    );
                                    $delete_url = add_query_arg(
                                        array(
                                            'page'           => 'kv-seria',
                                            'action'         => 'delete',
                                            'id'             => $index,
                                            'kv_seria_nonce' => wp_create_nonce('kv_delete_seria_' . $index),
                                        ),
                                        admin_url('admin.php')
                                    );
                                    $duplicate_url = add_query_arg(
                                        array(
                                            'page'   => 'kv-seria',
                                            'action' => 'duplicate',
                                            'id'     => $index,
                                        ),
                                        admin_url('admin.php')
                                    );
                                    ?>
                                    <a href="<?php echo esc_url( $edit_url ); ?>">Edytuj</a> | 
                                    <a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Czy na pewno chcesz usunąć tę serię?');">Usuń</a> | 
                                    <a href="<?php echo esc_url( $duplicate_url ); ?>">Duplikuj</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>Brak dodanych serii.</p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($){
            // Uploader dla formularza dodawania nowej serii
            $('.kv-upload-image-button').on('click', function(e){
                e.preventDefault();
                var custom_uploader = wp.media({
                    title: 'Wybierz obrazek',
                    library: { type: 'image' },
                    button: { text: 'Wybierz obrazek' },
                    multiple: false
                }).on('select', function(){
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    $('#seria_image').val(attachment.url);
                }).open();
            });
        });
        </script>
        <?php
    }
}
