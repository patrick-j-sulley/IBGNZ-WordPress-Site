<?php

/**
	help page
**/

defined( 'ABSPATH' ) OR exit;

require_once(EZFC_PATH . "class.ezfc_backend.php");
$ezfc = Ezfc_backend::instance();
$error = false;
$message = "";

// validate user
if (!empty($_POST["ezfc-request"])) $ezfc->validate_user("ezfc-nonce", "nonce");

$global_settings = Ezfc_settings::get_global_settings(true);

// clear logs
if (isset($_POST["clear_logs"]) && $_POST["clear_logs"] == 1) {
	$ezfc->clear_debug_log();
	$message = __("Logs cleared.", "ezfc");
}

// send test mail
else if (!empty($_POST["send_test_mail"]) && !empty($_POST["send_test_mail_recipient"])) {
	$sendername = isset($_POST["send_test_mail_recipient_sender"]) ? $_POST["send_test_mail_recipient_sender"] : null;
	$message = "";
	$file = null;

	// PDF attachment
	if (!empty($_POST["attach_test_pdf"])) {
		require_once(EZFC_PATH . "ext/class.ezfc_pdf.php");
		$pdf = new EZFC_Extension_PDF();
		$file = $pdf->test();

		if (!$file) $message .= __("Unable to create PDF.", "ezfc") . "<br>";
	}

	$message .= $ezfc->send_test_mail($_POST["send_test_mail_recipient"], $sendername, $file);
}

// paypal test
else if (!empty($_POST["paypal_test"])) {
	// cURL not installed
	if (!function_exists("curl_version")) {
		$message = __("cURL is not installed on your webserver. Please contact your webhoster to install this module in order to use PayPal.", "ezfc");
		$error = true;
	}
	else {
		$test_url_base = $_POST["paypal_env"]=="live" ? "https://api-3t.paypal.com/nvp" : "https://api-3t.sandbox.paypal.com/nvp";

		$test_query = http_build_query(array(
			"USER"                           => get_option("ezfc_pp_api_username"),
			"PWD"                            => get_option("ezfc_pp_api_password"),
			"SIGNATURE"                      => get_option("ezfc_pp_api_signature"),
			"VERSION"                        => "98",
			"method"                         => "SetExpressCheckout",
			"PAYMENTREQUEST_0_AMT"           => "10",
			"PAYMENTREQUEST_0_CURRENCYCODE"  => "USD",
			"PAYMENTREQUEST_0_PAYMENTACTION" => "Sale",
			"returnUrl"    	                 => "http://www.paypal.com/success.html",
			"cancelUrl"    	                 => "http://www.paypal.com/cancel.html"
		));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $test_url_base);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $test_query);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$res = curl_exec($ch);
		
		if (curl_errno($ch)) {
			$message .= curl_errno($ch) . ": " . curl_error($ch) . "<br>";
			$error = true;
		}

		curl_close($ch);

		parse_str($res, $test_result);
		if (!empty($test_result["ACK"]) && $test_result["ACK"] == "Success") {
			$message = sprintf(__("PayPal successfully verified your %s credentials.", "ezfc"), "<strong>{$_POST["paypal_env"]}</strong>");
		}
		else {
			$message .= sprintf(__("Error while validating %s credentials:", "ezfc"), "<strong>{$_POST["paypal_env"]}</strong>");
			
			if (!empty($test_result["L_LONGMESSAGE0"])) $message .= "<br><br>" . $test_result["L_LONGMESSAGE0"];

			$message .= "<br><br>" . sprintf(__("Please note that Sandbox API credentials are different! See %s to read how to get your API credentials.", "ezfc"), "<a href='https://developer.paypal.com/docs/classic/api/apiCredentials/'>PayPal docs</a>");

			$message .= "<br>";
			$error = true;
		}

		// check if necessary settings are empty
		$check_empty = array(
			"PayPal Username"      => get_option("ezfc_pp_api_username"),
			"PayPal Password"      => get_option("ezfc_pp_api_password"),
			"PayPal Signature"     => get_option("ezfc_pp_api_signature"),
			"PayPal Currency Code" => $global_settings["pp_currency_code"]["value"]
		);

		foreach ($check_empty as $option_name => $option_value) {
			if (empty($option_value)) {
				$message .= "<br>" . sprintf(__("Empty %s", "ezfc"), $option_name);
				$error = true;
			}
		}

		// check for valid URLs
		$pp_valid_urls = array(
			"PayPal Return URL" => filter_var($global_settings["pp_return_url"]["value"], FILTER_VALIDATE_URL),
			"PayPal Cancel URL" => filter_var($global_settings["pp_cancel_url"]["value"], FILTER_VALIDATE_URL),
		);

		foreach ($pp_valid_urls as $name => $value) {
			if (!$value) {
				$message .= "<br>" . __("Invalid url:", "ezfc") . " {$name}";
				$error = true;
			}
		}

		// check verify shortcode
		$pp_check_shortcode_query = array(
			new WP_Query(array(
			    's' => '[ezfc_verify]'
			)),
			new WP_Query(array(
			    's' => '[ezfc_verify /]'
			))
		);

		$pp_verify_shortcode_found = false;
		foreach ($pp_check_shortcode_query as $pp_query) {
			if ($pp_query->have_posts()) $pp_verify_shortcode_found = true;
		}

		if (!$pp_verify_shortcode_found) {
			$message .= "<br>" . sprintf(__("Unable to find verification shortcode. Please add the following shortcode to the PayPal Return URL page: %s", "ezfc"), "[ezfc_verify /]");
			$error = true;
		}
	}
}

