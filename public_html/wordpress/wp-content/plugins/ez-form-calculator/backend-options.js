jQuery(document).ready(function($) {
	//$("#tabs").tabs();
	
	/**
		global
	**/
	var reset_confirmation = true;
	var locale_count = 0;

	// reset confirmation
	$(".ezfc-form").on("submit", function() {
		if (($("#ezfc-overwrite").prop("checked") || $("#ezfc-reset").prop("checked")) && reset_confirmation) {
			if (!confirm("Really overwrite all settings?")) return false;
		}
	});

	$("#price-preview-wrapper").insertBefore("#tab-1 .form-table");

	$("#opt-price_format, #opt-email_price_format_dec_point, #opt-email_price_format_thousand, #ezfc-table-option-price_format_replace_trailing_zeros input, #ezfc-table-option-price_format_replace_trailing_zeros select").on("keyup change", function() {
		numeral.locale("en");
		var price_format    = $("#opt-price_format").val();
		var price_decimal   = $("#opt-email_price_format_dec_point :selected").val();
		var price_thousands = $("#opt-email_price_format_thousand :selected").val();
		var price_previews  = [];

		$(".ezfc-price-preview").each(function() {
			var price_orig = $(this).find(".ezfc-price-preview-orig").text();
			price_previews.push(numeral(price_orig));
		});

		var tmp_locale = "ezfc_" + (locale_count++);
		numeral.register("locale", tmp_locale, {
			delimiters: {
				decimal:   price_decimal,
				thousands: price_thousands
			},
			abbreviations: {
	            thousand: 'k',
	            million: 'm',
	            billion: 'b',
	            trillion: 't'
	        },
	        ordinal: function (number) {
	            var b = number % 10;
	            return (~~ (number % 100 / 10) === 1) ? 'th' :
	                (b === 1) ? 'st' :
	                (b === 2) ? 'nd' :
	                (b === 3) ? 'rd' : 'th';
	        },
	        currency: {
	            symbol: '$'
	        }
		});
		numeral.locale(tmp_locale);

		$(".ezfc-price-preview").each(function(i, el) {
			var price = price_previews[i];
			var price_formatted = numeral(price).format(price_format);

			if ($("#opt-price_format_replace_trailing_zeros-enabled").val() == 1 && parseFloat(price._value).toFixed(6) % 1 === 0) {
				var add_char = $("#opt-price_format_replace_trailing_zeros-text").val();
				price_formatted += price_decimal + add_char;
			}

			$(this).find(".ezfc-price-preview-formatted").text(price_formatted);
		});
	});

	$(".predefined-price-format").click(function() {
		var format = $(this).data("format");

		switch (format) {
			case "default":
				$("#opt-price_format").val("0,0[.]00");
				$("#opt-email_price_format_thousand [value=',']").attr("selected", "selected");
				$("#opt-email_price_format_dec_point [value='.']").attr("selected", "selected");
			break;

			case "eu":
				$("#opt-email_price_format_thousand [value='.']").attr("selected", "selected");
				$("#opt-email_price_format_dec_point [value=',']").attr("selected", "selected");
			break;

			case "show_decimal_numbers":
				$("#opt-price_format").val("0,0.00");
			break;
		}

		$("#opt-price_format").trigger("change");
		return false;
	});

	$("#opt-price_format").trigger("change");

	/**
		form
	**/
	$(".ezfc-single-overwrite-button").click(function() {
		if (!confirm(ezfc_vars.form_overwrite_confirm)) return false;

		// do not ask to overwrite twice
		reset_confirmation = false;

		var $form = $(this).parents(".ezfc-form");

		// get this option
		var $option_field = $(this).parents("tr").find("input, select, textarea");

		// disable all options
		$form.find("input, select, textarea").attr("disabled", "disabled");

		// reenable necessary options
		$option_field.removeAttr("disabled");
		$("#ezfc-overwrite").removeAttr("disabled").prop("checked", true);

		$form.submit();
	});
});