<?php

class Ezfc_shortcode {
	static $add_script;
	static $ezfc_frontend;
	static $is_preview;

	static function init() {
		add_shortcode("ezfc", array(__CLASS__, "get_output"));
		add_shortcode("ezfc_cond", array(__CLASS__, "conditional_content"));
		add_shortcode("ezfc_value", array(__CLASS__, "value_output"));
		add_shortcode("ezfc_verify", array(__CLASS__, "paypal_verify"));

		add_action("wp_enqueue_scripts", array(__CLASS__, "wp_head"));
		add_action("wp_footer", array(__CLASS__, "print_script"));

		// woocommerce add form hook
		$woo_add_hook = get_option("ezfc_woocommerce_add_hook");
		if (!empty($woo_add_hook) && get_option("ezfc_woocommerce_add_forms") == 1) {
			add_action($woo_add_hook, array(__CLASS__, "woo_add_form"));
		}
	}

	static function load_frontend() {
		if (self::$ezfc_frontend) return;

		require_once(EZFC_PATH . "class.ezfc_frontend.php");
		self::$ezfc_frontend = Ezfc_frontend::instance();
	}

	static function conditional_content($atts, $content = null) {
		self::load_frontend();

		extract(shortcode_atts(array(
			"e_id"     => "",
			"e_name"   => "",
			"operator" => "=",
			"value"    => "",
		), $atts));

		if (empty($e_id) && empty($e_name)) return __("Empty element ID and name.", "ezfc");

		// get element ID by name
		if (!empty($e_name)) {
			foreach (self::$ezfc_frontend->submission_data["form_elements"] as $id => $element) {
				$element_data = json_decode($element->data);

				// name found
				if (strtolower($element_data->name) == strtolower($e_name)) {
					$e_id = $id;
					break;
				}
			}
		}

		// get value from element
		if ($e_id) {
			// not set
			if (!isset(self::$ezfc_frontend->submission_data["raw_values"][$e_id])) return "";

			$calculated_value = self::$ezfc_frontend->get_calculated_target_value_from_input($e_id, self::$ezfc_frontend->submission_data["raw_values"][$e_id]);

			// check if condition is true. use equals operator by default
			$check_compare = $calculated_value == $value;

			switch ($operator) {
				case ">":  $check_compare = $calculated_value > $value; break;
				case ">=": $check_compare = $calculated_value >= $value; break;
				case "<":  $check_compare = $calculated_value < $value; break;
				case "<=": $check_compare = $calculated_value <= $value; break;
				case "!=": $check_compare = $calculated_value != $value; break;
			}
			
			// compare value
			if ($check_compare) {
				// condition is true, return content
				return $content;
			}
		}

		// return nothing since condition is not met
		return "";
	}

	static function get_form_output($id=null, $name=null, $product_id=null, $theme=null, $preview=null) {
		return self::$ezfc_frontend->get_output($id, $name, $product_id, $theme, $preview);
	}

	static function get_output($atts) {
		self::load_frontend();
		self::$add_script = true;

		extract(shortcode_atts(array(
			"id"       => null,
			"name"     => null,
			"preview"  => null,
			"theme"    => null
		), $atts));

		self::$is_preview = $preview !== null;

		return self::get_form_output($id, $name, null, $theme, $preview);
	}

	static function paypal_verify($atts, $content = null) {
		self::load_frontend();
		require_once(EZFC_PATH . "lib/paypal/expresscheckout.php");

		if (!isset($_GET["PayerID"]) || !isset($_GET["token"])) return __("No PayerID or no token.", "ezfc");

		// get total
		$submission = self::$ezfc_frontend->submission_paypal_get($_GET["token"]);

		if (!$submission) return __("Unable to find submission by token.", "ezfc");

		// verify paypal payment
		$_SESSION["payer_id"]         = $_GET["PayerID"];
		$_SESSION["TOKEN"]            = $_GET["token"];
		$_SESSION["PaymentType"]      = "Sale";
		$_SESSION["currencyCodeType"] = get_option("ezfc_pp_currency_code", "USD");
		$_SESSION["Payment_Amount"]   = $submission->total;

		$confirmation = Ezfc_paypal::confirm();

		// no payment
		if (!$confirmation || isset($confirmation["error"])) {
			$return = __("Payment could not be verified. :(", "ezfc");

			if (get_option("ezfc_debug_mode")) {
				$return .= "<br>" . var_export($confirmation, true);
			}

			return $return;
		}

		// user paid $$$ --> update submission
		$update = self::$ezfc_frontend->update_submission_paypal($_GET["token"], $confirmation["transaction_id"]);

		// update submission error
		if (!empty($update["error"])) return $update["error"];

		// get form options
		$options = self::$ezfc_frontend->form_get_option_values($update["submission"]->f_id);

		// prepare submission data for mails
		self::$ezfc_frontend->prepare_submission_data($update["submission"]->f_id, array("ezfc_element" => json_decode($update["submission"]->data, true)), true, $update["submission"]->ref_id, $update["submission"]->id);

		$submission_data = self::$ezfc_frontend->submission_data;

		// form email target
		self::$ezfc_frontend->check_conditional_email_target($submission_data);

		// prepare replace values
		self::$ezfc_frontend->prepare_replace_values($submission_data);
		
		// send mails
		self::$ezfc_frontend->send_mails($submission_data["submission_id"]);

		$success_text = apply_filters("ezfc_success_text_paypal", self::$ezfc_frontend->replace_values_text($options["pp_paid_text"]), $submission_data);

		$success_text = apply_filters("the_content", $success_text);

		return $success_text;
	}

