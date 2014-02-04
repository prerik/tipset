<?php

function tipset_plugin_menu() {
    add_options_page('Tipset Inställningar', 'Tipset', 'manage_options', 'tipset-installningar', 'tipset_plugin_options_page');
    add_action('admin_init', 'register_tipset_options');
}

function register_tipset_options() {
    register_setting('tipset_options_group', 'tipset_options', 'tipset_options_validate');
    add_settings_section('tipset_main', 'Huvudinställningar', 'tipset_main_section_text', 'tipset-installningar');
    add_settings_field('tipset_open', 'Tipset öppet', 'tipset_open_checkbox', 'tipset-installningar', 'tipset_main');
}

function tipset_main_section_text() {
    echo "";
}

function tipset_open_checkbox() {
    $options = get_option('tipset_options');
    echo "<input id=\"tipset_open\" name=\"tipset_options[tipset_open]\" type=\"checkbox\" " . checked("on", $options['tipset_open'], false) . "/> ";
}

function tipset_options_validate($input) {
    $newinput['tipset_open'] = $input['tipset_open'];
    return $newinput;
}

function tipset_plugin_options_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>
    <div class="wrap">
        <h2>Tipset inställningar</h2>
        <form action="options.php" method="post">
            <?php settings_fields('tipset_options_group'); ?>
            <?php do_settings_sections('tipset-installningar'); ?>
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>
        </form>
    </div>
    <?php
}
?>
