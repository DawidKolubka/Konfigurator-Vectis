<?php
defined('ABSPATH') or die('Brak dostępu');

/**
 * Wyświetla informacje debugowania dla administratorów
 */
function kv_debug_panel() {
    // Sprawdź, czy użytkownik jest adminem
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Pobierz dane
    $mechanizm_options = get_option('kv_mechanizm_options', []);
    $technologia_options = get_option('kv_technologia_options', []);
    
    ?>
    <div class="debug-panel" style="margin-top: 30px; padding: 15px; background: #f1f1f1; border: 1px solid #ddd; border-radius: 4px;">
        <h3>Panel diagnostyczny (tylko dla administratora)</h3>
        
        <div class="debug-section">
            <h4>Mechanizmy</h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nazwa</th>
                        <th>Typ ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mechanizm_options as $id => $mech): ?>
                        <?php if ($mech): ?>
                        <tr>
                            <td><?php echo esc_html($id); ?></td>
                            <td><?php echo esc_html($mech['nazwa'] ?? '—'); ?></td>
                            <td><?php echo gettype($id); ?></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="debug-section" style="margin-top: 20px;">
            <h4>Technologie</h4>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nazwa</th>
                        <th>Grupa/Mechanizm ID</th>
                        <th>Typ ID</th>
                        <th>Typ Group</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($technologia_options as $id => $tech): ?>
                        <?php if ($tech): ?>
                        <tr>
                            <td><?php echo esc_html($id); ?></td>
                            <td><?php echo esc_html($tech['nazwa'] ?? '—'); ?></td>
                            <td><?php echo esc_html($tech['group'] ?? '—'); ?></td>
                            <td><?php echo gettype($id); ?></td>
                            <td><?php echo isset($tech['group']) ? gettype($tech['group']) : '—'; ?></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="debug-actions" style="margin-top: 20px;">
            <a href="<?php echo admin_url('admin.php?page=kv-kreator&kv_repair_ids=1'); ?>" class="button button-primary" onclick="return confirm('Czy na pewno chcesz przeprowadzić naprawę ID? Ta operacja jest nieodwracalna.');">Napraw ID mechanizmów i technologii</a>
        </div>
    </div>
    <?php
}

// Panel debugowania jest teraz dostępny tylko w panelu administratora