// create paypal sites
else if (!empty($_POST["paypal_create_sites"])) {
	// return page
	$post_arr = array(
		'post_type'     => 'page',
		'post_title'    => __("PayPal Return Page", "ezfc"),
		'post_content'  => '[ezfc_verify /]',
		'post_status'   => 'publish'
	);
	$post_id_return = wp_insert_post( $post_arr );

	// cancel page
	$post_arr = array(
		'post_type'     => 'page',
		'post_title'    => __("PayPal Cancel Page", "ezfc"),
		'post_content'  => __("PayPal payment was cancelled.", "ezfc"),
		'post_status'   => 'publish'
	);
	$post_id_cancel = wp_insert_post( $post_arr );

	if (empty($post_id_return) || empty($post_id_cancel)) {
		$message .= __("Unable to create PayPal pages.", "ezfc");
	}
	else {
		// update ezfc paypal options
		$post_return_url = get_permalink($post_id_return);
		$post_cancel_url = get_permalink($post_id_cancel);

		update_option("ezfc_pp_return_url", $post_return_url);
		update_option("ezfc_pp_cancel_url", $post_cancel_url);
	}
}

// search for forms
else if (!empty($_POST["search_forms"])) {
	$search_forms_query = new WP_Query(array(
		"post_type" => array("post", "page", "product"),
		"s" => "[ezfc"
	));
 
 	$message = "";
	if ( $search_forms_query->have_posts() ) {
	    $message .= "<ul>";
	    while ( $search_forms_query->have_posts() ) {
	        $search_forms_query->the_post();

	        // get form id / name
	        $content_tmp = explode("[ezfc", get_the_content());
	        $shortcode_tmp = explode("]", $content_tmp[1]);

	        $link = get_the_permalink();
	        $message .= "<li><a href='{$link}' target='_blank'>" . get_the_title() . "</a> | {$shortcode_tmp[0]} | " . get_post_type() . "</li>";
	    }
	    $message .= "</ul>";
	} else {
	    $message = __("No forms found.", "ezfc");
	}

	wp_reset_postdata();
}

// clear transients
else if (!empty($_REQUEST["delete_transients"])) {
	delete_transient("ezfc_template_list");

	$message .= "<br>Transients deleted.";
}

$debug_active = get_option("ezfc_debug_mode", 0) != 0 ? true : false;
$debug_log    = $ezfc->get_debug_log();

