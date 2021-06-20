<?php

defined( 'ABSPATH' ) OR exit;

if (get_option("ezfc_debug_mode", 0) != 0) {
	@error_reporting(E_ALL);
	@ini_set("display_errors", "On");
}

@ini_set('max_execution_time', 300);
@set_time_limit(300);

global $wpdb;

require_once(EZFC_PATH . "class.ezfc_functions.php");
require_once(EZFC_PATH . "class.ezfc_backend.php");
$ezfc = Ezfc_backend::instance();

if (empty($_POST) || empty($_POST["data"])) die();

parse_str($_POST["data"], $data);

$action = isset($data["action"]) ? $data["action"] : null;
$id 	= isset($data["id"])     ? $data["id"] : 0;
$f_id 	= isset($data["f_id"])   ? $data["f_id"] : 0;

// validate user
$ezfc->validate_user("ezfc-nonce", "nonce");

/**
	actions
**/
switch ($action) {
	case "elements_get":
		$ret = array(
			"elements" => $ezfc->elements_get()
		);

		ezfc_send_ajax($ret);
	break;

	case "element_get":
		$ret = array(
			"element" => $ezfc->element_get($id)
		);

		ezfc_send_ajax($ret);
	break;

	case "form_add":
		$new_form_id = $ezfc->form_add($id);

		$ret = array(
			"elements" => $ezfc->form_elements_get($new_form_id),
			"form"     => $ezfc->form_get($new_form_id),
			"options"  => $ezfc->form_get_options($new_form_id)
		);

		update_option("ezfc_reopen_last_form_id", $new_form_id);
		ezfc_send_ajax($ret);
	break;

	case "form_add_template_elements":
		if (!$id || !$f_id) {
			ezfc_send_ajax(array("error" => __("Please select a form and a template to add elements from.", "ezfc")));
			die();
		}

		$ret = array(
			"elements" => $ezfc->form_add($id, false, false, $f_id, true)
		);

		ezfc_send_ajax($ret);
	break;

	case "form_clear":
		ezfc_send_ajax($ezfc->form_clear($f_id));
	break;

	case "form_delete":
		ezfc_send_ajax($ezfc->form_delete($f_id));
	break;

	case "form_delete_submissions":
		ezfc_send_ajax($ezfc->form_delete_submissions($f_id));
	break;

	case "form_download":
		$file = $ezfc->form_get_export_download($f_id);

		if (is_array($file) && isset($file["error"])) {
			ezfc_send_ajax($file);
			die();
		}

		$file_url = $ezfc->form_get_export_download_url($file["file_id"]);

		ezfc_send_ajax(array(
			"download_url" => $file_url
		));
	break;

	case "form_duplicate":
		ezfc_send_ajax($ezfc->form_duplicate($f_id));
	break;

	case "form_element_add":
		$f_id = isset($data["f_id"]) ? $data["f_id"] : null;
		$e_id = isset($data["e_id"]) ? $data["e_id"] : null;
		//$type = isset($data["type"]) ? $data["type"] : null;
		$element_settings = isset($data["element_settings"]) ? $data["element_settings"] : array();

		$extension_data = null;
		if (isset($data["extension"])) {
			// extension_id = $e_id
			$extension_data = json_encode(apply_filters("ezfc_element_data_{$e_id}", $e_id));
		}

		$new_element_id = $ezfc->form_element_add($f_id, $e_id, $extension_data, $element_settings);

		if (is_array($new_element_id) && isset($new_element_id["error"])) {
			ezfc_send_ajax($new_element_id);
			die();
		}

		ezfc_send_ajax($ezfc->form_element_get($new_element_id));
	break;

	case "form_element_change":
		ezfc_send_ajax($ezfc->form_element_change($id, $data["fe_id"]));
	break;

	case "form_element_delete":
		$child_element_ids = isset($data["child_element_ids"]) ? $data["child_element_ids"] : null;
		
		ezfc_send_ajax($ezfc->form_element_delete($id, $child_element_ids));
	break;

	case "form_element_duplicate":
		$element_data = isset($data["elements"]) ? $data["elements"][$id] : null;

		ezfc_send_ajax($ezfc->form_element_duplicate($id, $element_data));
	break;

	case "form_element_duplicate_group":
		if (empty($data["elements_to_duplicate"])) {
			ezfc_send_ajax(1);
			die();
		}
		
		ezfc_send_ajax($ezfc->form_element_duplicate_group($data["elements_to_duplicate"], $data["duplicate_element_id"]));
	break;

	case "form_file_delete":
		ezfc_send_ajax($ezfc->form_file_delete($id));
	break;

	case "form_get":
		ezfc_ajax_return_form($id);
	break;

	case "form_get_csv_submissions":
		$file = $ezfc->form_get_submissions_csv($f_id);

		if (is_array($file) && isset($file["error"])) {
			ezfc_send_ajax($file);
			die();
		}

		$file_url = $ezfc->form_get_export_download_url($file["file_id"]);

		ezfc_send_ajax(array(
			"download_url" => $file_url
		));
	break;

	case "form_get_submissions":
		$ret = array(
			"files"       => $ezfc->form_get_submissions_files($f_id),
			"submissions" => $ezfc->form_get_submissions($f_id)
		);

		ezfc_send_ajax($ret);
	break;

	case "form_import_data":
	case "form_import_upload":
	case "form_import_add_elements_data":
	case "form_import_add_elements_upload":
		// import by file upload
		if (!empty($_FILES)) {
			$import_data_json = EZFC_Functions::zip_read($_FILES["import_file"]["tmp_name"], "ezfc_form_export_data.json");
			
			if (!empty($import_data_json["error"])) {
				ezfc_send_ajax($import_data_json);
				die();
			}
		}
		// import by text
		else if (!empty($data["import_data"])) {
			$import_data_json = $data["import_data"];
		}
		// empty
		else {
			ezfc_send_ajax(array("error" => __("Empty form data or invalid file format", "ezfc")));
			die();
		}

		$import_data_json = json_decode($import_data_json);

		// elements couldn't be parsed - let's try with stripslashes
		if (!$import_data_json) {
			$import_data_json = json_decode(stripslashes($data["import_data"]));

			// still no luck - tell the user to remove special characters
			if (!$import_data_json) {
				ezfc_send_ajax(array("error" => __("Unable to import elements.", "ezfc")));
				die();
			}
		}

		$import_form_id = false;
		if ($action == "form_import_add_elements_data" || $action == "form_import_add_elements_upload") $import_form_id = $f_id;

		$ret = $ezfc->form_import($import_data_json, true, false, $import_form_id);

		ezfc_send_ajax($ret);
	break;

	case "form_preview":
	case "form_save":
	case "form_save_post":
		$preview = $action == "form_preview";

		if (!$preview) {
			// update form info
			$ezfc->form_update($id, $data["ezfc-form-name"]);
		}

		$elements_save = array();

		// replace ' with entity
		$data["elements"] = str_replace("\'", "&#39;", $data["elements"]);
		$elements = json_decode($data["elements"]);

		// empty form
		if (is_array($elements) && count($elements) < 1) {
			ezfc_send_ajax(1);
			die();
		}

		// elements couldn't be parsed - let's try with stripslashes
		if (!$elements) {
			$elements = json_decode(stripslashes($data["elements"]));

			// still no luck - tell the user to remove special characters
			if (!$elements) {
				ezfc_send_ajax(array("error" => __("Unable to save elements, please remove any special characters before saving.", "ezfc")));
				die();
			}
		}

		// empty form
		if (is_array($elements) && count($elements) < 1 && !$preview) {
			ezfc_send_ajax(1);
			die();
		}

		foreach ($elements as $element) {
			$tmp_str = $element->name . "=" . urlencode($element->value);
			parse_str($tmp_str, $tmp_save);
			
			$elements_save = EZFC_Functions::array_merge_recursive_distinct($elements_save, $tmp_save);
		}

		// preview
		if ($preview) {
			$ezfc->form_save_preview($id, $elements_save["elements"], $data["opt"]);

			$preview_nonce = wp_create_nonce("ezfc-preview-nonce");
			ezfc_send_ajax(array(
				"preview_url" => admin_url("admin.php") . "?page=ezfc-preview&preview_id={$id}&nonce={$preview_nonce}"
			));
		}
		else {
			// update form elements
			$res = $ezfc->form_elements_save($id, $elements_save["elements"]);
			if ($res !== 1) {
				ezfc_send_ajax($res);
				die();
			}

			if ($action == "form_save_post") {
				// send post url
				ezfc_send_ajax($ezfc->form_add_post($id));
			}
			else {
				ezfc_send_ajax($res);
			}
		}
	break;

	case "form_save_template":
		ezfc_send_ajax($ezfc->form_save_template($f_id));
	break;

	case "form_show_export":
		$ret = $ezfc->form_get_export_data(null, $f_id);

		ezfc_send_ajax($ret);
	break;

	case "form_submission_delete":
		ezfc_send_ajax($ezfc->form_submission_delete($id));
	break;

	case "form_template_delete":
		ezfc_send_ajax($ezfc->form_template_delete($id));
	break;

	case "form_update_options":
		$message = array();
		$update_result = $ezfc->form_update_options($f_id, $data["opt"]);

		// check for error
		if (!empty($update_result) && is_array($update_result)) {
			$message = array(
				"error" => __("Invalid form options", "ezfc"),
				"error_options" => json_encode($update_result)
			);
		}
		// no error
		else {
			$message = array("success" => __("Settings updated.", "ezfc"));
		}

		ezfc_send_ajax($message);
	break;

	/**
		quick add form
	**/
	case "quick_add":
		// todo
		ezfc_send_ajax($ezfc->quick_add($f_id, $data["text"]));
	break;

	/**
		advanced actions
	**/
	// show in email if not empty
	case "show_in_email_if_not_empty":
	case "show_in_email_if_not_empty_and_not_zero":
		$show_in_email_value = $action == "show_in_email_if_not_empty" ? 2 : 3;

		$ret = $ezfc->set_form_element_values($f_id, array(
			"show_in_email" => $show_in_email_value
		));

		if (!empty($ret["error"])) {
			ezfc_send_ajax($ret);
			die();
		}

		ezfc_ajax_return_form($f_id);
	break;

	// subtotal change add_to_price value
	case "subtotal_add_to_price_set_to_no":
		ezfc_send_ajax($ezfc->subtotal_add_to_price_change($f_id, 0));
	break;
	case "subtotal_add_to_price_set_to_yes":
		ezfc_send_ajax($ezfc->subtotal_add_to_price_change($f_id, 1));
	break;
	case "subtotal_add_to_price_set_to_partially":
		ezfc_send_ajax($ezfc->subtotal_add_to_price_change($f_id, 2));
	break;
	case "subtotal_add_to_price_set_to_partially_global":
		ezfc_send_ajax($ezfc->subtotal_add_to_price_change(false, 2));
	break;

	/**
		submission mails
	**/
	case "submission_send_admin":
		// load frontend
		require_once(EZFC_PATH . "class.ezfc_frontend.php");
		$ezfc_frontend = Ezfc_frontend::instance();

		// get submission
		$submission = $ezfc->get_submission($id);

		if (!$submission || (is_array($submission) && $submission["error"])) return $submission["error"];

		// get form options
		$options = $ezfc_frontend->form_get_option_values($submission->f_id);

		// prepare submission data for mails
		$ezfc_frontend->prepare_submission_data($submission->f_id, array("ezfc_element" => json_decode($submission->data, true)), true, $submission->ref_id, $submission->id);

		// form email target
		$ezfc_frontend->check_conditional_email_target();

		// prepare replace values
		$ezfc_frontend->prepare_replace_values();
		
		// send mails
		$ezfc_frontend->send_mails($submission->id, false, false, false, false, array(
			"send_to_customer" => false
		));

		ezfc_send_ajax(Ezfc_Functions::send_message("success", __("Email was sent to admin.", "ezfc")));
	break;
	case "submission_send_customer":
		// load frontend
		require_once(EZFC_PATH . "class.ezfc_frontend.php");
		$ezfc_frontend = Ezfc_frontend::instance();

		// get submission
		$submission = $ezfc->get_submission($id);

		if (!$submission || (is_array($submission) && $submission["error"])) return $submission["error"];

		// get form options
		$options = $ezfc_frontend->form_get_option_values($submission->f_id);

		// prepare submission data for mails
		$ezfc_frontend->prepare_submission_data($submission->f_id, array("ezfc_element" => json_decode($submission->data, true)), true, $submission->ref_id, $submission->id);

		// form email target
		$ezfc_frontend->check_conditional_email_target();

		// prepare replace values
		$ezfc_frontend->prepare_replace_values();
		
		// send mails
		$ezfc_frontend->send_mails($submission->id, false, false, false, false, array(
			"send_to_admin" => false
		));

		ezfc_send_ajax(Ezfc_Functions::send_message("success", __("Email was sent to customer.", "ezfc")));
	break;
}

function ezfc_ajax_return_form($id) {
	$ezfc = Ezfc_backend::instance();

	$ret = array(
		"elements"          => $ezfc->form_elements_get($id),
		"form"              => $ezfc->form_get($id),
		"options"           => $ezfc->form_get_options($id),
		"submissions_count" => $ezfc->form_get_submissions_count($id)
	);

	if (empty($ret["form"]) || isset($ret["form"]->error)) {
		ezfc_send_ajax($ret["form"]);
		die();
	}

	update_option("ezfc_reopen_last_form_id", $id);
	ezfc_send_ajax($ret);
}

// send ajax message
function ezfc_send_ajax($msg) {
	// check for errors in array
	if (is_array($msg)) {
		foreach ($msg as $m) {
			if (is_array($m) && isset($m["error"])) {
				echo json_encode($m);

				return;
			}
		}
	}

	echo json_encode($msg);
}

die();

?>