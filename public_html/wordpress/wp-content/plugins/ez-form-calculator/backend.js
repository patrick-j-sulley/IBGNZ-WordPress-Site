EZFC_Backend_Object = function($) {
	var _this = this;

	this.init = function() {
		/**
			ui
		**/
		$.fx.speeds._default = 200;
		if (typeof tinyMCE !== "undefined") {
			tinyMCE.init({
				plugins: "wordpress wplink",
				relative_urls: ezfc_vars.editor.tinymce_use_relative_urls == 1,
				menubar: false
			});
		}

		// colorpicker
		if (typeof $.prototype.wpColorPicker !== "undefined") {
			$(".ezfc-element-colorpicker-input").wpColorPicker();
		}

		// form builder vars
		this.vars = {
			active_element_id          : 0,
			$active_element_dropdown   : 0,
			active_section             : "",
			batch_separator            : ezfc_vars.editor.batch_separator,
			conditional_show_target_option_id_array: ["activate_option", "deactivate_option", "set_factor_option"],
			current_batch_keys         : [],
			current_dialog_action      : "",
			current_element_data       : [],
			current_form_elements      : [],
			drag_placeholder_html      : "<div id='ezfc-element-drag-placeholder' class='ui-state-default ezfc-add-element-placeholder-item' data-dropped='1'><i class='fa fa-cog fa-spin'></i></div>", // drag placeholder
			element_add_from_position  : 0, // placeholder - add element from main view
			enable_target_value_actions: ["set", "set_factor", "email_target", "set_min_selectable", "set_max_selectable", "add_class", "remove_class", "set_color", "select_option", "deselect_option", "show_option", "hide_option", "set_min", "set_max"],
			ezfc_elements_data         : [],
			form_options               : [],
			tooltips                   : [],
			ezfc_z_index               : 1000000,
			form_changed               : false,
			selected_element           : 0,
			// don't show these values in element data but include them in element data
			skip_early_options         : ["columns", "e_id", "group_id"],
			// don't show these values and exclude them since they're being added elsewhere
			skip_early_options_exclude : ["show_in_email_cond", "show_in_email_operator", "show_in_email_value", "slider_vertical"],
			// restrict calculation target / value field
			trigger_change_classes     : ".ezfc-form-element-calculate-target, .ezfc-form-element-calculate-operator, .ezfc-form-element-conditional-action, .ezfc-form-element-conditional-row-operator",

			// icons
			icons: {
				add: "<i class='fa fa-plus-square'></i> ",
				batch_edit: "<i class='fa fa-list'></i> ",
				calc_invalid: "<i class='fa fa-exclamation-triangle'></i> ",
				calc_valid: "<i class='fa fa-check-square'></i> ",
				delete: "<i class='fa fa-times'></i> ",
				matrix: {
					add_column: "<i class='fa fa-plus-square'></i> <i class='fa fa-arrow-right'></i> ",
					add_row: "<i class='fa fa-plus-square'></i> <i class='fa fa-arrow-down'></i> "
				},
				name_to_label: "<i class='fa fa-level-down'></i> ",
				option_create_condition: "<i class='fa fa-lightbulb-o'></i> ",
				option_create_ids: "<i class='fa fa-key'></i> ",
				prio_dec: "<i class='fa fa-chevron-left'></i> ",
				prio_inc: "<i class='fa fa-chevron-right'></i> ",
				select_target: "<i class='fa fa-mouse-pointer'></i>",
				tabs: {
					basic: "<i class='fa fa-cubes'></i> ",
					calculate: "<i class='fa fa-calculator'></i> ",
					conditional: "<i class='fa fa-lightbulb-o'></i> ",
					discount: "<i class='fa fa-percent'></i> ",
					styling: "<i class='fa fa-paint-brush'></i> "
				}
			},

			/**
				operator lists
			**/
			operators: [
				{ value: "0", text: " " },
				{ value: "add", text: "+" },
				{ value: "subtract", text: "-" },
				{ value: "multiply", text: "*" },
				{ value: "divide", text: "/" },
				{ value: "equals", text: "=" },
				{ value: "power", text: "^" },
				{ value: "abs", text: "abs" },
				{ value: "ceil", text: "ceil" },
				{ value: "floor", text: "floor" },
				{ value: "round", text: "round" },
				{ value: "sqrt", text: "sqrt" },
				{ value: "log", text: "log" },
				{ value: "log2", text: "log2" },
				{ value: "log10", text: "log10" },
				{ value: "subtotal", text: "subtotal" }
			],

			operators_discount: [
				{ value: "0", text: " " },
				{ value: "add", text: "+" },
				{ value: "subtract", text: "-" },
				{ value: "percent_add", text: "%+" },
				{ value: "percent_sub", text: "%-" },
				{ value: "equals", text: "=" },
				{ value: "factor", text: "factor" }
			],

			cond_operators: [
				{ value: "0", text: " " },
				{ value: "gr", text: ">" },
				{ value: "gre", text: ">=" },
				{ value: "less", text: "<" },
				{ value: "lesse", text: "<=" },
				{ value: "equals", text: "=" },
				{ value: "not", text: "not" },
				{ value: "between", text: "between" },
				{ value: "not_between", text: "not between" },
				{ value: "hidden", text: "is hidden" },
				{ value: "visible", text: "is visible" },
				{ value: "selected", text: "selected" },
				{ value: "selected_id", text: "selected ID" },
				{ value: "selected_count", text: "selected count" },
				{ value: "selected_count_gt", text: "selected count >" },
				{ value: "selected_count_lt", text: "selected count <" },
				{ value: "selected_index", text: "selected index" },
				{ value: "not_selected", text: "not selected" },
				{ value: "not_selected_id", text: "not selected ID" },
				{ value: "not_selected_count", text: "not selected count" },
				{ value: "not_selected_index", text: "not selected index" },
				{ value: "calculate_enabled", text: "calculate is enabled" },
				{ value: "calculate_disabled", text: "calculate is disabled" },
				{ value: "mod0", text: "%x = 0" },
				{ value: "mod1", text: "%x != 0" },
				{ value: "bit_and", text: "bitwise AND" },
				{ value: "bit_or", text: "bitwise OR" },
				{ value: "empty", text: "empty" },
				{ value: "notempty", text: "not empty" },
				{ value: "in", text: "in" },
				{ value: "not_in", text: "not in" },
				{ value: "once", text: "once" },
				{ value: "always", text: ezfc_vars.texts.always },
				{ value: "focus", text: ezfc_vars.texts.focus },
				{ value: "blur", text: ezfc_vars.texts.blur },
				{ value: "step_equals", text: ezfc_vars.texts.step_equals },
				{ value: "step_gt", text: ezfc_vars.texts.step_gt },
				{ value: "step_lt", text: ezfc_vars.texts.step_lt }
			],

			cond_actions: [
				{ value: "0", text: " " },
				{ value: "show", text: ezfc_vars.conditional_actions.show },
				{ value: "hide", text: ezfc_vars.conditional_actions.hide },
				{ value: "set", text: ezfc_vars.conditional_actions.set },
				{ value: "set_factor", text: ezfc_vars.conditional_actions.set_factor },
				{ value: "set_factor_option", text: "Set factor single option" },
				{ value: "activate", text: ezfc_vars.conditional_actions.activate },
				{ value: "activate_option", text: ezfc_vars.conditional_actions.activate_option },
				{ value: "deactivate", text: ezfc_vars.conditional_actions.deactivate },
				{ value: "deactivate_option", text: ezfc_vars.conditional_actions.deactivate_option },
				{ value: "select_option", text: ezfc_vars.conditional_actions.select_option },
				{ value: "deselect_option", text: ezfc_vars.conditional_actions.deselect_option },
				{ value: "show_option", text: ezfc_vars.conditional_actions.show_option },
				{ value: "hide_option", text: ezfc_vars.conditional_actions.hide_option },
				{ value: "redirect", text: ezfc_vars.conditional_actions.redirect },
				{ value: "step_goto", text: ezfc_vars.conditional_actions.step_goto },
				{ value: "step_next", text: ezfc_vars.conditional_actions.step_next },
				{ value: "step_prev", text: ezfc_vars.conditional_actions.step_prev },
				{ value: "email_target", text: ezfc_vars.conditional_actions.email_target },
				{ value: "set_min", text: ezfc_vars.conditional_actions.set_min },
				{ value: "set_max", text: ezfc_vars.conditional_actions.set_max },
				{ value: "set_min_selectable", text: ezfc_vars.conditional_actions.set_min_selectable },
				{ value: "set_max_selectable", text: ezfc_vars.conditional_actions.set_max_selectable },
				{ value: "add_class", text: ezfc_vars.conditional_actions.add_class },
				{ value: "remove_class", text: ezfc_vars.conditional_actions.remove_class },
				{ value: "set_color", text: ezfc_vars.conditional_actions.set_color }
			],

			set_operators: [
				{ value: "min", text: ezfc_vars.set_operators.min },
				{ value: "max", text: ezfc_vars.set_operators.max },
				{ value: "avg", text: ezfc_vars.set_operators.avg },
				{ value: "sum", text: ezfc_vars.set_operators.sum },
				{ value: "dif", text: ezfc_vars.set_operators.dif },
				{ value: "prod", text: ezfc_vars.set_operators.prod },
				{ value: "quot", text: ezfc_vars.set_operators.quot }
			],

			// element option sections
			element_option_sections: {
				basic: [],
				calculate: ["calculate_enabled", "add_to_price", "calculate", "overwrite_price", "calculate_when_hidden", "precision", "calculate_before", "inline_calculation"],
				conditional: ["conditional"],
				discount: ["discount_value_type", "discount"],
				styling: ["class", "wrapper_class", "style", "wrapper_style", "max_width", "max_height", "inline"]
			},

			// dom elements
			$form_elements: $("#form-elements"),
			$form_elements_list: $("#form-elements-list")
		};

		// filter calculation elements
		if (typeof ezfc !== "undefined") {
			this.vars.calculation_elements = [];
			$.each(ezfc.elements, function(i, el) {
				if (el.data.calculate !== undefined || el.data.custom_calculation !== undefined) _this.vars.calculation_elements.push(el.type);
			});
		}

		// additional options that can be chosen for dynamically generated dropdown elements list
		this.vars.elements_list_add = {
			calculation: ["<option value='submit_button'>" + ezfc_vars.submit_button + "</option>", "<option value='price'>" + ezfc_vars.price + "</option>"],
			conditional: ["<option value='submit_button'>" + ezfc_vars.submit_button + "</option>", "<option value='price'>" + ezfc_vars.price + "</option>"]
		};
		// convert to string
		$.each(_this.vars.elements_list_add, function(i, item) {
			_this.vars.elements_list_add[i] = item.join("");
		});

		_this.attach_events();

		// open last form
		if (typeof ezfc_vars.editor !== "undefined" && typeof ezfc_vars.editor.reopen_last_form_id !== "undefined") {
			var $form_list_item = $(".ezfc-form[data-id='" + ezfc_vars.editor.reopen_last_form_id + "']").first();
			
			if ($form_list_item.length) {
				$form_list_item.click();
				$(".ezfc-forms-list").animate({ scrollTop: $form_list_item.offset().top }, "slow");
			}
		}
	};

	this.attach_events = function() {
		_this.init_tooltips();

		// tabs
		if ($("#tabs").length) {
			$("#tabs").tabs();
		}

		// accordion
		if ($(".ezfc-accordion").length) {
			$(".ezfc-accordion").accordion({
				heightStyle: "content"
			});
		}

		// dialogs
		var dialog_default_attr = {
			autoOpen: false,
			height: Math.min(800, $(window).height() - 200),
			width: Math.min(1200, $(window).width() - 200),
			modal: true,
			buttons: {
				"Close": function() {
					$(this).dialog("close");
				}
			}
		};

		/**
			dialog setup
		**/
		if ($(".ezfc-options-dialog").length) {
			// default dialog
			$(".ezfc-default-dialog").dialog(dialog_default_attr);

			// options dialog
			$(".ezfc-options-dialog").dialog($.extend({}, dialog_default_attr, {
				buttons: {
					"Update options": function() {
						$(".ezfc-option-save").click();
					},
					"Close": function() {
						$(this).dialog("close");
					}
				}
			}));

			// import dialog
			$("#ezfc-import-dialog").dialog($.extend({}, dialog_default_attr, {
				buttons: {
					"Import text data": function() {
						$("[data-action='form_import_data']").click();
					},
					"Close": function() {
						$(this).dialog("close");
					}
				}
			}));

			// import add elements dialog
			$("#ezfc-import-add-elements-dialog").dialog($.extend({}, dialog_default_attr, {
				buttons: {
					"Import text data": function() {
						$("[data-action='form_import_add_elements_data']").click();
					},
					"Close": function() {
						$(this).dialog("close");
					}
				}
			}));

			// batch dialog
			$("#ezfc-dialog-batch-edit").dialog($.extend({}, dialog_default_attr, {
				buttons: {
					"Batch import": function() {
						_this.builder_functions.batch_edit_save();
					},
					"Close": function() {
						$(this).dialog("close");
					}
				},
				open: function(event, ui) {
					var $active_element = $(".ezfc-form-element-active");
					var out_array       = [];
					var out             = "";
					var option_list;
					var $options_wrapper;

					switch (_this.vars.current_dialog_action) {
						// options
						case "options":
							$options_wrapper   = $active_element.find(".ezfc-row-options .ezfc-option-container .ezfc-form-element-option");
							option_list        = ".ezfc-form-element-option-id, .ezfc-form-element-option-value, .ezfc-form-element-option-text, .ezfc-form-element-option-disabled";
							_this.vars.current_batch_keys = ["id", "value", "text", "disabled"];
						break;

						// calculation
						case "calculate":
							$options_wrapper   = $active_element.find(".ezfc-row-calculate .ezfc-option-container .ezfc-form-element-option");
							option_list        = ".ezfc-form-element-calculate-operator,.ezfc-form-element-calculate-target,.ezfc-form-element-calculate-ctv,.ezfc-form-element-calculate-value";
							_this.vars.current_batch_keys = ["operator", "target", "use_calculated_target_value", "value"];
						break;

						// conditional
						case "conditional":
							$options_wrapper   = $active_element.find(".ezfc-row-conditional .ezfc-option-container .ezfc-form-element-option");
							option_list        = ".ezfc-form-element-conditional-action, .ezfc-form-element-conditional-target, .ezfc-form-element-conditional-target-value, .ezfc-form-element-conditional-operator, .ezfc-form-element-conditional-value, .ezfc-form-element-conditional-row-operator, .ezfc-form-element-conditional-notoggle, .ezfc-form-element-conditional-use_factor, .ezfc-conditional-compare-value, .ezfc-conditional-chain-operator, .ezfc-conditional-chain-value";
							_this.vars.current_batch_keys = ["action", "target", "target_value", "operator", "value", "row_operator", "notoggle", "use_factor", "compare_value", "operator_chain", "value_chain"];
						break;

						case "discount":
							$options_wrapper   = $active_element.find(".ezfc-row-discount .ezfc-option-container .ezfc-form-element-option");
							option_list        = ".ezfc-form-element-discount-range_min,.ezfc-form-element-discount-range_max,.ezfc-form-element-discount-operator,.ezfc-form-element-discount-discount_value";
							_this.vars.current_batch_keys = ["range_min", "range_max", "operator", "discount_value"];
						break;
					}

					$options_wrapper.each(function(i, opt_wrapper) {
						var $inputs = $(opt_wrapper).find(option_list);
						var tmp_out = [];

						$inputs.each(function(ii, col_input) {
							var tmp_val = $(col_input).val();

							// checkbox?
							if ($(col_input).attr("type") == "checkbox") tmp_val = $(col_input).is(":checked") ? 1 : 0;

							tmp_out.push(tmp_val);
						});

						out_array.push(tmp_out.join(_this.vars.batch_separator));
					});

					// values
					$("#ezfc-batch-edit-textarea").val(out_array.join("\n"));
					// description
					$("#ezfc-dialog-batch-edit-description").text(_this.vars.current_batch_keys.join(_this.vars.batch_separator));
				}
			}));

			// quick-add dialog
			$("#ezfc-dialog-quick-add").dialog($.extend({}, dialog_default_attr, {
				buttons: {
					"Quick add": function() {
						var val = $("#ezfc-quick-add-textarea").val();
						_this.builder_functions.quick_add(val);
					},
					"Close": function() {
						$(this).dialog("close");
					}
				}
			}));
		}

		// ajax actions
		$(document).on("click", "[data-action]", function() {
			if ($(this).data("action") == "") return false;

			if ($(this).data("action") == "form_get" && _this.vars.form_changed) {
				if (!confirm(ezfc_vars.form_changed)) {
					$(".ezfc-loading").hide();
					return false;
				}
			}

			var args        = $(this).data("args");
			var selectgroup = $(this).data("selectgroup");
			if (selectgroup) {
				$(".button-primary[data-selectgroup='" + selectgroup + "']").removeClass("button-primary");
				$(this).addClass("button-primary");
			}

			_this.do_action($(this), false, false, false, false, args);

			return false;
		});

		// toggle form element data or select target
		$(document).on("click", ".ezfc-form-element-name", function() {
			var id = $(this).closest(".ezfc-form-element").data("id");

			// target selection
			if (_this.vars.active_element_id) {
				_this.builder_functions.select_target_selected(id);
			}
			// open element
			else {
				_this.builder_functions.element_data_open(id);
			}

			return false;
		});
		// toggle form element data via modal
		$(document).on("click", "#ezfc-element-data-modal", function() {
			_this.builder_functions.element_data_close();
		});
		// toggle form element data via modal (via escape)
		$(document).keyup(function(e) {
			if (e.keyCode == 27) {
				// selecting a target element
				if (_this.vars.active_element_id) {
					_this.builder_functions.element_data_open(_this.vars.active_element_id);
					_this.builder_functions.select_target_reset();
				}
				else {
					_this.builder_functions.element_data_close();
				}
			}
		});

		// toggle submission data
		$(document).on("click", ".ezfc-form-submission-name", function() {
			$(this).parent().find(".ezfc-form-submission-data").toggle();
		});

		// image upload
		$(document).on("click", ".ezfc-image-upload", function(e) {
			e.preventDefault();

			var file_frame;
			var __this = this;

			file_frame = wp.media.frames.file_frame = wp.media({
			  title: jQuery( this ).data( 'uploader_title' ),
			  button: {
				text: jQuery( this ).data( 'uploader_button_text' ),
			  },
			  multiple: false
			});
		 
			file_frame.on( 'select', function() {
				var attachment = file_frame.state().get('selection').first().toJSON();

				var $parent = $(__this).parent();
				$parent.find(".ezfc-image-upload-hidden").val(attachment.url);
				$parent.find(".ezfc-image-filename").text(attachment.url);
				$parent.find("input[data-element-name='image']").val(attachment.url);

				// preview
				if (_this.builder_functions.is_image(attachment.url)) {
					$parent.find(".ezfc-image-preview").attr("src", attachment.url);
				}
			});
		 
			file_frame.open();
		});
		// clear image
		$(document).on("click", ".ezfc-clear-image", function() {
			var $parent = $(this).parent();

			$parent.find(".ezfc-image-upload-hidden").val("");
			$parent.find(".ezfc-image-filename").text("");
			$parent.find("img").attr("src", "");

			return false;
		});

		// multiple files upload
		$(document).on("click", ".ezfc-files-upload", function(e) {
			e.preventDefault();

			var file_frame;
			var __this = this;

			file_frame = wp.media.frames.file_frame = wp.media({
			  title: jQuery( this ).data( 'uploader_title' ),
			  button: {
				text: jQuery( this ).data( 'uploader_button_text' ),
			  },
			  multiple: true
			});

			// preselect
			file_frame.on("open", function() {
				var selection = file_frame.state().get("selection");

				var attachment_ids = $(__this).parent().find(".ezfc-files-upload-hidden").val().split(",");
				attachment_ids.forEach(function(id) {
					attachment = wp.media.attachment(id);
					attachment.fetch();
					selection.add(attachment ? [ attachment ] : []);
				});
			});
		 
			// open
			file_frame.on( 'select', function() {
				var attachments = file_frame.state().get('selection').toJSON();
				if (!attachments.length) return;

				var attachment_ids = [];
				for (var i in attachments) {
					attachment_ids.push(attachments[i].id);
				}
				var attachment_ids_output = attachment_ids.join(",");

				$(__this).parent().find(".ezfc-files-upload-hidden").val(attachment_ids_output);
				$(__this).parent().find(".ezfc-files-ids").text(attachment_ids_output);
			});
		 
			file_frame.open();
		});
		// clear image
		$(document).on("click", ".ezfc-clear-files", function() {
			var $parent = $(this).parent();

			$parent.find(".ezfc-files-upload-hidden").val("");
			$parent.find(".ezfc-files-ids").text("");

			return false;
		});

		// add option
		$(document).on("click", ".ezfc-form-element-option-add", function() {
			_this.builder_functions.option_add($(this));

			return false;
		});
		// delete option
		$(document).on("click", ".ezfc-form-element-option-delete", function() {
			_this.builder_functions.option_remove($(this));

			return false;
		});

		// restrict calculation target / value field
		$(document).on("change", _this.vars.trigger_change_classes, function() {
			_this.custom_trigger_change($(this).closest(".ezfc-form-element-data"));
		});

		// label name keyboard input
		$(document).on("keyup change", ".ezfc-form-element-data .element-label-listener, [data-element-name='name'], [data-element-name='label'], [data-element-name='title']", function() {
			debounce_update_element_title($(this));
		});
		// -- debounce label name keyboard input
		var debounce_update_element_title = _this.debounce(function($el) {
			_this.builder_functions.update_element_title($el.closest(".ezfc-form-element").data("id"));
		}, 100);

		// add changed class upon change
		$(document).on("keyup change", ".ezfc-form-element-data input, .ezfc-form-element-data select", function() {
			_this.form_has_changed($(this));
		});
		// add changed class when options were added / removed
		$(document).on("click", ".ezfc-form-element-data button:not(.ezfc-form-element-close-data)", function() {
			_this.form_has_changed($(this));
		});

		// required toggle char
		$(document).on("click", ".ezfc-form-element-required-toggle", function() {
			_this.vars.form_changed = true;

			var req_char = $(this).val()==1 ? "*" : "";
			$(this).closest(".ezfc-form-element").find(".ezfc-form-element-required-char").text(req_char);
		});

		// preview suppress submit
		$(document).on("click", "form .ezfc-element-submit", function() {
			return false;
		});

		// column change
		$(document).on("click", ".ezfc-form-element-column-left", function() {
			_this.maybe_add_data_element($(this).closest(".ezfc-form-element"));

			_this.change_columns($(this), -1);
			return false;
		});
		$(document).on("click", ".ezfc-form-element-column-right", function() {
			_this.maybe_add_data_element($(this).closest(".ezfc-form-element"));

			_this.change_columns($(this), 1);
			return false;
		});

		// group toggle
		$(document).on("click", ".ezfc-form-element-group-toggle", function() {
			$(this).closest(".ezfc-form-element").find("> .ezfc-group").toggle();

			var icon_set = ["fa-toggle-up", "fa-toggle-down"];

			var $group_icon_el = $(this).find(".fa");
			var old_icon       = $group_icon_el.hasClass(icon_set[0]) ? icon_set[0] : icon_set[1];
			var set_icon       = old_icon == icon_set[0] ? icon_set[1] : icon_set[0];
			
			$group_icon_el.removeClass(old_icon).addClass(set_icon);
			return false;
		});

		// tinymce toggle
		$(document).on("click", ".ezfc-html-tinymce-toggle", function() {
			if (typeof tinyMCE !== "undefined" && typeof tinyMCE.execCommand === "function") {
				var target = $(this).data("target");
				tinymce.execCommand("mceToggleEditor", false, target);
			}

			return false;
		});

		// icon dialog
		$(document).on("click", ".ezfc-icon-button", function() {
			$icon_select_element = $(this);
			$(".ezfc-icons-dialog").dialog("open");

			return false;
		});
		$(".ezfc-icons-dialog i").on("click", function() {
			var icon = $(this).data("icon");
			var $icon_input, $icon_placeholder;

			// check if element with options
			var $option_wrapper   = $icon_select_element.closest(".ezfc-form-element-option");
			if ($option_wrapper.length > 0) {
				$icon_input       = $option_wrapper.find(".ezfc-form-element-option-icon");
				$icon_placeholder = $option_wrapper.find("[data-previewicon]");
			}
			// element with input field
			else {
				$icon_input       = $("#" + $icon_select_element.data("target"));
				$icon_placeholder = $icon_select_element.closest(".ezfc-row-icon").find("[data-previewicon]");
			}

			$icon_input.val(icon);
			$icon_placeholder.attr("class", "ezfc-option-icon-placeholder fa " + icon);

			$(".ezfc-icons-dialog").dialog("close");
		});

		// image dialog
		$(document).on("click", ".ezfc-option-image-button", function(e) {
			e.preventDefault();

			var file_frame;
			var __this = this;

			file_frame = wp.media.frames.file_frame = wp.media({
			  title: jQuery( this ).data( 'uploader_title' ),
			  button: {
				text: jQuery( this ).data( 'uploader_button_text' ),
			  },
			  multiple: false
			});
		 
			file_frame.on( 'select', function() {
				var attachment = file_frame.state().get('selection').first().toJSON();
				$(__this).parent().find("input").val(attachment.url);
				$(__this).parent().find(".ezfc-option-image-placeholder").attr("src", attachment.url);
			});
		 
			file_frame.open();

			return false;
		});
		// image remove
		$(document).on("click", ".ezfc-option-image-remove", function() {
			// remove option image + input
			$(this).siblings(".ezfc-form-element-option-image, .ezfc-image-upload-hidden").val("");
			$(this).siblings(".ezfc-option-image-placeholder, .ezfc-image-preview").attr("src", "");

			// remove icon image + input
			$(this).siblings(".ezfc-form-element-option-icon").val("");
			$(this).siblings(".ezfc-option-icon-placeholder").attr("class", "ezfc-option-icon-placeholder");

			return false;
		});

		// functions help dialog
		$(document).on("click", ".ezfc-open-function-dialog", function() {
			$("#ezfc-custom-calculation-functions").dialog("open");
			
			return false;
		});

		// toggle
		$(document).on("click", "[data-toggle]", function() {
			var $target = $($(this).data("toggle"));
			if ($target.length < 1) return;

			if ($target.hasClass("ezfc-hidden")) $target.removeClass("ezfc-hidden");
			else $target.addClass("ezfc-hidden");
		});

		// badge update listener
		$(document).on("change", ".ezfc-badge-listener", function() {
			var $element = $(this).closest(".ezfc-form-element");

			_this.builder_functions.set_section_badges($element);
		});

		// custom func
		$(document).on("click", "[data-func]", function() {
			var function_name = $(this).data("func");

			if (typeof _this.builder_functions[function_name] !== "function") return false;

			var args = $(this).data("args");

			_this.builder_functions[function_name]($(this), args);
			return false;
		});

		// value comma -> dot notification
		var tip_value_dot_notification_elements = ".ezfc-form-element-conditional-value, .ezfc-form-element-discount-range_min, .ezfc-form-element-discount-range_max";
		$(document).on("keyup change", tip_value_dot_notification_elements, function() {
			var $element = $(this);
			var has_comma = $element.val().indexOf(",") > -1;

			if (has_comma) {
				// todo
				$element.tooltip({
					content: ezfc_vars.notifications.value_dot_notfication
				});
				$element.tooltip("open");
			}
			else {
				try {
					$element.tooltip("close");
				} catch(e) {}
			}
		});

		// calculation input change -> update calculation text
		$(document).on("keyup change", ".ezfc-form-element-calculate-wrapper input, .ezfc-form-element-calculate-wrapper select", function() {
			_this.custom_trigger_change($(this).closest(".ezfc-form-element-data"));
		});

		// context menu -> open target
		/**$(document).on("contextmenu", ".fill-elements", function() {
			var target_id = $(this).find(":selected").val();
			if (target_id == 0) return false;

			//_this.builder_functions.element_data_close();
			_this.builder_functions.element_data_open(target_id);

			return false;
		});**/
		// context menu -> click target
		$(document).on("contextmenu", ".fill-elements", function() {
			_this.builder_functions.select_target_activate($(this));

			return false;
		});

		// dropdown selected data -> update selected item
		$(document).on("keyup change", "select[data-selected]", function() {
			var selected = $(this).val();
			$(this).data("selected", selected);
		});

		// option selected -> toggle
		$(document).on("change", ".ezfc-select-toggle", function() {
			_this.builder_functions.option_toggle_select($(this));
		});

		// section toggle
		$(document).on("click", ".ezfc-element-option-section-heading", function() {
			_this.builder_functions.set_section($(this).data("section"));
		});

		$(document).on("click", ".ezfc-group.ui-sortable", function() {
			// todo?
		});

		// window close confirmation if form was changed
		window.addEventListener("beforeunload", function (e) {
			if (_this.vars.form_changed) {
				var confirmationMessage = _this.vars.form_changed;
				(e || window.event).returnValue = confirmationMessage;
				return confirmationMessage;
			}
		});

		/**
			import data via file
		**/
		if ($("#ezfc_import_file").length > 0) {
			var import_btn          = $(".ezfc-import-upload");
			var import_message_el   = $("#ezfc-import-message");
			var import_progress_bar = import_btn.siblings(".ezfc-bar");

			$("#ezfc_import_file").fileupload({
				formData: {
					action: "ezfc_backend",
					data: "action=form_import_upload",
					nonce: ezfc_nonce
				},
				add: function (e, data) {
					data.submit();

					$(".ezfc-loading").fadeIn();
				},
				done: function (e, data) {
					import_message_el.text("");
					import_progress_bar.css("width", 0).text("");
					$(this).val("");

					if (data.result.error) {
						import_message_el.text(data.result.error);

						return false;
					}

					try {
						var result_json = $.parseJSON(data.result);

						_this.form_add(result_json);
						_this.form_show(result_json);
					}
					catch(err) {
						console.log("Unable to import form.");
					}

					$(".ezfc-dialog").dialog("close");
					$(".ezfc-loading").fadeOut();
				},
				progressall: function (e, data) {
					var progress = parseInt(data.loaded / data.total * 100, 10);
					import_progress_bar.css("width", progress + "%").text("Importing...");
				},
				replaceFileInput: false,
				url: ajaxurl
			});
		}

		// stick add elements div to top
		var $element_option_wrapper = $("#ezfc-form-options-wrapper");
		var $action_bar = $("#ezfc-action-bar");
		if ($element_option_wrapper.length > 0) {
			var action_row_top = $("#ezfc-action-row").offset().top;

			$(window).scroll(function() {
				if ($(window).scrollTop() > action_row_top + 30 ) {
					$element_option_wrapper.addClass("ezfc-sticky");
					$action_bar.addClass("ezfc-sticky");
				} else {
					$element_option_wrapper.removeClass("ezfc-sticky");
					$action_bar.removeClass("ezfc-sticky");
				}
			});
		}

		// rating dialog
		var rating_dialog = $("#ezfc-rating-dialog");
		if (rating_dialog.length > 0) {
			$("#ezfc-rating-dialog").dialog({
				height: 500,
				width: 700,
				modal: true
			});
		}
	};

	/**
		ui functions
	**/
	this.init_ui = function(sortable_only) {
		// groups
		var sortable_options = {
			connectWith: ".ezfc-group",
			distance: 5,
			forceHelperSize: true,
			forcePlaceholderSize: true,
			handle: ".ezfc-form-element-name",
			placeholder: "ui-state-highlight",
			stop: function(ev, ui) {
				var $item   = $(ui.item[0]);
				var item_id = $item.data("id");
				var index   = $item.index("li");

				// not dropped
				if (index < 0) {
					return false;
				}

				// dropped from list
				if ($item.hasClass("ezfc-elements-droppable")) return;

				// closest but exclude self first
				var group_id = $item.parent().closest(".ezfc-form-element-group").data("id") || 0;
				if (item_id == group_id) return;

				_this.vars.current_form_elements[item_id].data_json.group_id = group_id;

				var $element_wrapper = $item.closest(".ezfc-form-element");
				_this.maybe_add_data_element($element_wrapper, true);

				// set group id value to element
				//$element_wrapper.find("[data-element-name='group_id']").val(group_id);

				_this.form_has_changed($item);
			}
		};

		// sortable elements (main view)
		_this.vars.$form_elements_list.sortable(sortable_options);
		// group list
		$(".ezfc-group").sortable($.extend({}, sortable_options, { connectWith: "#form-elements-list,.ezfc-group" }));

		if (sortable_only) return;

		// put elements into groups
		$("#form-elements-list .ezfc-form-element").each(function() {
			var id       = $(this).data("id");
			var group_id = $(this).data("group_id");

			if (!group_id || group_id < 1) return;

			var $group_target = $("#ezfc-form-element-" + group_id);

			// check if group_id contains itself due to a bug in previous versions
			if ($group_target.data("group_id") == id) return;

			var $group_list = $group_target.find("> .ezfc-group");

			// check if group element exists
			if (!$group_list || $group_list.length < 1) return;

			$(this).appendTo($group_list);
		});

		// draggable elements (add elements)
		$(".ezfc-elements-add .ezfc-elements li").draggable({
			connectToSortable: "#form-elements-list,.ezfc-group",
			helper: "clone",
			start: function(ev, ui) {
				$(ui.helper[0])
					.attr("id", "ezfc-element-drag-placeholder")
					.data("dropped", false);
			},
			stop: function(ev, ui) {
				var $item = $(ui.helper[0]);

				//var dropped = $item.parents("#form-elements-list").length > 0;
				var dropped = $item.data("dropped") && $item.parents("#form-elements-list").length > 0;

				// check if item was actually dropped in the form elements list
				if (!dropped) {
					$item.remove();
					// do not return false since this would stop the user to being unable to drag the element again
				}
				// dropped in form list -> add element to form
				else {
					var item_count, index;

					// closest but exclude self first
					var $parent_group = $item.parent().closest(".ezfc-form-element-group");
					var group_id      = $parent_group.length ? $parent_group.data("id") : 0;

					item_count = _this.vars.$form_elements_list.find("li").length;
					index      = item_count - _this.vars.$form_elements_list.find("li").index($item);

					//do_action
					_this.do_action($item, { position: index, group_id: group_id });

					// hide first
					$("#ezfc-element-drag-placeholder").html("<i class='fa fa-cog fa-spin'></i>");
				}
			}
		});

		_this.vars.$form_elements_list.droppable({
			accept: "#ezfc-element-drag-placeholder",
			tolerance: "fit",
			out: function(ev, ui) {
				ui.helper.data("dropped", false);
			},
			over: function(ev, ui) {
				ui.helper.data("dropped", true);
			}
		});

		// spinner
		$(".ezfc-spinner").spinner();

		// modal
		$(document).on("dialogopen", ".ezfc-dialog", function() {
			$("body").addClass("overflow-y-hidden");
		});
		$(document).on("dialogclose", ".ezfc-dialog", function() {
			$("body").removeClass("overflow-y-hidden");
		});
	};

	this.init_tooltips = function() {
		$(document).tooltip({
			items: "[data-ot]",
			content: function() {
				return $(this).data("ot");
			},
			open: function(ev, ui) {
				if (typeof event.originalEvent === "undefined") return false;

				var $id = $(ui.tooltip).attr("id");
				// close any lingering tooltips
				$("div.ui-tooltip").not("#" + $id).remove();
			},
			show: {
				delay: 500
			}
		});
	};

	this.maybe_add_data_element = function($dom_element, force_add) {
		var $form_element_data;

		if (force_add) {
			$form_element_data = $dom_element.find("> .ezfc-form-element-data");
		}
		else {
			$form_element_data = $dom_element.find("> .ezfc-form-element-data:not(.ezfc-form-element-has-data)");
		}

		// create element input values
		$form_element_data.addClass("ezfc-form-element-has-data");

		var element_id = $dom_element.data("id");

		// data not available or already added
		if ((typeof _this.vars.current_form_elements === "undefined" || !_this.vars.current_form_elements[element_id]) && !force_add) return;

		var current_form_element = _this.vars.current_form_elements[element_id];
		var element_html = "";

		if (!current_form_element) return;
		
		if (typeof ezfc.elements[current_form_element.e_id] !== "undefined" && ezfc.elements[current_form_element.e_id].type == "fileupload") {
			element_html += "<p>" + ezfc_vars.texts.fileupload_conditional + "</p>";
		}

		element_html += _this.element_add_html(current_form_element);

		// delete "noupdate" flag
		$form_element_data.find(".noupdate-flag").remove();

		// output form element data
		if (force_add) {
			$form_element_data.html(element_html);
		}
		else {
			$form_element_data.append(element_html);
		}

		$(".ezfc-form-element-option-container-list").sortable({
			axis: "y",
			stop: function() {
				_this.builder_functions.reorder_options($(this));
			}
		});
		$(".ezfc-form-element-option-container-list").disableSelection();

		$(".ezfc-form-element-matrix-table tbody").sortable({
			axis: "y",
			stop: function() {
				_this.builder_functions.matrix_update($(this).closest("table"));
			}
		});
		$(".ezfc-form-element-matrix-table tbody").disableSelection();

		// re-fill list of elements
		_this.fill_calculate_fields();
	};

	// restrict certain fields upon change
	this.custom_trigger_change = function($element_data) {
		var element_id = $($element_data).closest(".ezfc-form-element").data("id");

		// calculation target / value field
		var calculate_wrapper = $($element_data).find(".ezfc-form-element-calculate-wrapper");

		$(calculate_wrapper).each(function(i, cw) {
			var selected_operator = $(cw).find(".ezfc-form-element-calculate-operator :selected").val();
			var selected_target   = $(cw).find(".ezfc-form-element-calculate-target");
			var selected_value    = $(cw).find(".ezfc-form-element-calculate-value");

			// if ceil/floor/round was selected, disable target element + value
			var disable_element_value_operators = ["floor", "ceil", "round", "abs", "subtotal"];
			if ($.inArray(selected_operator, disable_element_value_operators) > -1) {
				selected_target.attr("disabled", "disabled");
				selected_value.attr("disabled", "disabled");
			}
			else {
				selected_target.removeAttr("disabled");
				selected_value.removeAttr("disabled");

				if (!selected_target.val() || selected_target.val() == 0) {
					selected_value.removeAttr("disabled");
				}
				else {
					selected_value.attr("disabled", "disabled");
				}
			}
		});

		/* todo
		// set calculation text
		var calculation_text = _this.builder_functions.get_calculation_text(element_id);
		$element_data.find(".ezfc-calculation-text").text(calculation_text);

		// parse calculation
		var calculation_text_parsed = _this.builder_functions.calculation_check_valid(calculation_text);
		var calculation_text_icon   = calculation_text_parsed === false ? _this.vars.icons.calc_invalid : _this.vars.icons.calc_valid;
		$element_data.find(".ezfc-calculation-text-icon").html(calculation_text_icon);*/

		// conditional target value
		var conditional_wrapper = $($element_data).find(".ezfc-form-element-conditional-wrapper");

		$(conditional_wrapper).each(function(i, cw) {
			var selected_action      = $(cw).find(".ezfc-form-element-conditional-action :selected").val();
			var target_value_object  = $(cw).find(".ezfc-form-element-conditional-target-value");
			var redirect_wrapper     = $(cw).find(".ezfc-conditional-redirect-wrapper");
			var option_value_wrapper = $(cw).find(".ezfc-conditional-option-value-wrapper");

			// if 'set' was selected, enable target value field
			if ($.inArray(selected_action, _this.vars.enable_target_value_actions) > -1) {
				target_value_object.removeAttr("disabled");
			}
			else {
				target_value_object.attr("disabled", "disabled");
			}

			// show redirect url input
			if (selected_action == "redirect") {
				redirect_wrapper.removeClass("ezfc-hidden");
			}
			else {
				redirect_wrapper.addClass("ezfc-hidden");
			}

			// show option value
			if ($.inArray(selected_action, _this.vars.conditional_show_target_option_id_array) !== -1) {
				option_value_wrapper.removeClass("ezfc-hidden");
			}
			else {
				option_value_wrapper.addClass("ezfc-hidden");
			}
		});

		// add visual editor to html fields
		var $html_editor = $($element_data).find("textarea.ezfc-html");

		// remove tinymce editors from html elements
		if (typeof tinyMCE !== "undefined" && typeof tinyMCE.execCommand === "function") {
			if ($html_editor.hasClass("ezfc-has-tinymce")) return;

			tinyMCE.execCommand("mceAddEditor", false, $html_editor.attr("id"));
			$html_editor.addClass("ezfc-has-tinymce");
		}

		// and/or conditions
		var and_or_text = $element_data.find(".ezfc-form-element-conditional-row-operator").is(":checked") ? ezfc_vars.texts.or : ezfc_vars.texts.and;
		$element_data.find(".ezfc-conditional-chain-and-or").text(and_or_text);

		// option toggle
		$($element_data).find(".ezfc-select-toggle").each(function() {
			_this.builder_functions.option_toggle_select($(this));
		});
	};

	/**
		forms
	**/
	// add form
	this.form_add = function(data) {
		if (!data.form) {
			_this.message("Error adding form.");
			return false;
		}

		var html = "";
		html += "<li class='button ezfc-form' data-id='" + data.form.id + "' data-action='form_get' data-selectgroup='forms'>";
		html += "	<i class='fa fa-fw fa-list-alt'></i> ";
		html += 	data.form.id + " - ";
		html += "	<span class='ezfc-form-name'>" + data.form.name + "</span>";
		html += "</li>";

		$(".ezfc-forms-list").append(html);

		$(".ezfc-form.button-primary").removeClass("button-primary");
		$(".ezfc-form[data-id='" + data.form.id + "']").addClass("button-primary");

		_this.form_show(data);
	};

	this.form_clear = function() {
		_this.vars.current_form_elements = [];
		$(".ezfc-form-element").remove();
	};

	this.form_delete = function(id) {
		$(".ezfc-form-elements-actions, .ezfc-form-elements-container, .ezfc-form-options-wrapper").addClass("ezfc-hidden");
		$(".ezfc-form[data-id='" + id + "']").remove();
	};

	this.form_file_delete = function(id) {
		$(".ezfc-form-file[data-id='" + id + "']").remove();
	};

	// show single form
	this.form_show = function(data) {
		_this.vars.form_changed = false;

		if (data) {
			_this.form_show_elements(data.elements);

			if (typeof data.form !== "undefined") {
				$("#ezfc-form-save, #ezfc-form-delete, #ezfc-form-clear").data("id", data.form.id);
				$("#ezfc-shortcode-id").val("[ezfc id='" + data.form.id + "' /]");
				$("#ezfc-shortcode-name").val("[ezfc name='" + data.form.name + "' /]");
				$("#ezfc-form-name").val(_this.stripslashes(data.form.name).replace(/&apos;/g, "'"));
			}

			// calculate fields
			_this.fill_calculate_fields();

			// populate form option fields
			_this.form_show_options(data.options);

			// set submission entries
			var submissions_count = 0;
			if (typeof data.submissions_count !== "undefined") submissions_count = data.submissions_count;
			$("#ezfc-form-submissions-count").text(submissions_count);

			// grid
			var grid_12 = parseInt(_this.get_form_option_value("grid_12"));
			var grid_css = grid_12 ? "ezfc-grid-12" : "ezfc-grid-6";

			_this.vars.$form_elements_list.removeClass("ezfc-grid-6 ezfc-grid-12").addClass(grid_css);
		}

		$(".ezfc-form-submissions").addClass("ezfc-hidden");
		$(".ezfc-form-elements-actions, .ezfc-form-elements-container, .ezfc-form-options-wrapper").removeClass("ezfc-hidden");

		elements_add_div     = $("#ezfc-elements-add");
		elements_add_div_top = elements_add_div.offset().top;

		_this.init_ui();

		// unload tinymce editors
		if (typeof tinyMCE !== "undefined" && typeof tinyMCE.execCommand === "function") {
			for (var i = tinymce.editors.length - 1 ; i > -1 ; i--) {
				var ed_id = tinymce.editors[i].id;
				tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
			}
		}
	};

	/**
		show form elements
	**/
	this.form_show_elements = function(elements, append) {
		append = append || false;
		var out = [];

		if (!append || typeof _this.vars.current_form_elements === "undefined") {
			_this.vars.current_form_elements = {};
		}

		if (elements && elements.length > 0) {
			$.each(elements, function(i, element) {
				out.push(_this.element_add(element));
			});
		}

		if (append) {
			$(".ezfc-form-elements").append(out.join(""));
		}
		else {
			$(".ezfc-form-elements").html(out.join(""));	
		}

		_this.fill_calculate_fields(true, true);
		_this.init_ui();
	};

	/**
		show form options
	**/
	this.form_show_options = function(options) {
		_this.vars.form_options = options;

		// set options
		$.each(options, function(i, v) {
			var option_row = "#ezfc-table-option-" + v.id;
			var target = "#opt-" + v.id;

			switch (v.type) {
				case "bool_text":
					if (!v.value) {
						v.value = {
							enabled: 0,
							text: ""
						};
					}

					$(option_row + " select option[value='" + v.value.enabled + "']").attr("selected", "selected");
					$(option_row + " input").val(v.value.text);
				break;

				case "border":
					if (!v.value) {
						v.value = {
							color: "",
							width: "",
							style: "",
							radius: ""
						};
					}

					$(option_row + " .ezfc-element-border-color").val(v.value.color).trigger("change");
					$(option_row + " .ezfc-element-border-width").val(v.value.width);
					$(option_row + " .ezfc-element-border-style option[value='" + v.value.style + "']").attr("selected", "selected");
					$(option_row + " .ezfc-element-border-radius").val(v.value.radius);

					if (v.value.transparent) {
						$(option_row + " .ezfc-element-border-transparent").attr("checked", "checked");
					}
				break;

				case "colorpicker":
					if (!v.value) {
						v.value = {
							color: "",
							transparent: false
						};
					}

					$(option_row + " .ezfc-element-colorpicker-input").val(v.value.color).trigger("change");
					
					if (v.value.transparent) {
						$(option_row + " .ezfc-element-colorpicker-transparent").attr("checked", "checked");
					}
				break;

				case "dimensions":
					if (!v.value) {
						v.value = {
							value: "",
							unit: ""
						};
					}

					$(option_row + " input").val(v.value.value);
					$(option_row + " select option[value='" + v.value.unit + "']").attr("selected", "selected");
				break;

				case "editor":
					$("#opt-" + v.id).html(v.value);

					// visual editor
					try {
						$("#editor_" + v.id + "_ifr").contents().find("body").html(_this.nl2br(v.value));
					}
					catch (e) {
						$("#editor_" + v.id + "_ifr").contents().find("body").text(_this.nl2br(v.value));	
					}

					// textarea
					$("#editor_" + v.id).val(v.value);
				break;

				case "file_multiple":
					$(option_row + " .ezfc-files-upload-hidden").val(v.value);
					$(option_row + " .ezfc-files-ids").text(v.value);
				break;

				case "form_element":
					$(target + " option").removeAttr("selected");

					// form element not found
					if (!_this.vars.current_form_elements[v.value]) return;
					
					$(target + " option[value='" + v.value + "']").attr("selected", "selected");
				break;

				case "image":
					$(option_row + " .ezfc-image-upload-hidden").val(v.value);
					$(option_row + " .ezfc-image-filename").text(v.value);

					if (_this.builder_functions.is_image(v.value)) {
						$(option_row + " img").attr("src", v.value);
					}
				break;

				case "dropdown":
				case "lang":
				case "yesno":
					$(target + " option").removeAttr("selected");
					$(target + " option[value='" + v.value + "']").attr("selected", "selected");
				break;   			

				default:
					$(target).val(v.value);
				break;
			}
		});
	};

	// show form submissions
	this.form_show_submissions = function(submissions, f_id) {
		// no submissions
		if (!submissions || !submissions.submissions) {
			submissions = { submissions: [] };
		}
		
		// update counter
		$(".ezfc-forms-list .button-primary .ezfc-submission-counter").text(submissions.submissions.length);

		_this.vars.form_changed = false;
		var last_id = 0; // all forms
		var out = "<ul>";

		$.each(submissions.submissions, function(i, submission) {
			var date    = _this.parse_date(submission.date);
			var addIcon = "";

			if (f_id == -1 && submission.f_id != last_id) {
				last_id = submission.f_id;
				out += "<li><strong>" + submission.f_id + "</strong></li>";
			}

			// paypal
			if (submission.payment_id == 1) {
				addIcon += " <i class='fa fa-fw fa-paypal' data-ot='PayPal'></i>";

				if (submission.transaction_id.length > 0) addIcon += " <i class='fa fa-fw fa-check' data-ot='" + ezfc_vars.texts.pp_payment_verified + "'></i>";
				else addIcon += " <i class='fa fa-fw fa-times' data-ot='" + ezfc_vars.texts.pp_payment_denied + "'></i>";
			}
			// stripe
			if (submission.payment_id == 2) {
				addIcon += " <i class='fa fa-fw fa-cc-stripe' data-ot='Stripe'></i>";

				if (submission.transaction_id.length > 0) addIcon += " <i class='fa fa-fw fa-check' data-ot='Payment verified.'></i>";
			}

			out += "<li class='ezfc-form-submission' data-id='" + submission.id + "'>";
			out += "	<div class='ezfc-form-submission-name'>";
			out += "		<i class='fa fa-fw fa-envelope'></i>" + addIcon + " ID: " + submission.id + " - " + date.toUTCString();
			out += "		<button class='ezfc-form-submission-delete button' data-action='form_submission_delete' data-id='" + submission.id + "'><i class='fa fa-times'></i></button>";
			out += "	</div>";

			// additional data (toggle)
			out += "	<div class='ezfc-form-submission-data ezfc-hidden'>";

			// resend admin
			out += "<button class='button ezfc-submission-resend-admin' data-action='submission_send_admin' data-id='" + submission.id + "'>" + ezfc_vars.texts.submission_send_admin + "</button> &nbsp;";

			if (submission.user_mail.length > 0) {
				// resend customer
				out += "<button class='button ezfc-submission-resend-customer' data-action='submission_send_customer' data-id='" + submission.id + "'>" + ezfc_vars.texts.submission_send_customer + "</button>";
			}

			// paypal info
			if (submission.payment_id != 0) {
				var payment_text = {
					"1": ezfc_vars.texts.paid_with + "PayPal",
					"2": ezfc_vars.texts.paid_with + "Stripe"
				};

				out += "<div>";
				out += "	<p><strong>" + payment_text[submission.payment_id] + "</strong></p>";
				out += "	<p>Transaction-ID: " + submission.transaction_id;
				out += "</div>";
			}

			// content
			out += _this.escape_content(submission.content);

			// files
			if (submissions.files[submission.ref_id]) {
				out += "	<div class='ezfc-form-files'>";
				out += "		<p>Files</p>";

				$.each(submissions.files[submission.ref_id], function(fi, file) {
					var filename = file.url.split("/").slice(-1);

					out += "	<ul>";
					out += "		<li class='ezfc-form-file' data-id='" + file.id + "'>";
					out += "			<a href='" + ezfc_vars.file_download_url + "?file_id=" + file.id + "' target='_blank'>" + filename + "</a>";
					out += "			<button class='ezfc-form-file-delete button' data-action='form_file_delete' data-id='" + file.id + "'><i class='fa fa-times'></i></button>";
					out += "	</li>";
					out += "	</ul>";
				});

				out += "	</div>";
			}

			out += "</li>";
		});

		out += "</ul>";

		$(".ezfc-form-submissions").removeClass("ezfc-hidden").html(out);
		$(".ezfc-form-elements-container, .ezfc-form-options-wrapper").addClass("ezfc-hidden");

		// meh
		$(".ezfc-form-submission-data h2").remove();
	};

	// remove element
	this.element_remove = function($element, quick) {
		// quick remove without fx / reinit
		quick = quick || false;
		
		var id   = $element.data("id");
		var type = _this.builder_functions.get_element_type(id);

		// delete from current form elements
		if (typeof _this.vars.current_form_elements[id] !== "undefined") delete _this.vars.current_form_elements[id];

		// check if group (and not in recursive loop)
		if (type == "group" && !quick) {
			var $group_element  = _this.builder_functions.get_form_element_dom(id);
			var $group_elements = $group_element.find("li.ezfc-form-element");

			$group_elements.each(function() {
				_this.element_remove($(this), true);
			});
		}

		if (!quick) {
			$element.closest(".ezfc-form-element").fadeOut(400, function() {
				$(this).remove();
			});

			_this.builder_functions.check_individual_names();
		}
		else {
			$element.closest(".ezfc-form-element").remove();
		}
	};

	// add element
	this.element_add = function(element) {
		// add to current form elements
		_this.vars.current_form_elements[element.id] = element;

		// form element data
		var data_el = $.parseJSON(element.data);

		// use large element data editor
		var data_editor_class = ezfc_vars.editor.use_large_data_editor == 1 ? "ezfc-form-element-data-fixed" : "";

		if (!data_el) {
			return _this.builder_functions.get_element_error(element, data_editor_class);
		}

		_this.vars.current_form_elements[element.id].data_json = data_el;

		var columns             = data_el.columns ? data_el.columns : 6;
		var group_id            = data_el.group_id ? data_el.group_id : 0;
		var req_char            = (typeof data_el.required !== "undefined" && data_el.required == 1) ? "*" : "";
		var html                = [];
		var extension_id        = "";

		// data wrapper for inbuilt / extension elements
		var data_element_wrapper = _this.get_element_data_wrapper(element);
		if (!data_element_wrapper) {
			return _this.builder_functions.get_element_error(element, data_editor_class);
		}

		// name header
		var element_name_header = data_el.label ? data_el.label : data_el.name;
		// name header exceptions
		if (data_element_wrapper.type == "heading") element_name_header = data_el.title;

		// put element id into every element (necessary)
		html.push("<input type='hidden' class='ezfc-form-element-e_id' value='" + element.e_id + "' name='elements[" + element.id + "][e_id]' />");
		// input flag that this element was not changed (will be deleted when the element is opened)
		html.push("<input type='hidden' class='noupdate-flag' value='1' name='elements[" + element.id + "][__noupdate__]' />");

		var out = "";

		// element label
		var element_label = "<span class='element-label'>" + element_name_header + "</span>";

		out += "<li class='ezfc-form-element ezfc-cat-" + data_element_wrapper.category + " ezfc-form-element-" + data_element_wrapper.type + " ezfc-col-" + columns + "' data-columns='" + columns + "' data-id='" + element.id + "' data-group_id='" + group_id + "' id='ezfc-form-element-" + element.id + "'>";
		out += "	<div class='ezfc-form-element-name'>";
		
		// group buttons
		if (data_element_wrapper.type == "group") {
			out += "	<button class='ezfc-form-element-action ezfc-form-element-group-toggle button'><i class='fa fa-toggle-up'></i></button>";
		}

		// column buttons
		out += "		<button class='ezfc-form-element-action ezfc-form-element-column-left button'><i class='fa fa-toggle-left'></i></button>";
		out += "		<button class='ezfc-form-element-action ezfc-form-element-column-right button'><i class='fa fa-toggle-right'></i></button>";

		// element info
		var element_info = "ID: " + element.id;
		var element_info_add = [];

		// calculation icon
		if (_this.builder_functions.element_has_calculation(element.data_json)) {
			element_info_add.push(_this.get_tip("Calculation", "fa-calculator ezfc-form-element-info-icon"));
		}
		// conditional icon
		if (_this.builder_functions.element_has_condition(element.data_json)) {
			element_info_add.push(_this.get_tip("Condition", "fa-chain ezfc-form-element-info-icon"));
		}
		// discount icon
		if (_this.builder_functions.element_has_discount(element.data_json)) {
			element_info_add.push(_this.get_tip("Discount", "fa-percent ezfc-form-element-info-icon"));
		}

		// element type
		element_info_add.push("<span class='ezfc-form-element-type'>" + data_element_wrapper.name + "</span>");

		// add separator
		if (element_info_add.length > 0) {
			element_info += " | " + element_info_add.join("&nbsp;");
		}

		// more element info
		out += "<span class='ezfc-form-element-info'>" + element_info + " | </span>";
		// notification
		out += "<span class='ezfc-form-element-notification'></span>";
		// icon
		out += "<span class='fa fa-fw " + data_element_wrapper.icon + "'></span>";
		// required char
		out += "<span class='ezfc-form-element-required-char'>" + req_char + "</span> ";
		out += element_label;

		// duplicate element button
		var duplicate_action = data_element_wrapper.type == "group" ? "form_element_duplicate_group" : "form_element_duplicate";
		out += "<button class='ezfc-form-element-duplicate button' data-action='" + duplicate_action + "' data-id='" + element.id + "'><i class='fa fa-files-o' data-ot='Duplicate element'></i></button>";

		// delete element button
		out += "<button class='ezfc-form-element-delete button' data-action='form_element_delete' data-id='" + element.id + "'><i class='fa fa-times'></i></button>";
		out += "</div>";
		out += "<div class='container-fluid ezfc-form-element-data " + data_editor_class + " ezfc-form-element-" + data_element_wrapper.name.replace(" ", "-").toLowerCase() + " ezfc-hidden'>" + html.join("") + "</div>";

		// close element data button left side
		if (ezfc_vars.editor.use_large_data_editor == 1) {
			out += "<button class='ezfc-form-element-close-data ezfc-form-element-close-data-fixed-left ezfc-hidden' data-func='element_data_close'><i class='fa fa-chevron-right'></i></button>";
		}

		// group suffix
		if (data_element_wrapper.type == "group") {
			out += "<ul class='ezfc-group'></ul>";
			out += "<div class='ezfc-add-element-placeholder' data-func='add_form_element_dialog' data-args='1'><i class='fa fa-plus'></i></div>";
		}

		out += "</li>";

		return out;
	};

	this.element_add_html = function(element) {
		// form element data
		var data_el;

		if (typeof element.data_json !== "undefined") {
			data_el = element.data_json;
		}
		else {
			data_el = $.parseJSON(element.data);
		}

		if (!data_el) return;

		// output array
		var html = [];
		var html_sections = {};

		for (var key in _this.vars.element_option_sections) {
			html_sections[key] = [];
		}

		// get element data wrapper
		var data_element_wrapper = _this.get_element_data_wrapper(element);

		// advanced actions wrapper
		var advanced_actions = "<div class='ezfc-form-element-advanced-actions'>";

		// left side
		advanced_actions += "<div class='pull-left'>";

		// previous element
		advanced_actions += "	<button class='ezfc-form-element-previous button' data-func='element_open_prev' data-args='" + element.id + "'><i class='fa fa-chevron-left'></i> " + ezfc_vars.texts.previous + "</button>";
		// next element
		advanced_actions += "	<button class='ezfc-form-element-next button' data-func='element_open_next' data-args='" + element.id + "'>" + ezfc_vars.texts.next + " <i class='fa fa-chevron-right'></i></button>";

		// end left side, start right side
		advanced_actions += "</div><div class='pull-right'>";

		// change element (not for groups)
		if (data_element_wrapper.type != "group") {
			// change element
			advanced_actions += "	<button class='ezfc-form-element-change button' data-func='change_element_dialog' data-args='" + element.id + "'><i class='fa fa-exchange'></i> " + ezfc_vars.texts.change_element + "</button>";
			advanced_actions += "	<span class='ezfc-separator'></span>";
		}

		// close button
		advanced_actions += "	<button class='ezfc-form-element-close-data button' data-func='element_data_close'>" + _this.get_tip(ezfc_vars.texts.close_element_data, "fa-check") + "</button>";

		// end right side
		advanced_actions += "</div>";
		// end wrapper
		advanced_actions += "</div>";

		html.push(advanced_actions);
		
		// add extension field
		if (data_element_wrapper.hasOwnProperty("ext")) {
			html.push("<input type='hidden' value='" + data_element_wrapper.type + "' name='elements[" + element.id + "][extension]' />");
		}

		$.each(data_el, function(name, value) {
			// skip id
			if (name == "e_id" || name == "preselect" || name == "extension") return;

			var input_id   = "elements-" + name + "-" + element.id;
			var input_raw  = "elements[" + element.id + "]";
			var input_name = "elements[" + element.id + "][" + name + "]";
			var input      = "";

			var section = _this.builder_functions.get_element_option_section(name);

			// replace &apos;
			value = _this.sanitize_value(value);

			// element tip description
			var el_description = _this.get_element_option_description(name);
			
			// element option
			var element_option_output = _this.builder_functions.get_element_option_output(data_el, name, value, element, input_id, input_raw, input_name, element.id);

			// option shouldn't be displayed
			if (element_option_output.skip_early) {
				if (element_option_output.input) {
					html_sections[section].push(element_option_output.input);
				}

				return;
			}

			// columns override
			if (element_option_output.columns !== null) {
				columns = element_option_output.columns;
			}

			// still needed?
			// element_name_header: element_name_header,

			// element option input
			input = element_option_output.input;

			html_sections[section].push("<div class='row ezfc-row-" + name + "'>");
			html_sections[section].push("	<div class='col-xs-4 ezfc-element-option-label'>");

			if (el_description.length > 0) {
				var el_description_sanitized = el_description.replace(/"/g, "'");

				html_sections[section].push("		<a href='https://ez-form-calculator.ezplugins.de/element-option/" + name + "' target='_blank'><span class='fa fa-question-circle' data-ot=\"" + el_description_sanitized + "\"></span></a> &nbsp;");
			}

			html_sections[section].push("		<label for='" + input_id + "'>" + name.capitalize() + "</label>");
			html_sections[section].push("	</div>");
			html_sections[section].push("	<div class='col-xs-8'>");
			html_sections[section].push(input);
			html_sections[section].push("	</div>");
			html_sections[section].push("</div>");
		});

		var section_wrapper_html = [];
		var section_data_html    = [];
		for (var k in html_sections) {
			if (html_sections[k].length < 1) continue;

			var active_css = "";
			var badge      = " <span class='ezfc-badge'></span>";
			var icon       = "";

			if (k == "basic") active_css = "active";
			if (typeof _this.vars.icons.tabs[k] !== "undefined") icon = _this.vars.icons.tabs[k];

			// build sections
			section_wrapper_html.push("<div class='ezfc-element-option-section-heading " + active_css + "' data-section='" + k + "'>" + icon + k + badge + "</div>");
			section_data_html.push("<div class='ezfc-element-option-section-data ezfc-element-option-section-" + k + " " + active_css + "'>" + html_sections[k].join("") + "</div>");
		}

		html.push("<div class='ezfc-clear'></div>");
		// section wrapper
		html.push("<div class='ezfc-element-option-section-wrapper'>");
		html.push(section_wrapper_html.join(""));
		html.push("</div>");
		// section data
		html.push("<div class='ezfc-element-option-section-data-wrapper'>");
		html.push(section_data_html.join(""));
		html.push("</div>");

		html.push("<div class='ezfc-clear'></div>");

		return html.join("");
	};

	this.get_element_data_wrapper = function(element) {
		var data_el = $.parseJSON(element.data);

		if (element.e_id == 0) {
			extension_id = data_el.extension;

			// check if extension exists
			var extension_element = $(".ezfc-element[data-id='" + extension_id + "']");
			if (extension_element.length < 1) {
				// error message
				return;
			}

			// get element data
			var extension_element_data = extension_element.data("extension_data");

			data_element_wrapper = {
				ext:  true,
				icon: extension_element_data.icon,
				id:   extension_element_data.type, // wrapper
				name: extension_element_data.name,
				type: extension_element_data.type
			};
		}
		else {
			data_element_wrapper = ezfc.elements[element.e_id];
		}

		return data_element_wrapper;
	};

	this.set_element_data = function(id, key, value) {
		if (typeof _this.vars.current_form_elements[id] === "undefined") {
			_this.message_error("Trying to set propery '" + key + "' on form element error: element #" + id + " was not found.");
			return;
		}

		_this.vars.current_form_elements[id].data_json[key] = value;
	};

	this.fill_calculate_fields = function(show_all, force_reload) {
		var $active_element = _this.builder_functions.get_active_element();
		var elements_calc   = _this.builder_functions.get_element_names([], _this.vars.calculation_elements);
		var elements_all    = _this.builder_functions.get_element_names();

		// get dropdown list output for calculation elements
		var dropdown_output_calc = "<option value='0'> </option>";
		// get dropdown list output for conditional elements
		var dropdown_output_cond = dropdown_output_calc;

		// create calculation elements list
		$.each(elements_calc, function(i, el) {
			dropdown_output_calc += "<option value='" + el.id + "'>" + el.name + " (" + el.type + ")</option>";
		});

		// populate form elements without additional options
		$(".ezfc-settings-form-elements").html(dropdown_output_calc);

		// additional calc targets
		dropdown_output_calc += "<option value='__open__'>(</option>";
		dropdown_output_calc += "<option value='__close__'>)</option>";

		// create conditional elements list
		$.each(elements_all, function(i, el) {
			dropdown_output_cond += "<option value='" + el.id + "'>" + el.name + " (" + el.type + ")</option>";
		});
		// additional conditional targets
		dropdown_output_cond += "<option value='submit_button'>" + ezfc_vars.submit_button + "</option>";
		dropdown_output_cond += "<option value='price'>" + ezfc_vars.price + "</option>";

		// fill calculate elements
		$active_element.find(".fill-elements-calculate").html(dropdown_output_calc);
		// fill all elements
		$active_element.find(".fill-elements:not(.fill-elements-calculate)").html(dropdown_output_cond);

		$active_element.find(".fill-elements").each(function(i, dropdown_element) {
			var selected = $(dropdown_element).data("selected");

			$(dropdown_element).find("option[value='" + selected + "']").attr("selected", "selected");
		});

		_this.builder_functions.check_individual_names();
	};

	/**
		ajax
	**/
	this.do_action = function(el, settings, action, id, add_data, action_args_str) {
		$(".ezfc-loading").fadeIn("fast");
		var f_id = $(".ezfc-forms-list .button-primary").data("id");

		// take action/id from element
		if (el) {
			id = $(el).data("id");

			if ($(el).data("action") != "") {
				action = $(el).data("action");
			}
		}
		
		var action_args = action_args_str ? action_args_str.split(",") : [];
		var data = "action=" + action;
		var el_disabled_list;

		switch (action) {
			case "form_add":
			case "form_add_template_elements":
				id = $("#ezfc-form-template-id option:selected").val();
			break;

			case "form_duplicate":
				_this.form_clear(id);
			break;

			case "form_element_add":
				if (!f_id) return false;

				var $drag_placeholder = $("#ezfc-element-drag-placeholder");
				// check if dropped
				if ($drag_placeholder.length && !$drag_placeholder.data("dropped")) {
					$(".ezfc-loading").hide();
					return false;
				}

				var e_id = id;

				// check if element is an extension
				if ($(el).data("extension") != 0) {
					data += "&extension=1";
				}

				// custom position from dropped element
				if (settings) {
					for (var key in settings) {
						data += "&element_settings[" + key + "]=" + settings[key];
					}
				}
				
				data += "&e_id=" + e_id + "&f_id=" + f_id;

				_this.form_has_changed();
			break;

			case "form_element_change":
				data += "&fe_id=" + _this.vars.selected_element;
			break;

			case "form_clear":
			case "form_delete":
			case "form_delete_submissions":
			case "form_submission_delete":
			case "form_template_delete":
			case "form_file_delete":
				if (action == "form_template_delete") {
					id = $("#ezfc-form-template-id option:selected").val();

					if (id == 0) {
						$(".ezfc-loading").hide();
						return false;
					}
				} 

				if (!confirm(ezfc_vars.delete_element)) {
					$(".ezfc-loading").hide();
					return false;
				}
			break;

			case "form_element_delete":
				var $el_parent = $(el).closest(".ezfc-form-element");

				// check if group
				if ($el_parent.hasClass("ezfc-form-element-group")) {
					// put together child element ids
					var child_element_ids = [];
					
					$el_parent.find(".ezfc-form-element").each(function() {
						child_element_ids.push($(this).data("id"));
					});

					data += "&child_element_ids=" + child_element_ids.join(",");
				}
				
				if (!confirm(ezfc_vars.delete_element)) {
					$(".ezfc-loading").hide();
					return false;
				}

				// add disabled class
				$el_parent.addClass("ezfc-form-element-disabled");
			break;

			case "form_get":
				_this.form_clear();
			break;

			case "form_show":
				$(".ezfc-loading").hide();
				_this.form_show(null);
				return false;
			break;

			// import dialog
			case "form_show_import":
				$(".ezfc-loading").hide();
				$("#ezfc-import-dialog").dialog("open");
				$("#form-import-data").val("");
				return false;
			break;
			// import add elements to current form dialog
			case "form_show_import_add_elements":
				$(".ezfc-loading").hide();
				$("#ezfc-import-add-elements-dialog").dialog("open");
				$("#form-import-data").val("");
				return false;
			break;

			// import form data
			case "form_import_data":
				data += "&import_data=" + encodeURIComponent($("#form-import-data").val().replace(/'/g, "&apos;"));
			break;
			// import form add elements data
			case "form_import_add_elements_data":
				data += "&import_data=" + encodeURIComponent($("#form-import-add-elements-data").val().replace(/'/g, "&apos;"));
			break;

			case "form_save":
			case "form_save_post":
			case "form_preview":
				// add html data for all elements before saving
				if (action == "form_preview") {
					$(".ezfc-form-element").each(function() {
						_this.maybe_add_data_element($(this));
					});
				}

				if (typeof tinyMCE !== "undefined") {
					tinyMCE.triggerSave();
				}

				// temporarily remove disabled fields
				el_disabled_list = $("#form-elements [disabled='disabled']");
				el_disabled_list.removeAttr("disabled");

				var data_elements = encodeURIComponent(JSON.stringify(_this.vars.$form_elements.serializeArray()));
				var data_options = $("#form-options").serialize();

				var form_name = encodeURIComponent($("#ezfc-form-name").val());
				data += "&elements=" + data_elements + "&ezfc-form-name=" + form_name + "&" + data_options;

				if (action == "form_save_post" || action == "form_preview") id = f_id;
			break;

			case "form_show_options":
				$(".ezfc-loading").hide();
				$(".ezfc-options-dialog").dialog("open");
				return false;
			break;

			case "form_update_options":
				if (typeof tinyMCE !== "undefined") {
					tinyMCE.triggerSave();
				}
				
				var save_data = $("#form-options").serialize();
				data += "&" + save_data;
			break;

			// duplicate element
			case "form_element_duplicate":
				// check if element was changed before duplicating
				if ($(el).parents(".ezfc-form-element-name").hasClass("ezfc-changed")) {
					var element_data = $(el).closest(".ezfc-form-element").find(".ezfc-form-element-data").find("input, select, textarea").serialize();
					data += "&" + element_data;
				}

				_this.form_has_changed();
			break;
			// duplicate group
			case "form_element_duplicate_group":
				var $group = $(el).closest(".ezfc-form-element");
				var duplicate_group_data = _this.builder_functions.duplicate_group_build_data($group);

				data += "&" + duplicate_group_data;
			break;

			case "toggle_element_info":
				$("body").toggleClass("ezfc-form-element-info-active");
				$(".ezfc-loading").hide();

				return false;
			break;
		}

		// append id
		if (id) {
			data += "&id=" + id;
		}
		// append form id
		if (f_id) {
			data += "&f_id=" + f_id;
		}
		// append additional data
		if (add_data) {
			data += "&" + add_data;
		}

		$.ajax({
			type: "post",
			url: ajaxurl,
			data: {
				action: "ezfc_backend",
				data: data,
				nonce: ezfc_nonce
			},
			error: function(response) {
				$(".ezfc-loading").fadeOut("fast");

				if (ezfc_debug_mode != 0 && console) console.log(response);

				_this.message_error(response.status + " " + response.statusText + ": " + response.responseText, true);
			},
			success: function(response) {
				$(".ezfc-loading").fadeOut("fast");

				if (ezfc_debug_mode != 0 && console) console.log(response);

				var response_json;
				try {
					response_json = $.parseJSON(response);
				} catch (e) {
					_this.message_error("Unable to perform action " + action + ": " + response, false, action);
					return false;
				}

				if (!response_json) {
					_this.message_error("Something went wrong. :(", false, action);
						
					return false;
				}

				if (response_json.error) {
					if (action == "form_update_options") {
						_this.form_option_error(response_json.error_options);
					}
					else {
						_this.message_error(response_json.error, false, action);
					}

					return false;
				}

				// clear error messages
				$(".ezfc-error, .ezfc-form-option-error-message").text("");

				if (response_json.message) {
					_this.message(response_json.message);
				} 

				if (response_json.download_url) {
					$("body").append("<iframe src='" + response_json.download_url + "' style='display: none;' ></iframe>");
				}

				/**
					call functions after ajax request
				**/
				switch (action) {
					case "element_get":
						_this.element_show(response_json.element[0]);
					break;

					case "form_add":
					case "form_duplicate":
						_this.form_add(response_json);
					break;

					case "form_add_template_elements":
						_this.form_show_elements(response_json.elements, true);
					break;

					case "form_delete_submissions":
						_this.form_show_submissions();
					break;

					case "form_get":
						_this.form_show(response_json);
					break;

					case "form_get_submissions":
						_this.form_show_submissions(response_json, f_id);
					break;

					case "form_clear":
						_this.form_clear();
					break;

					case "form_delete":
						_this.vars.form_changed = false;
						_this.form_delete(id);
					break;

					case "form_file_delete":
						_this.form_file_delete(id);
					break;

					case "form_preview":
						if (!response_json.preview_url) {
							console.log("Error", response_json);
							return;
						}

						var preview_url = decodeURIComponent(response_json.preview_url);
						window.open(preview_url, "ezfc_" + id);
					break;

					case "form_save_post":
					case "form_save":
						_this.vars.form_changed = false;
						$(".ezfc-changed").removeClass("ezfc-changed");
						el_disabled_list.attr("disabled", "disabled");

						// update name in forms list
						var form_name = $("#ezfc-form-name").val();
						$(".ezfc-form[data-id='" + id + "'] .ezfc-form-name").text(form_name);
						// update name shortcodes
						$("#ezfc-shortcode-name").val("[ezfc name='" + form_name + "' /]");

						if (action == "form_save_post") window.open(decodeURIComponent(response_json.success), "ezfc_" + id);
					break;

					case "form_save_template":
						var template_name = $("#ezfc-form-name").val();
						$("#ezfc-templates-item-installed").after("<option value='" + response_json + "'>" + template_name + "</option>");
					break;

					case "form_template_delete":
						$("#ezfc-form-template-id option[value='" + id + "']").remove();
					break;

					case "form_element_change":
						var element_new = _this.element_add(response_json);
						$("#ezfc-form-element-" + _this.vars.selected_element).replaceWith(element_new);
						$("#ezfc-change-element-dialog").dialog("close");
						_this.builder_functions.element_data_close();

						_this.fill_calculate_fields();
						_this.init_ui(true);
					break;

					case "form_element_delete":
						_this.element_remove(el);

						_this.fill_calculate_fields(false, true);
						//_this.init_ui();
						_this.form_has_changed();
					break;

					case "form_element_add":
					case "form_element_duplicate":
						var element_new = _this.element_add(response_json);

						// dropped element
						if (settings) {
							$("#ezfc-element-drag-placeholder").after(element_new);
						}
						else {
							if (action == "form_element_duplicate") {
								$(el).closest(".ezfc-form-element").after(element_new);
								_this.form_has_changed($("#ezfc-form-element-" + response_json.id));
							}
							else {
								$(".ezfc-form-elements").append(element_new);
							}
						}

						// remove placeholder
						$("#ezfc-element-drag-placeholder").remove();

						_this.fill_calculate_fields(false, true);
						_this.init_ui(true);
					break;

					case "form_element_duplicate_group":
						if (!response_json.elements) return;

						var $group_el = $(el).closest(".ezfc-form-element");

						$.each(response_json.elements, function(i, element) {
							var element_new = _this.element_add(element);

							$group_el.after(element_new);
						});

						_this.fill_calculate_fields();
						_this.init_ui();
						_this.form_has_changed();
					break;

					case "form_submission_delete":
						$(el).parents(".ezfc-form-submission").remove();

						// update counter
						var $form_counter = $(".ezfc-forms-list .button-primary .ezfc-submission-counter");
						var counter = parseInt($form_counter.text());
						$form_counter.text(counter - 1);
					break;

					case "form_update_options":
						$(".ezfc-forms-list li[data-id='" + id + "'] .ezfc-form-name").text($("#opt-name").val());
						$(".ezfc-dialog").dialog("close");
					break;

					case "form_import_data":
						_this.form_add(response_json);
						_this.form_show(response_json);
						$("#ezfc-import-dialog").dialog("close");
					break;

					case "form_import_add_elements_data":
					case "form_import_add_elements_upload":
					case "quick_add":
						_this.form_show(response_json);
						$(".ezfc-dialog").dialog("close");
					break;

					case "form_show_export":
						$("#form-export-data").val(JSON.stringify(response_json));
						$(".ezfc-export-dialog").dialog("open");
					break;
				}

				// reload form
				if ($.inArray("reload_form", action_args) !== -1) {
					$(".ezfc-form[data-id='" + f_id + "']").click();
					$(".ui-dialog-content").dialog("close");
				}

				// show or hide empty text
				if (_this.vars.$form_elements_list.is(":empty")) {
					$("#empty-form-text").show();
				}
				else {
					$("#empty-form-text").hide();
				}
			}
		});

		return false;
	};

	this.message = function(message, html, error) {
		var selector = error ? ".ezfc-error" : ".ezfc-message";

		if (!html) {
			$(selector).text(message).slideDown();
		}
		else {
			$(selector).append(message).slideDown();	
		}

		if (!error) {
			setTimeout(function() {
				$(selector).slideUp();
			}, 7500);
		}
	};

	this.message_error = function(message, html, action) {
		_this.message(message, html, true);

		// remove disabled class if form element couldn't be deleted
		if (action == "form_element_delete") $("#ezfc-form-elements-container .ezfc-form-element-disabled").removeClass("ezfc-form-element-disabled");
	};

	this.form_option_error = function(errors_json) {
		var errors = [];

		try {
			errors = $.parseJSON(errors_json);
		}
		catch (e) {
			console.log("Something went wrong", e);
			return false;
		}

		// clear all messages before
		$(".ezfc-form-option-error-message").remove();

		if (errors.length > 0) {
			// add messages
			$.each(errors, function(i, error) {
				$("#ezfc-option-" + error.id).append("<p class='ezfc-form-option-error-message ezfc-color-error'>" + error.error + "</p>");
			});

			var $container   = $(".ezfc-options-dialog");
			var $first_error = $("#ezfc-option-" + errors[0].id);
			var $section_tab = $first_error.closest(".ui-tabs-panel");

			// check if section is visible
			if ($section_tab.attr("aria-hidden") == "true") {
				var parent_section_id = $section_tab.attr("id");
				var $section_tab_a    = $("#tabs a[href='#" + parent_section_id + "']");
				$section_tab_a.click();
			}

			// scroll to first error
			$(".ezfc-options-dialog").animate({ scrollTop: $first_error.offset().top - $container.offset().top + $container.scrollTop() - 20 }, "slow");
		}
	};

	this.form_has_changed = function($trigger_el) {
		_this.vars.form_changed = true;

		// add changed class to element
		if ($trigger_el) {
			$trigger_el.closest(".ezfc-form-element").find("> .ezfc-form-element-name").addClass("ezfc-changed");
		}

		// check that each element has a unique name
		_this.debounce(_this.builder_functions.check_individual_names(), 250);
	};

	this.stripslashes = function(str) {
		return (str + '')
		.replace(/\\(.?)/g, function(s, n1) {
		  switch (n1) {
		  case '\\':
			return '\\';
		  case '0':
			return '\u0000';
		  case '':
			return '';
		  default:
			return n1;
		  }
		});
	};

	this.get_form_option_value = function(option_name) {
		for (var i in _this.vars.form_options) {
			if (_this.vars.form_options[i].name == option_name) {
				var ret_value = typeof _this.vars.form_options[i].name === "undefined" ? false : _this.vars.form_options[i].value;
				return ret_value;
			}
		}

		return false;
	};

	// change form element columns
	this.change_columns = function(el, inc) {
		var $element_wrapper = $(el).closest(".ezfc-form-element");
		var element_id       = $element_wrapper.data("id");
		var columns = $element_wrapper.data("columns");
		
		var grid_12 = parseInt(_this.get_form_option_value("grid_12"));
		var max_col = grid_12 ? 12 : 6;
		var columns_new = Math.min(max_col, Math.max(1, columns + inc));

		this.set_element_data(element_id, "columns", columns_new);

		$element_wrapper
			.removeClass("ezfc-col-" + columns)
			.addClass("ezfc-col-" + columns_new)
			.data("columns", columns_new)
			.find("> .ezfc-form-element-data [data-element-name='columns']")
				.val(columns_new);

		_this.form_has_changed($(el));
	};

	this.nl2br = function(str, is_xhtml) {
		var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
		return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
	};

	this.get_tip = function(text, icon) {
		icon = icon || "fa-question-circle";

		return "<span class='fa " + icon + "' data-ot='" + _this.escape(text) + "'></span>";
	};

	this.clear_option_row = function(row) {
		$(row).find("input").val("");
		$(row).find("select").val("0");
	};

	this.get_element_option_description = function(option) {
		if (ezfc_vars.element_option_description[option]) return ezfc_vars.element_option_description[option];
		return "";
	};

	this.parse_date = function(d) {
		// yyyy-mm-dd hh:mm:ss
		var tmp = d.split(" ");

		var tmp_date = tmp[0].split("-");
		var tmp_time = tmp[1].split(":");

		return new Date(tmp_date[0], parseInt(tmp_date[1]) - 1, tmp_date[2], tmp_time[0], tmp_time[1], tmp_time[2]);
	};

	this.sanitize_value = function(value) {
		if (typeof value === "string") {
			value = value.replace("'", "&apos;");
		}
		else if (typeof value === "object") {
			$.each(value, function(i, v) {
				value[i] = _this.sanitize_value(v);
			});
		}

		return value;
	};

	this.escape = function(str) {
		str = str.replace("'", "&#x27;");
		str = str.replace('"', "&quot;");

		return str;
	};

	// internal builder functions
	this.get_html_input = function(type, name, args) {
		args = args || {};

		var input = "";
		var input_class = args.class || "";
		var input_data  = args.data || "";

		switch (type) {
			case "hidden":
				input += "<input class='" + input_class + "' name='" + name + "' value='" + args.value + "' " + input_data + " type='hidden' />";
			break;

			case "input":
				input += "<input class='" + input_class + "' name='" + name + "' value='" + args.value + "' " + input_data + " type='text' />";
			break;

			case "input_small":
				input += "<input class='ezfc-input-auto-width " + input_class + "' name='" + name + "' value='" + args.value + "' " + input_data + " type='text' />";
			break;

			case "select":
				var has_toggle = false;

				$.each(args.options, function(n, option) {
					var option_args = [];

					// check for selected value
					if (args.selected && args.selected == option.value) {
						option_args.push("selected='selected'");
					}

					// toggle hide/show if defined
					if (typeof option.toggle !== "undefined") {
						option_args.push("data-optiontoggle='" + option.toggle + "'");
						has_toggle = true; // toggle flag
					}

					input += "<option value='" + option.value + "' " + option_args.join(" ") + ">" + option.text + "</option>";
				});

				input += "</select>";

				// check for toggle flag
				if (has_toggle) input_class += " ezfc-select-toggle";

				// check for fill_elements flag
				if (input_class.includes("fill-elements")) {
					input_data += " data-selected='" + args.selected + "'";
				}

				var select_wrapper = "<select class='" + input_class + "' name='" + name + "'" + input_data + ">";

				input = select_wrapper + input;
			break;

			case "yesno":
				input += _this.get_html_input("select", name, {
					class: input_class,
					data: input_data,
					options: [
						{ value: 0, text: ezfc_vars.yes_no.no },
						{ value: 1, text: ezfc_vars.yes_no.yes }
					],
					selected: args.selected
				});
			break;
		}

		return input;
	};

	this.escape_content = function(unsafe) {
		return unsafe.replace(/<script/g, "");
	};

	// checks for key in object
	this.check_undefined_return_value = function(object, key, undefined_value) {
		if (typeof object[key] === "undefined") return undefined_value;
		return object[key];
	};

	this.debounce = function(func, wait, immediate) {
		var timeout;
		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			var callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	};

	this.builder_functions = new EZFC_Builder_Functions($, this);
};

// prototypes
String.prototype.capitalize = function() {
	return this.charAt(0).toUpperCase() + this.slice(1);
};


jQuery(document).ready(function($) {
	EZFC_Backend = new EZFC_Backend_Object($);
	EZFC_Backend.init();

	// https://github.com/macek/jquery-serialize-object
	// todo: include in lib
	jQuery.fn.serializeObject = function() {
		var self = this,
			json = {},
			push_counters = {},
			patterns = {
				"validate": /^[a-zA-Z][a-zA-Z0-9_]*(?:\[(?:\d*|[a-zA-Z0-9_]+)\])*$/,
				"key":      /[a-zA-Z0-9_]+|(?=\[\])/g,
				"push":     /^$/,
				"fixed":    /^\d+$/,
				"named":    /^[a-zA-Z0-9_]+$/
			};


		this.build = function(base, key, value){
			base[key] = value;
			return base;
		};

		this.push_counter = function(key){
			if(push_counters[key] === undefined){
				push_counters[key] = 0;
			}
			return push_counters[key]++;
		};

		jQuery.each(jQuery(this).serializeArray(), function(){

			// skip invalid keys
			if(!patterns.validate.test(this.name)){
				return;
			}

			var k,
				keys = this.name.match(patterns.key),
				merge = this.value,
				reverse_key = this.name;

			while((k = keys.pop()) !== undefined){

				// adjust reverse_key
				reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');

				// push
				if(k.match(patterns.push)){
					merge = self.build([], self.push_counter(reverse_key), merge);
				}

				// fixed
				else if(k.match(patterns.fixed)){
					merge = self.build([], k, merge);
				}

				// named
				else if(k.match(patterns.named)){
					merge = self.build({}, k, merge);
				}
			}

			json = jQuery.extend(true, json, merge);
		});

		return json;
	};
});