// files / dirs
$upload_dir      = wp_upload_dir();
$ezfc_upload_dir = $upload_dir["basedir"] . "/ezfc-uploads/";
$pdf_dir         = get_option("ezfc_ext_pdf_dirname");
$pdf_seed        = get_option("ezfc_ext_pdf_seed");
$install_file    = EZFC_PATH . "db.sql";

$icons = array(
	"bad"     => "<i class='fa fa-times'></i>",
	"good"    => "<i class='fa fa-check'></i>",
	"warning" => "<i class='fa fa-warning'></i>"
);

$debug_vars = array(
	"php_version" => array(
		"value"    => PHP_VERSION,
		"required" => "5.6.0",
		"error"    => sprintf(__("PHP version %s or greater is recommended.", "ezfc"), "5.6.0"),
		"is_version" => true
	),
	"wp_version" => array(
		"value"    => get_bloginfo("version"),
		"required" => version_compare(get_bloginfo("version"), "4.5"),
		"error"    => __("WP version 4.5 or greater is recommended.", "ezfc")
	),
	"file_get_contents" => array(
		"value"    => function_exists("file_get_contents"),
		"required" => true,
		"is_bool"  => true,
		"error"    => __("PHP function file_get_contents is disabled. Please contact your webhost to enable this function.", "ezfc")
	),
	"allow_url_fopen" => array(
		"value"    => @ini_get('allow_url_fopen'),
		"required" => true,
		"is_bool"  => true,
		"error"    => __("PHP option allow_url_fopen is disabled. Please contact your webhost to enable this option in order to retrieve external values.", "ezfc")
	),
	"memory_limit" => array(
		"value"    => @ini_get('memory_limit'),
		"required" => 128,
		"error"    => __("The PHP memory limit is too low. Please contact your webhost to increase the memory limit.", "ezfc")
	),
	"max_input_vars" => array(
		"value"    => @ini_get('max_input_vars'),
		"required" => 1000,
		"warning"  => 2000,
		"message"  => __("The option max_input_vars is used for form submissions. If you use large forms and can't get it to work, you can increase the value of this option. This might solve the 'Element can not be found' error message.", "ezfc")
	),
	"max_execution_time" => array(
		"value"    => @ini_get('max_execution_time'),
		"required" => 30,
		"warning"  => 120,
		"message"  => __("If you are working with large forms and you are unable to save, try to increase the PHP option max_execution_time to a higher value. You might need to contact your webhost to increase this value.", "ezfc")
	),
	"file_upload_dir" => array(
		"value"    => file_exists($ezfc_upload_dir) && is_writable($ezfc_upload_dir),
		"required" => true,
		"is_bool"  => true,
		"error"    => sprintf(__("Please make sure the following directory exists and it's writeable: %s", "ezfc"), $ezfc_upload_dir)
	),
	"PDF folder" => array(
		"value" => file_exists($pdf_dir) && is_writable($pdf_dir),
		"required" => true,
		"is_bool"  => true,
		"error"    => sprintf(__("Please make sure the following directory exists and it's writeable: %s", "ezfc"), $pdf_dir)
	),
	"PDF seed" => array(
		"value" => !empty($pdf_seed),
		"required" => true,
		"is_bool"  => true,
		"error"    => __("Empty pdf seed. Please go to the global settings page, check the option 'Manual update' and click on save.", "ezfc")
	),
	"cURL" => array(
		"value" => function_exists("curl_version"),
		"required" => true,
		"is_bool"  => true,
		"error"    => __("cURL is not installed on your webserver. Please contact your webhost to enable this function.", "ezfc")
	),
	"Install file" => array(
		"value"    => file_exists($install_file) && is_readable($install_file),
		"required" => true,
		"is_bool"  => true,
		"error"    => sprintf(__("Please make sure the following file exists and is readable: %s", "ezfc"), $install_file)
	),
	"mod_rewrite" => array(
		"value"    => function_exists("apache_get_modules") && in_array('mod_rewrite', apache_get_modules()),
		"required" => true,
		"is_bool"  => true,
		"error"    => __("If you encounter errors with message code 403, please enable mod_rewrite.", "ezfc")
	),
	"upload_max_filesize"   => @ini_get('upload_max_filesize'),
	"post_max_size"         => @ini_get('post_max_size')
);


