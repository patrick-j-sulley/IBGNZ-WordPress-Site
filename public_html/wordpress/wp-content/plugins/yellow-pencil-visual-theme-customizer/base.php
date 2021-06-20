<?php
/**
 * Framework for CSS properties
 *
 * @author 		WaspThemes
 * @category 	Core
 * @version     1.0
 */

// Don't run this file directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}


/* ---------------------------------------------------- */
/* Slider Option                                        */
/* ---------------------------------------------------- */
function yp_get_slider_markup($cssName, $name, $default = 'inherit', $decimals, $pxv, $pcv, $emv, $note = null, $dataFormats){
	
	// Tooltip
	$tooltip = '';
	if($note != null && $note != false){
		$tooltip = " data-toggle='tooltip' data-placement='left' title='".$note."'";
	}
    
    // Default
    if ($default === false || $default == '') {
        $default = 'no-defined'; // to not be same with empty datas.
    }

    // Dev CSS Filter
    $CSSID = yp_css_id($cssName);
    $option_status = apply_filters( 'yp_property__'.$CSSID, TRUE);
    if(!$option_status){return;}

    // Pro Label
    $proLabel = "";
    if(!defined("WTFV")){
        if($cssName == "font-family" || $cssName == "color" || $cssName == "background-color" || $cssName == "background-image" || $cssName == "width" || $cssName == "height" || $cssName == "animation-name"){
            $proLabel = "<a target='_blank' href='https://waspthemes.com/yellow-pencil/buy' class='yp-lite yp-pro-label'>GO PRO</a>";
        }
    }
    
    // Option HTML
    return "<div id='" . $cssName . "-group' class='yp-option-group yp-slider-option' data-support-formats='".$dataFormats."' data-css-default='".$default."' data-css='" . $cssName . "' data-css-id='" . yp_css_id($cssName) . "' data-decimals='" . $decimals . "' data-px-range='" . $pxv . "' data-pc-range='" . $pcv . "' data-em-range='" . $emv . "'><div class='yp-part'><label class='yp-option-label'>".$proLabel."<span".$tooltip.">" . $name . "</span><i class='phone-icon'></i><a class='yp-btn-action yp-disable-btn'></a></label><div id='yp-" . $cssName . "' class='yp-slider-div'></div><div class='yp-after'><input type='text' id='" . $cssName . "-value' class='yp-after-css yp-after-css-val' autocomplete='off' autocorrect='off' autocapitalize='off' spellcheck='false' /><input type='text' id='" . $cssName . "-after' class='yp-after-css yp-after-prefix' autocomplete='off' autocorrect='off' autocapitalize='off' spellcheck='false' /></div></div><div class='clearfix'></div><div class='prefix-select'></div></div>";

}


/* ---------------------------------------------------- */
/* Grid Builder                                         */
/* ---------------------------------------------------- */
function yp_grid_builder($cssName, $name, $note = null){

    // Tooltip
    $tooltip = '';
    if($note != null && $note != false){
        $tooltip = " data-toggle='tooltip' data-placement='left' title='".$note."'";
    }

    // Dev CSS Filter
    $CSSID = yp_css_id($cssName);
    $option_status = apply_filters( 'yp_property__'.$CSSID, TRUE);
    if(!$option_status){return;}

    // Option HTML
    $return = "<div id='" . $cssName . "-group' class='yp-option-group yp-grid-option' data-css='" . $cssName . "' data-css-id='" . yp_css_id($cssName) . "'><div class='yp-part'><label class='yp-option-label'><span".$tooltip.">" . $name . "</span><i class='phone-icon'></i><a class='yp-btn-action yp-disable-btn'></a></label>";
    
    
    // End Option
    $return .= "<input id='yp-" . $cssName . "' class='grid-builder-input' type='text' value='' autocomplete='off' autocorrect='off' autocapitalize='off' spellcheck='false' /><div class='grid-builder-area'></div></div></div>";
    
    // Return    
    return $return;

}


