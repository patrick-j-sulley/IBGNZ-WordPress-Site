<?php
/*
Plugin Name: ez Form Calculator
Plugin URI: https://ez-form-calculator.ezplugins.de/
Description: With ez Form Calculator, you can simply create a form calculator for both yourself and your customers. Easily add basic form elements like checkboxes, dropdown menus, radio buttons etc. with only a few clicks. Each form element can be assigned a value which will be calculated automatically.
Version: 2.11.2.1
Author: Michael Schuppenies
Author URI: https://www.ezplugins.de/
Text Domain: ezfc
Domain Path: /lang
*/

defined( 'ABSPATH' ) OR exit;

if (!defined("EZFC_VERSION")) :

/**
	setup
**/
define("EZFC_VERSION", "2.11.2.1");
define("EZFC_PATH", trailingslashit(plugin_dir_path(__FILE__)));
define("EZFC_SLUG", plugin_basename(__FILE__));
define("EZFC_URL", trailingslashit(plugin_dir_url(__FILE__)));

define("EZFC_PAYMENT_ID_DEFAULT", 0);
define("EZFC_PAYMENT_ID_PAYPAL", 1);
define("EZFC_PAYMENT_ID_STRIPE", 2);

// ez functions
require_once(EZFC_PATH . "class.ezfc_functions.php");

// wrapper
function ezfc_get_version() {
	return EZFC_VERSION;
}

/**
	install
**/
function ezfc_register() {
	require_once(EZFC_PATH . "ezfc-register.php");
}

/**
	uninstall
**/
function ezfc_uninstall() {
	require_once(EZFC_PATH . "ezfc-uninstall.php");
}

// hooks
register_activation_hook(__FILE__, "ezfc_register");
register_uninstall_hook(__FILE__, "ezfc_uninstall");

// custom filter
add_filter("ezfc_custom_filter_test", "ezfc_test_filter", 0, 2);
function ezfc_test_filter($element_data, $input_value) {
	if ($input_value%2 == 1) {
		return array("error" => "Error!");
	}
}


class EZFC_Free {
	public static $scripts_loaded = false;

	/**
		init plugin
	**/
	static function init() {
		// setup pages
		add_action("admin_menu", array(__CLASS__, "admin_menu"));
		// load languages
		add_action("plugins_loaded", array(__CLASS__, "load_hooks"));

		// load backend scripts / styles
		add_action("admin_enqueue_scripts", array(__CLASS__, "load_scripts"));

		// settings page
		$ezfc_plugin_name = plugin_basename(__FILE__);
		add_filter("plugin_action_links_{$ezfc_plugin_name}", array(__CLASS__, "plugin_settings_page"));

		// ** ajax **
		// backend
		add_action("wp_ajax_ezfc_backend", array(__CLASS__, "ajax"));
		// frontend
		add_action("wp_ajax_ezfc_frontend", array(__CLASS__, "ajax_frontend"));
		add_action("wp_ajax_nopriv_ezfc_frontend", array(__CLASS__, "ajax_frontend"));
		// frontend fileupload
		add_action("wp_ajax_ezfc_frontend_fileupload", array(__CLASS__, "ajax_fileupload"));
		add_action("wp_ajax_nopriv_ezfc_frontend_fileupload", array(__CLASS__, "ajax_fileupload"));

		// tinymce
		add_action("admin_head", array(__CLASS__, "tinymce"));
		add_action("admin_print_scripts", array(__CLASS__, "tinymce_script"));

		// widget
		add_action("widgets_init", array(__CLASS__, "register_widget"));

		// download file
		add_action("init", array(__CLASS__, "add_rewrite_rules"));
		add_filter("query_vars", array(__CLASS__, "download_file_add_query_vars"));
		add_action("parse_request", array(__CLASS__, "download_file_parse_request"));

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
	}