// check tables
$check_tables = $ezfc->tables;
unset($check_tables["elements"]);
unset($check_tables["tmp"]);
foreach ($check_tables as $table_key => $table) {
	$table_exists = $ezfc->wpdb->get_var($ezfc->wpdb->prepare("SHOW TABLES LIKE %s", $table)) == $table;

	$debug_vars["DB Table {$table_key}"] = array(
		"value" => $table_exists,
		"required" => true,
		"is_bool" => true,
		"error" => sprintf(__("Table %s does not exist. Please reinstall the plugin.", "ezfc"), $table)
	);
}

// generate help table
$help_table = array();
$help_table[] = "<table>";
foreach ($debug_vars as $key => $var) {
	$help_table[] = "<tr><td style='width: 150px'>";
	$help_table[] = $key;
	$help_table[] = "</td><td>";

	if (is_array($var)) {
		$option_success = false;
		$tmp_out = "";
		$warning = false;

		if (isset($var["warning"])) {
			$warning = true;

			$option_success = (float) $var["value"] >= $var["warning"];
			$tmp_out = $var["value"];
		}

		if (!empty($var["is_bool"])) {
			$option_success = (bool) $var["value"] == $var["required"];
		}
		else if (!empty($var["is_version"])) {
			$option_success = version_compare($var["value"], $var["required"]) >= 0;
			$tmp_out = $var["value"];
		}
		else {
			$option_success = (float) $var["value"] >= $var["required"];
			$tmp_out = $var["value"];
		}

		if (isset($var["warning"])) {
			$icon      = $option_success ? $icons["warning"] : $icons["bad"];
			$css_class = $option_success ? "ezfc-color-warning" : "ezfc-color-error";
		}
		else {
			$icon      = $option_success ? $icons["good"] : $icons["bad"];
			$css_class = $option_success ? "ezfc-color-success" : "ezfc-color-error";	
		}

		if (!$option_success) {
			if (!empty($var["message"])) $var["error"] = $var["message"];

			$tmp_out .= "<br><strong>{$var["error"]}</strong>";
		}
		else if (!empty($var["message"])) {
			$tmp_out .= "<br>{$var["message"]}";
		}

		$help_table[] = "<span class='{$css_class}'>{$icon} {$tmp_out}</span>";
	}
	else {
		$help_table[] = $var;
	}

	$help_table[] = "</td></tr>";
}
$help_table[] = "</table>";
$help_table_output = implode("", $help_table);

/*if (!empty($_POST["download_support_log"])) {
	$support_output  = "Environment vars\n";
	$support_output .= "================\n";
	$support_output .= strip_tags($help_table_output);
	$support_output .= "\n\n";
	$support_output .= "Debug log\n";
	$support_output .= "================\n";
	$support_output .= $debug_log;
}*/

$nonce = wp_create_nonce("ezfc-nonce");

?>