/* ---------------------------------------------------- */
/* Select Option                                        */
/* ---------------------------------------------------- */
function yp_get_select_markup($cssName, $name, $values, $default = 'none', $note = null){
	
	// Tooltip
	$tooltip = '';
	if($note != null && $note != false){
		$tooltip = " data-toggle='tooltip' data-placement='left' title='".$note."'";
	}
    
    // Default 1
    if ($default != false) {
        $defaultLink = "<a class='yp-btn-action yp-none-btn' data-default='".$default."'>" . $default . "</a>";
    } else {
        $defaultLink = '';
    }

    // Default 2
    if ($cssName == 'animation-name') {

    	$filter_animation_tools = apply_filters( 'yp_animation_tools', TRUE);

    	if($filter_animation_tools){
        	$defaultLink = "<span class='dashicons dashicons-controls-play anim-player-icon'></span><a class='yp-visual-editor-link'>Animator</a>";
	    }else{
        	$defaultLink = "<span class='dashicons dashicons-controls-play anim-player-icon'></span><a class='yp-visual-editor-link-holder'></a>";
	    }

    }

    // Dev CSS Filter
    $CSSID = yp_css_id($cssName);
    $option_status = apply_filters( 'yp_property__'.$CSSID, TRUE);
    if(!$option_status){return;}

	// Create json list
    if(is_array($values)){

    	$rArray = "[";

    	$i = 0;
	    foreach($values as $key => $value){

	    	if($i != 0){
	    		$rArray .= ",";
	    	}

	        $rArray .= '{"value":"'.$key.'","label":"'.$value.'"}';

	        $i++;

	    }

    	$values = $rArray . "]";

    }else{
    	$values = plugins_url('library/json/'.$values, __FILE__);
    }

    // Pro Label
    $proLabel = "";
    if(!defined("WTFV")){
        if($cssName == "font-family" || $cssName == "color" || $cssName == "background-color" || $cssName == "background-image" || $cssName == "width" || $cssName == "height" || $cssName == "animation-name"){
            $proLabel = "<a target='_blank' href='https://waspthemes.com/yellow-pencil/buy' class='yp-lite yp-pro-label'>GO PRO</a>";
        }
    }

    // Option HTML
    $return = "<div id='" . $cssName . "-group' class='yp-option-group yp-select-option' data-css='" . $cssName . "' data-css-id='" . yp_css_id($cssName) . "'><div class='yp-part'><label class='yp-option-label'>".$proLabel."<span".$tooltip.">" . $name . "</span><i class='phone-icon'></i> " . $defaultLink . " <a class='yp-btn-action yp-disable-btn'></a></label><textarea tabindex='-1' autocomplete='off' autocorrect='off' autocapitalize='off' spellcheck='false' disabled='disabled'>".$values."</textarea>";
    
    
    // End Option
    $return .= "<input id='yp-" . $cssName . "' type='text' class='input-autocomplete' value='' autocomplete='off' autocorrect='off' autocapitalize='off' spellcheck='false' /><div id='yp-autocomplete-place-" . $cssName . "' class='autocomplete-div'></div></div></div>";
	
	// Return    
    return $return;

}



/* ---------------------------------------------------- */
/* Radio Option                                         */
/* ---------------------------------------------------- */
function yp_get_radio_markup($cssName, $name, $values, $default = 'none',$note = null){
	
	// Tooltip
	$tooltip = '';
	if($note != null && $note != false){
		$tooltip = " data-toggle='tooltip' data-placement='left' title='".$note."'";
	}
    
    // Default
    if ($default != false) {
        $defaultLink = "<a class='yp-btn-action yp-none-btn' data-default='".$default."'>" . $default . "</a>";
    } else {
        $defaultLink = '';
    }

    // Dev CSS Filter
    $CSSID = yp_css_id($cssName);
    $option_status = apply_filters( 'yp_property__'.$CSSID, TRUE);
    if(!$option_status){return;}

    // Pro Label
    $proLabel = "";
    if(!defined("WTFV")){
        if($cssName == "font-family" || $cssName == "color" || $cssName == "background-color" || $cssName == "background-image" || $cssName == "width" || $cssName == "height" || $cssName == "animation-name"){
            $proLabel = "<a target='_blank' href='https://waspthemes.com/yellow-pencil/buy' class='yp-lite yp-pro-label'>GO PRO</a>";
        }
    }
    
    // Option HTML
    $return = "<div id='" . $cssName . "-group' class='yp-option-group yp-radio-option' data-css='" . $cssName . "' data-css-id='" . yp_css_id($cssName) . "'><div class='yp-part'><label class='yp-option-label'>".$proLabel."<span".$tooltip.">" . $name . "</span><i class='phone-icon'></i> " . $defaultLink . " <a class='yp-btn-action yp-disable-btn'></a></label><div class='yp-radio-grid-" . count($values) . " yp-radio-content' id='yp-" . $cssName . "'>";
    
    // Radio Settings
    foreach ($values as $key => $value) {
        $return .= '<div class="yp-radio"><input type="radio" name="' . $cssName . '" value="' . $key . '" id="s-'.$cssName.'-' . $key . '"><label id="'.$cssName.'-' . $key . '" data-for="s-'.$cssName.'-' . $key . '" class="yp-update">' . $value . '</label></div>';
    }
    
    // Close
    $return .= "<div class='yp-clear'></div></div></div></div>";
    
    // Return
    return $return;
    
}



