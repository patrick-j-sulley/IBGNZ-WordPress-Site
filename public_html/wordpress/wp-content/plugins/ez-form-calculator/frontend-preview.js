jQuery(document).ready(function($) {
	$(document).on("click", ".ezfc-submit", function() {
		$(this).closest(".ezfc-form").submit();
	});
});