	/**
		admin pages
	**/
	static function admin_menu() {
		// user role
		$role = get_option("ezfc_user_roles", "administrator");
		
		require_once(EZFC_PATH . "class.ezfc_backend.php");
		$ezfc_backend = Ezfc_backend::instance();

		// setup pages
		add_menu_page("ezfc", "ez Form Calculator", $role, "ezfc", array(__CLASS__, "page_main"), EZFC_URL . "assets/img/ez-icon.png");
		add_submenu_page("ezfc", __("Form settings", "ezfc"), __("Form settings", "ezfc"), $role, "ezfc-settings-form", array(__CLASS__, "page_settings_form"));
		add_submenu_page("ezfc", __("Global settings", "ezfc"), __("Global settings", "ezfc"), $role, "ezfc-options", array(__CLASS__, "page_settings"));
		add_submenu_page("ezfc", __("Form submissions", "ezfc"), __("Form submissions", "ezfc"), $role, "ezfc-submissions", array(__CLASS__, "page_submissions"));
		add_submenu_page("ezfc", __("Import / export", "ezfc"), __("Import / Export", "ezfc"), $role, "ezfc-importexport", array(__CLASS__, "page_importexport"));
		add_submenu_page("ezfc", __("Help / debug", "ezfc"), __("Help / debug", "ezfc"), $role, "ezfc-help", array(__CLASS__, "page_help"));
		add_submenu_page("ezfc", __("Preview", "ezfc"), __("Preview", "ezfc"), $role, "ezfc-preview", array(__CLASS__, "page_preview"));
		//add_submenu_page("ezfc", __("Wizard", "ezfc"), __("Wizard", "ezfc"), $role, "ezfc-wizard", array(__CLASS__, "page_wizard"));
		add_submenu_page("ezfc", __("Premium version", "ezfc"), __("Premium version", "ezfc"), $role, "ezfc-premium", array(__CLASS__, "page_premium"));
	}

	static function show_register_notice() {
		echo '
		<div class="updated">
		  <p>' . __("Hello! Please register your license to receive automatic updates for <strong>ez Form Calculator Premium</strong>.", "ezfc") . ' <a href="' . admin_url('admin.php') . '?page=ezfc-licensing">' . __("Register license", "ezfc") . '</a></p>
		</div>';
	}

	static function page_main() {
		require_once(EZFC_PATH . "ezfc-page-main.php");
	}

	static function page_settings_form() {
		require_once(EZFC_PATH . "ezfc-page-settings-form.php");
	}

	static function page_settings() {
		require_once(EZFC_PATH . "ezfc-page-settings.php");
	}

	static function page_importexport() {
		require_once(EZFC_PATH . "ezfc-page-importexport.php");
	}

	static function page_help() {
		require_once(EZFC_PATH . "ezfc-page-help.php");
	}

	static function page_licensing() {
		require_once(EZFC_PATH . "ezfc-page-licensing.php");
	}

	static function page_preview() {
		require_once(EZFC_PATH . "ezfc-page-preview.php");
	}

	static function page_premium() {
		require_once(EZFC_PATH . "ezfc-page-premium.php");
	}

	static function page_submissions() {
		require_once(EZFC_PATH . "ezfc-page-submissions.php");
	}


	static function page_templates() {
		require_once(EZFC_PATH . "ezfc-page-templates.php");
	}

	static function page_update() {
		require_once(EZFC_PATH . "ezfc-page-update.php");
	}

	static function page_wizard() {
		require_once(EZFC_PATH . "ezfc-page-wizard.php");
	}

	static function page_chart() {
		require_once(EZFC_PATH . "ezfc-page-chart.php");
	}

	/**
		add settings to plugins page
	**/
	static function plugin_settings_page($links) { 
		$settings_link = "<a href='" . admin_url("admin.php") . "?page=ezfc-options'>" . __("Global Settings", "ezfc") . "</a>";
		array_unshift($links, $settings_link);

		$form_settings_link = "<a href='" . admin_url("admin.php") . "?page=ezfc-settings-form'>" . __("Form Settings", "ezfc") . "</a>";
		array_unshift($links, $form_settings_link);

		return $links; 
	}

	/**
		ajax
	**/
	// frontend
	static function ajax_frontend() {
		require_once(EZFC_PATH . "ajax.php");
	}

	// frontend file upload
	static function ajax_fileupload() {
		require_once(EZFC_PATH . "ajax-fileupload.php");
	}

	// backend
	static function ajax() {
		require_once(EZFC_PATH . "ajax-admin.php");
	}