/* ---------------------------------------------------- */
/* Colorpicker Option                                    */
/* ---------------------------------------------------- */
function yp_get_color_markup($cssName, $name,$note = null){
	
	// Tooltip
	$tooltip = '';
	if($note != null && $note != false){
		$tooltip = " data-toggle='tooltip' data-placement='left' title='".$note."'";
	}

	// Dev CSS Filter
    $CSSID = yp_css_id($cssName);
    $option_status = apply_filters( 'yp_property__'.$CSSID, TRUE);
    if(!$option_status){return;}

    // Pro Label
    $proLabel = "";
    if(!defined("WTFV")){
        if($cssName == "font-family" || $cssName == "color" || $cssName == "background-color" || $cssName == "background-image" || $cssName == "width" || $cssName == "height" || $cssName == "animation-name"){
            $proLabel = "<a target='_blank' href='https://waspthemes.com/yellow-pencil/buy' class='yp-lite yp-pro-label'>GO PRO</a>";
        }
    }
    
    // Option HTML
    $return = "<div id='" . $cssName . "-group' class='yp-option-group yp-color-option' data-css='" . $cssName . "' data-css-id='" . yp_css_id($cssName) . "'><div class='yp-part'><label class='yp-option-label'>".$proLabel."<span".$tooltip.">" . $name . "</span><i class='phone-icon'></i> <a class='yp-btn-action yp-none-btn'>transparent</a> <a class='yp-btn-action yp-disable-btn'></a></label><div class='yp-color-input-box'><input id='yp-" . $cssName . "' type='text' maxlength='22' size='22' class='wqcolorpicker' value='' autocomplete='off' autocorrect='off' autocapitalize='off' spellcheck='false' /><span class='yp-color-background'><span class='wqminicolors-swatch-color'></span></span><span class='color-picker-icon yp-element-picker'></span></div><div class='yp-after'><a class='yp-flat-colors'>Flat</a><a class='yp-meterial-colors'>Material</a><a class='yp-nice-colors'>Trend</a><a class='yp-theme-colors'>Page Colors</a><div class='yp-clear'></div>";

        // Theme Colors
        $return .= "<div class='yp_theme_colors_area'></div>";

    	// Flat Colors
        $return .= "<div class='yp_flat_colors_area'></div>";
		
		// Meterial Colors	
		$return .= "<div class='yp_meterial_colors_area'></div>";
		
		// Nice Colors		
		$return .= "<div class='yp_nice_colors_area'></div>";

		// option End
		$return .= "</div></div></div>";
    
    // Return
    return $return;
    
}




