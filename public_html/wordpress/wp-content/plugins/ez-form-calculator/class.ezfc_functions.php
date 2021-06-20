<?php

require_once(EZFC_PATH . "class.ezfc_settings.php");
require_once(EZFC_PATH . "ezplugins/class.ez_css.php");

abstract class Ezfc_Functions {
	public static $plugin_slug = "ez-form-calculator-premium";
	public static $plugin_slug_short = "ezfc";
	public static $plugin_register_license_url = "https://www.ezplugins.de/licensing/register-license.php?slug=ezfc";
	public static $templates_remote_url = "https://www.ezplugins.de/template-server/templates.php?slug=ezfc";
	public static $test_url = "https://www.ezplugins.de/licensing/test.php?slug=ezfc";

	// upload folders
	public static $folders = array(
		"pdf"     => "ezfc-pdf",
		"tmp"     => "ezfc-tmp",
		"uploads" => "ezfc-uploads"
	);

	public static function array_empty($array = null) {
		if (!is_array($array)) return true;
	 
		foreach (array_values($array) as $value) {
			if ($value == "0") return false;
			if (!empty($value)) return false;
		}
	 
		return true;	 
	}

	public static function array_merge_recursive_distinct() {
		$arrays = func_get_args();
		$base = array_shift($arrays);

		foreach ($arrays as $array) {
			reset($base);

			while (list($key, $value) = @each($array)) {
				if (is_array($value) && @is_array($base[$key])) {
					$base[$key] = self::array_merge_recursive_distinct($base[$key], $value);
				} else {
					$base[$key] = $value;
				}
			}
		}

		return $base;
	}

	public static function array_merge_recursive_existing_keys() {
		$arrays = func_get_args();
		$base = array_shift($arrays);

		foreach ($arrays as $array) {
			reset($base);

			while (list($key, $value) = @each($array)) {
				if (is_array($value) && @is_array($base[$key])) {
					$base[$key] = self::array_merge_recursive_distinct($base[$key], $value);
				} else if (isset($base[$key])) {
					$base[$key] = $value;
				}
			}
		}

		return $base;
	}

	public static function array_merge_recursive_ignore_options() {
		$arrays = func_get_args();
		$base = array_shift($arrays);

		foreach ($arrays as $array) {
			reset($base);

			while (list($key, $value) = @each($array)) {
				if (is_array($value) && @is_array($base[$key]) && $key != "options") {
					$base[$key] = self::array_merge_recursive_ignore_options($base[$key], $value);
				} else {
					$base[$key] = $value;
				}
			}
		}

		return $base;
	}

	public static function get_datepicker_date_from_format($date_format, $date, $convert_jqueryui_format = false) {
		$date_format = $convert_jqueryui_format ? self::date_jqueryui_to_php($date_format) : $date_format;

		return DateTime::createFromFormat($date_format, $date);
	}

	public static function check_valid_date($date_format, $date, $convert_jqueryui_format = false) {
		$check_date = self::get_datepicker_date_from_format($date_format, $date, $convert_jqueryui_format);

		return ($check_date !== false);
	}

	public static function count_days_format($format, $from, $to, $workdays_only = false) {
		if (!self::check_valid_date($format, $from, true) || !self::check_valid_date($format, $to, true)) return 0;
		
		$datepicker_format = self::date_jqueryui_to_php($format);

		$date_from = DateTime::createFromFormat($datepicker_format, $from);
		$date_to   = DateTime::createFromFormat($datepicker_format, $to);

		if ($workdays_only) {
			$days = self::number_of_working_days($date_from, $date_to);
		}
		else {
			$days = $date_to->diff($date_from)->format("%a");
		}

		return $days;
	}

	public static function is_countable($var) {
		if (!function_exists("is_countable")) {
	        return (is_array($var) || $var instanceof Countable);
		}
		else {
			return is_countable($var);
		}
	}


	// thanks to http://stackoverflow.com/users/67332/glavi%c4%87
	public static function number_of_working_days($from, $to) {
		$workingDays = array(1, 2, 3, 4, 5); // date format = N (1 = Monday, ...)
		$holidayDays = apply_filters("ezfc_daterange_holidays", array()); // array('*-12-25', '*-01-01', '2013-12-23'); variable and fixed holidays

		$to->modify('+1 day');
		$interval = new DateInterval('P1D');
		$periods = new DatePeriod($from, $interval, $to);

		$days = 0;
		foreach ($periods as $period) {
			if (!in_array($period->format('N'), $workingDays)) continue;
			if (in_array($period->format('Y-m-d'), $holidayDays)) continue;
			if (in_array($period->format('*-m-d'), $holidayDays)) continue;
			$days++;
		}

		return $days;
	}

	public static function date_jqueryui_to_php($format) {
		$format_array = array(
			//   Day
			'dd' => 'd',
			'DD' => 'l',
			'd'  => 'j',
			'o'  => 'z',
			//   Month
			'MM' => 'F',
			'mm' => 'm',
			'M'  => 'M',
			'm'  => 'n',
			//   Year
			'yy' => 'Y',
			'y'  => 'y',
		);

		$format_ui     = array_keys($format_array);
		$format_php    = array_values($format_array);
		$output_format = "";

		$i = 0;
		while (isset($format[$i])) {
			$char   = $format[$i];
			$chars  = $format[$i];
			$chars .= isset($format[$i+1]) ? $format[$i+1] : "";

			// multiple chars
			if (isset($format_array[$chars])) {
				$output_format .= str_replace($chars, $format_array[$chars], $chars);
				$format         = substr_replace($format, "", 0, 2);
			}
			// single char
			else {
				if (isset($format_array[$char])) {
					$output_format .= str_replace($char, $format_array[$char], $char);
				}
				// other
				else {
					$output_format .= $char;
				}

				$format = substr_replace($format, "", 0, 1);
			}
		}

		return $output_format;
	}

	public static function esc_html_array($array) {
		if (!is_array($array) || count($array) < 1) return $array;

		$html = array();
		foreach ($array as $key => $value) {
			$html[] = "{$key}='" . esc_attr($value) . "'";
		}

		return implode(" ", $html);
	}

	// check if key exists in object and return object value. return default if $object is no object or key doesn't exist
	public static function get_object_value($object, $key, $default = "") {
		if (!is_object($object) || !property_exists($object, $key)) return $default;

		return $object->$key;
	}
	// check if key exists in array and return array value. return default if $array is no array or key doesn't exist
	public static function get_array_value($array, $key, $default = "") {
		if (!is_array($array) || !isset($array, $key)) return $default;

		return $array[$key];
	}

