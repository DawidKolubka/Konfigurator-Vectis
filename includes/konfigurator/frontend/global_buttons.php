<form method="post" class="global-actions-form">
    <?php wp_nonce_field('kv_configurator_submit', 'kv_configurator_nonce'); ?>
    <button type="submit" name="kv_global_save" class="btn-save">Zapisz</button>
    <button type="submit" name="kv_global_cancel" class="btn-cancel">Anuluj</button>
</form>