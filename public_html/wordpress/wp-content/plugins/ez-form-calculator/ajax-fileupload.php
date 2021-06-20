<?php

defined( 'ABSPATH' ) OR exit;

if (get_option("ezfc_debug_mode", 0) != 0) {
	@error_reporting(E_ALL);
	@ini_set("display_errors", "On");
}

parse_str($_REQUEST["data"], $data);

// no action
if (empty($data["action"])) die();

require_once(EZFC_PATH . "class.ezfc_frontend.php");
$ezfc = Ezfc_frontend::instance();

if ($data["action"] == "remove_uploaded_file") {
	// check for IDs
	if (empty($data["file_id"]) || empty($data["ref_id"])) {
		send_ajax(array("error" => __("Empty file ID or ref ID.", "ezfc")));
		die();
	}

	send_ajax($ezfc->remove_file($data["file_id"], $data["ref_id"]));
	die();
}

/**
	change upload dir
**/
add_filter('upload_dir', 'ezfc_change_upload_dir');
$upload = wp_upload_dir();

function ezfc_change_upload_dir($upload) {
	$upload['subdir']	= '/ezfc-uploads' . $upload['subdir'];
	$upload['path']		= $upload['basedir'] . $upload['subdir'];
	$upload['url']		= $upload['baseurl'] . $upload['subdir'];
	return $upload;
}

/**
	override myme types
**/
if (get_option("ezfc_upload_override_filetypes", 0) == 1) {
	add_filter('upload_mimes', 'ezfc_mime_types', 1, 1);
}

function ezfc_mime_types($mime_types) {
	$mime_types = array();
	$options = get_option("ezfc_upload_custom_filetypes");
	$options_array = explode(",", $options);

	if (count($options_array) > 0) {
		foreach ($options_array as $filetype) {
			$mime_type = Ezfc_Functions::mime_type($filetype);

			if ($mime_type !== false) {
				$mime_types[$filetype] = $mime_type;
			}
		}
	}
	
	return $mime_types;
}

/**
	upload handling
**/
if (empty($_FILES["ezfc-files"])) die();

$return_array = array();
$files = $_FILES["ezfc-files"];
foreach ($files["name"] as $key => $value) {
	$file = array(
		"name"     => $files["name"][$key],
		"type"     => $files["type"][$key],
		"tmp_name" => $files["tmp_name"][$key],
		"error"    => $files["error"][$key],
		"size"     => $files["size"][$key]
	);

	do_action("ezfc_fileupload_before", $file);

	$upload_overrides = array("test_form" => false);
	$movefile         = wp_handle_upload( $file, $upload_overrides );

	if ($movefile && !isset($movefile["error"])) {
		$insert = $ezfc->insert_file($data["id"], $data["ref_id"], $movefile);

		if (!$insert) {
			send_ajax(array("error" => $movefile["error"]));
			die();
		}

		$return_array[] = $insert;
	}
	else {
		send_ajax(array("error" => __("Filetype not allowed.", "ezfc")));
		die();
	}
}

do_action("ezfc_fileupload_after", $return_array);

send_ajax($return_array);

// use default upload dir again.
remove_filter('upload_dir', 'ezfc_change_upload_dir');
die();

function send_ajax($msg) {
	// check for errors in array
	if (is_array($msg)) {
		foreach ($msg as $m) {
			if (is_array($m) && !empty($m["error"])) {
				echo json_encode($m);

				return;
			}
		}
	}

	echo json_encode($msg);
}

?>