	// settings table
	public static function get_settings_table($settings, $options_id = "opt", $options_name = "", $single_overwrite = false) {
		// load mailchimp api wrapper
		$mailchimp_lists = array();
		if (file_exists(EZFC_PATH . "lib/mailchimp/MailChimp.php") && version_compare(PHP_VERSION, "5.3.0") >= 0) {
			require_once(EZFC_PATH . "lib/mailchimp/MailChimp.php");
			require_once(EZFC_PATH . "lib/mailchimp/wrapper.php");

			$mailchimp_api_key = get_option("ezfc_mailchimp_api_key", -1);
			$mailchimp_lists   = array();
			if (!empty($mailchimp_api_key) && $mailchimp_api_key != -1) {
				try {
					$mailchimp = Ezfc_Mailchimp_Wrapper::get_instance($mailchimp_api_key);
					$mailchimp_lists = $mailchimp->get("lists");
				} catch (Exception $e) {}
			}
		}

		$mailpoet_lists = array();
		if (class_exists("WYSIJA")) {
			$model_list = WYSIJA::get("list", "model");
			$mailpoet_lists = $model_list->get(array("name", "list_id"), array("is_enabled" => 1));
		}

		// currency codes
		$currency_array = array("Australian Dollar" => "AUD", "Brazilian Real" => "BRL", "Canadian Dollar" => "CAD", "Czech Koruna" => "CZK", "Danish Krone" => "DKK", "Euro" => "EUR", "Hong Kong Dollar" => "HKD", "Hungarian Forint" => "HUF", "Israeli New Sheqel" => "ILS", "Japanese Yen" => "JPY", "Malaysian Ringgit" => "MYR", "Mexican Peso" => "MXN", "Norwegian Krone" => "NOK", "New Zealand Dollar" => "NZD", "Philippine Peso" => "PHP", "Polish Zloty" => "PLN", "Pound Sterling" => "GBP", "Singapore Dollar" => "SGD", "Swedish Krona" => "SEK", "Swiss Franc" => "CHF", "Taiwan New Dollar" => "TWD", "Thai Baht" => "THB", "Turkish Lira" => "TRY", "U.S. Dollar" => "USD");
		// languages
		$langs = array('ar'=>'Arabic','ar-ma'=>'Moroccan Arabic','bs'=>'Bosnian','bg'=>'Bulgarian','br'=>'Breton','ca'=>'Catalan','cy'=>'Welsh','cs'=>'Czech','cv'=>'Chuvash','da'=>'Danish','de'=>'German','el'=>'Greek','en'=>'English','en-au'=>'English (Australia)','en-ca'=>'English (Canada)','en-gb'=>'English (England)','eo'=>'Esperanto','es'=>'Spanish','et'=>'Estonian','eu'=>'Basque','fa'=>'Persian','fi'=>'Finnish','fo'=>'Farose','fr-ca'=>'French (Canada)','fr'=>'French','gl'=>'Galician','he'=>'Hebrew','hi'=>'Hindi','hr'=>'Croatian','hu'=>'Hungarian','hy-am'=>'Armenian','id'=>'Bahasa Indonesia','is'=>'Icelandic','it'=>'Italian','ja'=>'Japanese','ka'=>'Georgian','ko'=>'Korean','lv'=>'Latvian','lt'=>'Lithuanian','ml'=>'Malayalam','mr'=>'Marathi','ms-my'=>'Bahasa Malaysian','nb'=>'Norwegian','ne'=>'Nepalese','nl'=>'Dutch','nn'=>'Norwegian Nynorsk','pl'=>'Polish','pt-br'=>'Portuguese (Brazil)','pt'=>'Portuguese','ro'=>'Romanian','ru'=>'Russian','sk'=>'Slovak','sl'=>'Slovenian','sq'=>'Albanian','sv'=>'Swedish','th'=>'Thai','tl-ph'=>'Tagalog (Filipino)','tr'=>'Turkish','tzm-la'=>'Tamaziɣt','uk'=>'Ukrainian','uz'=>'Uzbek','zh-cn'=>'Chinese','zh-tw'=>'Chinese (Traditional)');

		$out   = array();
		$out[] = "<table class='form-table'>";
		//$out[] = "	<tr>";

		$table_out = array();
		foreach ($settings as $i => $s) {
			$tmp_id = !empty($s["id"]) ? $s["id"] : $i;

			$tmp_value = "";
			if (isset($s["value"])) {
				$tmp_value = $s["value"];
			}
			else if (isset($s["default"])) {
				$tmp_value = $s["default"];
			}

			if (!empty($tmp_value)) {
				$tmp_value = maybe_unserialize($tmp_value);
			}

			$element_id = "{$options_id}-{$tmp_id}";
			$add_class  = "";
			$tmp_input  = "";

			$type_array = array("");
			if (!empty($s["type"])) {
				$type_array = explode(",", $s["type"]);
				$add_class  = "ezfc-settings-type-{$type_array[0]}";
			}

			switch ($type_array[0]) {
				case "bool_text":
					// default values
					$values = array(
						"enabled" => 0,
						"text"    => ""
					);

					if (is_array($tmp_value)) {
						$values = $tmp_value;
					}

					$selected_no = $selected_yes = "";

					if ($values["enabled"] == 0) $selected_no = " selected='selected'";
					else $selected_yes = " selected='selected'";

					$tmp_input  = "<select class='{$add_class}' id='{$element_id}-enabled' name='{$options_name}[{$tmp_id}][enabled]'>";
					$tmp_input .= "    <option value='0' {$selected_no}>" . __("No", "ezfc") . "</option>";
					$tmp_input .= "    <option value='1' {$selected_yes}>" . __("Yes", "ezfc") . "</option>";
					$tmp_input .= "</select>";

					$tmp_input .= "<input type='text' class='regular-text {$add_class}' id='{$element_id}-text' name='{$options_name}[{$tmp_id}][text]' value=\"{$values["text"]}\" />";
				break;

				case "border":
					// default values
					$border = array(
						"color"  => "",
						"width"  => "",
						"style"  => "",
						"radius" => ""
					);

					if (is_array($tmp_value)) {
						$border = $tmp_value;
					}

					$transparent_checked = empty($border["transparent"]) ? "" : "checked='checked'";

					$border_styles = array("none", "dotted", "dashed", "double", "groove", "inherit", "inset", "outset", "ridge", "solid");

					// color
					$tmp_input = "<input class='ezfc-element-colorpicker-input ezfc-element-border-color' name='{$options_name}[{$tmp_id}][color]' type='text' value='{$border["color"]}' />";
					// transparent
					$tmp_input .= "<input class='ezfc-element-colorpicker-transparent' name='{$options_name}[{$tmp_id}][transparent]' type='checkbox' value='1' {$transparent_checked} /><span class='ezfc-element-transparent-text'>" . __("Transparent", "ezfc") . "</span>";
					// width
					$tmp_input .= "<input class='ezfc-element-border-width' name='{$options_name}[{$tmp_id}][width]' type='text' value='{$border["width"]}' />";
					// style
					$tmp_input .= "<select class='ezfc-element-border-style' name='{$options_name}[{$tmp_id}][style]'>";
					foreach ($border_styles as $style) {
						$selected = "";
						if ($border["style"] == $style) $selected = "selected='selected'";

						$tmp_input .= "<option value='{$style}' {$selected}>{$style}</option>";
					}
					$tmp_input .= "</select>";
					// radius
					$tmp_input .= "<input class='ezfc-element-border-radius' name='{$options_name}[{$tmp_id}][radius]' type='text' value='{$border["radius"]}' />";
				break;

				case "colorpicker":
					// default values
					$color = array(
						"color"  => "",
						"transparent" => ""
					);

					if (is_array($tmp_value)) {
						$color = $tmp_value;
					}

					$transparent_checked = empty($color["transparent"]) ? "" : "checked='checked'";

					wp_enqueue_style("wp-color-picker");

					$tmp_input = "<input class='ezfc-element-colorpicker-input' name='{$options_name}[{$tmp_id}][color]' type='text' value='{$color["color"]}' />";
					$tmp_input .= "<input class='ezfc-element-colorpicker-transparent' name='{$options_name}[{$tmp_id}][transparent]' type='checkbox' value='1' {$transparent_checked} /><span class='ezfc-element-transparent-text'>" . __("Transparent", "ezfc") . "</span>";
				break;

				case "currencycodes":
					$tmp_input  = "<select id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
					foreach ($currency_array as $desc => $v) {
						$selected = "";
						if ($tmp_value == $v) $selected = "selected='selected'";

						$tmp_input .= "<option value='{$v}' {$selected}>({$v}) {$desc}</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "date_formats":
					$options = array(
						"mm/dd/yy" => date("m/d/Y"),
						"dd/mm/yy" => date("d/m/Y"),
						"dd.mm.yy" => date("d.m.Y")
					);

					$tmp_input  = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
					foreach ($options as $v => $desc) {
						$selected = "";
						if ($tmp_value == $v) $selected = "selected='selected'";

						$tmp_input .= "<option value='{$v}' {$selected}>" . $desc . "</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "datepicker_array":
					$closed_dates_json = json_decode($tmp_value);

					$tmp_input  = "<div id='{$element_id}' class='container-fluid option-wrapper datepicker-range-wrapper' data-option_name='{$options_name}' data-option_id='{$tmp_id}' data-inputnames='from,to'>";
					// add business hours button
					$tmp_input .= "		<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12 option-controls'>";
					$tmp_input .= "			<li class='button option-add'><i class='fa fa-fw fa-plus'></i> " . __("Add closed days", "ezfc") . "</li>";
					$tmp_input .= "		</div>";

					// clone element
					$tmp_input .= "		<div class='ezfc-hidden option-clone option-item' data-row='0'>";

					// day
					$tmp_input .= "			<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>";
					$tmp_input .= "				" . __("From" , "ezfc") . " <input class='datepicker-range datepicker-from' type='text' name='{$options_name}[{$tmp_id}][-1][from]' value='' />";
					$tmp_input .= "				" . __("To" , "ezfc") . " <input class='datepicker-range datepicker-to' type='text' name='{$options_name}[{$tmp_id}][-1][to]' value='' />";
					$tmp_input .= "				<button class='button option-remove' data-ot='" . __("Remove item", "ezfc") . "'><i class='fa fa-fw fa-times'></i></button>";
					$tmp_input .= "			</div>";

					// clone end
					$tmp_input .= "		</div>";

					if (count($closed_dates_json) > 0) {
						foreach ($closed_dates_json as $d => $closed_date) {
							if (!property_exists($closed_date, "from")) {
								$closed_date = json_encode(array(
									"from" => "",
									"to"   => ""
								));
							}

							if (empty($closed_date->from) && empty($closed_date->to)) continue;

							$tmp_input .= "<div class='option-item' data-row='{$d}'>";
							$tmp_input .= "		<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>";
							$tmp_input .= "			" . __("From" , "ezfc") . " <input class='datepicker-range datepicker-from' type='text' name='{$options_name}[{$tmp_id}][{$d}][from]' value='{$closed_date->from}' />";
							$tmp_input .= "			" . __("To" , "ezfc") . " <input class='datepicker-range datepicker-to' type='text' name='{$options_name}[{$tmp_id}][{$d}][to]' value='{$closed_date->to}' />";
							$tmp_input .= "				<button class='button option-remove' data-ot='" . __("Remove item", "ezfc") . "'><i class='fa fa-fw fa-times'></i></button>";
							$tmp_input .= "		</div>";
							$tmp_input .= "</div>";
						}
					}

					$tmp_input .= "</div>";
				break;

				case "dimensions":
					if (is_array($tmp_value) && isset($tmp_value["value"])) {
						$dim_value = $tmp_value["value"];
						$dim_unit  = $tmp_value["unit"];
					}
					else if (!empty($s["default"])) {
						$dim_value = $s["default"]["value"];
						$dim_unit  = $s["default"]["units"];
					}
					else {
						$dim_value = "";
						$dim_unit  = "";
					}

					// default units
					$default_units = array("px", "em", "rem", "%", "vw", "vh");
					if (!empty($s["units"])) {
						$default_units = $s["units"];
					}

					$tmp_input = "<input type='text' class='ezfc-input-small {$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}][value]' value=\"{$dim_value}\" />";

					$tmp_input .= "<select id='{$element_id}-select' name='{$options_name}[{$tmp_id}][unit]'>";

					foreach ($default_units as $unit) {
						$selected = "";
						if ($dim_unit == $unit) $selected = "selected";
						
						$tmp_input .= "<option value='{$unit}' {$selected}>{$unit}</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "dropdown":
					$tmp_input = "<select id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";

					foreach ($s["options"] as $value => $description) {
						$selected = "";
						if ($tmp_value == $value) $selected = "selected";
						
						$tmp_input .= "<option value='{$value}' {$selected}>" . $description . "</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "editor":
					if (get_option("ezfc_use_tinymce", 1) == 1) {
						ob_start();

						wp_editor($tmp_value, "editor_{$tmp_id}", array(
							"textarea_name" => "{$options_name}[{$tmp_id}]",
							"textarea_rows" => 5,
							"teeny"         => true
						));
						$tmp_input = ob_get_contents();

						ob_end_clean();
					}
					// textarea
					else {
						$tmp_input  = "<textarea class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
						$tmp_input .= $tmp_value;
						$tmp_input .= "</textarea>";
					}
				break;

				case "file":
					$tmp_input  = "<div class='ezfc-image-upload-wrapper'>";
					$tmp_input .= "		<input class='ezfc-image-upload-hidden' type='hidden' name='{$options_name}[{$tmp_id}]' value='{$tmp_value}' />";
					$tmp_input .= "		<button class='button ezfc-image-upload'>" . __("Choose file", "ezfc") . "</button>&nbsp;";
					$tmp_input .= "		<button class='button ezfc-clear-image'>" . __("Clear file", "ezfc") . "</button>";
					$tmp_input .= "		<br><span class='ezfc-image-filename'></span>";
					$tmp_input .= "		<br><img src='{$tmp_value}' class='ezfc-image-preview' />";
					$tmp_input .= "</div>";
				break;

				case "file_multiple":
					$tmp_input  = "<div class='ezfc-files-upload-wrapper'>";
					$tmp_input .= "		<input class='ezfc-files-upload-hidden' type='hidden' name='{$options_name}[{$tmp_id}]' value='{$tmp_value}' />";
					$tmp_input .= "		<button class='button ezfc-files-upload'>" . __("Choose file(s)", "ezfc") . "</button>&nbsp;";
					$tmp_input .= "		<button class='button ezfc-clear-files'>" . __("Clear file(s)", "ezfc") . "</button>";
					$tmp_input .= "		<br><span class='ezfc-files-ids'></span>";
					$tmp_input .= "</div>";
				break;

				case "font":
					$tmp_input = "<select id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";

					$fonts_json = file_get_contents(EZFC_PATH . "ezplugins/google-fonts.json");
					$fonts      = json_decode($fonts_json, true);
					array_unshift($fonts, array("css-name" => "", "font-name" => "", "font-family" => ""));

					foreach ($fonts as $font) {
						$selected = "";
						if ($tmp_value == $font["font-name"]) $selected = "selected";
						
						$tmp_input .= "<option value='{$font["font-name"]}' {$selected}>{$font["font-name"]}</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "form_element":
					$tmp_input = "<select id='{$element_id}' class='ezfc-settings-form-elements' name='{$options_name}[{$tmp_id}]'></select>";
				break;

				case "hidden":
					$tmp_value = esc_attr($tmp_value);
					
					$tmp_input = "<input type='hidden' class='regular-text {$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]' value=\"{$tmp_value}\" />";
				break;

				case "info":
					$tmp_input = "<p>{$tmp_value}</p>";
				break;

				case "input":
					$tmp_value = esc_attr($tmp_value);
					
					$tmp_input = "<input type='text' class='regular-text {$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]' value=\"{$tmp_value}\" />";
				break;

				case "input_multiple":
					$tmp_input .= "<div class='ezfc-settings-input-multiple-wrapper'>";

					foreach ($s["inputs"] as $input_key => $input_array) {
						$input_loop_id    = "{$element_id}-{$input_key}";
						$input_loop_value = Ezfc_Functions::get_array_value($tmp_value, $input_key, "");
						$input_loop_value = esc_attr($input_loop_value);
					
						$tmp_input .= "<div class='ezfc-settings-input-multiple-option-wrapper'>";

						if (isset($input_array["label"])) {
							$tmp_input .= "<label for='{$input_loop_id}'>{$input_array["label"]}</label><br>";
						}

						$tmp_input .= "<input type='text' class='regular-text {$add_class}' id='{$input_loop_id}' name='{$options_name}[{$tmp_id}][{$input_key}]' value=\"{$input_loop_value}\" />";
						$tmp_input .= "</div>";
					}

					$tmp_input .= "</div>";
				break;

				case "lang":
					$tmp_input  = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
					foreach ($langs as $lang => $langdesc) {
						$selected = "";
						if ($tmp_value == $lang) $selected = "selected='selected'";

						$tmp_input .= "<option value='{$lang}' {$selected}>[{$lang}] {$langdesc}</option>";	
					}
					$tmp_input .= "</select>";
				break;

				case "mailchimp_list":
					$tmp_input = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";

					if (!empty($mailchimp_lists)) {
						foreach ($mailchimp_lists["lists"] as $list) {
							$selected = $tmp_value==$list["id"] ? "selected='selected'" : "";

							$tmp_input .= "<option value='{$list["id"]}' {$selected}>{$list["name"]}</option>";
						}
					}
					// no lists
					else {
						$tmp_input .= "<option value='-1'>" . __("No MailChimp lists found or wrong API key", "ezfc") . "</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "mailpoet_list":
					$tmp_input = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";

					if (count($mailpoet_lists) > 0) {
						foreach ($mailpoet_lists as $list) {
							$selected = $tmp_value==$list["list_id"] ? "selected='selected'" : "";

							$tmp_input .= "<option value='{$list["list_id"]}' {$selected}>{$list["name"]}</option>";
						}
					}
					// no lists
					else {
						$tmp_input .= "<option value='-1'>" . __("No Mailpoet lists found.", "ezfc") . "</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "numbers":
					$type_numbers = explode("-", $type_array[1]);

					$tmp_input = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
					for ($ti = $type_numbers[0]; $ti <= $type_numbers[1]; $ti++) {
						$selected = $tmp_value==$ti ? "selected='selected'" : "";

						$tmp_input .= "<option value='{$ti}' {$selected}>{$ti}</option>";
					}
					$tmp_input .= "</select>";
				break;

				case "password":
					$tmp_value = esc_attr($tmp_value);
					
					$tmp_input = "<input type='password' class='regular-text {$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]' value=\"{$tmp_value}\" />";
				break;

				case "post_types":
					$options = get_post_types(array(
						"public" => true
					));

					$tmp_input  = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}][]' multiple>";
					foreach ($options as $v => $desc) {
						$selected = "";
						if (!empty($tmp_value) && in_array($v, $tmp_value)) $selected = "selected='selected'";

						$tmp_input .= "<option value='{$v}' {$selected}>" . $desc . "</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "roles":
					global $current_user;
					$tmp_input = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
					$caps = $current_user->allcaps;
					ksort($caps);

					foreach ($caps as $role => $value) {
						$selected = "";
						if (!empty($tmp_value) && $role == $tmp_value) $selected = "selected='selected'";

						$tmp_input .= "<option value='{$role}' {$selected}>{$role}</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "select":
					$options = explode("|", $type_array[1]);

					$tmp_input  = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
					foreach ($options as $v => $desc) {
						$selected = "";
						if ($tmp_value == $v) $selected = "selected='selected'";

						$tmp_input .= "<option value='{$v}' {$selected}>" . $desc . "</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "textarea":
					$tmp_input  = "<textarea class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
					$tmp_input .= $tmp_value;
					$tmp_input .= "</textarea>";
				break;

				case "themes":
					$tmp_input = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";

					foreach (glob(EZFC_PATH . "themes/*.css") as $file) {
						$file_id  = basename($file, ".css");
						$selected = $tmp_value==$file_id ? "selected='selected'" : "";

						$tmp_input .= "<option value='{$file_id}' {$selected}>{$file_id}</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "timepicker_array":
					$times_json = json_decode($tmp_value);

					$tmp_input  = "<div id='{$element_id}' class='container-fluid option-wrapper ezfc-hours' data-option_name='{$options_name}' data-option_id='{$tmp_id}' data-inputnames='from,to'>";
					// add business hours button
					$tmp_input .= "		<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12 option-controls'>";
					$tmp_input .= "			<li class='button option-add'><i class='fa fa-fw fa-plus'></i> " . __("Add business hours", "ezfc") . "</li>";
					$tmp_input .= "		</div>";

					// clone element
					$tmp_input .= "		<div class='ezfc-hidden option-clone option-item' data-row='0'>";

					// from
					$tmp_input .= "			<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>";
					$tmp_input .= "				" . __("From" , "ezfc") . " <input class='timepicker timepicker-from' type='text' value='09:00' />";
					// to
					$tmp_input .= "				" . __("To" , "ezfc") . " <input class='timepicker timepicker-to' type='text' value='17:00' />";
					// remove button
					$tmp_input .= "				<button class='button option-remove'><i class='fa fa-fw fa-times'></i></button>";
					$tmp_input .= "			</div>";

					// clone end
					$tmp_input .= "		</div>";

					foreach ($times_json as $t => $times_array) {
						if (!property_exists($times_array, "from") || !property_exists($times_array, "to")) {
							$times_array = json_encode(array(
								"from" => "09:00",
								"to"   => "17:00"
							));
						}

						$tmp_input .= "<div class='option-item' data-row='{$t}'>";
						$tmp_input .= "		<div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>";
						$tmp_input .= "			" . __("From" , "ezfc") . " <input class='timepicker timepicker-from' type='text' name='{$options_name}[{$tmp_id}][{$t}][from]' value='{$times_array->from}' />";
						$tmp_input .= "			" . __("To" , "ezfc") . " <input class='timepicker timepicker-to' type='text' name='{$options_name}[{$tmp_id}][{$t}][to]' value='{$times_array->to}' />";
						$tmp_input .= "				<button class='button option-remove' data-ot='" . __("Remove item", "ezfc") . "'><i class='fa fa-fw fa-times'></i></button>";
						$tmp_input .= "		</div>";
						$tmp_input .= "</div>";
					}

					// option wrapper
					$tmp_input .= "</div>";
				break;

				case "time_formats":
					$options = array(
						"H:i"   => "13:00",
						"h:i A" => "01:00 AM",
						"h:i a" => "01:00 am"
					);

					$tmp_input  = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
					foreach ($options as $v => $desc) {
						$selected = "";
						if ($tmp_value == $v) $selected = "selected='selected'";

						$tmp_input .= "<option value='{$v}' {$selected}>" . $desc . "</option>";
					}

					$tmp_input .= "</select>";
				break;

				case "weekdays":
					$days_selected = explode(",", $tmp_value);
					$days = array(
						1 => __("Monday", "ezfc"),
						2 => __("Tuesday", "ezfc"),
						3 => __("Wednesday", "ezfc"),
						4 => __("Thursday", "ezfc"),
						5 => __("Friday", "ezfc"),
						6 => __("Saturday", "ezfc"),
						0 => __("Sunday", "ezfc")
					);

					$tmp_input  = "<input type='hidden' class='regular-text {$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]' value='{$tmp_value}' />";
					$tmp_input .= "<div class='buttonset'>";

					foreach ($days as $i => $day) {
						$checked = in_array($i, $days_selected) ? "checked" : "";
						$tmp_input .= "<input class='{$s["name"]}' type='checkbox' value='{$i}' id='{$s["name"]}_{$i}' {$checked} />";
						$tmp_input .= "<label for='{$s["name"]}_{$i}'>";
						$tmp_input .= $day;
						$tmp_input .= "</label>";
					}
					$tmp_input .= "</div>";
				break;

				case "yesno":
					$selected_no = $selected_yes = "";

					if ($tmp_value == 0) $selected_no = " selected='selected'";
					else                $selected_yes = " selected='selected'";

					$tmp_input  = "<select class='{$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]'>";
					$tmp_input .= "    <option value='0' {$selected_no}>" . __("No", "ezfc") . "</option>";
					$tmp_input .= "    <option value='1' {$selected_yes}>" . __("Yes", "ezfc") . "</option>";
					$tmp_input .= "</select>";
				break;

				default:
					$tmp_value = esc_attr($tmp_value);
					
					$tmp_input = "<input type='text' class='regular-text {$add_class}' id='{$element_id}' name='{$options_name}[{$tmp_id}]' value=\"{$tmp_value}\" />";
				break;
			}

			if (isset($s["premium"])) {
				$tmp_input = __("This option is only available in the premium version.", "ezfc");
			}

			$single_overwrite_button = "";
			if ($single_overwrite) {
				$single_overwrite_button = "<br><input type='submit' name='submit' class='button ezfc-single-overwrite-button' value='" . __("Overwrite", "ezfc") . "' />";
			}

			$table_out[] = "
				<tr class='ezfc-table-option-{$s["type"]}' id='ezfc-table-option-{$tmp_id}'>
					<th scope='row'>
						<label for='{$options_name}-{$tmp_id}'>" . $s["description"] . "</label>
						{$single_overwrite_button}
					</th>
					<td id='ezfc-option-{$tmp_id}'>
						{$tmp_input}
						<p class='description'>" . (empty($s["description_long"]) ? "" : $s["description_long"]) . "</p>
					</td>
				</tr>
			";
		}

		$out[] = implode("", $table_out);
		$out[] = "</table>";

		return implode("", $out);
	}

	/**
		copy directory
	**/
	public static function recurse_copy($src, $dst) {
		$dir = opendir($src); 
		@mkdir($dst); 
		while(false !== ( $file = readdir($dir)) ) { 
			if (( $file != '.' ) && ( $file != '..' )) { 
				if ( is_dir($src . '/' . $file) ) { 
					self::recurse_copy($src . '/' . $file,$dst . '/' . $file); 
				} 
				else { 
					copy($src . '/' . $file,$dst . '/' . $file); 
				} 
			} 
		}
		closedir($dir);
	}

	/**
		create WP dir in upload dir
	**/
	public static function create_upload_dir($dirname, $deny_all = true) {
		// invalid call
		if (!function_exists("WP_Filesystem")) return self::send_message("error", __("Invalid WP Filesystem call.", "ezfc"));

		WP_Filesystem();
		global $wp_filesystem;

		// strip invalid chars
		$dirname        = str_replace(array(".", "/", "\\"), "", $dirname);

		$upload_dir     = wp_upload_dir();
		$upload_dirname = $upload_dir["basedir"] . "/{$dirname}/";

		if ( ! file_exists( $upload_dirname ) ) {
			wp_mkdir_p( $upload_dirname );
			$index_file    = $upload_dirname . "/index.php";
			$htaccess_file = $upload_dirname . "/.htaccess";

			if ( ! file_exists( $upload_dirname ) ) return self::send_message("error", __("Unable to create upload directory. Please create a folder named 'ezfc-uploads' in the WP upload directory.", "ezfc"));
			
			if ( ! file_exists( $index_file ) ) {	    	
				// index file
				$wp_filesystem->put_contents(
					$index_file,
					"",
					FS_CHMOD_FILE
				);

				// write deny from all htaccess file
				if ($deny_all) {
					$wp_filesystem->put_contents(
						$htaccess_file,
						"deny from all",
						FS_CHMOD_FILE
					);
				}

				if ( ! file_exists( $index_file ) )	return self::send_message("error", sprintf(__("Unable to create index file in the upload directory. Please create a blank file named %s in the ezfc upload directory created by ez Form Calculator (see %s).", "ezfc"), "'index.php'", "/wp-content/uploads/{$dirname}"));
			}
		}

		// dir created
		return $upload_dirname;
	}

	/**
		delete directory with files
	**/
	public static function delete_dir($dir) {
		if (!is_dir($dir)) return false;

		$files = array_diff(scandir($dir), array('.','..'));

		foreach ($files as $file) { 
			(is_dir("$dir/$file")) ? self::delete_dir("$dir/$file") : unlink("$dir/$file"); 
		} 
		return rmdir($dir);
	}

	/**
		read data from file $name in zip file $file
	**/
	public static function zip_read($file, $name) {
		// skip if unzip file does not exist
		if (!function_exists("file_get_contents")) return self::send_message("error", __("Function 'file_get_contents' does not exist.", "ezfc"));
		// check if file exists
		if (!file_exists($file)) return self::send_message("error", __("Unable to find file {$file}", "ezfc"));

		$result = file_get_contents("zip://" . $file . "#" . $name);
		return $result;
	}

	/**
		write data to zip file
	**/
	public static function zip_write($data, $name, $path_to_write) {
		global $wp_filesystem;

		// skip if ziparchive class does not exist
		if (!class_exists("ZipArchive")) return self::send_message("error", __("Library ZipArchive is not installed on your webserver.", "ezfc"));

		$zip = new ZipArchive();

		$file_id  = md5(microtime());
		$filename = self::$plugin_slug_short . "_export_data_" . $file_id . ".zip";
		$file     = $path_to_write . $filename;

		if ($zip->open($file, ZIPARCHIVE::CREATE) !== true) {
			return self::send_message("error", __("Unable to create temporary file.", "ezfc"));
		}

		$zip->addFromString($name, $data);
		$zip->close();

		// build pdf link
		$upload_dir   = wp_upload_dir();
		$download_url = trailingslashit($upload_dir["baseurl"]) . "ezfc-tmp/" . $filename;

		return array(
			"filepath" => $file,
			"filename" => $filename,
			"file_id"  => $file_id,
			"file_url" => $download_url
		);
	}

	/**
		clear temporary files
	**/
	public static function delete_tmp_files() {
		WP_Filesystem();
		global $wp_filesystem;

		$plugin_dir      = plugin_dir_path( __FILE__ );
		$plugin_path     = str_replace(ABSPATH, $wp_filesystem->abspath(), $plugin_dir);
		$plugin_path_tmp = $plugin_path . "/tmp/";

		$files = glob($plugin_path_tmp . "*.zip");
		foreach ($files as $file) {
			unlink($file);
		}

		$files = glob($plugin_path_tmp . "*.csv");
		foreach ($files as $file) {
			unlink($file);
		}
		
		return self::send_message("success", __("Temporary files deleted.", "ezfc"));
	}

	/**
		send file to browser
	**/
	public static function send_file_to_browser($file, $filename = null) {
		if (!file_exists($file)) return;

		$filename = empty($filename) ? basename($file) : $filename;

		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . basename($filename));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		ob_clean();
		flush();
		readfile($file);

		exit;
	}

	/**
		ajax message
	**/
	public static function send_message($type, $msg = "", $id = 0) {
		if (is_array($type)) {
			return array_merge($type, array("id" => $id));
		}

		return array(
			$type => $msg,
			"id"  => $id
		);
	}

	/**
		register license
	**/
	public static function register_license($code) {
		$remote_url  = self::$plugin_register_license_url . "&code={$code}";
		$remote_url .= "&action=register";
		$remote_url .= "&site=" . urlencode(network_site_url( '/' ));

		$result = wp_remote_get($remote_url, array(
			"timeout" => 30
		));

		$response = wp_remote_retrieve_body( $result );
		$response_code = wp_remote_retrieve_response_code( $result );

		// request error
		if ($response_code !== 200) return array("error" => sprintf(__("Error response code %s: %s", "ezfc"), $response_code, strip_tags($response)));

		// wp error
		if (is_wp_error($result)) return array("error" => $result->get_error_message());
		// empty result
		if (empty($result["body"])) return false;

		return json_decode($result["body"], true);
	}

	/**
		revoke license
	**/
	public static function revoke_license($code) {
		$remote_url  = self::$plugin_register_license_url . "&code={$code}";
		$remote_url .= "&action=revoke";
		$remote_url .= "&site=" . urlencode(network_site_url( '/' ));

		$result = wp_remote_get($remote_url, array(
			"timeout" => 30
		));

		$response = wp_remote_retrieve_body( $result );
		$response_code = wp_remote_retrieve_response_code( $result );

		// request error
		if ($response_code !== 200) return array("error" => sprintf(__("Error response code %s: %s", "ezfc"), $response_code, strip_tags($response)));

		if (empty($result["body"])) return false;

		return json_decode($result["body"], true);
	}

	/**
		get templates
	**/
	public static function template_get_list() {
		$templates = get_transient("ezfc_template_list");

		if ($templates === false) {
			$code = get_option("ezfc_purchase_code", "");
			$remote_url = self::$templates_remote_url . "&action=list&code={$code}";

			$result = wp_remote_get($remote_url, array(
				"timeout" => 30
			));

			if (empty($result["body"])) return false;

			$templates = json_decode($result["body"]);
			set_transient("ezfc_template_list", $templates, DAY_IN_SECONDS);

			return json_decode($templates->templates);
		}
		else if (property_exists($templates, "templates")) {
			return json_decode($templates->templates);
		}

		return array();
	}

	/**
		install template
	**/
	public static function template_install($id) {
		$code = get_option("ezfc_purchase_code", "");

		$remote_url = self::$templates_remote_url . "&action=install&id={$id}&code={$code}";

		$result = wp_remote_get($remote_url, array(
			"timeout" => 30
		));

		if (empty($result["body"])) {
			return self::send_message("error", __("Service is unavailable.", "ezfc"));
		}

		$template_json = json_decode($result["body"]);

		if (empty($template_json->template)) {
			return self::send_message("error", __("Unable to install template: no template.", "ezfc"));
		}

		if (empty($template_json->template->data)) {
			return self::send_message("error", __("Unable to install template: no data.", "ezfc"));
		}

		require_once(EZFC_PATH . "class.ezfc_backend.php");
		$ezfc_backend = Ezfc_backend::instance();

		$form_template = json_decode(json_encode(array(
			"form"     => array("name" => $template_json->template->name),
			"elements" => json_decode($template_json->template->data),
			"options"  => array()
		)));

		$res = $ezfc_backend->form_save_template(null, $form_template);
		if (is_array($res) && !empty($res["error"])) {
			return self::send_message("error", $res["error"]);
		}
		
		return self::send_message("success", sprintf(__("Template %s was installed.", "ezfc"), "<strong>{$template_json->template->name}</strong>"));
	}

	/**
		submit template
	**/
	public static function template_submit($data) {
		require_once(EZFC_PATH . "class.ezfc_backend.php");
		$ezfc_backend = Ezfc_backend::instance();

		$template = $ezfc_backend->form_template_get($data["template_submit_id"]);
		if (!$template) {
			return self::send_message("error", __("Template not found.", "ezfc"));
		}

		$remote_url  = self::$templates_remote_url . "&action=submit";

		$result = wp_remote_post($remote_url, array(
			"timeout" => 30,
			"body" => array(
				"name"        => $template->name,
				"author"      => $data["author"],
				"email"       => $data["email"],
				"description" => $data["description"],
				"data"        => $template->data,
				"version"     => EZFC_VERSION
			)
		));

		if (empty($result["body"])) {
			return self::send_message("error", __("Service is unavailable.", "ezfc"));
		}

		$res = json_decode($result["body"]);
		
		$message = "";
		if (!empty($res->error)) {
			return self::send_message("error", $res->error);
		}
		elseif (!empty($res->success)) {
			return self::send_message("success", $res->success);
		}

		return self::send_message("error", $result["body"]);
	}

	public static function array_index_key($array, $key) {
		$ret_array = array();

		if (!self::is_countable($array) || count($array) < 1) return $ret_array;

		foreach ($array as $v) {
			if (is_object($v)) {
				$ret_array[$v->$key] = $v;
			}
			if (is_array($v)) {
				$ret_array[$v[$key]] = $v;
			}
		}

		return $ret_array;
	}

	/**
	* Formats a line (passed as a fields  array) as CSV and returns the CSV as a string.
	* Adapted from http://us3.php.net/manual/en/function.fputcsv.php#87120
	*/
	static function arrayToCsv( array &$fields, $delimiter = ';', $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false ) {
		$delimiter_esc = preg_quote($delimiter, '/');
		$enclosure_esc = preg_quote($enclosure, '/');

		$output = array();
		foreach ( $fields as $field ) {
			if ($field === null && $nullToMysqlNull) {
				$output[] = 'NULL';
				continue;
			}

			// Enclose fields containing $delimiter, $enclosure or whitespace
			if ( $encloseAll || preg_match( "/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field ) ) {
				$output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
			}
			else {
				$output[] = $field;
			}
		}

		return implode( $delimiter, $output );
	}

	static function write_tmp_file($data, $basename, $extension) {
		$upload_dir = wp_upload_dir();
		$hash       = md5(microtime(true));

		$filename = self::$plugin_slug_short . "_" . $basename . "_" . $hash . "." . $extension;
		$file     = $upload_dir["basedir"] . "/" . Ezfc_Functions::$folders["tmp"] . "/" . $filename;

		$file_handle = fopen($file, "a");
		fputs($file_handle, $data);
		fclose($file_handle);

		return array(
			"filepath" => $file,
			"filename" => $filename,
			"file_url" => site_url() . "/ezfc-download-export-form.php?download_csv=1&hash={$hash}"
		);
	}

	/**
		this will encode/decode entities correctly (also &acute; and other damned characters!)
	**/
	static function normalize_encoding($string, $encode = "encode") {
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
		sort by object key name
	**/
	static function sort_object_key_name($a, $b) {
		return strcmp(strtolower($a->name), strtolower($b->name));
	}

	/**
		smtp setup
	**/
	static function smtp_setup() {
		if (!class_exists("PHPMailer")) {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			require_once ABSPATH . WPINC . '/class-smtp.php';
		}

		$smtp = new PHPMailer();
		$smtp->isSMTP();
		$smtp->isHTML(true);
		$smtp->Host       = get_option("ezfc_email_smtp_host");
		$smtp->Port       = get_option("ezfc_email_smtp_port");
		$smtp->SMTPSecure = get_option("ezfc_email_smtp_secure");
		$smtp->SMTPAuth   = get_option("ezfc_email_smtp_anon", 0) ? false : true;

		// connect with login credentials
		if (!get_option("ezfc_email_smtp_anon", 0)) {
			$smtp->Username   = get_option("ezfc_email_smtp_user");
			$smtp->Password   = get_option("ezfc_email_smtp_pass");
		}

		return $smtp;
	}

	/**
		get mime type
	**/
	static function mime_type($extension) {
		$mime_type = array(
			"3dml"			=>	"text/vnd.in3d.3dml",
			"3g2"			=>	"video/3gpp2",
			"3gp"			=>	"video/3gpp",
			"7z"			=>	"application/x-7z-compressed",
			"aab"			=>	"application/x-authorware-bin",
			"aac"			=>	"audio/x-aac",
			"aam"			=>	"application/x-authorware-map",
			"aas"			=>	"application/x-authorware-seg",
			"abw"			=>	"application/x-abiword",
			"ac"			=>	"application/pkix-attr-cert",
			"acc"			=>	"application/vnd.americandynamics.acc",
			"ace"			=>	"application/x-ace-compressed",
			"acu"			=>	"application/vnd.acucobol",
			"adp"			=>	"audio/adpcm",
			"aep"			=>	"application/vnd.audiograph",
			"afp"			=>	"application/vnd.ibm.modcap",
			"ahead"			=>	"application/vnd.ahead.space",
			"ai"			=>	"application/postscript",
			"aif"			=>	"audio/x-aiff",
			"air"			=>	"application/vnd.adobe.air-application-installer-package+zip",
			"ait"			=>	"application/vnd.dvb.ait",
			"ami"			=>	"application/vnd.amiga.ami",
			"apk"			=>	"application/vnd.android.package-archive",
			"application"		=>	"application/x-ms-application",
			"apr"			=>	"application/vnd.lotus-approach",
			"asf"			=>	"video/x-ms-asf",
			"aso"			=>	"application/vnd.accpac.simply.aso",
			"atc"			=>	"application/vnd.acucorp",
			"atom"			=>	"application/atom+xml",
			"atomcat"		=>	"application/atomcat+xml",
			"atomsvc"		=>	"application/atomsvc+xml",
			"atx"			=>	"application/vnd.antix.game-component",
			"au"			=>	"audio/basic",
			"avi"			=>	"video/x-msvideo",
			"aw"			=>	"application/applixware",
			"azf"			=>	"application/vnd.airzip.filesecure.azf",
			"azs"			=>	"application/vnd.airzip.filesecure.azs",
			"azw"			=>	"application/vnd.amazon.ebook",
			"bcpio"			=>	"application/x-bcpio",
			"bdf"			=>	"application/x-font-bdf",
			"bdm"			=>	"application/vnd.syncml.dm+wbxml",
			"bed"			=>	"application/vnd.realvnc.bed",
			"bh2"			=>	"application/vnd.fujitsu.oasysprs",
			"bin"			=>	"application/octet-stream",
			"bmi"			=>	"application/vnd.bmi",
			"bmp"			=>	"image/bmp",
			"box"			=>	"application/vnd.previewsystems.box",
			"btif"			=>	"image/prs.btif",
			"bz"			=>	"application/x-bzip",
			"bz2"			=>	"application/x-bzip2",
			"c"			=>	"text/x-c",
			"c11amc"		=>	"application/vnd.cluetrust.cartomobile-config",
			"c11amz"		=>	"application/vnd.cluetrust.cartomobile-config-pkg",
			"c4g"			=>	"application/vnd.clonk.c4group",
			"cab"			=>	"application/vnd.ms-cab-compressed",
			"car"			=>	"application/vnd.curl.car",
			"cat"			=>	"application/vnd.ms-pki.seccat",
			"ccxml"			=>	"application/ccxml+xml,",
			"cdbcmsg"		=>	"application/vnd.contact.cmsg",
			"cdkey"			=>	"application/vnd.mediastation.cdkey",
			"cdmia"			=>	"application/cdmi-capability",
			"cdmic"			=>	"application/cdmi-container",
			"cdmid"			=>	"application/cdmi-domain",
			"cdmio"			=>	"application/cdmi-object",
			"cdmiq"			=>	"application/cdmi-queue",
			"cdx"			=>	"chemical/x-cdx",
			"cdxml"			=>	"application/vnd.chemdraw+xml",
			"cdy"			=>	"application/vnd.cinderella",
			"cer"			=>	"application/pkix-cert",
			"cgm"			=>	"image/cgm",
			"chat"			=>	"application/x-chat",
			"chm"			=>	"application/vnd.ms-htmlhelp",
			"chrt"			=>	"application/vnd.kde.kchart",
			"cif"			=>	"chemical/x-cif",
			"cii"			=>	"application/vnd.anser-web-certificate-issue-initiation",
			"cil"			=>	"application/vnd.ms-artgalry",
			"cla"			=>	"application/vnd.claymore",
			"class"			=>	"application/java-vm",
			"clkk"			=>	"application/vnd.crick.clicker.keyboard",
			"clkp"			=>	"application/vnd.crick.clicker.palette",
			"clkt"			=>	"application/vnd.crick.clicker.template",
			"clkw"			=>	"application/vnd.crick.clicker.wordbank",
			"clkx"			=>	"application/vnd.crick.clicker",
			"clp"			=>	"application/x-msclip",
			"cmc"			=>	"application/vnd.cosmocaller",
			"cmdf"			=>	"chemical/x-cmdf",
			"cml"			=>	"chemical/x-cml",
			"cmp"			=>	"application/vnd.yellowriver-custom-menu",
			"cmx"			=>	"image/x-cmx",
			"cod"			=>	"application/vnd.rim.cod",
			"cpio"			=>	"application/x-cpio",
			"cpt"			=>	"application/mac-compactpro",
			"crd"			=>	"application/x-mscardfile",
			"crl"			=>	"application/pkix-crl",
			"cryptonote"		=>	"application/vnd.rig.cryptonote",
			"csh"			=>	"application/x-csh",
			"csml"			=>	"chemical/x-csml",
			"csp"			=>	"application/vnd.commonspace",
			"css"			=>	"text/css",
			"csv"			=>	"text/csv",
			"cu"			=>	"application/cu-seeme",
			"curl"			=>	"text/vnd.curl",
			"cww"			=>	"application/prs.cww",
			"dae"			=>	"model/vnd.collada+xml",
			"daf"			=>	"application/vnd.mobius.daf",
			"davmount"		=>	"application/davmount+xml",
			"dcurl"			=>	"text/vnd.curl.dcurl",
			"dd2"			=>	"application/vnd.oma.dd2+xml",
			"ddd"			=>	"application/vnd.fujixerox.ddd",
			"deb"			=>	"application/x-debian-package",
			"der"			=>	"application/x-x509-ca-cert",
			"dfac"			=>	"application/vnd.dreamfactory",
			"dir"			=>	"application/x-director",
			"dis"			=>	"application/vnd.mobius.dis",
			"djvu"			=>	"image/vnd.djvu",
			"dna"			=>	"application/vnd.dna",
			"doc"			=>	"application/msword",
			"docm"			=>	"application/vnd.ms-word.document.macroenabled.12",
			"docx"			=>	"application/vnd.openxmlformats-officedocument.wordprocessingml.document",
			"dotm"			=>	"application/vnd.ms-word.template.macroenabled.12",
			"dotx"			=>	"application/vnd.openxmlformats-officedocument.wordprocessingml.template",
			"dp"			=>	"application/vnd.osgi.dp",
			"dpg"			=>	"application/vnd.dpgraph",
			"dra"			=>	"audio/vnd.dra",
			"dsc"			=>	"text/prs.lines.tag",
			"dssc"			=>	"application/dssc+der",
			"dtb"			=>	"application/x-dtbook+xml",
			"dtd"			=>	"application/xml-dtd",
			"dts"			=>	"audio/vnd.dts",
			"dtshd"			=>	"audio/vnd.dts.hd",
			"dvi"			=>	"application/x-dvi",
			"dwf"			=>	"model/vnd.dwf",
			"dwg"			=>	"image/vnd.dwg",
			"dxf"			=>	"image/vnd.dxf",
			"dxp"			=>	"application/vnd.spotfire.dxp",
			"ecelp4800"		=>	"audio/vnd.nuera.ecelp4800",
			"ecelp7470"		=>	"audio/vnd.nuera.ecelp7470",
			"ecelp9600"		=>	"audio/vnd.nuera.ecelp9600",
			"edm"			=>	"application/vnd.novadigm.edm",
			"edx"			=>	"application/vnd.novadigm.edx",
			"efif"			=>	"application/vnd.picsel",
			"ei6"			=>	"application/vnd.pg.osasli",
			"eml"			=>	"message/rfc822",
			"emma"			=>	"application/emma+xml",
			"eol"			=>	"audio/vnd.digital-winds",
			"eot"			=>	"application/vnd.ms-fontobject",
			"epub"			=>	"application/epub+zip",
			"es"			=>	"application/ecmascript",
			"es3"			=>	"application/vnd.eszigno3+xml",
			"esf"			=>	"application/vnd.epson.esf",
			"etx"			=>	"text/x-setext",
			"exe"			=>	"application/x-msdownload",
			"exi"			=>	"application/exi",
			"ext"			=>	"application/vnd.novadigm.ext",
			"ez2"			=>	"application/vnd.ezpix-album",
			"ez3"			=>	"application/vnd.ezpix-package",
			"f"			=>	"text/x-fortran",
			"f4v"			=>	"video/x-f4v",
			"fbs"			=>	"image/vnd.fastbidsheet",
			"fcs"			=>	"application/vnd.isac.fcs",
			"fdf"			=>	"application/vnd.fdf",
			"fe_launch"		=>	"application/vnd.denovo.fcselayout-link",
			"fg5"			=>	"application/vnd.fujitsu.oasysgp",
			"fh"			=>	"image/x-freehand",
			"fig"			=>	"application/x-xfig",
			"fli"			=>	"video/x-fli",
			"flo"			=>	"application/vnd.micrografx.flo",
			"flv"			=>	"video/x-flv",
			"flw"			=>	"application/vnd.kde.kivio",
			"flx"			=>	"text/vnd.fmi.flexstor",
			"fly"			=>	"text/vnd.fly",
			"fm"			=>	"application/vnd.framemaker",
			"fnc"			=>	"application/vnd.frogans.fnc",
			"fpx"			=>	"image/vnd.fpx",
			"fsc"			=>	"application/vnd.fsc.weblaunch",
			"fst"			=>	"image/vnd.fst",
			"ftc"			=>	"application/vnd.fluxtime.clip",
			"fti"			=>	"application/vnd.anser-web-funds-transfer-initiation",
			"fvt"			=>	"video/vnd.fvt",
			"fxp"			=>	"application/vnd.adobe.fxp",
			"fzs"			=>	"application/vnd.fuzzysheet",
			"g2w"			=>	"application/vnd.geoplan",
			"g3"			=>	"image/g3fax",
			"g3w"			=>	"application/vnd.geospace",
			"gac"			=>	"application/vnd.groove-account",
			"gdl"			=>	"model/vnd.gdl",
			"geo"			=>	"application/vnd.dynageo",
			"gex"			=>	"application/vnd.geometry-explorer",
			"ggb"			=>	"application/vnd.geogebra.file",
			"ggt"			=>	"application/vnd.geogebra.tool",
			"ghf"			=>	"application/vnd.groove-help",
			"gif"			=>	"image/gif",
			"gim"			=>	"application/vnd.groove-identity-message",
			"gmx"			=>	"application/vnd.gmx",
			"gnumeric"		=>	"application/x-gnumeric",
			"gph"			=>	"application/vnd.flographit",
			"gqf"			=>	"application/vnd.grafeq",
			"gram"			=>	"application/srgs",
			"grv"			=>	"application/vnd.groove-injector",
			"grxml"			=>	"application/srgs+xml",
			"gsf"			=>	"application/x-font-ghostscript",
			"gtar"			=>	"application/x-gtar",
			"gtm"			=>	"application/vnd.groove-tool-message",
			"gtw"			=>	"model/vnd.gtw",
			"gv"			=>	"text/vnd.graphviz",
			"gxt"			=>	"application/vnd.geonext",
			"h261"			=>	"video/h261",
			"h263"			=>	"video/h263",
			"h264"			=>	"video/h264",
			"hal"			=>	"application/vnd.hal+xml",
			"hbci"			=>	"application/vnd.hbci",
			"hdf"			=>	"application/x-hdf",
			"hlp"			=>	"application/winhlp",
			"hpgl"			=>	"application/vnd.hp-hpgl",
			"hpid"			=>	"application/vnd.hp-hpid",
			"hps"			=>	"application/vnd.hp-hps",
			"hqx"			=>	"application/mac-binhex40",
			"htke"			=>	"application/vnd.kenameaapp",
			"html"			=>	"text/html",
			"hvd"			=>	"application/vnd.yamaha.hv-dic",
			"hvp"			=>	"application/vnd.yamaha.hv-voice",
			"hvs"			=>	"application/vnd.yamaha.hv-script",
			"i2g"			=>	"application/vnd.intergeo",
			"icc"			=>	"application/vnd.iccprofile",
			"ice"			=>	"x-conference/x-cooltalk",
			"ico"			=>	"image/x-icon",
			"ics"			=>	"text/calendar",
			"ief"			=>	"image/ief",
			"ifm"			=>	"application/vnd.shana.informed.formdata",
			"igl"			=>	"application/vnd.igloader",
			"igm"			=>	"application/vnd.insors.igm",
			"igs"			=>	"model/iges",
			"igx"			=>	"application/vnd.micrografx.igx",
			"iif"			=>	"application/vnd.shana.informed.interchange",
			"imp"			=>	"application/vnd.accpac.simply.imp",
			"ims"			=>	"application/vnd.ms-ims",
			"ipfix"			=>	"application/ipfix",
			"ipk"			=>	"application/vnd.shana.informed.package",
			"irm"			=>	"application/vnd.ibm.rights-management",
			"irp"			=>	"application/vnd.irepository.package+xml",
			"itp"			=>	"application/vnd.shana.informed.formtemplate",
			"ivp"			=>	"application/vnd.immervision-ivp",
			"ivu"			=>	"application/vnd.immervision-ivu",
			"jad"			=>	"text/vnd.sun.j2me.app-descriptor",
			"jam"			=>	"application/vnd.jam",
			"jar"			=>	"application/java-archive",
			"java"			=>	"text/x-java-source,java",
			"jisp"			=>	"application/vnd.jisp",
			"jlt"			=>	"application/vnd.hp-jlyt",
			"jnlp"			=>	"application/x-java-jnlp-file",
			"joda"			=>	"application/vnd.joost.joda-archive",
			"jpeg"			=>	"image/jpeg",
			"jpg"			=>	"image/jpeg",
			"jpgv"			=>	"video/jpeg",
			"jpm"			=>	"video/jpm",
			"js"			=>	"application/javascript",
			"json"			=>	"application/json",
			"karbon"		=>	"application/vnd.kde.karbon",
			"kfo"			=>	"application/vnd.kde.kformula",
			"kia"			=>	"application/vnd.kidspiration",
			"kml"			=>	"application/vnd.google-earth.kml+xml",
			"kmz"			=>	"application/vnd.google-earth.kmz",
			"kne"			=>	"application/vnd.kinar",
			"kon"			=>	"application/vnd.kde.kontour",
			"kpr"			=>	"application/vnd.kde.kpresenter",
			"ksp"			=>	"application/vnd.kde.kspread",
			"ktx"			=>	"image/ktx",
			"ktz"			=>	"application/vnd.kahootz",
			"kwd"			=>	"application/vnd.kde.kword",
			"lasxml"		=>	"application/vnd.las.las+xml",
			"latex"			=>	"application/x-latex",
			"lbd"			=>	"application/vnd.llamagraphics.life-balance.desktop",
			"lbe"			=>	"application/vnd.llamagraphics.life-balance.exchange+xml",
			"les"			=>	"application/vnd.hhe.lesson-player",
			"link66"		=>	"application/vnd.route66.link66+xml",
			"lrm"			=>	"application/vnd.ms-lrm",
			"ltf"			=>	"application/vnd.frogans.ltf",
			"lvp"			=>	"audio/vnd.lucent.voice",
			"lwp"			=>	"application/vnd.lotus-wordpro",
			"m21"			=>	"application/mp21",
			"m3u"			=>	"audio/x-mpegurl",
			"m3u8"			=>	"application/vnd.apple.mpegurl",
			"m4v"			=>	"video/x-m4v",
			"ma"			=>	"application/mathematica",
			"mads"			=>	"application/mads+xml",
			"mag"			=>	"application/vnd.ecowin.chart",
			"map"			=>	"application/json",
			"mathml"		=>	"application/mathml+xml",
			"mbk"			=>	"application/vnd.mobius.mbk",
			"mbox"			=>	"application/mbox",
			"mc1"			=>	"application/vnd.medcalcdata",
			"mcd"			=>	"application/vnd.mcd",
			"mcurl"			=>	"text/vnd.curl.mcurl",
			"md"			=>	"text/x-markdown", // http://bit.ly/1Kc5nUB
			"mdb"			=>	"application/x-msaccess",
			"mdi"			=>	"image/vnd.ms-modi",
			"meta4"			=>	"application/metalink4+xml",
			"mets"			=>	"application/mets+xml",
			"mfm"			=>	"application/vnd.mfmp",
			"mgp"			=>	"application/vnd.osgeo.mapguide.package",
			"mgz"			=>	"application/vnd.proteus.magazine",
			"mid"			=>	"audio/midi",
			"mif"			=>	"application/vnd.mif",
			"mj2"			=>	"video/mj2",
			"mlp"			=>	"application/vnd.dolby.mlp",
			"mmd"			=>	"application/vnd.chipnuts.karaoke-mmd",
			"mmf"			=>	"application/vnd.smaf",
			"mmr"			=>	"image/vnd.fujixerox.edmics-mmr",
			"mny"			=>	"application/x-msmoney",
			"mods"			=>	"application/mods+xml",
			"movie"			=>	"video/x-sgi-movie",
			"mp1"			=>	"audio/mpeg",
			"mp2"			=>	"audio/mpeg",
			"mp3"			=>	"audio/mpeg",
			"mp4"			=>	"video/mp4",
			"mp4a"			=>	"audio/mp4",
			"mpc"			=>	"application/vnd.mophun.certificate",
			"mpeg"			=>	"video/mpeg",
			"mpga"			=>	"audio/mpeg",
			"mpkg"			=>	"application/vnd.apple.installer+xml",
			"mpm"			=>	"application/vnd.blueice.multipass",
			"mpn"			=>	"application/vnd.mophun.application",
			"mpp"			=>	"application/vnd.ms-project",
			"mpy"			=>	"application/vnd.ibm.minipay",
			"mqy"			=>	"application/vnd.mobius.mqy",
			"mrc"			=>	"application/marc",
			"mrcx"			=>	"application/marcxml+xml",
			"mscml"			=>	"application/mediaservercontrol+xml",
			"mseq"			=>	"application/vnd.mseq",
			"msf"			=>	"application/vnd.epson.msf",
			"msh"			=>	"model/mesh",
			"msl"			=>	"application/vnd.mobius.msl",
			"msty"			=>	"application/vnd.muvee.style",
			"mts"			=>	"model/vnd.mts",
			"mus"			=>	"application/vnd.musician",
			"musicxml"		=>	"application/vnd.recordare.musicxml+xml",
			"mvb"			=>	"application/x-msmediaview",
			"mwf"			=>	"application/vnd.mfer",
			"mxf"			=>	"application/mxf",
			"mxl"			=>	"application/vnd.recordare.musicxml",
			"mxml"			=>	"application/xv+xml",
			"mxs"			=>	"application/vnd.triscape.mxs",
			"mxu"			=>	"video/vnd.mpegurl",
			"n3"			=>	"text/n3",
			"nbp"			=>	"application/vnd.wolfram.player",
			"nc"			=>	"application/x-netcdf",
			"ncx"			=>	"application/x-dtbncx+xml",
			"n-gage"		=>	"application/vnd.nokia.n-gage.symbian.install",
			"ngdat"			=>	"application/vnd.nokia.n-gage.data",
			"nlu"			=>	"application/vnd.neurolanguage.nlu",
			"nml"			=>	"application/vnd.enliven",
			"nnd"			=>	"application/vnd.noblenet-directory",
			"nns"			=>	"application/vnd.noblenet-sealer",
			"nnw"			=>	"application/vnd.noblenet-web",
			"npx"			=>	"image/vnd.net-fpx",
			"nsf"			=>	"application/vnd.lotus-notes",
			"oa2"			=>	"application/vnd.fujitsu.oasys2",
			"oa3"			=>	"application/vnd.fujitsu.oasys3",
			"oas"			=>	"application/vnd.fujitsu.oasys",
			"obd"			=>	"application/x-msbinder",
			"oda"			=>	"application/oda",
			"odb"			=>	"application/vnd.oasis.opendocument.database",
			"odc"			=>	"application/vnd.oasis.opendocument.chart",
			"odf"			=>	"application/vnd.oasis.opendocument.formula",
			"odft"			=>	"application/vnd.oasis.opendocument.formula-template",
			"odg"			=>	"application/vnd.oasis.opendocument.graphics",
			"odi"			=>	"application/vnd.oasis.opendocument.image",
			"odm"			=>	"application/vnd.oasis.opendocument.text-master",
			"odp"			=>	"application/vnd.oasis.opendocument.presentation",
			"ods"			=>	"application/vnd.oasis.opendocument.spreadsheet",
			"odt"			=>	"application/vnd.oasis.opendocument.text",
			"oga"			=>	"audio/ogg",
			"ogv"			=>	"video/ogg",
			"ogx"			=>	"application/ogg",
			"onetoc"		=>	"application/onenote",
			"opf"			=>	"application/oebps-package+xml",
			"org"			=>	"application/vnd.lotus-organizer",
			"osf"			=>	"application/vnd.yamaha.openscoreformat",
			"osfpvg"		=>	"application/vnd.yamaha.openscoreformat.osfpvg+xml",
			"otc"			=>	"application/vnd.oasis.opendocument.chart-template",
			"otf"			=>	"application/x-font-otf",
			"otg"			=>	"application/vnd.oasis.opendocument.graphics-template",
			"oth"			=>	"application/vnd.oasis.opendocument.text-web",
			"oti"			=>	"application/vnd.oasis.opendocument.image-template",
			"otp"			=>	"application/vnd.oasis.opendocument.presentation-template",
			"ots"			=>	"application/vnd.oasis.opendocument.spreadsheet-template",
			"ott"			=>	"application/vnd.oasis.opendocument.text-template",
			"oxt"			=>	"application/vnd.openofficeorg.extension",
			"p"			=>	"text/x-pascal",
			"p10"			=>	"application/pkcs10",
			"p12"			=>	"application/x-pkcs12",
			"p7b"			=>	"application/x-pkcs7-certificates",
			"p7m"			=>	"application/pkcs7-mime",
			"p7r"			=>	"application/x-pkcs7-certreqresp",
			"p7s"			=>	"application/pkcs7-signature",
			"p8"			=>	"application/pkcs8",
			"par"			=>	"text/plain-bas",
			"paw"			=>	"application/vnd.pawaafile",
			"pbd"			=>	"application/vnd.powerbuilder6",
			"pbm"			=>	"image/x-portable-bitmap",
			"pcf"			=>	"application/x-font-pcf",
			"pcl"			=>	"application/vnd.hp-pcl",
			"pclxl"			=>	"application/vnd.hp-pclxl",
			"pcurl"			=>	"application/vnd.curl.pcurl",
			"pcx"			=>	"image/x-pcx",
			"pdb"			=>	"application/vnd.palm",
			"pdf"			=>	"application/pdf",
			"pfa"			=>	"application/x-font-type1",
			"pfr"			=>	"application/font-tdpfr",
			"pgm"			=>	"image/x-portable-graymap",
			"pgn"			=>	"application/x-chess-pgn",
			"pgp"			=>	"application/pgp-signature",
			"pic"			=>	"image/x-pict",
			"pki"			=>	"application/pkixcmp",
			"pkipath"		=>	"application/pkix-pkipath",
			"plb"			=>	"application/vnd.3gpp.pic-bw-large",
			"plc"			=>	"application/vnd.mobius.plc",
			"plf"			=>	"application/vnd.pocketlearn",
			"pls"			=>	"application/pls+xml",
			"pml"			=>	"application/vnd.ctc-posml",
			"png"			=>	"image/png",
			"pnm"			=>	"image/x-portable-anymap",
			"portpkg"		=>	"application/vnd.macports.portpkg",
			"potm"			=>	"application/vnd.ms-powerpoint.template.macroenabled.12",
			"potx"			=>	"application/vnd.openxmlformats-officedocument.presentationml.template",
			"ppam"			=>	"application/vnd.ms-powerpoint.addin.macroenabled.12",
			"ppd"			=>	"application/vnd.cups-ppd",
			"ppm"			=>	"image/x-portable-pixmap",
			"ppsm"			=>	"application/vnd.ms-powerpoint.slideshow.macroenabled.12",
			"ppsx"			=>	"application/vnd.openxmlformats-officedocument.presentationml.slideshow",
			"ppt"			=>	"application/vnd.ms-powerpoint",
			"pptm"			=>	"application/vnd.ms-powerpoint.presentation.macroenabled.12",
			"pptx"			=>	"application/vnd.openxmlformats-officedocument.presentationml.presentation",
			"prc"			=>	"application/x-mobipocket-ebook",
			"pre"			=>	"application/vnd.lotus-freelance",
			"prf"			=>	"application/pics-rules",
			"psb"			=>	"application/vnd.3gpp.pic-bw-small",
			"psd"			=>	"image/vnd.adobe.photoshop",
			"psf"			=>	"application/x-font-linux-psf",
			"pskcxml"		=>	"application/pskc+xml",
			"ptid"			=>	"application/vnd.pvi.ptid1",
			"pub"			=>	"application/x-mspublisher",
			"pvb"			=>	"application/vnd.3gpp.pic-bw-var",
			"pwn"			=>	"application/vnd.3m.post-it-notes",
			"pya"			=>	"audio/vnd.ms-playready.media.pya",
			"pyv"			=>	"video/vnd.ms-playready.media.pyv",
			"qam"			=>	"application/vnd.epson.quickanime",
			"qbo"			=>	"application/vnd.intu.qbo",
			"qfx"			=>	"application/vnd.intu.qfx",
			"qps"			=>	"application/vnd.publishare-delta-tree",
			"qt"			=>	"video/quicktime",
			"qxd"			=>	"application/vnd.quark.quarkxpress",
			"ram"			=>	"audio/x-pn-realaudio",
			"rar"			=>	"application/x-rar-compressed",
			"ras"			=>	"image/x-cmu-raster",
			"rcprofile"		=>	"application/vnd.ipunplugged.rcprofile",
			"rdf"			=>	"application/rdf+xml",
			"rdz"			=>	"application/vnd.data-vision.rdz",
			"rep"			=>	"application/vnd.businessobjects",
			"res"			=>	"application/x-dtbresource+xml",
			"rgb"			=>	"image/x-rgb",
			"rif"			=>	"application/reginfo+xml",
			"rip"			=>	"audio/vnd.rip",
			"rl"			=>	"application/resource-lists+xml",
			"rlc"			=>	"image/vnd.fujixerox.edmics-rlc",
			"rld"			=>	"application/resource-lists-diff+xml",
			"rm"			=>	"application/vnd.rn-realmedia",
			"rmp"			=>	"audio/x-pn-realaudio-plugin",
			"rms"			=>	"application/vnd.jcp.javame.midlet-rms",
			"rnc"			=>	"application/relax-ng-compact-syntax",
			"rp9"			=>	"application/vnd.cloanto.rp9",
			"rpss"			=>	"application/vnd.nokia.radio-presets",
			"rpst"			=>	"application/vnd.nokia.radio-preset",
			"rq"			=>	"application/sparql-query",
			"rs"			=>	"application/rls-services+xml",
			"rsd"			=>	"application/rsd+xml",
			"rss"			=>	"application/rss+xml",
			"rtf"			=>	"application/rtf",
			"rtx"			=>	"text/richtext",
			"s"			=>	"text/x-asm",
			"saf"			=>	"application/vnd.yamaha.smaf-audio",
			"sbml"			=>	"application/sbml+xml",
			"sc"			=>	"application/vnd.ibm.secure-container",
			"scd"			=>	"application/x-msschedule",
			"scm"			=>	"application/vnd.lotus-screencam",
			"scq"			=>	"application/scvp-cv-request",
			"scs"			=>	"application/scvp-cv-response",
			"scurl"			=>	"text/vnd.curl.scurl",
			"sda"			=>	"application/vnd.stardivision.draw",
			"sdc"			=>	"application/vnd.stardivision.calc",
			"sdd"			=>	"application/vnd.stardivision.impress",
			"sdkm"			=>	"application/vnd.solent.sdkm+xml",
			"sdp"			=>	"application/sdp",
			"sdw"			=>	"application/vnd.stardivision.writer",
			"see"			=>	"application/vnd.seemail",
			"seed"			=>	"application/vnd.fdsn.seed",
			"sema"			=>	"application/vnd.sema",
			"semd"			=>	"application/vnd.semd",
			"semf"			=>	"application/vnd.semf",
			"ser"			=>	"application/java-serialized-object",
			"setpay"		=>	"application/set-payment-initiation",
			"setreg"		=>	"application/set-registration-initiation",
			"sfd-hdstx"		=>	"application/vnd.hydrostatix.sof-data",
			"sfs"			=>	"application/vnd.spotfire.sfs",
			"sgl"			=>	"application/vnd.stardivision.writer-global",
			"sgml"			=>	"text/sgml",
			"sh"			=>	"application/x-sh",
			"shar"			=>	"application/x-shar",
			"shf"			=>	"application/shf+xml",
			"sis"			=>	"application/vnd.symbian.install",
			"sit"			=>	"application/x-stuffit",
			"sitx"			=>	"application/x-stuffitx",
			"skp"			=>	"application/vnd.koan",
			"sldm"			=>	"application/vnd.ms-powerpoint.slide.macroenabled.12",
			"sldx"			=>	"application/vnd.openxmlformats-officedocument.presentationml.slide",
			"slt"			=>	"application/vnd.epson.salt",
			"sm"			=>	"application/vnd.stepmania.stepchart",
			"smf"			=>	"application/vnd.stardivision.math",
			"smi"			=>	"application/smil+xml",
			"snf"			=>	"application/x-font-snf",
			"spf"			=>	"application/vnd.yamaha.smaf-phrase",
			"spl"			=>	"application/x-futuresplash",
			"spot"			=>	"text/vnd.in3d.spot",
			"spp"			=>	"application/scvp-vp-response",
			"spq"			=>	"application/scvp-vp-request",
			"src"			=>	"application/x-wais-source",
			"sru"			=>	"application/sru+xml",
			"srx"			=>	"application/sparql-results+xml",
			"sse"			=>	"application/vnd.kodak-descriptor",
			"ssf"			=>	"application/vnd.epson.ssf",
			"ssml"			=>	"application/ssml+xml",
			"st"			=>	"application/vnd.sailingtracker.track",
			"stc"			=>	"application/vnd.sun.xml.calc.template",
			"std"			=>	"application/vnd.sun.xml.draw.template",
			"step"          =>  "text/plain",
			"stf"			=>	"application/vnd.wt.stf",
			"sti"			=>	"application/vnd.sun.xml.impress.template",
			"stk"			=>	"application/hyperstudio",
			"stl"			=>	"application/vnd.ms-pki.stl",
			"stp"           =>  "text/plain",
			"str"			=>	"application/vnd.pg.format",
			"stw"			=>	"application/vnd.sun.xml.writer.template",
			"sub"			=>	"image/vnd.dvb.subtitle",
			"sus"			=>	"application/vnd.sus-calendar",
			"sv4cpio"		=>	"application/x-sv4cpio",
			"sv4crc"		=>	"application/x-sv4crc",
			"svc"			=>	"application/vnd.dvb.service",
			"svd"			=>	"application/vnd.svd",
			"svg"			=>	"image/svg+xml",
			"swf"			=>	"application/x-shockwave-flash",
			"swi"			=>	"application/vnd.aristanetworks.swi",
			"sxc"			=>	"application/vnd.sun.xml.calc",
			"sxd"			=>	"application/vnd.sun.xml.draw",
			"sxg"			=>	"application/vnd.sun.xml.writer.global",
			"sxi"			=>	"application/vnd.sun.xml.impress",
			"sxm"			=>	"application/vnd.sun.xml.math",
			"sxw"			=>	"application/vnd.sun.xml.writer",
			"t"			=>	"text/troff",
			"tao"			=>	"application/vnd.tao.intent-module-archive",
			"tar"			=>	"application/x-tar",
			"tcap"			=>	"application/vnd.3gpp2.tcap",
			"tcl"			=>	"application/x-tcl",
			"teacher"		=>	"application/vnd.smart.teacher",
			"tei"			=>	"application/tei+xml",
			"tex"			=>	"application/x-tex",
			"texinfo"		=>	"application/x-texinfo",
			"tfi"			=>	"application/thraud+xml",
			"tfm"			=>	"application/x-tex-tfm",
			"thmx"			=>	"application/vnd.ms-officetheme",
			"tiff"			=>	"image/tiff",
			"tmo"			=>	"application/vnd.tmobile-livetv",
			"torrent"		=>	"application/x-bittorrent",
			"tpl"			=>	"application/vnd.groove-tool-template",
			"tpt"			=>	"application/vnd.trid.tpt",
			"tra"			=>	"application/vnd.trueapp",
			"trm"			=>	"application/x-msterminal",
			"tsd"			=>	"application/timestamped-data",
			"tsv"			=>	"text/tab-separated-values",
			"ttf"			=>	"application/x-font-ttf",
			"ttl"			=>	"text/turtle",
			"twd"			=>	"application/vnd.simtech-mindmapper",
			"txd"			=>	"application/vnd.genomatix.tuxedo",
			"txf"			=>	"application/vnd.mobius.txf",
			"txt"			=>	"text/plain",
			"ufd"			=>	"application/vnd.ufdl",
			"umj"			=>	"application/vnd.umajin",
			"unityweb"		=>	"application/vnd.unity",
			"uoml"			=>	"application/vnd.uoml+xml",
			"uri"			=>	"text/uri-list",
			"ustar"			=>	"application/x-ustar",
			"utz"			=>	"application/vnd.uiq.theme",
			"uu"			=>	"text/x-uuencode",
			"uva"			=>	"audio/vnd.dece.audio",
			"uvh"			=>	"video/vnd.dece.hd",
			"uvi"			=>	"image/vnd.dece.graphic",
			"uvm"			=>	"video/vnd.dece.mobile",
			"uvp"			=>	"video/vnd.dece.pd",
			"uvs"			=>	"video/vnd.dece.sd",
			"uvu"			=>	"video/vnd.uvvu.mp4",
			"uvv"			=>	"video/vnd.dece.video",
			"vcd"			=>	"application/x-cdlink",
			"vcf"			=>	"text/x-vcard",
			"vcg"			=>	"application/vnd.groove-vcard",
			"vcs"			=>	"text/x-vcalendar",
			"vcx"			=>	"application/vnd.vcx",
			"vis"			=>	"application/vnd.visionary",
			"viv"			=>	"video/vnd.vivo",
			"vsd"			=>	"application/vnd.visio",
			"vsf"			=>	"application/vnd.vsf",
			"vtu"			=>	"model/vnd.vtu",
			"vxml"			=>	"application/voicexml+xml",
			"wad"			=>	"application/x-doom",
			"wav"			=>	"audio/x-wav",
			"wax"			=>	"audio/x-ms-wax",
			"wbmp"			=>	"image/vnd.wap.wbmp",
			"wbs"			=>	"application/vnd.criticaltools.wbs+xml",
			"wbxml"			=>	"application/vnd.wap.wbxml",
			"weba"			=>	"audio/webm",
			"webm"			=>	"video/webm",
			"webp"			=>	"image/webp",
			"wg"			=>	"application/vnd.pmi.widget",
			"wgt"			=>	"application/widget",
			"wm"			=>	"video/x-ms-wm",
			"wma"			=>	"audio/x-ms-wma",
			"wmd"			=>	"application/x-ms-wmd",
			"wmf"			=>	"application/x-msmetafile",
			"wml"			=>	"text/vnd.wap.wml",
			"wmlc"			=>	"application/vnd.wap.wmlc",
			"wmls"			=>	"text/vnd.wap.wmlscript",
			"wmlsc"			=>	"application/vnd.wap.wmlscriptc",
			"wmv"			=>	"video/x-ms-wmv",
			"wmx"			=>	"video/x-ms-wmx",
			"wmz"			=>	"application/x-ms-wmz",
			"woff"			=>	"application/x-font-woff",
			"woff2"			=>	"application/font-woff2",
			"wpd"			=>	"application/vnd.wordperfect",
			"wpl"			=>	"application/vnd.ms-wpl",
			"wps"			=>	"application/vnd.ms-works",
			"wqd"			=>	"application/vnd.wqd",
			"wri"			=>	"application/x-mswrite",
			"wrl"			=>	"model/vrml",
			"wsdl"			=>	"application/wsdl+xml",
			"wspolicy"		=>	"application/wspolicy+xml",
			"wtb"			=>	"application/vnd.webturbo",
			"wvx"			=>	"video/x-ms-wvx",
			"x3d"			=>	"application/vnd.hzn-3d-crossword",
			"xap"			=>	"application/x-silverlight-app",
			"xar"			=>	"application/vnd.xara",
			"xbap"			=>	"application/x-ms-xbap",
			"xbd"			=>	"application/vnd.fujixerox.docuworks.binder",
			"xbm"			=>	"image/x-xbitmap",
			"xdf"			=>	"application/xcap-diff+xml",
			"xdm"			=>	"application/vnd.syncml.dm+xml",
			"xdp"			=>	"application/vnd.adobe.xdp+xml",
			"xdssc"			=>	"application/dssc+xml",
			"xdw"			=>	"application/vnd.fujixerox.docuworks",
			"xenc"			=>	"application/xenc+xml",
			"xer"			=>	"application/patch-ops-error+xml",
			"xfdf"			=>	"application/vnd.adobe.xfdf",
			"xfdl"			=>	"application/vnd.xfdl",
			"xhtml"			=>	"application/xhtml+xml",
			"xif"			=>	"image/vnd.xiff",
			"xlam"			=>	"application/vnd.ms-excel.addin.macroenabled.12",
			"xls"			=>	"application/vnd.ms-excel",
			"xlsb"			=>	"application/vnd.ms-excel.sheet.binary.macroenabled.12",
			"xlsm"			=>	"application/vnd.ms-excel.sheet.macroenabled.12",
			"xlsx"			=>	"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
			"xltm"			=>	"application/vnd.ms-excel.template.macroenabled.12",
			"xltx"			=>	"application/vnd.openxmlformats-officedocument.spreadsheetml.template",
			"xml"			=>	"application/xml",
			"xo"			=>	"application/vnd.olpc-sugar",
			"xop"			=>	"application/xop+xml",
			"xpi"			=>	"application/x-xpinstall",
			"xpm"			=>	"image/x-xpixmap",
			"xpr"			=>	"application/vnd.is-xpr",
			"xps"			=>	"application/vnd.ms-xpsdocument",
			"xpw"			=>	"application/vnd.intercon.formnet",
			"xslt"			=>	"application/xslt+xml",
			"xsm"			=>	"application/vnd.syncml+xml",
			"xspf"			=>	"application/xspf+xml",
			"xul"			=>	"application/vnd.mozilla.xul+xml",
			"xwd"			=>	"image/x-xwindowdump",
			"xyz"			=>	"chemical/x-xyz",
			"yaml"			=>	"text/yaml",
			"yang"			=>	"application/yang",
			"yin"			=>	"application/yin+xml",
			"zaz"			=>	"application/vnd.zzazz.deck+xml",
			"zip"			=>	"application/zip",
			"zir"			=>	"application/vnd.zul",
			"zmm"			=>	"application/vnd.handheld-entertainment+xml"
		);

		$mime_type = apply_filters("ezfc_mime_types", $mime_type);

		if (isset($mime_type[$extension])) {
			return $mime_type[$extension];
		}

		return false;
	}

	public static function multi_implode($glue, $array) {
		$ret = '';

		foreach ($array as $item) {
			if (is_array($item)) {
				$ret .= self::multi_implode($glue, $item) . $glue;
			} else {
				$ret .= $item . $glue;
			}
		}

		$ret = substr($ret, 0, 0-strlen($glue));

		return $ret;
	}
}