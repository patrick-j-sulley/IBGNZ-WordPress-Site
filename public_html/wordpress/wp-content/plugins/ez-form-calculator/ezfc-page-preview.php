<?php

/**
	preview page
**/

defined( 'ABSPATH' ) OR exit;

if (!isset($_GET["nonce"])) {
	echo __("This page is intended for preview purposes.", "ezfc");
	die();
}

require_once(EZFC_PATH . "class.ezfc_backend.php");
$ezfc = Ezfc_backend::instance();
$ezfc->validate_user("ezfc-preview-nonce", "nonce");

$preview_id = (int) $_GET["preview_id"];

?>

<div class="wrap ezfc ezfc-wrapper ezfc-preview container-fluid">
	<div class="row">
		<div class="col-lg-12">
			<div class="inner">
				<h3><?php echo sprintf(__("Preview form #%s", "ezfc"), $preview_id); ?></h3>
				
				<?php
				Ezfc_shortcode::$add_script = true;
				Ezfc_shortcode::wp_head();
				echo do_shortcode("[ezfc preview='{$preview_id}' /]");
				Ezfc_shortcode::print_script();
				?>
			</div>
		</div>
	</div>
</div>