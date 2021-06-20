<?php

class Ezfc_backend {
	private static $_instance = null;
	
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->tables = array(
			"debug"          => "{$this->wpdb->prefix}ezfc_debug",
			"elements"       => "{$this->wpdb->prefix}ezfc_elements", // deprecated
			"files"          => "{$this->wpdb->prefix}ezfc_files",
			"forms"			 => "{$this->wpdb->prefix}ezfc_forms",
			"forms_elements" => "{$this->wpdb->prefix}ezfc_forms_elements",
			"forms_options"  => "{$this->wpdb->prefix}ezfc_forms_options",
			"options"        => "{$this->wpdb->prefix}ezfc_options",
			"preview"        => "{$this->wpdb->prefix}ezfc_preview",
			"submissions"    => "{$this->wpdb->prefix}ezfc_submissions",
			"templates"      => "{$this->wpdb->prefix}ezfc_templates",
			"tmp"            => "{$this->wpdb->prefix}ezfc_tmptable"
		);

		$this->elements     = Ezfc_Functions::array_index_key(Ezfc_settings::get_elements(), "id");
		$this->form_options = Ezfc_Functions::array_index_key(Ezfc_settings::get_form_options(true), "id");

		// extension elements
		$elements_ext = apply_filters("ezfc_show_backend_elements", array());
		foreach ($elements_ext as $element_name => $element_options) {
			// convert array data to object
			$element_data_json = json_decode(json_encode($element_options));
			$element_data_json->extension = true;

			$this->elements[$element_name] = $element_data_json;
		}
	}

	public function get_debug_log() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["debug"]} ORDER BY id DESC LIMIT 500");

		if (count($res) < 1) return __("No debug logs found.", "ezfc");

		$logs = array();
		foreach ($res as $log) {
			$logs[] = "{$log->time}: {$log->msg}";
		}

		return implode("\n", $logs);
	}

	public function clear_debug_log() {
		$this->wpdb->query("TRUNCATE TABLE `{$this->tables["debug"]}`");
	}

	public function debug($msg, $force = false) {
		if (get_option("ezfc_debug_mode", 0) == 0 && !$force) return;

		$this->wpdb->insert(
			$this->tables["debug"],
			array("msg" => $msg),
			array("%s")
		);
	}

	/**
		forms
	**/
	public function form_add($template_id = 0, $import_data = false, $preserve_ids = false, $form_id = false, $return_elements_only = false, $import_options = true) {
		$form_name = __("New form", "ezfc");

		if ($import_data) {
			if (!property_exists($import_data, "form")) return Ezfc_Functions::send_message("error", __("Corrupt form data.", "ezfc"));

			$form_name = $import_data->form->name;
		}

		// template / import
		$template = null;

		// add by template
		if ($template_id) {
			$template          = $this->form_template_get($template_id);
			$template_elements = json_decode($template->data);
			$template_options  = json_decode($template->options);
			$form_name         = $template->name;
		}
		// add by import data
		else if ($import_data && property_exists($import_data, "elements")) {
			$template          = $import_data;
			$template_elements = $template->elements;
			$template_options  = $template->options;
		}

		// add to existing form
		if ($form_id) {
			$insert_id = $form_id;
		}
		// add new form
		else {
			// preserve ids when importing forms
			if ($preserve_ids) {
				// check if form exists
				$form_exists = $this->wpdb->get_row($this->wpdb->prepare(
					"SELECT * FROM {$this->tables["forms"]} WHERE id = %d",
					$import_data->form->id
				));

				// form exists -> remove form + form elements
				if ($form_exists) {
					$this->form_delete($import_data->form->id);
				}

				$res = $this->wpdb->insert(
					$this->tables["forms"],
					array("name" => $form_name, "id" => $import_data->form->id),
					array("%s", "%d")
				);
			}
			else {
				$res = $this->wpdb->insert(
					$this->tables["forms"],
					array("name" => $form_name),
					array("%s")
				);
			}

			$insert_id = $this->wpdb->insert_id;
		}

		// template data exists -> import them
		if (is_object($template)) {
			foreach ($template_elements as $element) {
				if (!is_object($element)) return Ezfc_Functions::send_message("error", __("Invalid element.", "ezfc"));

				$el_insert_id = $this->form_element_add($insert_id, $element->e_id, $element->data, $element->position);

				if (!$el_insert_id) return Ezfc_Functions::send_message("error", __("Could not insert element data from template.", "ezfc"));

				$element->id = $el_insert_id;
			}

			// replace calculate positions with target ids
			$template_elements_indexed = Ezfc_Functions::array_index_key($template_elements, "position");

			foreach ($template_elements_indexed as $pos => $element) {
				if (!property_exists($element, "data")) continue;

				$element_data = json_decode($element->data);
				if (!is_object($element_data)) {
					$this->debug("Error while importing:");
					$this->debug(var_export($element->data, true));
					continue;
				}

				$element_data_sql = null;
				$element_update = false;

				// calculate elements
				if (property_exists($element_data, "calculate") &&
					!empty($element_data->calculate) &&
					count($element_data->calculate) > 0) {

					// convert object to array
					if (!is_array($element_data->calculate)) {
						$element_data->calculate = (array) $element_data->calculate;
					}

					foreach ($element_data->calculate as $cn => &$calc_value) {
						// no target
						if (empty($calc_value->target)) continue;

						// target doesn't exist
						if (!isset($template_elements_indexed[$calc_value->target])) continue;

						$target_element = $template_elements_indexed[$calc_value->target];
						$calc_id = $target_element->id;

						$calc_value->target = $calc_id;
						// unset id due to duplication
						//unset($element_data->id);

						$element_update = true;
					}
				}

				// conditional elements
				if (property_exists($element_data, "conditional") &&
					!empty($element_data->conditional) &&
					count($element_data->conditional) > 0) {

					// convert object to array
					if (!is_array($element_data->conditional)) {
						$element_data->conditional = (array) $element_data->conditional;
					}

					foreach ($element_data->conditional as &$cond_value) {
						if (empty($cond_value->target)) continue;

						if (!isset($template_elements_indexed[$cond_value->target])) continue;

						$target_element = $template_elements_indexed[$cond_value->target];
						$cond_id = $target_element->id;
						$cond_value->target = $cond_id;

						// chain target
						if (property_exists($cond_value, "compare_value") && is_array($cond_value->compare_value) && count($cond_value->compare_value > 0)) {
							foreach ($cond_value->compare_value as &$chain_target_id) {
								if (isset($template_elements_indexed[$chain_target_id])) {
									$chain_target_id = $template_elements_indexed[$chain_target_id]->id;
								}
							}
						}

						$element_update = true;
					}
				}

				// set elements
				if (property_exists($element_data, "set") &&
					!empty($element_data->set) &&
					count($element_data->set) > 0) {
					// convert object to array
					if (!is_array($element_data->set)) {
						$element_data->set = (array) $element_data->set;
					}

					foreach ($element_data->set as &$set_element) {
						if (empty($set_element->target)) continue;

						if (!isset($template_elements_indexed[$set_element->target])) continue;

						$target_element = $template_elements_indexed[$set_element->target];
						$cond_id = $target_element->id;

						$set_element->target = $cond_id;
						// unset id due to duplication
						//unset($element_data->id);

						$element_update = true;
					}
				}

				// groups
				if (!empty($element_data->group_id)) {
					// check if group exists
					if (isset($template_elements_indexed[$element_data->group_id])) {
						$target_element = $template_elements_indexed[$element_data->group_id];
						$target_id      = $target_element->id;

						$element_data->group_id = $target_id;
						// unset id due to duplication
						//unset($element_data->id);

						$element_update = true;
					}
				}

				// show in email target
				if (!empty($element_data->show_in_email_cond) && is_array($element_data->show_in_email_cond)) {
					foreach ($element_data->show_in_email_cond as $i => $cond) {
						if (!isset($template_elements_indexed[$cond])) continue;

						$target_element = $template_elements_indexed[$cond];
						$target_id      = $target_element->id;

						$element_data->show_in_email_cond[$i] = $target_id;

						$element_update = true;
					}
				}

				// matrix
				if (!empty($element_data->matrix)) {
					// target elements
					if (!empty($element_data->matrix->target_elements)) {
						foreach ($element_data->matrix->target_elements as $i => $target_element_matrix) {
							if (!isset($template_elements_indexed[$target_element_matrix])) continue;

							$target_element = $template_elements_indexed[$target_element_matrix];
							$target_id      = $target_element->id;

							$element_data->matrix->target_elements[$i] = $target_id;
							$element_update = true;
						}
					}

					// matrix conditions
					if (!empty($element_data->matrix->conditions)) {
						foreach ($element_data->matrix->conditions as $i => $matrix_condition) {
							if (empty($matrix_condition->elements) || !is_array($matrix_condition->elements)) continue;
							
							foreach ($matrix_condition->elements as $mi => $matrix_condition_element) {
								$target_element = $template_elements_indexed[$matrix_condition_element];
								$target_id      = $target_element->id;

								$element_data->matrix->conditions[$i]->elements[$mi] = $target_id;
								$element_update = true;
							}
						}
					}
				}

				// element data was changed -> update element in sql db
				if ($element_update) {
					$element_data_sql = json_encode($element_data);

					$this->wpdb->query($this->wpdb->prepare(
						"UPDATE {$this->tables["forms_elements"]} SET data=%s WHERE id=%d",
						$element_data_sql, $element->id
					));

					$template_elements_indexed[$pos]->data = $element_data_sql;
				}
			}
		}

		// return added elements only
		if ($return_elements_only) {
			$return_elements = $this->merge_default_elements($template_elements_indexed, false);

			return $return_elements;
		}
		// add default options
		else if ($import_options) {
			$default_options = $this->wpdb->query($this->wpdb->prepare(
				"INSERT INTO {$this->tables["forms_options"]} (f_id, o_id, value)
				(SELECT %d, id, value FROM {$this->tables["options"]})",
				$insert_id
			));

			// import options
			if (isset($template_options) && count($template_options) > 0) {
				$update_values = array();
				$update_placeholders = array();
				$update_query = "INSERT IGNORE INTO {$this->tables["forms_options"]} (f_id, o_id, value) VALUES ";

				foreach ($template_options as $export_option_id => $o) {
					// fallback
					$option_id = empty($o->o_id) ? $export_option_id : $o->o_id;

					array_push($update_values, $insert_id, $option_id, $o->value);
					$update_placeholders[] = "(%d, %d, %s)";
				}

				$update_query .= implode(", ", $update_placeholders);
				$update_query .= " ON DUPLICATE KEY UPDATE value=VALUES(value)";
				
				$this->wpdb->query($this->wpdb->prepare($update_query, $update_values));
			}
		}

		return $insert_id;
	}

	public function form_add_post($id) {
		$post_name = "ezfc-form-{$id}";

		$args = array(
			'meta_query' => array(
				array(
					'key' => 'ezfc_form_id',
					'value' => $id,
					'compare' => '='
				)
			)
		);

		$post_id = null;
		$post_exists = new WP_Query($args);

		if ($post_exists->have_posts()) {
			$post_exists->the_post();
			$new_post_id = get_the_ID();
		}
		else {
			$new_post_id = wp_insert_post(array(
				"ID"           => $post_id,
				"post_content" => "[ezfc id='{$id}' /]",
				"post_name"    => $post_name,
				"post_status"  => "private",
				"post_title"   => "EZFC Form #{$id}",
				"post_type"    => "post"
			));

			if (!$new_post_id) return Ezfc_Functions::send_message("error", __("Unable to add post", "ezfc"));

			add_post_meta($new_post_id, "ezfc_form_id", $id);
		}

		// return permalink
		return Ezfc_Functions::send_message("success", urlencode(get_permalink($new_post_id)));
	}

	/**
		add default form options
	**/
	public function form_add_default_options($id) {
		$settings = Ezfc_Functions::array_index_key($this->get_settings_fields(true), "id");

		foreach ($settings as $o_id => $value) {
			$this->wpdb->insert(
				$this->tables["forms_options"],
				array(
					"f_id"  => $id,
					"o_id"  => $o_id,
					"value" => $value
				),
				array(
					"%d",
					"%d",
					"%s"
				)
			);
		}
	}

	public function form_clear($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["forms_elements"],
			array("f_id" => $id),
			array("%d")
		);

		return Ezfc_Functions::send_message("success", __("Form cleared.", "ezfc"));
	}

	public function form_delete($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["forms"],
			array("id" => $id),
			array("%d")
		);

		if ($res === false) return Ezfc_Functions::send_message("error", sprintf(__("Could not delete form: %s", "ezfc"), $wpdb->last_error));

		$res = $this->wpdb->delete(
			$this->tables["forms_elements"],
			array("f_id" => $id),
			array("%d")
		);

		if ($res === false) return Ezfc_Functions::send_message("error", sprintf(__("Could not delete form elements: %s", "ezfc"), $wpdb->last_error));

		$res = $this->wpdb->delete(
			$this->tables["forms_options"],
			array("f_id" => $id),
			array("%d")
		);

		if ($res === false) return Ezfc_Functions::send_message("error", sprintf(__("Could not delete form options: %s", "ezfc"), $wpdb->last_error));

		$res = $this->wpdb->delete(
			$this->tables["preview"],
			array("f_id" => $id),
			array("%d")
		);

		if ($res === false) return Ezfc_Functions::send_message("error", sprintf(__("Could not delete form preview: %s", "ezfc"), $wpdb->last_error));

		return Ezfc_Functions::send_message("success", __("Form deleted.", "ezfc"));
	}

	public function form_delete_submissions($f_id) {
		if (empty($f_id)) return Ezfc_Functions::send_message("error", __("No form selected.", "ezfc"));

		// delete all submissions
		if ($f_id == -1) {
			$res = $this->wpdb->delete(
				$this->tables["submissions"]
			);
		}
		// delete submissions from single form
		else {
			$res = $this->wpdb->delete(
				$this->tables["submissions"],
				array("f_id" => $f_id),
				array("%d")
			);	
		}

		if ($res === false) return Ezfc_Functions::send_message("error", sprintf(__("Unable to delete form submissions: %s", "ezfc"), $wpdb->last_error));

		return Ezfc_Functions::send_message("success", __("Submissions deleted.", "ezfc"));
	}

	public function form_duplicate($id) {
		// get import data
		$form_data = $this->form_get_export_data(null, $id);
		// convert objects
		$form_object = json_decode(json_encode($form_data));
		$form_object->form->name .= " " . __("duplicate", "ezfc");

		// add form
		$new_form_id = $this->form_add(0, $form_object);

		// get form options for new form
		$form_options = $this->form_get_options($new_form_id);
		// convert to option array [id] -> value
		$form_options_update_array = array();
		foreach ($form_options as $option_id => $option_array) {
			$form_options_update_array[$option_id] = $option_array["value"];
		}
		// update form options (generate new styles)
		$this->form_update_options($new_form_id, $form_options_update_array);
		
		// get form data
		$ret = array(
			"elements" => $this->form_elements_get($new_form_id),
			"form"     => $this->form_get($new_form_id),
			"options"  => $form_options
		);

		return $ret;
	}

	// change element type
	// $id = element id, $fe_id = form element id
	public function form_element_change($id, $fe_id) {
		$element      = $this->element_get($id);
		$form_element = $this->form_element_get($fe_id);

		if (!$element)      return Ezfc_Functions::send_message("error", sprintf(__("Element #%s not found.", "ezfc"), $id));
		if (!$form_element) return Ezfc_Functions::send_message("error", sprintf(__("Form element #%s not found.", "ezfc"), $id));

		// get form element data first
		$element_data      = (array) $element->data;
		$form_element_data = (array) json_decode($form_element->data);

		// merge only existing element options
		$new_data = EZFC_Functions::array_merge_recursive_existing_keys($element_data, $form_element_data);
		// change element id
		$new_data["e_id"] = $id;

		$new_data_json = json_encode($new_data);

		$res = $this->wpdb->update(
			$this->tables["forms_elements"],
			array(
				"data" => $new_data_json,
				"e_id" => $id
			),
			array("id" => $fe_id),
			array(
				"%s",
				"%d"
			),
			array("%d")
		);

		if ($res === false) return Ezfc_Functions::send_message("error", __("Unable to change form element.", "ezfc"));

		return $this->form_element_get($fe_id);
	}

	public function form_file_delete($id) {
		$id = (int) $id;

		$file = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["files"]} WHERE id=%d",
			$id
		));

		if (!unlink($file->file)) return Ezfc_Functions::send_message("error", __("Could not delete file from server.", "ezfc"));

		$res = $this->wpdb->delete(
			$this->tables["files"],
			array("id" => $id),
			array("%d")
		);

		if ($res === false) return Ezfc_Functions::send_message("error", __("Could not delete file entry: {$wpdb->last_error}", "ezfc"));

		return Ezfc_Functions::send_message("success", __("File deleted.", "ezfc"));
	}

	public function forms_get() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["forms"]}");

		return $res;
	}

	public function form_get($id) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["forms"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	public function form_get_options($id, $values_only = false) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID", "ezfc"));

		$settings = Ezfc_Functions::array_index_key($this->get_settings_fields(true), "id");

		// merge values
		$settings_db = Ezfc_Functions::array_index_key($this->wpdb->get_results($this->wpdb->prepare("SELECT o_id, value FROM {$this->tables["forms_options"]} WHERE f_id=%d", $id), ARRAY_A), "o_id");

		foreach ($settings as &$setting) {
			if (!empty($settings_db[$setting["id"]])) $setting["value"] = maybe_unserialize($settings_db[$setting["id"]]["value"]);
			else if (isset($setting["default"])) {
				$setting["value"] = maybe_unserialize($setting["default"]);
			}

			if ($values_only) {
				$setting = array("value" => maybe_serialize($setting["value"]));
			}
		}

		return $settings;
	}

	/**
		$import_data = json_object
	**/
	public function form_import($import_data, $return_data = true, $preserve_id = false, $form_id = false) {
		if ($form_id) {
			// import to existing form
			$new_form_id = $this->form_add(null, $import_data, $preserve_id, $form_id, false, false);
		}
		else {
			$new_form_id = $this->form_add(null, $import_data, $preserve_id);
		}

		// error
		if (is_array($new_form_id)) return $new_form_id;

		// do not return any data (when importing multiple forms)
		if (!$return_data) return $new_form_id;

		$ret = array(
			"elements" => $this->form_elements_get($new_form_id),
			"form"     => $this->form_get($new_form_id),
			"options"  => $this->form_get_options($new_form_id)
		);

		return $ret;
	}

	/**
		submissions
	**/
	public function get_submission($id, $woo=false) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["submissions"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	public function form_get_submissions($id, $order = "id", $order_dir = "DESC") {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID", "ezfc"));

		$allowed_columns = array("id", "f_id", "date");
		$allowed_order   = array("ASC", "DESC");

		if (!in_array($order, $allowed_columns)) $order = "id";
		if (!in_array($order_dir, $allowed_order)) $order_dir = "DESC";

		// all submissions
		if ($id == -1) {
			$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["submissions"]} ORDER BY f_id ASC, {$order} {$order_dir}");
		}
		// get submissions by id
		else {
			$res = $this->wpdb->get_results($this->wpdb->prepare(
				"SELECT * FROM {$this->tables["submissions"]} WHERE f_id=%d ORDER BY {$order} {$order_dir}",
				$id
			));
		}

		return $res;
	}

	public function form_get_submissions_count($id) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID", "ezfc"));

		// all submissions
		if ($id == -1) {
			$res = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables["submissions"]}");
		}
		// get submissions by id
		else {
			$res = $this->wpdb->get_var($this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->tables["submissions"]} WHERE f_id=%d",
				$id
			));
		}

		return $res;
	}

	public function form_get_submissions_csv($id) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID", "ezfc"));
		$id = (int) $id;

		$submissions = $this->form_get_submissions($id);

		// no submissions
		if (count($submissions) < 1) return Ezfc_Functions::send_message("error", __("No submissions found.", "ezfc"));

		// get form elements
		$form_elements = $this->form_elements_get($id, true);

		// convert to array
		$submission_array = json_decode(json_encode($submissions), true);

		// load frontend functions
		require_once(EZFC_PATH . "class.ezfc_frontend.php");
		$frontend = Ezfc_frontend::instance();

		$keys = array_keys($submission_array[0]);

		// get element names and set them as keys
		$tmp_form_elements = json_decode($submission_array[0]["data"], true);
		foreach ($tmp_form_elements as $tmp_e_id => $tmp_value) {
			if (isset($form_elements[$tmp_e_id])) {
				$tmp_form_element_data = json_decode($form_elements[$tmp_e_id]->data);
				$keys[] = $tmp_form_element_data->name;
			}
			else {
				//$keys[] = sprintf(__("Unknown element: %s", "ezfc"), "#{$tmp_e_id}");
			}
		}

		$csv_array = array();
		$csv_array[] = Ezfc_Functions::arrayToCsv($keys);

		foreach ($submission_array as $submission) {
			$tmp_data = array();

			// replace id with element name
			$submission_form_elements = json_decode($submission["data"], true);

			// prepare data to properly format submitted values
			$dummy_data = array(
				"ezfc_element" => $submission_form_elements
			);
			$frontend->prepare_submission_data($id, $dummy_data);

			foreach ($submission_form_elements as $e_id => $value) {
				if (is_array($value)) $value = implode(",", $value);

				// form element not found
				if (!isset($form_elements[$e_id])) {
					//$submission[] = $value;
					continue;
				}

				$form_element_data = json_decode($form_elements[$e_id]->data);
				$submission[] = $frontend->get_text_from_input($form_element_data, $value, $e_id, true);
			}

			$csv_array[] = Ezfc_Functions::arrayToCsv($submission);
		}

		$csv_output = implode("\n", $csv_array); 

		$save_dir = Ezfc_Functions::create_upload_dir(Ezfc_Functions::$folders["tmp"], false);
		$file     = EZFC_Functions::zip_write($csv_output, "export.csv", $save_dir);

		return $file;
	}

	/**
		search for email in submissions
	**/
	public function submission_search_email($email) {
		// invalid email
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return Ezfc_Functions::send_message("error", __("Please enter a valid email address.", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["submissions"]} WHERE user_mail = %s",
			$email
		));

		if (!$res) return Ezfc_Functions::send_message("success", sprintf(__("Email %s not found.", "ezfc"), $email));

		return $res;
	}

	/**
	 * get a list of uploaded files by form id
	 */
	public function form_get_submissions_files($id) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["files"]} WHERE f_id=%d ORDER BY id DESC",
			$id
		));

		$files = array();

		if (count($res) > 0) {
			// index by ref_id
			foreach ($res as $file) {
				$files[$file->ref_id][] = $file;
			}
		}

		return $files;
	}

	/**
		import submissions
	**/
	public function submissions_import($submission_array, $f_id) {
		if (!is_array($submission_array) || !$f_id) throw new Exception(__("Unable to import submissions.", "ezfc"));

		if (count($submission_array) < 1) return true;

		foreach ($submission_array as $submission) {
			$this->submission_import($submission, $f_id);
		}

		return true;
	}

	public function submission_import($submission_data, $f_id) {
		$res = $this->wpdb->insert(
			$this->tables["submissions"],
			array(
				"f_id" => $f_id,
				"data" => $submission_data->data,
				"content" => $submission_data->content,
				"date" => $submission_data->date,
				"ip" => $submission_data->ip,
				"ref_id" => $submission_data->ref_id,
				"total" => $submission_data->total,
				"payment_id" => $submission_data->payment_id,
				"transaction_id" => $submission_data->transaction_id,
				"token" => $submission_data->token,
				"user_mail" => $submission_data->user_mail
			),
			array(
				"%d",
				"%s",
				"%s",
				"%s",
				"%s",
				"%s",
				"%s",
				"%d",
				"%s",
				"%s",
				"%s"
			)
		);

		if (!$res) throw new Exception(sprintf(__("Unable to import submission: %s", "ezfc"), var_export($submission_data, true)));

		return true;
	}

	/**
	 * get file path
	 */
	public function get_file($id) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID", "ezfc"));

		$file = $this->wpdb->get_var($this->wpdb->prepare(
			"SELECT file FROM {$this->tables["files"]} WHERE id=%d",
			$id
		));

		// id not found in db
		if (!$file) return Ezfc_Functions::send_message("error", __("No file with the requested ID found.", "ezfc"));
		
		// file not found on server
		if (!file_exists($file)) return Ezfc_Functions::send_message("error", __("The requested file doesn't exist.", "ezfc"));

		return $file;
	}

	public function form_submission_delete($id) {
		$res = $this->wpdb->delete(
			$this->tables["submissions"],
			array("id" => $id),
			array("%d")
		);

		if ($res === false) return Ezfc_Functions::send_message("error", sprintf(__("Unable to delete form submission: %s", "ezfc"), $wpdb->last_error));

		return Ezfc_Functions::send_message("success", __("Submission deleted.", "ezfc"));
	}

	public function form_save_template($id, $form_data=null) {
		// save template from data
		if ($form_data) {
			$form          = $form_data->form;
			$form_elements = (array) $form_data->elements;
			$form_options  = $form_data->options;
		}
		// save template from form
		else {
			$form          = $this->form_get($id);
			$form_elements = $this->form_elements_get($id, true);
			$form_options  = $this->form_get_options($id, true);
		}

		$form_elements = Ezfc_settings::form_elements_prepare_export($form_elements);

		$form_elements_json = json_encode($form_elements);
		$form_options_json  = json_encode($form_options);

		$res = $this->wpdb->insert(
			$this->tables["templates"],
			array(
				"name"    => $form->name,
				"data"    => $form_elements_json,
				"options" => $form_options_json
			),
			array(
				"%s",
				"%s",
				"%s"
			)
		);

		if (!$res) return Ezfc_Functions::send_message("error", __("Could not insert template", "ezfc") . ": " . $this->wpdb->last_error);

		return $this->wpdb->insert_id;
	}

	public function form_template_delete($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["templates"],
			array("id" => $id),
			array("%d")
		);

		if ($res === false) return Ezfc_Functions::send_message("error", __("Could not delete template: {$wpdb->last_error}"));

		return Ezfc_Functions::send_message("success", __("Template deleted.", "ezfc"));
	}

	public function form_update($id, $name) {
		$id = (int) $id;

		$res = $this->wpdb->update(
			$this->tables["forms"],
			array("name" => $name),
			array("id" => $id),
			array("%s"),
			array("%d")
		);

		return $res;
	}

	/**
		update form options (single form via ajax)
	**/
	public function form_update_options($id, $options, $preview=null) {	
		if (count($options) < 1) return Ezfc_Functions::send_message("error", __("No settings", "ezfc"));

		$settings_fields       = $this->get_settings_fields(true);
		$settings_fields_assoc = Ezfc_Functions::array_index_key($settings_fields, "name");
		$css_builder           = new EZ_CSS_Builder(".ezfc-form-{$id}");
		$query_data            = array();
		$return_message        = array();

		foreach ($options as $o_id => $value) {
			$settings_field = json_decode(json_encode($settings_fields[$o_id]), true);

			// check for css
			if (!empty($settings_field["css"]) && !empty($value)) {
				$css_builder->add_css($settings_field["css"], $value);
			}

			if (is_array($value)) {
				$value = serialize($value);
			}
			else {
				$value = Ezfc_settings::validate_option($settings_field, $value, $o_id);

				if (is_array($value) && !empty($value["error"])) {
					$return_message[] = array("error" => $value["error"], "id" => $o_id);
					continue;
				}
			}

			if ($preview === null) {
				$query_data[] = $this->wpdb->prepare(
					"(%d, %d, %s)",
					$id, $o_id, $value
				);
			}
			else {
				$options[$o_id] = array("value" => $value);
			}
		}

		if (!empty($query_data) && $preview === null) {
			$query = "REPLACE INTO {$this->tables["forms_options"]} (f_id, o_id, value) VALUES " . implode(",", $query_data);
			$res = $this->wpdb->query($query);

			if ($res === false) {
				return Ezfc_Functions::send_message("error", $this->wpdb->last_error);
			}
		}

		// write css output
		$css_output = $css_builder->get_output();

		if ($preview === null) {
			$this->wpdb->replace(
				$this->tables["forms_options"],
				array(
					"f_id"  => $id,
					"o_id"  => $settings_fields_assoc["css_custom_styling"]["id"],
					"value" => $css_output
				),
				array(
					"%d",
					"%d",
					"%s"
				)
			);
		}
		else {
			return $options;
		}

		return $return_message;
	}

	/**
		elements
		(deprecated since elements are added upon construction)
	**/
	public function elements_get() {
		return $this->elements;
	}

	public function element_get($id) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID.", "ezfc"));

		return $this->elements[$id];
	}

	/**
		form elements
	**/
	public function form_elements_get($id, $index=false, $key="id", $merge=true, $e_id=false) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID given.", "ezfc"));

		// filter by e_id
		if ($e_id) {
			$res = $this->wpdb->get_results($this->wpdb->prepare(
				"SELECT * FROM {$this->tables["forms_elements"]} WHERE f_id=%d AND e_id=%d ORDER BY position DESC",
				$id, $e_id
			));
		}
		else {
			$res = $this->wpdb->get_results($this->wpdb->prepare(
				"SELECT * FROM {$this->tables["forms_elements"]} WHERE f_id=%d ORDER BY position DESC",
				$id
			));
		}

		if ($merge && count($res) > 0) {
			foreach ($res as &$element) {
				// extension elements (todo)
				if (!isset($this->elements[$element->e_id])) continue;

				$tmp_element = (array) $this->elements[$element->e_id]->data;
				$tmp_array = (array) json_decode($element->data);

				// do not add default element options for payment element
				if ($this->elements[$element->e_id]->type == "payment") {
					$data = json_encode(Ezfc_Functions::array_merge_recursive_ignore_options($tmp_element, $tmp_array));
				}
				// add default element options
				else {
					$data = json_encode(Ezfc_Functions::array_merge_recursive_distinct($tmp_element, $tmp_array));
				}

				$element->data = $data;
			}
		}

		if ($index) $res = Ezfc_Functions::array_index_key($res, $key);

		return $res;
	}

	public function form_element_get($id) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID given.", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["forms_elements"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	public function form_element_add($f_id, $e_id, $data=null, $element_data=array()) {
		$form_elements = $this->form_elements_get($f_id);
		if (count($form_elements) >= 5) return Ezfc_Functions::send_message("error", __("Only 5 elements allowed in the free version."));

		// get default element data
		if (!isset($this->elements[$e_id])) {
			$tmp_element_data = json_decode($data);
			// check for extension
			if (empty($tmp_element_data)) return Ezfc_Functions::send_message("error", __("Unable to import element.", "ezfc"));
			
			if (property_exists($tmp_element_data, "extension") && !isset($this->elements[$tmp_element_data->extension])) return Ezfc_Functions::send_message("error", __("Unable to find element. Perhaps you are missing an extension?", "ezfc"));

			$element_default_data = $this->elements[$tmp_element_data->extension];
		}
		else {
			// default element
			$element_default_data = $this->elements[$e_id];
		}

		$default_data = $element_default_data->data;

		// put e_id into default data
		$default_data->e_id = $e_id;

		// ... and merge it with the data provided
		if ($data) {
			$element_default_data_array = json_decode(json_encode($default_data), true);

			if (!is_array($data)) {
				$data = json_decode($data, true);
			}

			$default_data = Ezfc_Functions::array_merge_recursive_distinct($element_default_data_array, $data);
		}

		if (is_array($default_data)) {
			$default_data = json_decode(json_encode($default_data));
		}

		// set position
		$position = 1;
		$position_update = $position;
		if (isset($element_data["position"])) {
			$position = (int) $element_data["position"];
		}

		// update position for other elements
		$this->wpdb->query($this->wpdb->prepare("
			UPDATE {$this->tables["forms_elements"]}
			SET position = position + 1
			WHERE f_id = %d AND position >= %d
		", $f_id, $position));

		// group id
		if (isset($element_data["group_id"])) $default_data->group_id = $element_data["group_id"];

		// convert some element properties to htmlentities
		$default_data_object = is_object($default_data) ? $default_data : json_decode($default_data);
		$default_data_object = $this->convert_html_fields($default_data_object, "decode");
		$default_data        = json_encode($default_data_object);

		$res = $this->wpdb->insert(
			$this->tables["forms_elements"],
			array(
				"f_id"     => $f_id,
				"e_id"     => $e_id,
				"data"     => $default_data,
				"position" => $position
			),
			array(
				"%d",
				"%d",
				"%s",
				"%d"
			)
		);

		if (!$res) return Ezfc_Functions::send_message("error", __("Could not insert element to form", "ezfc") . ": " . $this->wpdb->last_error);

		return $this->wpdb->insert_id;
	}

	public function form_element_delete($id, $child_element_ids = null) {
		$element = $this->form_element_get($id);

		// element was already removed
		if (!$element) return;

		$f_id = $this->wpdb->get_var($this->wpdb->prepare(
			"SELECT f_id FROM {$this->tables["forms_elements"]} WHERE id = %d",
			$id
		));

		$res = $this->wpdb->delete(
			$this->tables["forms_elements"],
			array("id" => $id),
			array("%d")
		);

		if ($child_element_ids) {
			$child_element_array = explode(",", $child_element_ids);

			foreach ($child_element_array as $e_id) {
				$this->form_element_delete($e_id);
			}
		}
		
		// update position
		$this->wpdb->query($this->wpdb->prepare(
			"UPDATE {$this->tables["forms_elements"]}
			SET position = position - 1
			WHERE f_id = %d AND position >= %d",
			$f_id, $element->position
		));

		return Ezfc_Functions::send_message("success", __("Element deleted.", "ezfc"));
	}

	public function form_element_duplicate($id, $element_data=null) {
		$id = (int) $id;

		$element = $this->form_element_get($id);
		if (!$element) {
			return Ezfc_Functions::send_message("error", __("Failed to duplicate element.", "ezfc"));
		}

		$element_data_insert = $element->data;
		if ($element_data) {
			// convert data into array
			$element_data_default_array = json_decode($element->data, true);

			// merge arrays
			$element_data_array = Ezfc_Functions::array_merge_recursive_distinct($element_data_default_array, $element_data);

			// convert into json string again
			$element_data_insert = json_encode($element_data_array);
		}

		$new_element_id = $this->form_element_add($element->f_id, $element->e_id, $element_data_insert);
		return $this->form_element_get($new_element_id);
	}

	public function get_elements_by_ids($e_ids=array()) {
		if (empty($e_ids)) return Ezfc_Functions::send_message("error", __("Empty list.", "ezfc"));

		$ids_sanitized = array();
		foreach ($e_ids as $e_id) {
			$ids_sanitized[] = intval($e_id);
		}
		$ids_sql = implode(",", $ids_sanitized);

		$elements = $this->wpdb->get_results(
			"SELECT id, e_id, data
			FROM {$this->tables["forms_elements"]}
			WHERE id IN ({$ids_sql})"
		);

		return $elements;
	}

	/**
		duplicates all elements in a group and the group itself
	**/
	public function form_element_duplicate_group($elements, $element_new_group_ids) {
		$elements_to_duplicate_array = explode(",", $elements);

		if (count($elements_to_duplicate_array) < 1) return Ezfc_Functions::send_message("error", __("No elements to duplicate.", "ezfc"));

		// old group ids are stored as key, new ids as value
		// $group_ids[old_group_id] = new_group_id;
		$elements_to_duplicate = array();
		$group_ids             = array();
		$new_elements          = array();
		$old_elements          = array();

		foreach ($elements_to_duplicate_array as $id) {
			$element = $this->form_element_get($id);

			$element_data = json_decode($element->data, true);
			$new_element  = $this->form_element_duplicate($element->id);

			if (!isset($this->elements[$element->e_id])) continue;
			// add to array
			$elements_to_duplicate[] = $element;

			// save old/new group id
			if ($this->elements[$element->e_id]->type == "group") {
				$group_ids[$element->id] = $new_element->id;
			}

			$new_elements[] = $new_element->id;
			$old_elements[] = $id;
		}

		$new_elements_return = array();
		foreach ($new_elements as $i => $id) {
			$element = $elements_to_duplicate[$i];
			$element_data = json_decode($element->data, true);

			// update to new group id
			if (isset($element_data["group_id"]) && isset($group_ids[$element_new_group_ids[$old_elements[$i]]])) {
				$element_data["group_id"] = $group_ids[$element_new_group_ids[$old_elements[$i]]];
				$element_data = json_encode($element_data);

				$res = $this->wpdb->update(
					$this->tables["forms_elements"],
					array("data" => $element_data),
					array("id" => $id),
					array("%s"),
					array("%d")
				);
			}

			$new_elements_return[] = $this->form_element_get($id);
		}

		// reverse order
		$new_elements_return = array_reverse($new_elements_return);

		return array("elements" => $new_elements_return);
	}

	public function form_elements_save($id, $data) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID.", "ezfc"));

		// no elements present --> save complete
		if (count($data) < 1) return 1;

		$max = count($data);
		foreach ($data as $id => $element) {
			// element was not changed, so only update the position
			if (!empty($element["__noupdate__"])) {
				$res = $this->wpdb->update(
					$this->tables["forms_elements"],
					array("position" => $max--),
					array("id" => $id),
					array("%d"),
					array("%d")
				);

				continue;
			}

			$element_data = $this->convert_html_fields($element, "encode");
			$element_data = json_encode($element_data);

			$res = $this->wpdb->update(
				$this->tables["forms_elements"],
				array(
					"data"     => $element_data,
					"position" => $max--
				),
				array("id" => $id),
				array(
					"%s",
					"%d"
				),
				array("%d")
			);

			if (!$res && $res!=0) return Ezfc_Functions::send_message("error", __("Form elements update error:", "ezfc") . $this->wpdb->last_error);
		}

		return 1;
	}

	public function form_save_preview($f_id, $form_elements, $form_options_tmp) {
		$form_elements = $this->form_elements_save_preview($f_id, $form_elements);
		$form_options  = $this->form_update_options(0, $form_options_tmp, $f_id);

		$data = json_encode(array(
			"form"     => array("id" => $f_id),
			"elements" => $form_elements,
			"options"  => $form_options
		));

		$query = $this->wpdb->prepare(
			"INSERT INTO {$this->tables["preview"]} (f_id, data) VALUES (%d, %s) ON DUPLICATE KEY UPDATE data = %s",
			$f_id, $data, $data
		);

		$res = $this->wpdb->query($query);

		return $res;
	}

	public function form_elements_save_preview($f_id, $form_elements = array()) {
		// no elements present --> save complete
		if (count($form_elements) < 1) return $form_elements;

		$max = count($form_elements);
		foreach ($form_elements as $id => &$element) {
			$tmp_element = array(
				"id" => $id,
				"f_id" => $f_id,
				"e_id" => $element["e_id"],
				"position" => $max--
			);

			$element_data = $this->convert_html_fields($element, "encode");
			$element_data = json_encode($element_data);

			$tmp_element["data"] = $element_data;
			$element = $tmp_element;
		}

		return $form_elements;
	}


	/**
		form templates
	**/
	public function form_templates_get() {
		// get installed templates
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["templates"]} ORDER BY id DESC");

		return $res;
	}

	public function form_template_get($id) {
		if (!$id) return Ezfc_Functions::send_message("error", __("No ID given.", "ezfc"));

		// check for inbuilt template
		if (substr($id, 0, 3) == "ez-") {
			require_once(EZFC_PATH . "ext/class.ezfc_templates.php");
			$res = json_decode(json_encode(Ezfc_Templates::get_template($id)));
		}
		else {
			$res = $this->wpdb->get_row($this->wpdb->prepare(
				"SELECT * FROM {$this->tables["templates"]} WHERE id=%d",
				$id
			));
		}

		return $res;
	}


	/**
		form settings
	**/
	public function get_settings_fields($flat = false) {
		return Ezfc_settings::get_form_options($flat);
	}

	/**
		form default settings
	**/
	public function get_options($flat = false) {
		$settings = $this->get_settings_fields();

		// merge values
		$settings_db = Ezfc_Functions::array_index_key($this->wpdb->get_results("SELECT id, value FROM {$this->tables["options"]}", ARRAY_A), "id");

		foreach ($settings as $cat => &$settings_cat) {
			foreach ($settings_cat as &$setting) {
				if (!empty($settings_db[$setting["id"]])) {
					$setting["value"] = $settings_db[$setting["id"]]["value"];
				}
			}
		}

		if ($flat) return Ezfc_settings::flatten($settings);

		return $settings;
	}
	
	/**
		update form options (form settings page)
	**/
	public function update_options($settings, $overwrite=0, $upgrade=0) {
		$settings_fields       = $this->get_settings_fields(true);
		$settings_fields_assoc = Ezfc_Functions::array_index_key($settings_fields, "name");
		$css_builder           = new EZ_CSS_Builder();
		$return_message        = array();

		if (!$settings) $settings = array();
		
		foreach ($settings as $o_id => $value) {
			if (!isset($settings_fields[$o_id])) continue;

			$settings_field = json_decode(json_encode($settings_fields[$o_id]), true);

			// check for css
			if (!empty($settings_field["css"]) && !empty($value)) {
				$css_builder->add_css($settings_field["css"], $value);
			}

			if (is_array($value)) {
				$value = serialize($value);
			}
			else {
				$value = Ezfc_settings::validate_option($settings_field, $value, $o_id);

				if (is_array($value) && !empty($value["error"])) {
					$return_message[] = array("error" => $value["error"], "id" => $o_id);
					continue;
				}
			}

			// update option in db
			$res = $this->wpdb->replace(
				$this->tables["options"],
				array(
					"id"    => $o_id,
					"name"  => $settings_field["name"],
					"value" => $value
				),
				array(
					"%d",
					"%s",
					"%s"
				)
			);

			if ($res === false) {
				return Ezfc_Functions::send_message("error", $this->wpdb->last_error);
			}

			// overwrite settings, skip on name
			if ($overwrite == 0) continue;

			$res = $this->wpdb->update(
				$this->tables["forms_options"],
				array("value" => $value),
				array("o_id" => $o_id),
				array("%s"),
				array("%d")
			);
		}

		// write css output
		$css_output = $css_builder->get_output();

		$this->wpdb->replace(
			$this->tables["options"],
			array(
				"id" => $settings_fields_assoc["css_custom_styling"]["id"],
				"name"  => "css_custom_styling",
				"value" => $css_output
			),
			array(
				"%d",
				"%s",
				"%s"
			)
		);

		// manual upgrade
		if ($upgrade == 1) {
			$this->setup_db();
			$this->upgrade();
		}

		return $return_message;
	}

	public function add_missing_element_options($f_id=-1) {
		$elements = Ezfc_Functions::array_index_key(Ezfc_settings::get_elements(), "id");

		if ($f_id != -1) {
			$forms = array($this->form_get($f_id));
		}
		else {
			$forms = $this->forms_get();
		}

		foreach ($forms as $fi => $form) {
			if (empty($form->id)) continue;

			// insert missing options
			$query = "SELECT 
				o.id, o.value
			FROM
				{$this->tables["options"]} as o
				left join {$this->tables["forms_options"]} as fo on (o.id = fo.o_id AND fo.f_id = {$form->id})
			WHERE
				fo.o_id is NULL";

			$missing_options = $this->wpdb->get_results($query);
			if (count($missing_options) > 0) {
				foreach ($missing_options as $mo) {
					$mo_res = $this->wpdb->insert(
						$this->tables["forms_options"],
						array(
							"f_id"  => $form->id,
							"o_id"  => $mo->id,
							"value" => $mo->value
						),
						array(
							"%d",
							"%d",
							"%s"
						)
					);

					if ($mo_res === false) {
						return Ezfc_Functions::send_message("error", $this->wpdb->last_error);
					}
				}
			}

			// insert missing element options
			$form_elements_data = $this->wpdb->get_results($this->wpdb->prepare("SELECT id, e_id, data FROM {$this->tables["forms_elements"]} WHERE f_id=%d", $form->id));
			if (count($form_elements_data) > 0) {
				foreach ($form_elements_data as $element_data) {
					// invalid element or extension (skip for now)
					if ($element_data->e_id == 0 || !$elements[$element_data->e_id]) continue;

					$data_array    = $elements[$element_data->e_id]->data;
					$data_el_array = $element_data->data;

					if (!is_array($elements[$element_data->e_id]->data)) {
						$data_array    = json_decode(json_encode($elements[$element_data->e_id]->data), true);
					}
					if (!is_array($data_el_array)) {
						$data_el_array = json_decode($element_data->data, true);
					}

					if (!is_array($data_array) || !is_array($data_el_array)) continue;
					
					// merge global element data with form element data
					$data_merged = Ezfc_Functions::array_merge_recursive_distinct($data_array, $data_el_array);
					$data_new    = json_encode($data_merged);

					$res = $this->wpdb->update(
						$this->tables["forms_elements"],
						array("data" => $data_new),
						array("id" => $element_data->id),
						array("%s"),
						array("%d")
					);

					if ($res === false) {
						return Ezfc_Functions::send_message("error", $this->wpdb->last_error);
					}
				}
			}
		}
	}


	public function form_get_preview($id) {
		if ($id === null) return Ezfc_Functions::send_message("error", __("No preview id found.", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["preview"]} WHERE f_id=%d",
			$id
		));

		if (!$res) return Ezfc_Functions::send_message("error", __("No preview form with f_id={$id} found.", "ezfc"));

		// convert form to object
		$form = json_decode($res->data);

		if (count($form->elements) > 0) {
			// replace calculate positions with target ids
			$form_elements = Ezfc_Functions::array_index_key($form->elements, "position");

			$form_elements = Ezfc_settings::form_elements_prepare_export($form_elements);
		}

		return $form;
	}


	public function setup_db() {
		$query = file_get_contents(EZFC_PATH . "db.sql");
		if (!$query) {
			die("Error opening file 'db.sql'");
		}

		$query_replaced = str_replace("__PREFIX__", $this->wpdb->prefix, $query);
		$this->execute_multiline_sql($query_replaced);
	}

	public function upgrade() {
		$old_version = get_option("ezfc_version", "1.0");

		/**
			create folders
		**/
		// upload
		Ezfc_Functions::create_upload_dir(Ezfc_Functions::$folders["uploads"]);
		// pdf
		$pdf_dir = Ezfc_Functions::create_upload_dir(Ezfc_Functions::$folders["pdf"]);
		if (file_exists($pdf_dir)) {
			update_option("ezfc_ext_pdf_dirname", $pdf_dir);
		}
		// tmp
		Ezfc_Functions::create_upload_dir(Ezfc_Functions::$folders["tmp"], true);

		// use seed for pdf filenames
		$pdf_seed = get_option("ezfc_ext_pdf_seed", "");
		if (empty($pdf_seed)) {
			$filename_seed = md5(microtime(true));
			update_option("ezfc_ext_pdf_seed", $filename_seed);
		}

		flush_rewrite_rules(true);

		// do not perform table changes when old version is current version
		if (version_compare($old_version, EZFC_VERSION) == 0) return;

		// version specific upgrades
		$query_upgrade = array();

		if (version_compare($old_version, "2.7.4.0") < 0) {
			// change theme values (int to string) as theme integration was changed
			$query_upgrade[] = "UPDATE `{$this->tables["forms_options"]}` SET `value`='default' WHERE `o_id`=19 AND `value`=1;";
			$query_upgrade[] = "UPDATE `{$this->tables["forms_options"]}` SET `value`='default-alternative' WHERE `o_id`=19 AND `value`=2;";
			$query_upgrade[] = "UPDATE `{$this->tables["options"]}` SET `value`='default' WHERE `name`='theme' AND `value`=1;";
			$query_upgrade[] = "UPDATE `{$this->tables["options"]}` SET `value`='default-alternative' WHERE `name`='theme' AND `value`=2;";

			// remove group element (accidentally put in production)
			$query_upgrade[] = "DELETE FROM `{$this->tables["elements"]}` WHERE `name`='Group';";
			$query_upgrade[] = "DELETE FROM `{$this->tables["forms_elements"]}` WHERE `e_id`=21;";
		}

		// add form preview table
		if (version_compare($old_version, "2.9.4.0") < 0) {
			$query_upgrade[] = "CREATE TABLE IF NOT EXISTS `{$this->tables["preview"]}` (
			  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			  `f_id` INT(11) NOT NULL,
			  `data` MEDIUMTEXT NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE INDEX `f_id` (`f_id`)
			) COLLATE='latin1_swedish_ci' ENGINE=InnoDB;";
		}

		if (version_compare($old_version, "2.10.4.5") < 0) {
			// template table increase size
			$query_upgrade[] = "ALTER TABLE `{$this->tables["templates"]}` CHANGE COLUMN `name` `name` VARCHAR(200) NOT NULL AFTER `id`, CHANGE COLUMN `data` `data` MEDIUMTEXT NOT NULL AFTER `name`, CHANGE COLUMN `options` `options` MEDIUMTEXT NOT NULL AFTER `data`;";

			/*// add reference ID column for payments (pp ipn)
			$query_upgrade[] = "ALTER TABLE `{$this->tables["submissions"]}` ADD COLUMN `add_data` MEDIUMTEXT NOT NULL AFTER `user_mail`;";

			// add group id column for form elements
			$query_upgrade[] = "ALTER TABLE `{$this->tables["forms_elements"]}` ADD COLUMN `group_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `position`;";*/
		}

		// do it already!
		foreach ($query_upgrade as $q) {
			$res = $this->wpdb->query($q);
		}

		// all upgrades finished, update version
		update_option("ezfc_version", EZFC_VERSION);
	}

	/**
		get all export data
	**/
	public function get_export_data($include_submissions = false) {
		$forms_export           = array();
		$global_settings_export = array();
		$settings_export        = array();
		$submissions_export     = array();

		// forms
		$forms = $this->forms_get();
		if (count($forms) > 0) {
			foreach ($forms as $f) {
				$forms_export[$f->id] = $this->form_get_export_data($f);

				if ($include_submissions) {
					$submissions_export[$f->id] = $this->form_get_submissions($f->id, "date", "ASC");
				}
			}
		}

		// global settings
		$global_settings_tmp = Ezfc_settings::get_global_settings(true);
		foreach ($global_settings_tmp as $name => $setting) {
			$global_settings_export[$name] = $setting["value"];
		}

		// form settings
		$settings_tmp = $this->get_options(true);
		if (count($settings_tmp) > 0) {
			foreach ($settings_tmp as $s) {
				$settings_export[$s["id"]] = $s["value"];
			}
		}

		$export_data = array(
			"forms"           => $forms_export,
			"global_settings" => $global_settings_export,
			"settings"        => $settings_export
		);

		// include submissions
		if ($include_submissions) $export_data["submissions"] = $submissions_export;

		return $export_data;
	}

	/**
		get form export data
	**/
	public function form_get_export_data($form=null, $f_id=null, $preview_id=null) {
		if (!$form && $f_id) $form = $this->form_get($f_id);
		$form_elements = $this->form_elements_get($form->id);
		$form_options  = $this->form_get_options($form->id, true);

		if (count($form_elements) > 0) {
			$form_elements = Ezfc_settings::form_elements_prepare_export($form_elements);
			$elements_indexed = Ezfc_Functions::array_index_key($form_elements, "id");

			foreach ($form_elements as $i => $e) {
				$data = json_decode($e->data);
				if (!is_object($data)) continue;

				// convert some element properties to htmlentities
				$data = $this->convert_html_fields($data, "encode");
				$form_elements[$i]->data = json_encode($data);
			}

			usort($form_elements, array($this, "sort_by_position_desc"));
		}

		return array(
			"form"     => $form,
			"elements" => $form_elements,
			"options"  => $form_options,
			"version"  => EZFC_VERSION
		);
	}


	/**
		export form
	**/
	public function form_get_export_download($f_id) {
		global $wp_filesystem;

		$export_data      = $this->form_get_export_data(null, $f_id);
		$export_data_json = json_encode($export_data);

		// create tmp dir
		$save_dir = Ezfc_Functions::create_upload_dir(Ezfc_Functions::$folders["tmp"], false);
		$file     = EZFC_Functions::zip_write($export_data_json, "ezfc_form_export_data.json", $save_dir);

		return $file;
	}

	/**
		get export form url
	**/
	public function form_get_export_download_url($hash) {
		$url = home_url("/") . "/ezfc-download-export-form.php?download_export_form=1&hash={$hash}";

		return $url;
	}

	/**
		download export form
	**/
	public function form_download_export_file($filename) {
		$filename = sanitize_file_name($filename);

		$upload_dir = wp_upload_dir();
		$file       = $upload_dir["basedir"] . "/" . Ezfc_Functions::$folders["tmp"] . "/{$filename}";

		if (!file_exists($file)) {
			throw new Exception(sprintf(__("File %s does not exist.", "ezfc"), $file));
			die();
		}

		Ezfc_Functions::send_file_to_browser($file);
	}

	/**
		merge elements with default element values
	**/
	public function merge_default_elements($elements, $index = true) {
		$elements_array = array();

		foreach ($elements as &$element) {
			// extension elements (todo)
			if (!isset($this->elements[$element->e_id])) continue;

			$tmp_element = (array) $this->elements[$element->e_id]->data;
			$tmp_array = (array) json_decode($element->data);
			$data = json_encode(Ezfc_Functions::array_merge_recursive_distinct($tmp_element, $tmp_array));

			$element->data = $data;

			$elements_array[] = $element;
		}

		if ($index) $elements_array = Ezfc_Functions::array_index_key($elements_array, $key);

		return $elements_array;
	}


	public function convert_html_fields($object, $convert="encode") {
		$convert_html_keys = array(
			"name"    => 1,
			"label"   => 1,
			"html"    => 1,
			"options" => array("text")
		);

		// convert array to object
		if (is_array($object)) $object = json_decode(json_encode($object));
		// invalid data -> return object
		if (!is_object($object)) return $object;

		foreach ($convert_html_keys as $key => $val) {
			if (!property_exists($object, $key)) continue;

			// options
			if (is_array($val) && (is_array($object->$key) || is_object($object->$key))) {
				foreach ($val as $array_val) {
					if (count($object->$key) < 1) continue;

					foreach ($object->$key as &$option_array) {
						if (!property_exists($option_array, $array_val)) continue;

						$option_array->$array_val = $this->normalize_encoding($option_array->$array_val, $convert);
					}
				}
			}
			// name, label etc.
			else {
				$object->$key = $this->normalize_encoding($object->$key, $convert);
			}
		}

		return $object;
	}

	// this will encode/decode entities correctly (also &acute; and other damned characters!)
	public function normalize_encoding($string, $encode = "encode") {
		// encode
		if ($encode == "encode") {
			$string = htmlentities($string, ENT_COMPAT, "UTF-8", false);
		}
		// decode
		else {
			if (function_exists("mb_convert_encoding")) {
				$string = mb_convert_encoding($string, "HTML-ENTITIES", "UTF-8");
			}
			
			$string = html_entity_decode($string, ENT_COMPAT, "UTF-8");
		}

		return $string;
	}


	/**
		import all data
	**/
	public function import_data($data, $stripslashes = true, $preserve_ids = false, $include_submissions = false) {
		$sanitized_json = $stripslashes ? stripslashes($data) : $data;
		$data_json      = json_decode($sanitized_json);

		if (!$data_json) return Ezfc_Functions::send_message("error", __("Corrupt form data.", "ezfc"));

		// import global settings
		if (property_exists($data_json, "global_settings")) {
			foreach ($data_json->global_settings as $name => $value) {
				update_option("ezfc_{$name}", $value);
			}
		}

		// import settings
		if (property_exists($data_json, "settings")) {
			$this->update_options($data_json->settings, 0, 0);
		}

		// import forms + form settings
		if (property_exists($data_json, "forms") && count($data_json->forms) > 0) {
			foreach ($data_json->forms as $f_id => $f) {
				$new_form_id = $this->form_import($f, false, $preserve_ids);

				if (property_exists($data_json, "submissions") && !empty($data_json->submissions->$f_id) && $include_submissions) {
					$this->submissions_import($data_json->submissions->$f_id, $new_form_id);
				}
			}
		}

		return Ezfc_Functions::send_message("success", __("Data import complete.", "ezfc"));
	}

	/**
		test mail
	**/
	public function send_test_mail($recipient, $sendername = null, $attachment = null) {
		$subject = __("ez Form Calculator Test Email", "ezfc");
		$text    = __("This is a test email sent by ez Form Calculator.", "ezfc");

		// use smtp
		if (get_option("ezfc_email_smtp_enabled") == 1) {
			$mail = Ezfc_Functions::smtp_setup();

			$mail->addAddress($recipient);
			$mail->Subject   = $subject;
			$mail->Body      = $text;
			
			if (get_option("ezfc_debug_mode", 0) > 0) $mail->SMTPDebug = 2;

			if ($sendername) {
				$mail->SetFrom($sendername, "ez Form Calculator", 0);
			}

			if (!empty($attachment) && file_exists($attachment)) {
				$mail->AddAttachment($attachment);
			}

			if ($mail->send()) {
				$res = __("Mail successfully sent.", "ezfc");
			}
			else {
				$res = __("Unable to send mails:", "ezfc") . $mail->ErrorInfo;
			}

			return $res;
		}
		else {
			$headers = "";
			if ($sendername) {
				$headers .= "From: {$sendername}\r\n";
			}

			$res = wp_mail(
				$recipient,
				$subject,
				$text,
				$headers,
				$attachment
			);

			return $res==1 ? __("Mail successfully sent.", "ezfc") : __("Unable to send mails.", "ezfc");
		}
	}

	/**
		quick add
	**/
	public function quick_add($f_id, $text) {
		require_once(EZFC_PATH . "ext/class.quick_add.php");
		$qa = new EZFC_Extension_Quick_Add();
		$qa->set_text($text);

		if (!$qa->parse()) return Ezfc_Functions::send_message("error", "No good.");

		// add to form
		$lines = $qa->get_result();
		foreach ($lines as $line) {
			$data = array(
				"name"  => $line["name"],
				"label" => $line["name"]
			);

			if (is_array($line["data"])) $data = array_merge($data, $line["data"]);

			$this->form_element_add($f_id, $line["e_id"], $data);
		}

		$ret = array(
			"elements" => $this->form_elements_get($f_id),
			"form"     => $this->form_get($f_id),
			"options"  => $this->form_get_options($f_id)
		);

		return $ret;
	}

	/**
		advanced actions
	**/
	// set form element values
	public function set_form_element_values($f_id, $values_array) {
		if (!is_array($values_array) || count($values_array) < 1) throw new Exception("Invalid argument.");

		$elements = $this->form_elements_get($f_id);

		if (empty($elements)) return Ezfc_Functions::send_message("error", __("No elements found.", "ezfc"));

		foreach ($elements as $element) {
			$data = json_decode($element->data);

			foreach ($values_array as $option_name => $option_value) {
				if (!property_exists($data, $option_name)) continue;

				$data->$option_name = $option_value;
			}

			$data = json_encode($data);

			$this->wpdb->update(
				$this->tables["forms_elements"],
				array("data" => $data),
				array("id" => $element->id),
				array("%s"),
				array("%d")
			);
		}

		return Ezfc_Functions::send_message("message", __("Elements updated.", "ezfc"));
	}

	// subtotal add to price change
	public function subtotal_add_to_price_change($f_id, $new_value) {
		// only get subtotal elements (e_id = 15)
		$elements = $this->form_elements_get($f_id, false, false, true, 15);

		if (empty($elements)) return Ezfc_Functions::send_message("error", __("No Subtotal elements found.", "ezfc"));

		foreach ($elements as $element) {
			$data = json_decode($element->data);
			$data->add_to_price = $new_value;
			$data = json_encode($data);

			$this->wpdb->update(
				$this->tables["forms_elements"],
				array("data" => $data),
				array("id" => $element->id),
				array("%s"),
				array("%d")
			);
		}

		return Ezfc_Functions::send_message("message", __("Elements updated.", "ezfc"));
	}


	private function execute_multiline_sql($sql, $delim=";") {
		global $wpdb;
		
		$sqlParts = $this->split_sql_file($sql, $delim);
		foreach($sqlParts as $part) {
			$res = $wpdb->query($part);

			if ($res === false) {
				$wpdb->print_error();
				return false;
			}
		}

		return true;
	}

	private function split_sql_file($sql, $delimiter) {
	   // Split up our string into "possible" SQL statements.
	   $tokens = explode($delimiter, $sql);

	   // try to save mem.
	   $sql = "";
	   $output = array();

	   // we don't actually care about the matches preg gives us.
	   $matches = array();

	   // this is faster than calling count($oktens) every time thru the loop.
	   $token_count = count($tokens);
	   for ($i = 0; $i < $token_count; $i++)
	   {
		  // Don't wanna add an empty string as the last thing in the array.
		  if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0)))
		  {
			 // This is the total number of single quotes in the token.
			 $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
			 // Counts single quotes that are preceded by an odd number of backslashes,
			 // which means they're escaped quotes.
			 $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

			 $unescaped_quotes = $total_quotes - $escaped_quotes;

			 // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
			 if (($unescaped_quotes % 2) == 0)
			 {
				// It's a complete sql statement.
				$output[] = $tokens[$i];
				// save memory.
				$tokens[$i] = "";
			 }
			 else
			 {
				// incomplete sql statement. keep adding tokens until we have a complete one.
				// $temp will hold what we have so far.
				$temp = $tokens[$i] . $delimiter;
				// save memory..
				$tokens[$i] = "";

				// Do we have a complete statement yet?
				$complete_stmt = false;

				for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++)
				{
				   // This is the total number of single quotes in the token.
				   $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
				   // Counts single quotes that are preceded by an odd number of backslashes,
				   // which means they're escaped quotes.
				   $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

				   $unescaped_quotes = $total_quotes - $escaped_quotes;

				   if (($unescaped_quotes % 2) == 1)
				   {
					  // odd number of unescaped quotes. In combination with the previous incomplete
					  // statement(s), we now have a complete statement. (2 odds always make an even)
					  $output[] = $temp . $tokens[$j];

					  // save memory.
					  $tokens[$j] = "";
					  $temp = "";

					  // exit the loop.
					  $complete_stmt = true;
					  // make sure the outer loop continues at the right point.
					  $i = $j;
				   }
				   else
				   {
					  // even number of unescaped quotes. We still don't have a complete statement.
					  // (1 odd and 1 even always make an odd)
					  $temp .= $tokens[$j] . $delimiter;
					  // save memory.
					  $tokens[$j] = "";
				   }

				} // for..
			 } // else
		  }
	   }

	   return $output;
	}

	public function removeslashes($string)	{
		$string=implode("",explode("\\",$string));
		return stripslashes(trim($string));
	}

	public function sort_by_position($a, $b) {
		if (is_object($a)) {
			if (!property_exists($a, "position")) return 0;

			return $a->position > $b->position;
		}
		else if (is_array($a)) {
			if (!isset($a["position"])) return 0;

			return $a["position"] > $b["position"];
		}
	}

	public function sort_by_position_desc($a, $b) {
		if (is_object($a)) {
			if (!property_exists($a, "position")) return 0;

			return $b->position > $a->position;
		}
		else if (is_array($a)) {
			if (!isset($a["position"])) return 0;

			return $b["position"] > $a["position"];
		}
	}

	// check if current user is allowed to manage backend things
	public function validate_user($nonce_verify, $nonce_name) {
		$role = get_option("ezfc_user_roles", "administrator");

		if (!current_user_can($role)) die();

		check_admin_referer($nonce_verify, $nonce_name);

		return true;
	}

	// wrapper for deprecated extensions / customizations
	public function array_index_key($array, $key) {
		return Ezfc_Functions::array_index_key($array, $key);
	}

	public function send_message($type, $msg = "", $id = 0) {
		Ezfc_Functions::send_message($type, $msg, $id);
	}

	// instance
	public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __clone() {}
    public function __wakeup() {}
}