<?php
defined('ABSPATH') or die('Brak dostępu');

// Ładujemy funkcję submit_button, jeśli nie jest załadowana
if ( ! function_exists('submit_button') ) {
    require_once( ABSPATH . 'wp-admin/includes/template.php' );
}

// Dołącz plik z funkcjami CRUD (kv_get_items, kv_add_item, kv_update_item, kv_delete_item)
require_once __DIR__ . '/crud.php';

/**
 * Funkcja wyświetlająca stronę administracyjną dla elementu "Kształt".
 */
if ( ! function_exists('kv_admin_ksztalt_page') ) {
    function kv_admin_ksztalt_page() {
        $option_key = 'kv_ksztalt_options';

        // Obsługa duplikacji
        if ( isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $new_id = kv_duplicate_item( $option_key, $id );
            if ( $new_id !== false ) {
                echo '<div class="notice notice-success is-dismissible"><p>Kształt został zduplikowany.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nie udało się zduplikować kształtu.</p></div>';
            }
        }

        // Obsługa usuwania elementu
        if ( isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            if ( isset($_GET['kv_ksztalt_nonce']) && wp_verify_nonce($_GET['kv_ksztalt_nonce'], 'kv_delete_ksztalt_' . $id) ) {
                kv_delete_item( $option_key, $id );
                echo '<div class="notice notice-success is-dismissible"><p>Kształt został usunięty.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Brak uprawnień do usunięcia kształtu.</p></div>';
            }
        }

        // Obsługa edycji
        if ( isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $items = kv_get_items( $option_key );
            if ( isset($items[$id]) ) {
                $current = $items[$id];
                // Jeśli formularz edycji został przesłany
                if ( isset($_POST['kv_edit_ksztalt_nonce']) && wp_verify_nonce($_POST['kv_edit_ksztalt_nonce'], 'kv_edit_ksztalt_' . $id) ) {
                    $edited_name  = sanitize_text_field( $_POST['edit_ksztalt'] );
                    $edited_image = esc_url_raw( $_POST['edit_ksztalt_image'] );
                    $edited_snippet = isset($_POST['edit_ksztalt_snippet']) ? sanitize_text_field($_POST['edit_ksztalt_snippet']) : '';
                    kv_update_item( $option_key, $id, array(
                        'name'    => $edited_name,
                        'image'   => $edited_image,
                        'snippet' => $edited_snippet,
                    ) );
                    echo '<div class="notice notice-success is-dismissible"><p>Kształt został zaktualizowany.</p></div>';
                    echo '<a href="' . esc_url( remove_query_arg( array('action','id','kv_ksztalt_nonce') ) ) . '" class="button">Powrót</a>';
                    return;
                }
                // Formularz edycji
                ?>
                <div class="wrap">
                    <h1>Edytuj kształt</h1>
                    <form method="post" action="">
                        <?php wp_nonce_field( 'kv_edit_ksztalt_' . $id, 'kv_edit_ksztalt_nonce' ); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit_ksztalt">Nazwa kształtu</label></th>
                                <td>
                                    <input type="text" id="edit_ksztalt" name="edit_ksztalt" class="regular-text" value="<?php echo esc_attr( $current['name'] ); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_ksztalt_image">Obrazek kształtu</label></th>
                                <td>
                                    <input type="text" id="edit_ksztalt_image" name="edit_ksztalt_image" class="regular-text" value="<?php echo esc_url( $current['image'] ); ?>" />
                                    <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_ksztalt_snippet">Cząstka kodu</label></th>
                                <td>
                                    <input type="text" id="edit_ksztalt_snippet" name="edit_ksztalt_snippet" class="regular-text" value="<?php echo isset($current['snippet']) ? esc_attr($current['snippet']) : ''; ?>" placeholder="Opcjonalnie" />
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
                                $('#edit_ksztalt_image').val(attachment.url);
                            }).open();
                        });
                    });
                    </script>
                </div>
                <?php
                return;
            }
        }

        // Obsługa dodawania nowego kształtu
        if ( isset($_POST['kv_ksztalt_nonce']) && wp_verify_nonce($_POST['kv_ksztalt_nonce'], 'kv_save_ksztalt') ) {
            $new_ksztalt   = sanitize_text_field( $_POST['new_ksztalt'] );
            $ksztalt_image = esc_url_raw( $_POST['ksztalt_image'] );
            $ksztalt_snippet = isset($_POST['ksztalt_snippet']) ? sanitize_text_field($_POST['ksztalt_snippet']) : '';
            if ( ! empty( $new_ksztalt ) ) {
                kv_add_item( $option_key, array(
                    'name'    => $new_ksztalt,
                    'image'   => $ksztalt_image,
                    'snippet' => $ksztalt_snippet,
                ) );
                echo '<div class="notice notice-success is-dismissible"><p>Nowy kształt został dodany.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nazwa kształtu jest wymagana.</p></div>';
            }
        }

        // Pobierz aktualne kształty
        $ksztalt_options = kv_get_items( $option_key );
        // Pobierz wszystkie układy, aby policzyć, ile jest przypisanych do danego kształtu
        $uklad_options = get_option('kv_uklad_options', array());
        ?>
        <div class="wrap">
            <h1>Konfigurator – Kształt</h1>
            <!-- Formularz dodawania nowego kształtu -->
            <form method="post" action="">
                <?php wp_nonce_field('kv_save_ksztalt', 'kv_ksztalt_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="new_ksztalt">Nazwa kształtu</label></th>
                        <td>
                            <input type="text" id="new_ksztalt" name="new_ksztalt" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ksztalt_image">Obrazek kształtu</label></th>
                        <td>
                            <input type="text" id="ksztalt_image" name="ksztalt_image" class="regular-text" />
                            <input type="button" class="button kv-upload-image-button" value="Wybierz obrazek" />
                        </td>
                    </tr>
                    <!-- Dodaj nowy wiersz: Cząstka kodu -->
                    <tr>
                        <th scope="row"><label for="ksztalt_snippet">Cząstka kodu</label></th>
                        <td>
                            <input type="text" id="ksztalt_snippet" name="ksztalt_snippet" class="regular-text" placeholder="Opcjonalnie" />
                        </td>
                    </tr>
                </table>
                <?php submit_button('Dodaj nowy kształt'); ?>
            </form>

            <h2>Aktualne kształty</h2>
            <?php if ( ! empty( $ksztalt_options ) ) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Nazwa kształtu</th>
                            <th>Obrazek</th>
                            <th>Cząstka kodu</th>
                            <th>Liczba przypisanych układów</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $ksztalt_options as $index => $ksztalt ) : ?>
                            <tr>
                                <td><?php echo esc_html( $ksztalt['name'] ); ?></td>
                                <td>
                                    <?php if ( ! empty( $ksztalt['image'] ) ) : ?>
                                        <img src="<?php echo esc_url( $ksztalt['image'] ); ?>" style="max-width:100px;height:auto;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($ksztalt['snippet']) ? esc_html($ksztalt['snippet']) : ''; ?></td>
                                <td>
                                    <?php
                                    $count_uklady = 0;
                                    foreach ( $uklad_options as $u_item ) {
                                        if ( isset($u_item['ksztalt_id']) && $u_item['ksztalt_id'] == $index ) {
                                            $count_uklady++;
                                        }
                                    }
                                    echo $count_uklady;
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $edit_url = add_query_arg(
                                        array(
                                            'page'   => 'kv-ksztalt',
                                            'action' => 'edit',
                                            'id'     => $index,
                                        ),
                                        admin_url('admin.php')
                                    );
                                    $delete_url = add_query_arg(
                                        array(
                                            'page'            => 'kv-ksztalt',
                                            'action'          => 'delete',
                                            'id'              => $index,
                                            'kv_ksztalt_nonce' => wp_create_nonce( 'kv_delete_ksztalt_' . $index ),
                                        ),
                                        admin_url('admin.php')
                                    );
                                    // Dodaj link do duplikowania
                                    $duplicate_url = add_query_arg(
                                        array(
                                            'page'   => 'kv-ksztalt',
                                            'action' => 'duplicate',
                                            'id'     => $index,
                                        ),
                                        admin_url('admin.php')
                                    );
                                    ?>
                                    <a href="<?php echo esc_url( $edit_url ); ?>">Edytuj</a> | 
                                    <a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Czy na pewno chcesz usunąć ten kształt?');">Usuń</a> | 
                                    <a href="<?php echo esc_url( $duplicate_url ); ?>">Duplikuj</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>Brak dodanych kształtów.</p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($){
            // Uploader dla formularza dodawania nowego kształtu
            $('.kv-upload-image-button').on('click', function(e) {
                e.preventDefault();
                var custom_uploader = wp.media({
                    title: 'Wybierz obrazek',
                    library: { type: 'image' },
                    button: { text: 'Wybierz obrazek' },
                    multiple: false
                }).on('select', function(){
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    $('#ksztalt_image').val(attachment.url);
                }).open();
            });
        });
        </script>
        <?php
    }
}
