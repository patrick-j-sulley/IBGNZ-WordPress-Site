<?php

/**
	templates page
**/

defined( 'ABSPATH' ) OR exit;

//delete_transient("ezfc_template_list");

require_once(EZFC_PATH . "class.ezfc_functions.php");
require_once(EZFC_PATH . "class.ezfc_backend.php");
$ezfc = Ezfc_backend::instance();

// validate user
if (!empty($_POST["ezfc-request"])) $ezfc->validate_user("ezfc-nonce", "nonce");

$nonce = wp_create_nonce("ezfc-nonce");
$message = "";
$templates = array();
$templates_browse = array();

$licensed = get_option("ezfc_license_activated", 0);
if (!$licensed) {
	$message = __("You need to register your license in order to install templates.", "ezfc");
	$tabs = array(
		__("No license", "ezfc")
	);
}
else {
	if (!empty($_POST["template_install_id"])) {
		$res = EZFC_Functions::template_install($_POST["template_install_id"]);
		$message = array_shift($res);
	}
	else if (!empty($_POST["template_submit_id"])) {
		$res = EZFC_Functions::template_submit($_POST);
		$message = array_shift($res);
	}

	$templates = $ezfc->form_templates_get();
	array_unshift($templates, json_decode(json_encode(array("id" => "-1", "name" => __("Please select a template", "ezfc")))));
	$templates_browse = EZFC_Functions::template_get_list();

	$tabs = array(
		__("Browse templates", "ezfc")
		//__("Submit template", "ezfc")
	);
}

?>

<div class="ezfc wrap ezfc-wrapper container-fluid">
	<div class="row">
		<div class="col-lg-12">
			<div class="inner">
				<?php echo "<h2>" . __("Template Browser", "ezfc") . " - ez Form Calculator v" . EZFC_VERSION . "</h2>"; ?>

				<?php if (!empty($message)) { ?>
					<div id="message" class="updated"><?php echo $message; ?></div>
				<?php } ?>
			</div>
		</div>
	</div>

	<div id="tabs">
		<ul>
			<?php
			foreach ($tabs as $i => $cat) {
				echo "<li><a href='#tab-{$i}'>{$cat}</a></li>";
			}
			?>
		</ul>

		<!-- browse templates -->
		<div id="tab-0">
			<div class="row">
				<form method="POST" name="ezfc-form" class="ezfc-form" action="<?php echo admin_url('admin.php'); ?>?page=ezfc-templates" novalidate>
					<input type="hidden" name="ezfc-request" value="1" />
					<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
					<input type="hidden" name="template_install_id" />

					<?php
					if (count($templates_browse) > 0 && $licensed) {
						foreach ($templates_browse as $i => $template) {
							$install_enabled = true;
							if (version_compare($template->version, EZFC_VERSION) > 0) {
								$install_enabled = false;
							}

							$install_link = admin_url("admin.php") . "?page=ezfc-templates&template_install_id={$template->id}";
							?>

							<div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
								<div class="ezfc-template-list-wrapper">
									<h4 class="ezfc-template-list-name"><?php echo $template->name; ?></h4>

									<p><?php echo $template->description; ?></p>
									<p><?php echo __("Downloads", "ezfc") . ": " . $template->downloads; ?></p>

									<p class="ezfc-template-list-buttons">
										<?php if (!empty($template->preview_link)) { ?>
											<a href="<?php echo $template->preview_link; ?>" class="button" target="_blank"><?php echo __("Preview", "ezfc"); ?></a>
										<?php } ?>

										<?php if ($install_enabled) { ?>
											<input type="submit" name="install_template" data-id="<?php echo $template->id; ?>" value="<?php echo __("Install", "ezfc"); ?>" class="button button-primary ezfc-template-list-install" />
										<?php } else {
											echo "<br>" . sprintf(__("Requires version %s", "ezfc"), $template->version);
										} ?>
									</p>
								</div>
							</div>

							<?php if (($i + 1)%4 == 0 && $i > 0) { ?>
								<div class="ezfc-clear"></div>
							<?php }
						}
					}
					else {
						echo __("No templates available.", "ezfc");
					}
					?>
				</form>
			</div>
		</div>

		<!-- submit template -->
		<?php if ($licensed && 0) { ?>
			<div id="tab-1">
				<div class="row">
					<p>
						<?php echo __("You can submit your own templates to share with the community. We will review the template and if it fits for public purpose, we will add it to the list.", "ezfc"); ?>
					</p>

					<p>
						<?php echo __("We will not expose any personal data.", "ezfc"); ?>
					</p>

					<form method="POST" name="ezfc-form" class="ezfc-form" action="<?php echo admin_url('admin.php') . '?page=ezfc-templates'; ?>">
						<input type="hidden" name="nonce" value="<?php echo $nonce; ?>" />
						
						<p>
							<label for="template_submit_id"><?php echo __("Which template do you want to submit?", "ezfc"); ?></label><br>
							<select name="template_submit_id" id="template_submit_id">
								<?php
									$out = "";
									foreach ($templates as $t) {
										$out .= "<option value='{$t->id}'>{$t->name}</option>";
									}
									echo $out;
									?>
							</select>
						</p>

						<p>
							<label for="author"><?php echo __("Your name or website", "ezfc"); ?></label><br>
							<input class="ezfc-input" type="text" name="author" />
						</p>

						<p>
							<label for="email"><?php echo __("Your email address", "ezfc"); ?></label><br>
							<input class="ezfc-input" type="text" name="email" />
						</p>

						<p>
							<label for="description"><?php echo __("What is the form about?", "ezfc"); ?></label><br>
							<textarea class="ezfc-textarea" name="description"></textarea>
						</p>

						<p>
							<input class="button button-primary" type="submit" id="submit_template" name="submit_template" value="<?php echo __("Submit template", "ezfc"); ?>" />
						</p>
					</form>
				</div>
			</div>
		<?php } ?>
	</div>
</div>

<script>
ezfc_nonce = "<?php echo $nonce; ?>";

jQuery(document).ready(function($) {
	$(".ezfc-template-list-install").click(function() {
		var id = $(this).data("id");
		$(this).parents("form").find("[name='template_install_id']").val(id);
	});

	$("#submit_template").click(function() {
		var id = $("#template_submit_id :selected").val();
		
		if (id == -1) return false;
	});
})
</script>