	static function value_output($atts, $content = null) {
		self::load_frontend();

		extract(shortcode_atts(array(
			"name" => ""
		), $atts));

		if (session_id() == "") @session_start();

		if (empty($name) || empty($_SESSION["ezfc_submission_values"]) || !isset($_SESSION["ezfc_submission_values"][$name])) return "";

		$value = apply_filters("ezfc_shortcode_value", $_SESSION["ezfc_submission_values"][$name], $name, $_SESSION["ezfc_submission_values"]);

		return $value;
	}

	static function woo_add_form() {
		$product_id = get_the_ID();
		// check if we are in the loop
		if (!$product_id) return;

		// check post meta
		$form_id = get_post_meta($product_id, "ezfc_form_id", true);

		// no single form id found -> get global form id instead
		if (!$form_id) {
			$global_form_id = get_option("ezfc_woocommerce_global_form_id");

			// no global form found
			if (empty($global_form_id)) return;

			// check if form is restricted to categories
			$form_options = self::$ezfc_frontend->form_get_option_values($global_form_id);
			$cat_forms = array_filter(explode(",", $form_options["woo_categories"]));

			if (count($cat_forms) > 0) {
				$category_name = wp_get_post_terms($product_id, "product_cat");
				$form_add = false;

				foreach ($category_name as $cat) {
					if (in_array($cat->slug, $cat_forms)) {
						$form_add = true;
						break;
					}
				}

				if (!$form_add) return;
			}

			$form_id = $global_form_id;
		}

		self::$add_script = true;

		echo self::get_form_output($form_id, null, $product_id);
	}

	static function wp_head() {
		wp_register_style("ezfc-css-frontend", plugins_url("style-frontend.css", __FILE__), array(), EZFC_VERSION);
		wp_register_style("ezfc-opentip", plugins_url("assets/css/opentip.css", __FILE__));
		wp_register_style("ezfc-font-awesome", plugins_url("assets/css/font-awesome.min.css", __FILE__));

		// custom css
		$custom_css = get_option("ezfc_custom_css");
		if (!empty($custom_css)) {
			wp_add_inline_style("ezfc-css-frontend", $custom_css);
		}

		// generated css
		if (get_option("ezfc_load_custom_styling", 0) == 1) {
			wp_add_inline_style("ezfc-css-frontend", get_option("ezfc_css_custom_styling", ""));
		}

		// jquery theme
		if (get_option("ezfc_jquery_ui", 1) == 1) {
			wp_register_style("ezfc-jquery-ui", plugins_url("assets/css/jquery-ui.min.css", __FILE__));
			wp_register_style("ezfc-jquery-ui-theme", plugins_url("assets/css/jquery-ui.theme.min.css", __FILE__));
		}

		// register scripts
		do_action("ezfc_ext_enqueue_head");
	}

