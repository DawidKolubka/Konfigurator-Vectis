<?php
defined('ABSPATH') or die('Brak dostępu');

// Upewnij się, że funkcja submit_button jest dostępna
if ( ! function_exists('submit_button') ) {
    require_once ABSPATH . 'wp-admin/includes/template.php';
}

// Dołącz plik z funkcjami CRUD
require_once __DIR__ . '/crud.php';

/**
 * Funkcja wyświetlająca panel administracyjny dla modułu "Technologia".
 * Formularz zawiera pola:
 * - Grupa mechanizmów (wybór z opcji "kv_mechanizm_options")
 * - Technologia mechanizmu (tekst)
 * - Kolor mechanizmu (wybór z opcji "kv_kolor_mechanizmu_options")
 * - Kod mechanizmu (tekst)
 * - Cena mechanizmu (tekst)
 */
if ( ! function_exists('kv_admin_technologia_page') ) {
    function kv_admin_technologia_page() {
        $option_key = 'kv_technologia_options';

        // --- Obsługa duplikacji ---
        if ( isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $new_id = kv_duplicate_item($option_key, $id);
            if ($new_id !== false) {
                echo '<div class="notice notice-success is-dismissible"><p>Nowa technologia została zduplikowana.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nie udało się zduplikować technologii.</p></div>';
            }
        }

        // --- Obsługa usuwania ---
        if ( isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            if ( isset($_GET['kv_technologia_nonce']) && wp_verify_nonce($_GET['kv_technologia_nonce'], 'kv_delete_technologia_' . $id) ) {
                kv_delete_item($option_key, $id);
                echo '<div class="notice notice-success is-dismissible"><p>Technologia została usunięta.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Brak uprawnień do usunięcia technologii.</p></div>';
            }
        }

        // --- Obsługa edycji ---
        if ( isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $items = kv_get_items($option_key);
            if ( isset($items[$id]) ) {
                $current = $items[$id];
                if ( isset($_POST['kv_edit_technologia_nonce']) && wp_verify_nonce($_POST['kv_edit_technologia_nonce'], 'kv_edit_technologia_' . $id) ) {
                    $edited_group = isset($_POST['edit_technologia_group']) ? intval($_POST['edit_technologia_group']) : 0;
                    $edited_technology = sanitize_text_field($_POST['edit_technologia']);
                    $edited_color = isset($_POST['edit_technologia_color']) ? intval($_POST['edit_technologia_color']) : 0;
                    $edited_code = sanitize_text_field($_POST['edit_technologia_code']);
                    $edited_price = sanitize_text_field($_POST['edit_technologia_price']);
                    kv_update_item($option_key, $id, array(
                        'group'      => $edited_group,
                        'technology' => $edited_technology,
                        'color'      => $edited_color,
                        'code'       => $edited_code,
                        'price'      => $edited_price
                    ));
                    echo '<div class="notice notice-success is-dismissible"><p>Technologia została zaktualizowana.</p></div>';
                    echo '<a href="' . esc_url(remove_query_arg(array('action','id','kv_technologia_nonce'))) . '" class="button">Powrót</a>';
                    return;
                }
                ?>
                <div class="wrap">
                    <h1>Edytuj Technologię</h1>
                    <form method="post" action="">
                        <?php wp_nonce_field('kv_edit_technologia_' . $id, 'kv_edit_technologia_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="edit_technologia_group">Grupa mechanizmów</label></th>
                                <td>
                                    <select id="edit_technologia_group" name="edit_technologia_group">
                                        <option value="0">Wybierz grupę mechanizmów</option>
                                        <?php
                                        $mechanizm_options = kv_get_items('kv_mechanizm_options');
                                        if (!empty($mechanizm_options)) {
                                            foreach ($mechanizm_options as $m_index => $mechanizm) {
                                                ?>
                                                <option value="<?php echo esc_attr($m_index); ?>" <?php selected(isset($current['group']) ? $current['group'] : 0, $m_index); ?>>
                                                    <?php echo esc_html($mechanizm['name']); ?>
                                                </option>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_technologia">Technologia mechanizmu</label></th>
                                <td>
                                    <input type="text" id="edit_technologia" name="edit_technologia" class="regular-text" value="<?php echo esc_attr($current['technology'] ?? ''); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_technologia_color">Kolor mechanizmu</label></th>
                                <td>
                                    <select id="edit_technologia_color" name="edit_technologia_color">
                                        <option value="0">Wybierz kolor mechanizmu</option>
                                        <?php
                                        $color_options = kv_get_items('kv_kolor_mechanizmu_options');
                                        if (!empty($color_options)) {
                                            foreach ($color_options as $c_index => $color) {
                                                ?>
                                                <option value="<?php echo esc_attr($c_index); ?>" <?php selected(isset($current['color']) ? $current['color'] : 0, $c_index); ?>>
                                                    <?php echo esc_html($color['name']); ?>
                                                </option>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_technologia_code">Kod mechanizmu</label></th>
                                <td>
                                    <input type="text" id="edit_technologia_code" name="edit_technologia_code" class="regular-text" value="<?php echo esc_attr($current['code'] ?? ''); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="edit_technologia_price">Cena mechanizmu</label></th>
                                <td>
                                    <input type="text" id="edit_technologia_price" name="edit_technologia_price" class="regular-text" value="<?php echo esc_attr($current['price'] ?? ''); ?>">
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Zapisz zmiany'); ?>
                    </form>
                </div>
                <?php
                return;
            }
        }

        // Obsługa dodawania nowej technologii
        if ( isset($_POST['kv_technologia_nonce']) && wp_verify_nonce($_POST['kv_technologia_nonce'], 'kv_save_technologia') ) {
            $new_group = isset($_POST['technologia_group']) ? intval($_POST['technologia_group']) : 0;
            $new_technology = sanitize_text_field($_POST['new_technologia']);
            $new_color = isset($_POST['technologia_color']) ? intval($_POST['technologia_color']) : 0;
            $new_code = sanitize_text_field($_POST['technologia_code']);
            $new_price = sanitize_text_field($_POST['technologia_price']);
            if ( ! empty($new_technology) ) {
                kv_add_item($option_key, array(
                    'group'      => $new_group,
                    'technology' => $new_technology,
                    'color'      => $new_color,
                    'code'       => $new_code,
                    'price'      => $new_price
                ));
                echo '<div class="notice notice-success is-dismissible"><p>Nowa technologia została dodana.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Nazwa technologii jest wymagana.</p></div>';
            }
        }

        // Pobierz aktualne technologie
        $tech_options = kv_get_items($option_key);
        ?>
        <div class="wrap">
            <h1>Konfigurator – Technologia</h1>
            <!-- Formularz dodawania nowej technologii -->
            <form method="post" action="">
                <?php wp_nonce_field('kv_save_technologia', 'kv_technologia_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="technologia_group">Grupa mechanizmów</label></th>
                        <td>
                            <select id="technologia_group" name="technologia_group">
                                <option value="0">Wybierz grupę mechanizmów</option>
                                <?php
                                $mechanizm_options = kv_get_items('kv_mechanizm_options');
                                if (!empty($mechanizm_options)) {
                                    foreach ($mechanizm_options as $m_index => $mechanizm) {
                                        ?>
                                        <option value="<?php echo esc_attr($m_index); ?>"><?php echo esc_html($mechanizm['name']); ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="new_technologia">Technologia mechanizmu</label></th>
                        <td>
                            <input type="text" id="new_technologia" name="new_technologia" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="technologia_color">Kolor mechanizmu</label></th>
                        <td>
                            <select id="technologia_color" name="technologia_color">
                                <option value="0">Wybierz kolor mechanizmu</option>
                                <?php
                                $color_options = kv_get_items('kv_kolor_mechanizmu_options');
                                if (!empty($color_options)) {
                                    foreach ($color_options as $c_index => $color) {
                                        ?>
                                        <option value="<?php echo esc_attr($c_index); ?>"><?php echo esc_html($color['name']); ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="technologia_code">Kod mechanizmu</label></th>
                        <td>
                            <input type="text" id="technologia_code" name="technologia_code" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="technologia_price">Cena mechanizmu</label></th>
                        <td>
                            <input type="text" id="technologia_price" name="technologia_price" class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button('Dodaj nową technologię'); ?>
            </form>

            <h2>Lista dostępnych technologii</h2>
            <?php if ( ! empty($tech_options) ) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Grupa mechanizmów</th>
                            <th>Technologia</th>
                            <th>Kolor mechanizmu</th>
                            <th>Kod mechanizmu</th>
                            <th>Cena mechanizmu</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tech_options as $index => $tech) : ?>
                            <tr>
                                <td>
                                    <?php
                                    $group = $tech['group'] ?? 0;
                                    $mechanizm_options = kv_get_items('kv_mechanizm_options');
                                    echo isset($mechanizm_options[$group]) ? esc_html($mechanizm_options[$group]['name']) : '—';
                                    ?>
                                </td>
                                <td><?php echo esc_html($tech['technology'] ?? ''); ?></td>
                                <td>
                                    <?php
                                    $color = $tech['color'] ?? 0;
                                    $color_options = kv_get_items('kv_kolor_mechanizmu_options');
                                    echo isset($color_options[$color]) ? esc_html($color_options[$color]['name']) : '—';
                                    ?>
                                </td>
                                <td><?php echo esc_html($tech['code'] ?? ''); ?></td>
                                <td><?php echo esc_html($tech['price'] ?? ''); ?></td>
                                <td>
                                    <?php
                                    $edit_url = add_query_arg( array(
                                        'page'   => 'kv-technologia',
                                        'action' => 'edit',
                                        'id'     => $index,
                                    ), admin_url('admin.php') );
                                    $delete_url = add_query_arg( array(
                                        'page'                => 'kv-technologia',
                                        'action'              => 'delete',
                                        'id'                  => $index,
                                        'kv_technologia_nonce'=> wp_create_nonce('kv_delete_technologia_' . $index)
                                    ), admin_url('admin.php') );
                                    $duplicate_url = add_query_arg( array(
                                        'page'   => 'kv-technologia',
                                        'action' => 'duplicate',
                                        'id'     => $index,
                                    ), admin_url('admin.php') );
                                    ?>
                                    <a href="<?php echo esc_url($edit_url); ?>">Edytuj</a> |
                                    <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Czy na pewno chcesz usunąć tę technologię?');">Usuń</a> |
                                    <a href="<?php echo esc_url($duplicate_url); ?>">Duplikuj</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Brak dodanych technologii.</p>
            <?php endif; ?>
        </div>
        <?php
    }
}
