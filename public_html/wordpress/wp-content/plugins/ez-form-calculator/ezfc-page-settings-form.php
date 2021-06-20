<?php

/**
	form settings
**/

defined( 'ABSPATH' ) OR exit;

require_once(EZFC_PATH . "class.ezfc_backend.php");
$ezfc = Ezfc_backend::instance();

// validate user
if (!empty($_POST["ezfc-request"])) $ezfc->validate_user("ezfc-nonce", "nonce");

// update form options
if (isset($_POST["submit"])) {
	$_POST["opt"] = isset($_POST["opt"]) ? $_POST["opt"] : null;
	$_POST["ezfc-overwrite"] = isset($_POST["ezfc-overwrite"]) ? $_POST["ezfc-overwrite"] : null;
	$_POST["ezfc-manual-update"] = isset($_POST["ezfc-manual-update"]) ? $_POST["ezfc-manual-update"] : null;

	$ezfc->update_options($_POST["opt"], $_POST["ezfc-overwrite"], $_POST["ezfc-manual-update"]);

	$updated = 1;
}

// get form options
$settings = $ezfc->get_options();
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
				<h2><?php echo __("Form settings", "ezfc"); ?> - ez Form Calculator v<?php echo EZFC_VERSION; ?></h2> 
				<p><?php echo __("These options can be changed individually in each form. Saving these options will be applied to new forms only.", "ezfc"); ?></p>
				<p><?php echo __("If you want to override these settings to all forms, please check the option 'Overwrite settings' below.", "ezfc"); ?></p>

				<?php if (isset($updated)) { ?>
					<div id="message" class="updated"><?php echo __("Settings saved.", "ezfc"); ?></div>
				<?php } ?>
				<?php if (isset($error)) { ?>
					<div class="ezfc-error notice notice-error" id="ezfc-error"><?php echo $error; ?></div>
				<?php }	?>
			</div>
		</div>
	</div>

	<form method="POST" name="ezfc-form" class="ezfc-form" action="<?php echo $_SERVER['REQUEST_URI']; ?>" novalidate>
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
					<?php
					echo Ezfc_Functions::get_settings_table($cat, "opt", "opt", true);
					?>
				</div>

				<?php

				$tab_i++;
			}

			?>

		</div> <!-- tabs -->

		<table class="form-table" style="margin-top: 1em;">
			<!-- overwrite settings -->
			<tr>
				<th scope='row'>
					<label for="ezfc-overwrite"><?php echo __("Overwrite settings", "ezfc"); ?></label>
		    	</th>
		    	<td>
		    		<input type="checkbox" name="ezfc-overwrite" id="ezfc-overwrite" value="1" /><br>
		    		<p class="description"><?php echo __("Checking this option will overwrite <strong>ALL</strong> existing form settings!", "ezfc"); ?></p>
		    	</td>
		    </tr>
		</table>

		<!-- save -->
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __("Save", "ezfc"); ?>" /></p>
	</form>
</div>