<?php

defined( 'ABSPATH' ) OR exit;

if (defined("Ezfc_widget")) exit;

class Ezfc_widget extends WP_Widget {
	public static $fields;

	function __construct() {
		parent::__construct(
			'Ezfc_widget',
			__('ez Form Calculator', 'ezfc'),
			array( 'description' => __( 'Form widget', 'ezfc' ), )
		);

		self::$fields = array(
			"title" => array(
				"title" => __("Title", "ezfc"),
				"type"  => "input",
				"value" => ""
			),
			"form_id" => array(
				"title" => __("Form", "ezfc"),
				"type"  => "form_id",
				"value" => ""
			)
		);
	}

	public function widget( $args, $instance ) {
		if (empty($instance["form_id"])) {
			echo __("No form selected.", "ezfc");
			return;
		}

		$title = !empty($instance["title"]) ? apply_filters( 'widget_title', $instance['title'] ) : "";
		$id    = !empty($instance["form_id"]) ? $instance['form_id'] : "";

		echo $args['before_widget'];

		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		$shortcode = "[ezfc id='{$id}' /]";
		echo do_shortcode($shortcode);

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		require_once(EZFC_PATH . "class.ezfc_backend.php");
		$ezfc_backend = Ezfc_backend::instance();

		$out = "";

		foreach (self::$fields as $field_name => $field) {
			$input_id    = $this->get_field_id($field_name);
			$input_name  = $this->get_field_name($field_name);
			$input_value = isset($instance[$field_name]) ? $instance[$field_name] : $field["value"];

			$out .= "<p>";
			$out .= "	<label for='{$input_id}'>{$field["title"]}</label>";

			switch ($field["type"]) {
				case "input":
					$field_value = esc_attr($input_value);

					$out .= "<input class='widefat' name='{$input_name}' id='{$input_id}' type='text' value='{$field_value}' />";
				break;

				case "form_id":
					$forms = $ezfc_backend->forms_get();

    				$out .= "<select class='widefat' id='{$input_id}' name='{$input_name}'>";

    				if (count($forms) > 0) {
	    				foreach ($forms as $form) {
	    					$selected = "";
	    					
	    					$id    = empty($form->id) ? 0 : $form->id;
	    					$title = empty($form->name) ? "" : $form->name;

	    					if (!empty($id) && $input_value == $id) $selected = "selected='selected'";

	    					$out .= "<option value='{$id}' {$selected}>#{$id} {$title}</option>";
	    				}
	    			}
	    			else {
	    				$out .= "<option value=''>" . __("No forms found.", "ezfc") . "</option>";
	    			}

    				$out .= "</select>";
				break;
			}

			$out .= "</p>";
		}

		echo $out;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();

		foreach (self::$fields as $name => $field) {
			if (!empty($new_instance[$name])) {
				if (is_array($field)) {
					$new_value = $new_instance[$name];
				}
				else {
					$new_value = strip_tags($new_instance[$name]);
				}

				$instance[$name] = $new_value;
			}
			else {
				$instance[$name] = "";
			}
		}

		return $instance;
	}
}