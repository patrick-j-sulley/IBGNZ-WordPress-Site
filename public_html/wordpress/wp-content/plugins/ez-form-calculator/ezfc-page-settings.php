<?php

/**
	global settings
**/

defined( 'ABSPATH' ) OR exit;

global $wp_rewrite;

require_once(EZFC_PATH . "class.ezfc_backend.php");
$ezfc = Ezfc_backend::instance();

// validate user
if (!empty($_POST["ezfc-request"])) $ezfc->validate_user("ezfc-nonce", "nonce");

// update global options
if (isset($_POST["submit"])) {
	// manual update
	if (!empty($_POST["ezfc-manual-update"])) {
		$ezfc->update_options(false, false, true);
	}

	// check for errors
	$update_result = Ezfc_settings::update_global_settings($_POST["opt"]);
	if (!empty($update_result) && is_array($update_result)) {
		$tmp_error = array();

		foreach ($update_result as $v) {
			$tmp_error[] = $v["error"];
		}

		$error = implode("<br>", $tmp_error);
	}
	else {
		$updated = 1;
	}

	$wp_rewrite->flush_rules(false);
}

// reset
if (isset($_POST["ezfc-reset"])) {
	$keep_data_option = get_option("ezfc_uninstall_keep_data", 0);
	update_option("ezfc_uninstall_keep_data", 0);

	ezfc_uninstall();
	ezfc_register();

	$wp_rewrite->flush_rules(false);

	update_option("ezfc_uninstall_keep_data", $keep_data_option);
	$_POST = array();
}

// global settings
$settings = Ezfc_settings::get_global_settings();
$settings_raw = Ezfc_settings::get_global_settings(true);

// categorize settings
$settings_cat = array();
foreach ($settings as $cat => $s) {
	$settings_cat[$cat] = $s;
}

// security nonce
$nonce = wp_create_nonce("ezfc-nonce");

?>

<div class="ezfc wrap ezfc-wrapper container-fluid">
	<div class="row">
		<div class="col-lg-12">
			<div class="inner">
				<h2><?php echo __("Global settings", "ezfc"); ?> - ez Form Calculator v<?php echo EZFC_VERSION; ?></h2> 

				<?php if (isset($updated)) { ?>
					<div id="message" class="updated"><?php echo __("Settings saved.", "ezfc"); ?></div>
				<?php }	?>
				<?php if (isset($error)) { ?>
					<div class="ezfc-error notice notice-error" id="ezfc-error"><?php echo $error; ?></div>
				<?php }	?>
			</div>
		</div>
	</div>

	<form method="POST" name="ezfc-form" class="ezfc-form" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<input type="hidden" name="ezfc-request" value="1" />
		<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />

		<div id="tabs">
			<ul>
				<?php
				$tabs = array_keys($settings_cat);

				foreach ($tabs as $i => $cat) {
					echo "<li><a href='#tab-{$i}'>{$cat}</a></li>";
				}
				?>
			</ul>

		    <?php

		    $tab_i = 0;
		    foreach ($settings_cat as $cat_name => $cat) {
		    	?>

				<div id="tab-<?php echo $tab_i; ?>">
					<?php if ($cat_name == "PayPal") echo "<p>" . sprintf(__("If you want to use PayPal payment, please follow all steps in the PayPal integration guide here: %s. Alternatively, the plugin can create all necessary sites on the Help / debug page.", "ezfc"), "<a href='http://ez-form-calculator.ezplugins.de/documentation/paypal-integration/' target='_blank'>" . __("PayPal Integration Guide", "ezfc") . "</a>") . "</p>"; ?>

					<?php echo Ezfc_Functions::get_settings_table($cat, "opt", "opt"); ?>
				</div>

				<?php

				$tab_i++;
			}
			?>
		</div>

		<table class="form-table" style="margin-top: 1em;">
		    <!-- manual update -->
			<tr>
				<th scope='row'>
					<label for="ezfc-manual-update"><?php echo __("Manual update", "ezfc"); ?></label>
		    	</th>
		    	<td>
		    		<input type="checkbox" name="ezfc-manual-update" id="ezfc-manual-update" value="1" /><br>
		    		<p class="description"><?php echo __("Checking this option will perform certain database changes. Check this if you recently updated the script as this will perform necessary changes. <strong>Please make sure to backup your form data before!", "ezfc"); ?></strong></p>
		    	</td>
		    </tr>

		    <!-- reset -->
			<tr>
				<th scope='row'>
					<label for="ezfc-manual-update"><?php echo __("Reset", "ezfc"); ?></label>
		    	</th>
		    	<td>
		    		<input type="checkbox" name="ezfc-reset" id="ezfc-reset" value="1" /><br>
		    		<p class="description"><?php echo __("Complete reset of this plugin. <strong>This will reset all existing data. Use with caution.</strong>", "ezfc"); ?></p>
		    	</td>
		    </tr>	
		</table>

		<!-- save -->
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __("Save", "ezfc"); ?>" /></p>
	</form>
</div>

<div id="price-preview-wrapper">
	<strong><?php echo __("Price preview (currency does not apply here)", "ezfc"); ?></strong>
	<p><?php echo __("You can change the currency and price label in the form options.", "ezfc"); ?></p>

	<p>
		<?php echo __("Predefined price formats: ", "ezfc"); ?>
		<a class="predefined-price-format" data-format="default" href="#"><?php echo __("Default", "ezfc"); ?></a>,
		<a class="predefined-price-format" data-format="eu" href="#"><?php echo __("European", "ezfc"); ?></a>,
		<a class="predefined-price-format" data-format="show_decimal_numbers" href="#"><?php echo __("Always show decimal numbers", "ezfc"); ?></a>
	</p>

	<table class="ezfc-table-styled">
		<thead>
			<tr><td><?php echo __("Original price", "ezfc"); ?></td><td><?php echo __("Formatted price", "ezfc"); ?></td></tr>
		</thead>
		<tbody>
			<tr class="ezfc-price-preview"><td class="ezfc-price-preview-orig">$ 1,337.99</td><td>$ <span class="ezfc-price-preview-formatted">1337.99</span></td></tr>
			<tr class="ezfc-price-preview"><td class="ezfc-price-preview-orig">$ 1,337.00</td><td>$ <span class="ezfc-price-preview-formatted">1337.00</span></td></tr>
			<tr class="ezfc-price-preview"><td class="ezfc-price-preview-orig">$ 0</td><td>$ <span class="ezfc-price-preview-formatted">0</span></td></tr>
		</tbody>
	</table>
</div>