/* ---------------------------------------------------- */
/* Input Option   		                                */
/* ---------------------------------------------------- */
function yp_get_input_markup($cssName, $name, $none = null, $note = null){
	
	// Tooltip
	$tooltip = '';
	if($note != null && $note != false){
		$tooltip = " data-toggle='tooltip' data-placement='left' title='".$note."'";
	}

	// Dev CSS Filter
    $CSSID = yp_css_id($cssName);
    $option_status = apply_filters( 'yp_property__'.$CSSID, TRUE);
    if(!$option_status){return;}

    // Pro Label
    $proLabel = "";
    if(!defined("WTFV")){
        if($cssName == "font-family" || $cssName == "color" || $cssName == "background-color" || $cssName == "background-image" || $cssName == "width" || $cssName == "height" || $cssName == "animation-name"){
            $proLabel = "<a target='_blank' href='https://waspthemes.com/yellow-pencil/buy' class='yp-lite yp-pro-label'>GO PRO</a>";
        }
    }
    
    // Option HTML
    $return = "<div id='" . $cssName . "-group' class='yp-option-group yp-input-option' data-css='" . $cssName . "' data-css-id='" . yp_css_id($cssName) . "'><div class='yp-part'><label class='yp-option-label'>".$proLabel."<span".$tooltip.">" . $name . "</span><i class='phone-icon'></i> <a class='yp-btn-action yp-none-btn' data-default='".$none."'>".$none."</a> <a class='yp-btn-action yp-disable-btn'></a></label><div class='yp-input-wrapper'><input autocomplete='off' autocorrect='off' autocapitalize='off' spellcheck='false' id='yp-" . $cssName . "' type='text' class='yp-input' value='' />";

    // Upload image icon
    if($cssName == 'list-style-image' || $cssName == "background-image"){
    	$return .= "<span class='yp-upload-btn yp-gallery-btn'></span>";
    }

    $return .= "</div>";

    
	
	// Background Image
	if($cssName == "background-image"){

		$return .= "<div style='clear:both;'></div><a class='yp-unsplash-btn'>Stock Image</a><a data-json='".plugins_url('library/json/gradient.json', __FILE__)."' class='yp-gradient-btn'>Gradient</a><a data-json='".plugins_url('library/json/patterns.json', __FILE__)."' class='yp-bg-img-btn'>Pattern</a><div style='clear:both;'></div>";


		// Background patterns section starts
		$return .= "<div class='yp_background_assets'>";
		$return .= "</div>";
		// Background patterns section end.

		// Background gradient section starts
		$return .= '<div class="yp-gradient-section"><div class="yp-gradient-list"></div><div class="uigradient-api">by <a href="https://uigradients.com">uiGradients</a></div><div class="yp-gradient-bar-background"><div class="yp-gradient-bar"></div></div><div class="yp-gradient-pointer-area"></div><input id="iris-gradient-color" type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" /><div class="yp-gradient-space"></div><div class="yp-gradient-orientation" data-degree="90"><b>Orientation</b><i></i></div></div>';
		// Background gradient section end

        $return .= "<div class='yp-unsplash-section'>";
		$return .= "<div class='yp-unsplash-inner'><input id='unsplash-search' type='text' value='' placeholder='Search an image' autocomplete='off' autocorrect='off' autocapitalize='off' spellcheck='false' />";
		$return .= "<div class='yp-unsplash-list'></div></div>";
		$return .= '<div class="unsplash-api">by <a href="https://unsplash.com">Unsplash</a></div>';
		$return .= "</div>";

	}

	// Close
    $return .= "</div></div>";
    
    // Return
    return $return;
    
}



/* ---------------------------------------------------- */
/* Input Option   		                                */
/* ---------------------------------------------------- */
function yp_get_textarea_markup($cssName, $name, $none = null, $note = null){
	
	// Tooltip
	$tooltip = '';
	if($note != null && $note != false){
		$tooltip = " data-toggle='tooltip' data-placement='left' title='".$note."'";
	}

	// Dev CSS Filter
    $CSSID = yp_css_id($cssName);
    $option_status = apply_filters( 'yp_property__'.$CSSID, TRUE);
    if(!$option_status){return;}

    // Pro Label
    $proLabel = "";
    if(!defined("WTFV")){
        if($cssName == "font-family" || $cssName == "color" || $cssName == "background-color" || $cssName == "background-image" || $cssName == "width" || $cssName == "height" || $cssName == "animation-name"){
            $proLabel = "<a target='_blank' href='https://waspthemes.com/yellow-pencil/buy' class='yp-lite yp-pro-label'>GO PRO</a>";
        }
    }
    
    // Option HTML
    $return = "<div id='" . $cssName . "-group' class='yp-option-group yp-input-option' data-css='" . $cssName . "' data-css-id='" . yp_css_id($cssName) . "'><div class='yp-part'><label class='yp-option-label'>".$proLabel."<span".$tooltip.">" . $name . "</span><i class='phone-icon'></i> <a class='yp-btn-action yp-none-btn' data-default='".$none."'>".$none."</a> <a class='yp-btn-action yp-disable-btn'></a></label><textarea autocomplete='off' autocorrect='off' autocapitalize='off' spellcheck='false' id='yp-" . $cssName . "' type='text' class='yp-textarea'></textarea>";
    $return .= "</div></div>";
    
    // Return
    return $return;
    
}