	static function print_script() {
		if ( ! self::$add_script )
			return;

		$frontend   = Ezfc_frontend::instance();
		$debug_mode = get_option("ezfc_debug_mode", 0);

		// main styles
		wp_enqueue_style("ezfc-css-frontend");
		wp_enqueue_style("ezfc-opentip");
		wp_enqueue_style("ezfc-font-awesome");
		// jquery theme
		if (get_option("ezfc_jquery_ui", 1) == 1) {
			wp_enqueue_style("ezfc-jquery-ui");
			wp_enqueue_style("ezfc-jquery-ui-theme");
		}

		// datepicker language
		if (get_option("ezfc_datepicker_load_languages", 0) == 1) {
			wp_enqueue_script("jquery-languages", plugins_url("assets/js/jquery.ui.i18n.all.min.js", __FILE__));
		}

		wp_enqueue_script("jquery");
		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-datepicker");
		wp_enqueue_script("jquery-ui-dialog");
		wp_enqueue_script("jquery-ui-widget");
		wp_enqueue_script("jquery-touch-punch", plugins_url("assets/js/jquery.ui.touch-punch.min.js", __FILE__), array("jquery"));
		wp_enqueue_script("jquery-opentip", plugins_url("assets/js/opentip-jquery.min.js", __FILE__), array("jquery"));
		wp_enqueue_script("ezfc-numeraljs", plugins_url("assets/js/numeral-v2.0.6.min.js", __FILE__), array("jquery"));
		wp_enqueue_script("jquery-countto", plugins_url("assets/js/jquery.countTo.min.js", __FILE__), array("jquery"));

		// get frontend file
		$calculation_version = get_option("ezfc_calculation_version", "current");
		$frontend_file_min = "frontend.min.js";
		wp_enqueue_script("ezfc-frontend", plugins_url($frontend_file_min, __FILE__), array("jquery"), EZFC_VERSION);

		// preview
		if (self::$is_preview) {
			wp_enqueue_script("ezfc-frontend-preview", plugins_url("frontend-preview.js", __FILE__), array("jquery"), microtime(true));
		}

		// general options
		wp_localize_script("ezfc-frontend", "ezfc_vars", array(
			"ajaxurl"   => admin_url( 'admin-ajax.php' ),
			"form_vars" => array(),
			"authorize" => array(
				"api_login_id" => get_option("ezfc_authorize_login_id"),
				"client_key"   => get_option("ezfc_authorize_client_key")
			),
			"auto_scroll_steps"          => get_option("ezfc_auto_scroll_steps", 1),
			"datepicker_language"        => get_option("ezfc_datepicker_language", "en"),
			"debug_mode"                 => $debug_mode,
			"element_vars"               => $frontend->element_js_vars,
			"form_submit_error"          => get_option("ezfc_form_submit_error", __("An unknown error occured. Please try again later.", "ezfc")),
			"noid"                       => __("No form with the requested ID found.", "ezfc"),
			"opentip" => array(
				"background" => get_option("ezfc_opentip_background", "yellow")
			),
			"price_format"               => get_option("ezfc_price_format"),
			"price_format_dec_num"       => get_option("ezfc_email_price_format_dec_num", 2),
			"price_format_dec_point"     => get_option("ezfc_email_price_format_dec_point", "."),
			"price_format_dec_thousand"  => get_option("ezfc_email_price_format_thousand", ","),
			"price_format_replace_trailing_zeros" => maybe_unserialize(get_option("ezfc_price_format_replace_trailing_zeros", array("enabled" => 0, "text" => "-"))),
			"required_text_position"     => get_option("ezfc_required_text_position", "middle right"),
			"required_text_auto_hide"    => get_option("ezfc_required_text_auto_hide", 0),
			"scroll_steps_offset"        => get_option("ezfc_scroll_steps_offset", -200),
			"stripe" => array(
				"publishable_key" => get_option("ezfc_stripe_publishable_key", "")
			),
			"uploading"                  => __("Uploading...", "ezfc"),
			"upload_error"               => __("File upload error. Please try again.", "ezfc"),
			"upload_remove_error"        => __("Unable to remove file.", "ezfc"),
			"upload_removed"             => __("File removed.", "ezfc"),
			"upload_success"             => __("File upload successful.", "ezfc"),
			"woocommerce_update_cart_selector" => get_option("ezfc_woocommerce_update_cart_selector"),
			"yes_no" => array(
				"yes" => __("Yes", "ezfc"),
				"no"  => __("No", "ezfc")
			)
		));

		/*// element vars
		$element_js_vars = json_encode($frontend->element_js_vars);
		$element_js_vars_output = ";ezfc_element_vars = {$element_js_vars};";
		wp_add_inline_script("ezfc-frontend", $element_js_vars_output);*/

		// custom JS
		if (get_option("ezfc_custom_js_enable", 0) == 1 && function_exists("wp_add_inline_script")) {
			wp_add_inline_script("ezfc-frontend", get_option("ezfc_custom_js"));
		}

		// extension scripts / styles
		do_action("ezfc_ext_enqueue_scripts");
	}
}
Ezfc_shortcode::init();