	/**
		load hooks
	**/
	static function load_hooks() {
		// load language
		load_plugin_textdomain("ezfc", false, dirname(plugin_basename(__FILE__)) . '/lang/');

		// visual composer widget
		if ( defined( 'WPB_VC_VERSION' ) ) {
            require_once(EZFC_PATH . "ext/class.vc_widget.php");
        }
	}

	// deprecated
	static function load_language() {}

	/**
		scripts
	**/
	static function load_scripts($page, $force_load=false) {
		// scripts were already loaded
		if (self::$scripts_loaded) return;

		// only load scripts for ezfc pages
		if (!$force_load && $page != "toplevel_page_ezfc" && substr($page, 0, 23) != "ez-form-calculator_page") return;

		// do not load backend scripts for preview page
		if ($page == "ez-form-calculator_page_ezfc-preview") return;

		require_once(EZFC_PATH . "ext/class.ezfc_i18n.php");

		self::$scripts_loaded = true;

		wp_enqueue_media();
		
		wp_enqueue_style("bootstrap-grid", plugins_url("assets/css/bootstrap-grid.min.css", __FILE__));
		wp_enqueue_style("ezfc-jquery-ui", plugins_url("assets/css/jquery-ui.min.css", __FILE__));
		wp_enqueue_style("ezfc-jquery-ui-theme", plugins_url("assets/css/jquery-ui.theme.min.css", __FILE__));
		wp_enqueue_style("jquerytimepicker-css", plugins_url("assets/css/jquery.timepicker.css", __FILE__));
		//wp_enqueue_style("opentip", plugins_url("assets/css/opentip.css", __FILE__));
		wp_enqueue_style("thickbox");
		wp_enqueue_style("ezfc-css-backend", plugins_url("style-backend.css", __FILE__), array(), EZFC_VERSION);
		wp_enqueue_style("ezfc-font-awesome", plugins_url("assets/css/font-awesome.min.css", __FILE__));

		wp_enqueue_script("jquery");
		wp_enqueue_script("jquery-ui-accordion");
		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-dialog");
		wp_enqueue_script("jquery-ui-draggable");
		wp_enqueue_script("jquery-ui-droppable");
		wp_enqueue_script("jquery-ui-mouse");
		wp_enqueue_script("jquery-ui-selectable");
		wp_enqueue_script("jquery-ui-sortable");
		wp_enqueue_script("jquery-ui-spinner");
		wp_enqueue_script("jquery-ui-tabs");
		wp_enqueue_script("jquery-ui-tooltip");
		wp_enqueue_script("jquery-ui-widget");
		//wp_enqueue_script("jquery-opentip", plugins_url("assets/js/opentip-jquery.min.js", __FILE__), array("jquery"));
		wp_enqueue_script("ezfc-numeraljs", plugins_url("assets/js/numeral.min.js", __FILE__), array("jquery"));
		wp_enqueue_script("ezfc-jquery-timepicker", plugins_url("assets/js/jquery.timepicker.min.js", __FILE__), array("jquery"));
		wp_enqueue_script("ezfc-jquery-file-upload", plugins_url("assets/js/jquery.fileupload.min.js", __FILE__), array("jquery"));
		wp_enqueue_script("jquery-iframe-transport", plugins_url("assets/js/jquery.iframe-transport.min.js", __FILE__), array("jquery-ui-widget"));
		wp_enqueue_script("ezfc-math-js", plugins_url("assets/js/math.min.js", __FILE__));
		wp_enqueue_script("ezfc-underscore-js", plugins_url("assets/js/underscore-min.js", __FILE__));
		wp_enqueue_script("thickbox");
		wp_enqueue_script("wp-color-picker");

		// force-reload for debug modes
		$enqueue_version = get_option("ezfc_debug_mode", 0) > 0 ? microtime(true) : EZFC_VERSION;

		wp_register_script("ezfc-backend", plugins_url("backend.js", __FILE__), array("jquery"), microtime(true), $enqueue_version);
		wp_enqueue_script("ezfc-backend");
		wp_enqueue_script("ezfc-backend-builder-functions", plugins_url("inc/js/builder_functions.js", __FILE__), array("ezfc-backend"), $enqueue_version);
		wp_enqueue_script("ezfc-backend-options", plugins_url("backend-options.js", __FILE__), array("ezfc-backend"), $enqueue_version);

		if ($page == "ez-form-calculator_page_ezfc-chart") {
			wp_enqueue_script("ezfc-visjs", plugins_url("lib/vis-js/vis.min.js", __FILE__), array("ezfc-backend"), $enqueue_version);
			wp_enqueue_style("ezfc-visjs", plugins_url("lib/vis-js/vis.min.css", __FILE__));
		}
		else if ($page == "ez-form-calculator_page_ezfc-wizard") {
			wp_dequeue_script( "ezfc-backend" );
			wp_dequeue_script( "ezfc-backend-options" );
		}

		// get translation strings
		$ezfc_vars = Ezfc_i18n::get_vars();

		foreach ($ezfc_vars["element_option_description"] as $option_name => &$option_description) {
			$option_description = esc_attr($option_description);
		}

		wp_localize_script("ezfc-backend", "ezfc_vars", $ezfc_vars);
	}

