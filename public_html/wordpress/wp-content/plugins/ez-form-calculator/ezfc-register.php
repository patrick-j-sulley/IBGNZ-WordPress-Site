<?php

defined( 'ABSPATH' ) OR exit;
if (!current_user_can('activate_plugins')) return;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

$query = file_get_contents(EZFC_PATH . "db.sql");
if (!$query) {
	die("Error opening file.");
}

require_once(EZFC_PATH . "class.ezfc_backend.php");
$ezfc_backend = Ezfc_backend::instance();
$ezfc_backend->setup_db();
update_option("ezfc_version", EZFC_VERSION);
$ezfc_backend->upgrade();

// default global options
/*$ezfc_options = Ezfc_settings::get_global_settings(true);
foreach ($ezfc_options as $name => $option) {
    $value = isset($option["default"]) ? $option["default"] : "";

    update_option("ezfc_{$name}", $value);
}

// default form options
$ezfc_form_options = Ezfc_settings::get_form_options(true);

$tmp_array = array();
foreach ($ezfc_form_options as $name => $option) {
    $value = isset($option["default"]) ? $option["default"] : "";

    $tmp_array[$name] = $value;
}
$ezfc_backend->update_options($tmp_array);*/