<div class="ezfc wrap ezfc-wrapper container-fluid">
	<div class="row">
		<div class="col-lg-12">
			<div class="inner">
				<h2><?php echo __("Help / debug", "ezfc"); ?> - ez Form Calculator v<?php echo EZFC_VERSION; ?></h2> 
				<p>
					<a class="button button-primary" href="http://ez-form-calculator.ezplugins.de/documentation/" target="_blank"><?php echo __("Open documentation site", "ezfc"); ?></a>
				</p>

				<p>
					<?php echo sprintf(__("If you have found any bugs, please report them to %s. Thank you!", "ezfc"), "<a href='mailto:support@ezplugins.de'>support@ezplugins.de</a>"); ?>
				</p>
			</div>
		</div>

		<?php if (!empty($message)) { ?>
			<div class="col-lg-12">
				<div class="inner">
					<div id="message" class="<?php echo $error ? 'error' : 'updated'; ?>"><?php echo $message; ?></div>
				</div>
			</div>
		<?php } ?>
	</div>
	
	<div class="row">
		<div class="col-lg-3 col-sm-6 col-md-6 col-xs-12">
			<div class="inner">
				<h3>Debug log</h3>

				<p><?php echo sprintf(__("Debug mode is %s", "ezfc"), $debug_active ? __("active", "ezfc") : __("inactive", "ezfc")); ?>.</p>
				<textarea class="ezfc-settings-type-textarea" style="height: 400px;"><?php echo $debug_log; ?></textarea>

				<form action="" method="POST">
					<input type="hidden" name="ezfc-request" value="1" />
					<input type="hidden" value="1" name="clear_logs" />
					<input type="submit" value="<?php echo __("Clear logs", "ezfc"); ?>" class="button button-primary" />
					<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
				</form>

				<h3><?php echo __("Search for forms", "ezfc"); ?></h3>
				<form action="" method="POST">
					<input type="hidden" name="ezfc-request" value="1" />
					<input type="hidden" value="1" name="search_forms" />
					<input type="submit" value="<?php echo __("Search", "ezfc"); ?>" class="button button-primary" />
					<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
				</form>
			</div>
		</div>

		<div class="col-lg-3 col-sm-6 col-md-6 col-xs-12">
			<div class="inner">
				<h3><?php echo __("Environment Vars", "ezfc"); ?></h3>

				<?php echo $help_table_output; ?>
			</div>
		</div>

		<div class="col-lg-3 col-sm-6 col-md-6 col-xs-12">
			<div class="inner">
				<h3><?php echo __("Emails", "ezfc"); ?></h3>

				<form action="" method="POST">
					<input type="hidden" name="ezfc-request" value="1" />
					<input type="hidden" value="1" name="send_test_mail" />

					<p>
						<label for="send_test_mail_recipient"><?php echo __("Email recipient", "ezfc"); ?></label><br>
						<input type="text" value="" name="send_test_mail_recipient" placeholder="your@email.com" id="send_test_mail_recipient" />
					</p>

					<p>
						<label for="send_test_mail_recipient_sender"><?php echo __("Sendername (optional)", "ezfc"); ?></label><br>
						<input type="text" value="" name="send_test_mail_recipient_sender" placeholder="sendername@email.com" id="send_test_mail_recipient_sender" /><br>
						<?php echo __("Some webhosts require a valid email address from the same domain or a single specified email address.", "ezfc"); ?>
					</p>

					<p>
						<input type="checkbox" id="attach_test_pdf" name="attach_test_pdf" value="1" /> <label for="attach_test_pdf"><?php echo __("Attach test PDF", "ezfc"); ?></label>
					</p>

					<input type="submit" value="<?php echo __("Send test mail", "ezfc"); ?>" class="button" />
					<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
				</form>	
			</div>

			<div class="inner">
				<h3>PayPal</h3>

				<form action="" method="POST">
					<input type="hidden" name="ezfc-request" value="1" />
					<input type="hidden" value="1" name="paypal_test" />
					<select name="paypal_env">
						<option value="live"><?php echo __("Live", "ezfc"); ?></option>
						<option value="sandbox" <?php if (get_option("ezfc_pp_sandbox")) echo "selected='selected'"; ?>><?php echo __("Sandbox", "ezfc"); ?></option>
					</select>
					<br />
					<input type="submit" value="<?php echo __("Test PayPal integration", "ezfc"); ?>" class="button" />
					<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
				</form>

				<hr />

				<p><?php echo __("The plugin can create all relevant PayPal sites for you automatically. The plugin will create 2 new sites with the relevant shortcodes. Please note that if you change the permalink of the pages, you need to update the pages in the global settings as well.", "ezfc"); ?></p>

				<form action="" method="POST">
					<input type="hidden" name="ezfc-request" value="1" />
					<input type="hidden" value="1" name="paypal_create_sites" />
					<input type="submit" value="<?php echo __("Create PayPal sites", "ezfc"); ?>" class="button" />
					<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
				</form>
			</div>
		</div>

		<div class="col-lg-3 col-sm-6 col-md-6 col-xs-12">
			<a class="twitter-timeline" href="https://twitter.com/ezPlugins" data-widget-id="575319170478383104">Tweets by @ezPlugins</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</div>
	</div>
</div>
