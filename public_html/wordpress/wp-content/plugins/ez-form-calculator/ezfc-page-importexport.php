<?php

/**
	import / export page
**/

defined( 'ABSPATH' ) OR exit;

require_once(EZFC_PATH . "class.ezfc_functions.php");
require_once(EZFC_PATH . "class.ezfc_backend.php");
$ezfc = Ezfc_backend::instance();

$include_submissions_export = isset($_POST["submit-export-submissions"]) ? true : false;
$include_submissions_import = isset($_POST["submit-import-submissions"]) ? true : false;
$preserve_ids               = isset($_POST["preserve_ids"]);

$message = "";

if (!empty($_POST["ezfc-request"])) $ezfc->validate_user("ezfc-nonce", "nonce");

// import form
if (isset($_POST["submit-import-text"])) {
	@ini_set("max_execution_time", 3000);

	$ezfc->import_data($_POST["import_data"], true, $preserve_ids, $include_submissions_import);
	$message = __("Data imported.", "ezfc");
}

// import form by file
if (isset($_POST["submit-import-file"]) && isset($_FILES["ezfc-import-file"])) {
	@ini_set("max_execution_time", 3000);

	$file = $_FILES["ezfc-import-file"]["tmp_name"];
	$file_data = EZFC_Functions::zip_read($file, "ezfc_export_data.json");

	if (empty($file_data)) {
		$message = __("Unable to import form data from file.", "ezfc");
	}
	else {
		// import data
		$result = $ezfc->import_data($file_data, false, $preserve_ids, $include_submissions_import);
		
		$message = isset($result["error"]) ? $result["error"] : $result["success"];
	}
}

// get export data
$download = 0;

$export_data_default_message = __("Click 'Show export data' to view plain export data.", "ezfc");

$export_data_json = $export_data_default_message;
// only show export data when needed
if (isset($_POST["show-export-data"])) {
	$export_data = $ezfc->get_export_data($include_submissions_export);
	$export_data_json = json_encode($export_data);
}

// download form data
if (isset($_POST["submit-export-download"])) {
	@ini_set("max_execution_time", 3000);

	$export_data = $ezfc->get_export_data($include_submissions_export);
	$export_data_json = json_encode($export_data);
	
	$save_dir = Ezfc_Functions::create_upload_dir("ezfc-tmp", false);
	$file = EZFC_Functions::zip_write($export_data_json, "ezfc_export_data.json", $save_dir);
	$export_data_json = $export_data_default_message;

	// unknown error
	if (!is_array($file)) {
		$message = __("Unable to download export data.", "ezfc");
	}
	else {
		// error message
		if (isset($file["error"])) {
			$message = $file["error"];
		}
		// download file
		else {
			$download = 1;

			$file_url = site_url() . "/ezfc-download-export-form.php?download_export_form=1&hash={$file["file_id"]}";
		}
	}
}

// clear temporary export files
if (isset($_POST["submit-clear-export-files"])) {
	$result = EZFC_Functions::delete_tmp_files();

	// error
	if (isset($result["error"])) $message = $result["error"];
	else $message = $result["success"];
}

$nonce = wp_create_nonce("ezfc-nonce");

?>

<div class="ezfc wrap ezfc-wrapper container-fluid">
	<div class="row">
		<div class="col-lg-12">
			<div class="inner">
				<h2><?php echo __("Import / Export data", "ezfc"); ?></h2> 

				<?php if (!empty($message)) { ?>
					<div id="message" class="updated"><?php echo $message; ?></div>
				<?php }	?>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<div class="inner">
				<h3><?php echo __("Export data", "ezfc"); ?></h3>
				<textarea class="ezfc-settings-type-textarea"><?php echo htmlentities($export_data_json); ?></textarea>

				<form method="POST" name="ezfc-form-export-download" action="">
					<input type="hidden" name="ezfc-request" value="1" />
					<p>
						<input type="checkbox" name="submit-export-submissions" value="1" id="submit-export-submissions" <?php echo $include_submissions_export ? "checked='checked'" : ""; ?> /> <label for="submit-export-submissions"><?php _e("Include submissions", "ezfc"); ?></label>
					</p>
					<p>
						<input type="submit" name="submit-export-download" class="button button-primary" value="<?php echo __("Download export data", "ezfc"); ?>" /> &nbsp; 
						<input type="submit" name="submit-clear-export-files" class="button" value="<?php echo __("Clear temporary export files", "ezfc"); ?> " />
						<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
					</p>

					<p>
						<input type="submit" name="show-export-data" class="button" value="<?php esc_attr(_e("Show export data", "ezfc")); ?>" />
					</p>
				</form>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<div class="inner">
				<h3><?php echo __("Import text data", "ezfc"); ?></h3>
				<form method="POST" name="ezfc-form-import-text" class="ezfc-form" action="">
					<input type="hidden" name="ezfc-request" value="1" />
					<!-- import text -->
					<p><?php echo __("Paste form data here:", "ezfc"); ?></p>
					<textarea class="ezfc-settings-type-textarea" name="import_data"></textarea>

					<p>
						<input type="checkbox" name="preserve_ids" id="preserve_ids" value="1" /> <label for="preserve_ids"><?php _e("Preserve form IDs", "ezfc"); ?></label>
					</p>
					<p>
						<input type="checkbox" name="submit-import-submissions" value="1" id="submit-import-submissions" /> <label for="submit-import-submissions"><?php _e("Include submissions", "ezfc"); ?></label>
					</p>

					<p class="submit"><input type="submit" name="submit-import-text" id="submit-import-text" class="button button-primary" value="<?php echo __("Import text data", "ezfc"); ?>" /></p>

					<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
				</form>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<div class="inner">
				<h3><?php echo __("Import file", "ezfc"); ?></h3>	
				<form method="POST" name="ezfc-form-import-file" class="ezfc-form" action="" enctype="multipart/form-data">
					<input type="hidden" name="ezfc-request" value="1" />
					<!-- import file -->
					<p><?php echo __("Import exported file here:", "ezfc"); ?></p>
					<p><input type="file" name="ezfc-import-file" /></p>

					<p>
						<input type="checkbox" name="preserve_ids" id="preserve_ids2" value="1" /> <label for="preserve_ids2"><?php _e("Preserve form IDs", "ezfc"); ?></label>
					</p>
					<p>
						<input type="checkbox" name="submit-import-submissions" value="1" id="submit-import-submissions2" /> <label for="submit-import-submissions2"><?php _e("Include submissions", "ezfc"); ?></label>
					</p>

					<p class="submit"><input type="submit" name="submit-import-file" id="submit-import-file" class="button button-primary" value="<?php echo __("Upload and import file", "ezfc"); ?>" /></p>

					<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
				</form>	
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	$(".ezfc-form").on("submit", function() {
		// confirmation
		if (!confirm("Importing will overwrite all existing data. Continue?")) return false;
	});

	<?php if ($download == 1) { ?>
	document.location.href = "<?php echo $file_url; ?>";
	<?php } ?>
});
</script>