	/**
		tinymce button
	**/
	static function tinymce() {
		global $typenow;

		if( ! in_array( $typenow, array( 'post', 'page' ) ) )
			return;

		add_filter('mce_external_plugins', array(__CLASS__, 'add_tinymce_plugin'));
		add_filter('mce_buttons', array(__CLASS__, 'add_tinymce_button'));
	}

	static function tinymce_script() {
		global $typenow;

		if( ! in_array( $typenow, array( 'post', 'page' ) ) )
			return;

		require_once(EZFC_PATH . "class.ezfc_backend.php");
		$ezfc_backend = Ezfc_backend::instance();

		echo "<script>ezfc_forms = " . json_encode($ezfc_backend->forms_get()) . ";</script>";
	}

	static function add_tinymce_plugin( $plugin_array ) {
		$plugin_array['ezfc_tinymce'] = plugins_url('/ezfc_tinymce.js', __FILE__ );

		return $plugin_array;
	}

	static function add_tinymce_button( $buttons ) {
		array_push( $buttons, 'ezfc_tinymce_button' );

		return $buttons;
	}

	/**
		download file proxy
	**/
	static function add_rewrite_rules() {
		add_rewrite_rule("ezfc-file\.php", "index.php?ezfc_file=1", "top");
		add_rewrite_rule("ezfc-form\.php", "index.php?ezfc_form=1", "top");
		add_rewrite_rule("ezfc-download-export-form\.php", "index.php?ezfc_download_export_form=1", "top");
	}

	/**
		download file add query var
	**/
	static function download_file_add_query_vars($query_vars) {
		$query_vars[] = "ezfc_file";
		$query_vars[] = "ezfc_form";
		$query_vars[] = "ezfc_download_export_form";
		return $query_vars;
	}

	/**
		download file parse request
	**/
	static function download_file_parse_request($query) {
		$role = get_option("ezfc_user_roles", "administrator");

		if (!current_user_can($role)) return;

		require_once(EZFC_PATH . "class.ezfc_backend.php");
		$ezfc = Ezfc_backend::instance();

		// download uploaded file
		if (isset($query->query_vars["ezfc_file"]) && isset($_GET["file_id"])) {
	        $file = $ezfc->get_file($_GET["file_id"]);
	        if (is_array($file) && isset($file["error"])) {
	        	die($file["error"]);
	        }

	        // download file
	        Ezfc_Functions::send_file_to_browser($file);

	        die();
		}

		// download export form or export data
		else if (isset($query->query_vars["ezfc_download_export_form"]) && isset($_GET["download_export_form"]) && isset($_GET["hash"])) {
			$hash = sanitize_file_name($_GET["hash"]);
			$filename = "ezfc_export_data_{$hash}.zip";

	        $ezfc->form_download_export_file($filename);

			die();
		}

		// download form submissions csv
		else if (isset($query->query_vars["ezfc_download_export_form"]) && isset($_GET["download_csv"]) && isset($_GET["hash"])) {
			$hash = sanitize_file_name($_GET["hash"]);
			$filename = "ezfc_export_csv_{$hash}.csv";

	        $ezfc->form_download_export_file($filename);

			die();
		}
	}

	/**
		widget
	**/
	static function register_widget() {
		require_once(EZFC_PATH . "widget.php");

		return register_widget("Ezfc_widget");
	}
}

// init class
EZFC_Free::init();

// shortcode
require_once(EZFC_PATH . "shortcode.php");

endif;