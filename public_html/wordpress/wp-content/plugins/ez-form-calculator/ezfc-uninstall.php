<?php

defined( 'ABSPATH' ) OR exit;
if (!current_user_can('activate_plugins')) return;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

$tmp_path = plugin_dir_path(__FILE__);
require_once($tmp_path . "class.ezfc_settings.php");

// revoke license if needed
$code = get_option("ezfc_purchase_code", "");
if (!empty($code)) {
	require_once(EZFC_PATH . "class.ezfc_functions.php");
	Ezfc_Functions::revoke_license($code);

	delete_option("ezfc_purchase_code");
	delete_option("ezfc_license_activated");
}

// do not delete data
if (get_option("ezfc_uninstall_keep_data") == 1) return;

require_once(EZFC_PATH . "class.ezfc_backend.php");
$ezfc_backend = Ezfc_backend::instance();

// remove tables
foreach ($ezfc_backend->tables as $table) {
	$wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

// default global options
$options = Ezfc_settings::get_global_settings(true);

foreach ($options as $name => $option) {
	delete_option("ezfc_{$name}");
}

// additional submission data
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ezfc_submission_pdf_file_%'" );

// remove upload folders
if (function_exists("WP_Filesystem")) {
	WP_Filesystem();
	global $wp_filesystem;

	$upload_dir = wp_upload_dir();
	foreach (Ezfc_Functions::$folders as $folder) {
		$upload_dirname = $upload_dir["basedir"] . "/{$folder}";
		Ezfc_Functions::delete_dir($upload_dirname);
	}
}