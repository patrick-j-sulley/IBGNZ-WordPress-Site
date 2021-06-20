<?php
/*
Plugin Name: YellowPencil
Plugin URI: https://yellowpencil.waspthemes.com
Description: The most powerful visual CSS editor for WordPress. Customize any theme and page visually without coding.
Version: 7.2.1
Author: WaspThemes
Author URI: https://yellowpencil.waspthemes.com
*/


/* ---------------------------------------------------- */
/* Basic 												*/
/* ---------------------------------------------------- */
if (!defined('ABSPATH')) {
    die('-1');
}


/* ---------------------------------------------------- */
/* Check if lite version or not. 						*/
/* ---------------------------------------------------- */
if (strstr(__FILE__, "yellow-pencil-visual-theme-customizer")) {
    $lite_dir       = __FILE__;
    $pro_dir        = str_replace("yellow-pencil-visual-theme-customizer", "waspthemes-yellow-pencil", __FILE__);
} else {
    $pro_dir        = __FILE__;
    $lite_dir       = str_replace("waspthemes-yellow-pencil", "yellow-pencil-visual-theme-customizer", __FILE__);
}

// Checking if files exists
$pro_exists  = file_exists($pro_dir);
$lite_exists = file_exists($lite_dir);

// Define it if this is Pro installation
if($pro_exists){
    define("YP_PRO_DIRECTORY", TRUE);
}

// If pro version is there?
if ($pro_exists == true && $lite_exists == true) {
    
    // Be sure deactivate_plugins function is exists
    if (!function_exists("deactivate_plugins")) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    
    // deactivate Lite Version.
    deactivate_plugins(plugin_basename($lite_dir));
    
}

// Unlock all features
function yp_define_pro(){

    // Get purchase code from database
    $purchase_code = get_option("yp_purchase_code");

    // Has?
    if($purchase_code){

        if(!defined('WTFV')){

            define('WTFV',TRUE);
            
        }

    }

}
add_action("init","yp_define_pro");

// Generate Base Editor URL.
function yp_get_uri() {

    if (current_user_can("edit_theme_options") == true) {

        return admin_url('admin.php?page=yellow-pencil-editor');

    } elseif (defined('YP_DEMO_MODE')) {

        return add_query_arg(array(
            'yellow_pencil' => 'true'
        ), get_home_url() . '/');

    }

}


/* ---------------------------------------------------- */
/* Define 												*/
/* ---------------------------------------------------- */
define('WT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Define
define('YP_VERSION', "7.2.1");

// Admin Settings Page
include(WT_PLUGIN_DIR . 'library/php/admin.php');

// Editor Right Panel
include(WT_PLUGIN_DIR . 'library/php/panel.php');

// Check if it is demo mode
function yp_check_demo_mode() {
    
    // Demo mode avaiable for just non-logout users.
    if (defined('WT_DEMO_MODE') && is_user_logged_in() == false) {
        define('YP_DEMO_MODE', TRUE);
    }
    
}
add_action("init", "yp_check_demo_mode");



/* ---------------------------------------------------- */
/* Add animation ajax callback                          */
/* ---------------------------------------------------- */
function yp_add_animation() {
    
    if (current_user_can("edit_theme_options") == true && check_admin_referer("yp_editor_nonce")) {
        
        $css  = wp_strip_all_tags($_POST['yp_anim_data']);
        $name = wp_strip_all_tags($_POST['yp_anim_name']);
        
        if (!update_option("yp_anim_" . $name, $css)) {
            add_option("yp_anim_" . $name, $css);
        }
        
    }
    
    wp_die();
    
}

add_action('wp_ajax_yp_add_animation', 'yp_add_animation');



/* ---------------------------------------------------- */
/* Download from unsplash and upload to wp              */
/* ---------------------------------------------------- */
function yp_unsplash_api(){

    // Let
    if(current_user_can("upload_files") && check_admin_referer("yp_editor_nonce")){

        global $wpdb;
        $attachments = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE post_title = '".esc_sql($_POST["yp_id"])."' AND post_type = 'attachment' ", OBJECT);

        if($attachments){
            $attachment_url = $attachments[0]->guid;
            die($attachment_url);
        }

        // The URL
        $url = $_POST["yp_link"]."&.jpg";

        // TMP
        $tmp = download_url($url, 60);

        // Error Check
        if(is_wp_error($tmp)){
            die("There is a problem with downloading the image from the remote server. please increase the file upload size limit and try again.");
        }

        // Array
        $file_array = array();

        // Name
        $file_array['name'] = $_POST["yp_id"].".jpg";
        $file_array['tmp_name'] = $tmp;

        // Error Check
        if ( is_wp_error( $tmp ) ) {
            @unlink($file_array['tmp_name']);
            $file_array['tmp_name'] = '';
            die("This image file seems to be invalid.");
        }

        // do the validation and storage stuff
        $id = media_handle_sideload($file_array, 0, $_POST["yp_id"]);

        // If error storing permanently, unlink
        if (is_wp_error($id)){
            @unlink($file_array['tmp_name']);
            die("There is a problem with uploading the image to WordPress. please increase the file upload size limit and try again.");
        }

        // Print Result
        die(wp_get_attachment_url($id));

    }

}

add_action('wp_ajax_yp_unsplash_api', 'yp_unsplash_api');


/* ---------------------------------------------------- */
/* Saving live option with ajax                         */
/* ---------------------------------------------------- */
function yp_save_live_option() {
    
    // If User can edit theme options
    if (current_user_can("edit_theme_options") == true && check_admin_referer("yp_editor_nonce")) {
            
        // Get Value
        $name = wp_strip_all_tags($_POST['yp_option_name']);
        $value = wp_strip_all_tags($_POST['yp_option_value']);
        
        // Update Option with yp_op_ prefix
        if (!update_option("yp_op_" . $name, $value)) {
            add_option("yp_op_" . $name, $value);
        }
        
    }
    
    // Die
    wp_die();
    
}

add_action('wp_ajax_yp_live_save_option', 'yp_save_live_option');


/* ---------------------------------------------------- */
/* Reading live options                                 */
/* ---------------------------------------------------- */
function yp_get_live_option($name){

    // IF Pro and hides preimum options, show all
    if($name == "hide_premium_options" && defined("WTFV") == true){
        return "false";
    }

    // Database Option
    $option = get_option("yp_op_".$name);

    // If no option on database, read defaults
    if($option === null || $option === false){

        // YP Defaults
        $defaultOption = array(
            'fixed_right_panel' => false,
            'show_parent_tree' => true,
            'hide_premium_options' => false,
            'show_margin_padding_on_hover' => true,
            'smart_responsive_technology' => true,
            'smart_important_tag' => true,
            'high_performance' => false,
            'append_auto_comments' => true
        );

        // Get after filters
        $data = apply_filters( 'yp_'.$name, $defaultOption[$name]);

        if($data){
            return "true";
        }else{
            return "false";
        }

    }else{
        return $option;
    }

}


/* ---------------------------------------------------- */
/* Saving selector comments                             */
/* ---------------------------------------------------- */
function yp_save_comments_option() {
    
    // If User can edit theme options
    if (current_user_can("edit_theme_options") == true && check_admin_referer("yp_editor_nonce")) {
            
        // Get Value
        $value = wp_strip_all_tags($_POST['yp_selector_comments']);

        // Stripslashes
        $value = yp_stripslashes($value);
        
        // IS Valid
        json_decode($value);
        if(json_last_error() === JSON_ERROR_NONE){
        
            // Update Option with yp_op_ prefix
            if (!update_option("yp_selector_comments", $value)) {
                add_option("yp_selector_comments", $value);
            }

        }
        
    }
    
    // Die
    wp_die();
    
}

add_action('wp_ajax_yp_save_comments_option', 'yp_save_comments_option');


/* ---------------------------------------------------- */
/* Ajax check plugin license                            */
/* ---------------------------------------------------- */
function yp_check_license() {
    
    if (current_user_can("edit_theme_options") == true && check_admin_referer("yp_editor_nonce")) {

        $key = get_option("yp_purchase_code");
        if($key === null || $key === false){
            die("0");
        }else{
            die("1");
        }

    }

}
add_action('wp_ajax_yp_check_license', 'yp_check_license');


/* ---------------------------------------------------- */
/* Delete Stylesheets, animations with ajax             */
/* ---------------------------------------------------- */
function yp_delete_stylesheet_live() {
    
    if(current_user_can("edit_theme_options") == true && check_admin_referer('yp_live_styles_delete_nonce')){

        // delete global data.
        if(isset($_POST['yp_reset_global'])){
            delete_option('wt_css');
            delete_option('wt_styles');
        }

        // delete anim
        if(isset($_POST['yp_delete_animate'])){
            delete_option(trim(strip_tags(($_POST['yp_delete_animate']))));
        }

        // delete Post type.
        if(isset($_POST['yp_reset_type'])){

            $reset_type = trim( strip_tags( $_POST['yp_reset_type'] ) );

            delete_option('wt_'.$reset_type.'_css');
            delete_option('wt_'.$reset_type.'_styles');
        }

        // delete by id.
        if(isset($_POST['yp_reset_id'])){
            delete_post_meta(intval($_POST['yp_reset_id']),'_wt_css');
            delete_post_meta(intval($_POST['yp_reset_id']),'_wt_styles');
        }

        // delete options
        if(isset($_POST['yp_reset_options'])){

            // Delete selector comment
            delete_option('yp_selector_comments');

            // Settings page
            delete_option('yp-output-option');
            delete_option('yp-draft-mode');
            delete_option('yp-default-global');

            // Delete all other options starts with yp_op_
            global $wpdb;
            $prefix = "yp_op_";
            $options = $wpdb->get_results($wpdb->prepare("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $prefix . '%'), ARRAY_A);
            
            if (!empty($options)) {
                foreach ($options as $v) {
                    delete_option($v['option_name']);
                }
            }

        }

        // Get All CSS data as ready-to-use
        $output = yp_get_export_css("create");
            
        // Update custom.css file
        yp_create_custom_css($output);

    }
    
    wp_die();
    
}

add_action('wp_ajax_yp_delete_stylesheet_live', 'yp_delete_stylesheet_live');


/* ---------------------------------------------------- */
/* GET UPDATE API                                       */
/* ---------------------------------------------------- */
/* This is CodeCanyon update API and this requires just */
/* in Pro version. update-api.php not available in lite */
/* ---------------------------------------------------- */
$update_dir = WT_PLUGIN_DIR.'/library/php/update-api.php';
if(defined('YP_PRO_DIRECTORY') && file_exists($update_dir)){
    require_once(WT_PLUGIN_DIR.'/library/php/update-api.php');
}


/* ---------------------------------------------------- */
/* Delete some options when uninstall                   */
/* ---------------------------------------------------- */
if (function_exists('register_uninstall_hook')){
    register_uninstall_hook(__FILE__, 'uninstall_yellow_pencil');
}


/* ---------------------------------------------------- */
/* delete some options on uninstall                     */
/* ---------------------------------------------------- */
function uninstall_yellow_pencil() {
    delete_option('yp_purchase_code');
}


/* ---------------------------------------------------- */
/* Add a customize link in wp plugins page              */
/* ---------------------------------------------------- */
function yp_customize_link($links, $file) {
    
    if ($file == plugin_basename(dirname(__FILE__) . '/yellow-pencil.php')) {

        // Manage Customizations link
        $in = '<a href="' . admin_url('themes.php?page=yellow-pencil-changes') . '">Manage Customizations</a>';
        array_unshift($links, $in);

        // Customize link
        $in = '<a href="' . admin_url('themes.php?page=yellow-pencil') . '">Customize</a>';
        array_unshift($links, $in);

        // Show GO PRO link if lite version
        if(!defined("WTFV")){
            $in = '<a style="font-weight:700;" target="_blank" href="' . esc_url('https://waspthemes.com/yellow-pencil/buy/') . '">Go Pro</a>';
            array_unshift($links, $in);
        }

    }

    return $links;

}

add_filter('plugin_action_links', 'yp_customize_link', 10, 2);



/* ---------------------------------------------------- */
/* Update database for 7.0.0                            */
/* ---------------------------------------------------- */
function yp_database_update(){

    $databaseUpdated = get_option('yp_700_db_updateX');

    // one time only.
    if($databaseUpdated === false){

        global $wpdb;

        // Find data in options
        $options = $wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name REGEXP '^wt_.*_?styles$'", ARRAY_A);
    
        // if array not empty
        if (!empty($options)) {

            // loop
            foreach ($options as $option) {

                // convert old data to new data
                $newData = yp_convert_new_data(yp_stripslashes($option['option_value']));

                // update
                update_option($option['option_name'], $newData);

            }

        }

        // Find data in metaOptions
        $metaOptions = $wpdb->get_results("SELECT post_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wt_styles'", ARRAY_A);
    
        // if array not empty
        if (!empty($metaOptions)) {

            // loop
            foreach ($metaOptions as $option) {

                // convert old data to new data
                $newData = yp_convert_new_data(yp_stripslashes($option['meta_value']));

                // update
                update_post_meta($option['post_id'], $option['meta_key'], $newData);

            }

        }

        // add option for not convert again.
        add_option("yp_700_db_updateX", "1");

    }

}

add_action("init", "yp_database_update");



/* ---------------------------------------------------- */
/* Get ID of rule                                       */
/* ---------------------------------------------------- */
function yp_css_id($css){

    // No webkit
    $css = str_replace("-webkit-", "", $css);

    // Update transfrom parts
    if(strrpos($css, "-transform") !== false && $css != 'text-transform'){
        $css = 'transform';
    }

    // Update filter parts
    if(strrpos($css, "-filter") !== false){
        $css = 'filter';
    }

    // Update filter parts
    if(strrpos($css, "box-shadow-") !== false){
        $css = 'box-shadow';
    }

    // Return
    return $css;

}



/* ---------------------------------------------------- */
/* Convert old data to new data 7.0.0                   */
/* ---------------------------------------------------- */
function yp_convert_new_data($data){

    // empty
    if(empty($data)){
        return $data;
    }

    // if not have a style tag
    if(strrpos($data, "<style ") === false){
        return $data;
    }

    // converting with preg_match_all regex 
    $re = '/<style(.*?)<\/style>/';
    preg_match_all($re, $data, $matches, PREG_SET_ORDER, 0);

    // no matches, then don't make anything.
    if(empty($matches)){
        return $data;
    }

    // keep all as array
    $result = array();

    // loop matches
    foreach ($matches as $value) {

        $style = $value[0];

        preg_match('/data\-rule\=\"(.*?)\"/', $style, $rule);
        preg_match('/data\-style\=\"(.*?)\"/', $style, $selector);
        preg_match('/data\-size\-mode\=\"(.*?)\"/', $style, $msize);
        preg_match('/>(.*?)<\/style>/', $style, $content);

        // push
        array_push($result, "/* [rule=".$rule[1]."] [selector=".$selector[1]."] [msize=".$msize[1]."] */\n ".$content[1]);

    }

    // join array and return;
    return join("", $result);

}



/* ---------------------------------------------------- */
/* Get Font Families                                    */
/* ---------------------------------------------------- */
function yp_load_fonts() {
    $css = yp_get_css(true);
    yp_get_font_families($css, null);
}



/* ---------------------------------------------------- */
/* Getting font Families By CSS OUTPUT					*/
/* ---------------------------------------------------- */
// Type null = 'wp_enqueue_style'
// Type import = 'import'
// Type = wp_enqueue_style OR return @import CSS
function yp_get_font_families($css, $type) {
    
    $protocol = is_ssl() ? 'https' : 'http';
    
    preg_match_all('/font-family:(.*?);/', $css, $r);
    
    foreach ($r['1'] as &$k) {
        $k = yp_font_name($k);
    }
    
    $importArray = array();
    
    foreach (array_unique($r['1']) as $family) {
        
        $id = str_replace("+", "-", strtolower($family));
        
        $id = str_replace("\\", "", $id);
        
        if ($id == 'arial' || $id == 'helvetica' || $id == 'georgia' || $id == 'serif' || $id == 'helvetica-neue' || $id == 'times-new-roman' || $id == 'times' || $id == 'sans-serif' || $id == 'arial-black' || $id == 'gadget' || $id == 'impact' || $id == 'charcoal' || $id == 'tahoma' || $id == 'geneva' || $id == 'verdana' || $id == 'inherit') {
            return false;
        }
        
        if ($id == '' || $id == ' ') {
            return false;
        }
        
        // Getting fonts from google api.
        if ($type == null) {
            wp_enqueue_style($id, esc_url('' . $protocol . '://fonts.googleapis.com/css?family=' . $family . ':300,300italic,400,400italic,500,500italic,600,600italic,700,700italic'));
        } else {
            array_push($importArray, esc_url('' . $protocol . '://fonts.googleapis.com/css?family=' . $family . ':300,300italic,400,400italic,500,500italic,600,600italic,700,700italic'));
        }
        
    }
    
    if ($type != null) {
        return $importArray;
    }
    
}



/* ---------------------------------------------------- */
/* Finding Font Names From CSS data     				*/
/* ---------------------------------------------------- */
function yp_font_name($a) {
    
    $a = str_replace(array(
        
        "font-family:",
        '"',
        "'",
        " ",
        "+!important",
        "!important"
        
    ), array(
        
        "",
        "",
        "",
        "+",
        "",
        ""
        
    ), $a);
    
    if (strstr($a, ",")) {
        $array = explode(",", $a);
        return trim($array[0], "+");
    } else {
        return trim($a, "+");
    }
    
}



/* ---------------------------------------------------- */
/* Admin Javascript                                     */
/* ---------------------------------------------------- */
function yp_adminj_javascript(){ ?>
    <script type="text/javascript" >
    jQuery(document).ready(function($){

        <?php

            // Default customization type
            $yp_default_global = get_option("yp-default-global");

            // Open in global customization
            if($yp_default_global == "1"){
                echo "window.default_global_customization_type = true;";
            }else{
                echo "default_global_customization_type = false;";
            }

        ?>


    });
    </script><?php
}

// Admin script
add_action( 'admin_footer', 'yp_adminj_javascript' );



/* ---------------------------------------------------- */
/* Checking current user can or not                     */
/* ---------------------------------------------------- */
function yp_check_let() {
    
    // If Demo Mode
    if (defined('YP_DEMO_MODE') == true && isset($_GET['yellow_pencil_frame']) == true) {
        return true;
    }
    
    // If user can.
    if (current_user_can("edit_theme_options") == true) {
        return true;
    } else {
        return false;
    }
    
}



/* ---------------------------------------------------- */
/* Checking current user can or not (FOR FRAME)			*/
/* ---------------------------------------------------- */
function yp_check_let_frame() {
    
    // If Demo Mode
    if (defined('YP_DEMO_MODE') == true && isset($_GET['yellow_pencil_frame']) == true) {
        return true;
    }
    
    // Be sure, user can.
    if (current_user_can("edit_theme_options") == true && isset($_GET['yellow_pencil_frame']) == true) {
        return true;
    } else {
        return false;
    }
    
}



/* ---------------------------------------------------- */
/* Getting Last Post Title 								*/
/* ---------------------------------------------------- */
function yp_getting_last_post_title() {
    $last = wp_get_recent_posts(array(
        "numberposts" => 1,
        "post_status" => "publish"
    ));
    
    if (isset($last['0']['ID'])) {
        $last_id = $last['0']['ID'];
    } else {
        return false;
    }
    
    $title = get_the_title($last_id);
    
    if (strstr($title, " ")) {
        $words = explode(" ", $title);
        return $words[0];
    } else {
        return $title;
    }
    
}



/* ---------------------------------------------------- */
/* Clean protocol from URL 								*/
/* ---------------------------------------------------- */
function yp_urlencode($v) {
    $v = explode("://", urldecode($v));
    return urlencode($v[1]);
}


/* ---------------------------------------------------- */
/* Register Admin Script                                */
/* ---------------------------------------------------- */
function yp_deactivation_function($hook) {

    // clean options
    delete_option("yp_700_db_updateX");

}

register_deactivation_hook(__FILE__, 'yp_deactivation_function');


/* ---------------------------------------------------- */
/* Register Admin Script                                */
/* ---------------------------------------------------- */
function yp_enqueue_admin_pages($hook) {
    
    // Post pages.
    if ('post.php' == $hook || 'post-new.php' == $hook) {
        wp_enqueue_script('yellow-pencil-admin', plugins_url('js/admin.js', __FILE__), 'jquery', '1.0', TRUE);
    }

    // Yellow Pencil WordPress Admin Page. // loading ace editor
    if ('toplevel_page_yellow-pencil-changes' == $hook || "yellowpencil_page_yellow-pencil-animations" == $hook || "yellowpencil_page_yellow-pencil-settings" == $hook) {

        // Ace Editor
        wp_enqueue_script('yp-admin-page-ace', plugins_url('library/ace/ace.js', __FILE__), 'jquery', '1.0', TRUE);

        // Ace Editor
        wp_enqueue_script('yp-admin-page-ace2', plugins_url('library/ace/ext-language_tools.js', __FILE__), 'jquery', '1.0', TRUE);

        // General Scripts
        wp_enqueue_script('yp-admin-page', plugins_url('js/admin-page.js', __FILE__), 'jquery', '1.0', TRUE);

    }
    
    // Admin CSS
    wp_enqueue_style('yellow-pencil-admin', plugins_url('css/admin.css', __FILE__));
    
}

add_action('admin_enqueue_scripts', 'yp_enqueue_admin_pages');



/* ---------------------------------------------------- */
/* Adding Link To Admin Appearance Menu					*/
/* ---------------------------------------------------- */
function yp_menu() {
    add_theme_page('YellowPencil Editor', 'YellowPencil Editor', 'edit_theme_options', 'yellow-pencil', 'yp_menu_function', 999);
}


/*
/* ---------------------------------------------------- */
/* Appearance page Loading And Location                 */
/* ---------------------------------------------------- */
function yp_menu_function() {
    
}

add_action('admin_menu', 'yp_menu');


/* ---------------------------------------------------- */
/* Appearance page Loading And Location                 */
/* ---------------------------------------------------- */
function yp_admin_headr() {

    if(!isset($_GET['page'])){
        return false;
    }

    if($_GET['page'] != 'yellow-pencil'){
        return false;
    }
    
    // Home URL
    $yellow_pencil_uri = yp_get_uri();

    // Basic
    $frontpage_id = get_option('page_on_front');

    if($frontpage_id == 0 || $frontpage_id == null){
        $page_id = "home";
        $yp_page_type = "home";
        $yp_mode = "single";
    }else{
        $page_id = $frontpage_id;
        $yp_page_type = get_post_type($frontpage_id);
        $yp_mode = "single"; 
    }

    // Dev filter for auto popup.
    $auto_popup = apply_filters( 'yp_auto_load_popup', TRUE);

    // get option value
    $global_option = get_option("yp-default-global");

    // use global as default
    if($yp_mode == "single" && $global_option == "1"){
        $yp_mode = "global";
    }

    // Redirect Link
    if($auto_popup){
        $href = add_query_arg(array('href' => yp_urlencode(get_home_url().'/'), 'yp_page_id' => $page_id, 'yp_page_type' => $yp_page_type, 'yp_mode' => $yp_mode, 'yp_load_popup' => "1"), $yellow_pencil_uri);
    }else{
        $href = add_query_arg(array('href' => yp_urlencode(get_home_url().'/'), 'yp_page_id' => $page_id, 'yp_page_type' => $yp_page_type, 'yp_mode' => $yp_mode), $yellow_pencil_uri);
    }

    // Redirect
    wp_safe_redirect($href);
    
}

add_action('admin_init', 'yp_admin_headr');



/* ---------------------------------------------------- */
/* Sub string after 18chars								*/
/* ---------------------------------------------------- */
function yp_get_short_title($title, $limit) {
    
    $title = strip_tags($title);
    
    if ($title == '') {
        $title = 'Untitled';
    }
    
    if (strlen($title) > $limit) {
        return mb_substr($title, 0, $limit, 'UTF-8') . '..';
    } else {
        return $title;
    }
    
}



/* ---------------------------------------------------- */
/* Getting Custom Animations Codes						*/
/* ---------------------------------------------------- */
function yp_get_custom_animations() {
    
    $all_options = wp_load_alloptions();
    foreach ($all_options as $name => $value) {
        if (stristr($name, 'yp_anim')) {
            
            // Get animations
            $value = yp_stripslashes(yp_auto_prefix($value));
            $value = preg_replace('/\s+|\t/', ' ', $value);
            
            echo "\n" . '<style id="yp-animate-' . strtolower(str_replace("yp_anim_", "", $name)) . '">' . "\n" . '' . $value . "\n" . str_replace("keyframes", "-webkit-keyframes", $value) . '' . "\n" . '</style>'. "\n";
            
        }
    }
    
}



// Update custom.css when reading settings change
// Because this need to update body.blog etc prefixes after change.
add_action( 'update_option_page_on_front', 'yp_check_settings', 10, 2 );
add_action( 'update_option_page_for_posts', 'yp_check_settings', 10, 2 );
add_action( 'update_option_show_on_front', 'yp_check_settings', 10, 2 );

function yp_check_settings(){

    // Get All CSS data as ready-to-use
    $output = yp_get_export_css("create");
        
    // Update custom.css file
    yp_create_custom_css($output);

}


/* ---------------------------------------------------- */
/* Helper tool to print login styles                    */
/* ---------------------------------------------------- */
/* CSS codes, library.js, animate.css, custom-anims     */
function yp_login_styles($r){

    $onlyCSS = "";

    // Login
    if($GLOBALS['pagenow'] === 'wp-login.php' && empty($_REQUEST['action'])){
        $onlyCSS .= get_option("wt_login_css");
    }

    // Register
    if($GLOBALS['pagenow'] === 'wp-login.php' && !empty($_REQUEST['action']) && $_REQUEST['action'] === 'register'){
        $onlyCSS .= get_option("wt_register_css");
    }

    // Lost Password
    if($GLOBALS['pagenow'] === 'wp-login.php' && !empty($_REQUEST['action']) && $_REQUEST['action'] === 'lostpassword'){
        $onlyCSS .= get_option("wt_lostpassword_css");
    }

    // No print
    if(strlen($onlyCSS) == 0){
        return false;
    }

    // Delete CSS Comments
    $onlyCSS = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!","", $onlyCSS);

    // Return mode
    if($r){
        return $onlyCSS;
    }

    // Return
    $return = '<style id="yellow-pencil">';
    $return .= "\r\n/*\r\n\tThe following CSS generated by YellowPencil Plugin.\r\n\thttps://yellowpencil.waspthemes.com\r\n*/\r\n";
        
    // process
    $onlyCSS = yp_stripslashes(yp_auto_prefix($onlyCSS));
        
    // min and add
    $return .= str_replace(array(
        "\n",
        "\r",
        "\t"
    ), '', $onlyCSS);
    
    // Close style
    $return .= "\n" . '</style>';
    
    // Print
    echo $return;

    // Echo Custom Animations
    yp_get_custom_animations();

    // Animate library.
    if (strstr($onlyCSS, "animation-name:")) {
        wp_enqueue_style('yellow-pencil-animate', plugins_url('library/css/animate.css', __FILE__));
    }

    // Check if there any animation
    if (strstr($onlyCSS, "animation-name:") == true || strstr($onlyCSS, "animation-duration:") == true || strstr($onlyCSS, "animation-delay:") == true) {
            
        // Load library and jQuery
        wp_enqueue_script('yellow-pencil-library', plugins_url('library/js/library.js', __FILE__), 'jquery', '1.0', TRUE);
        wp_enqueue_script('jquery');
            
    }

}

if(isset($_GET["yellow_pencil_frame"]) == false){
    add_action('login_head', 'yp_login_styles', 999999999);
}



/* ---------------------------------------------------- */
/* Getting CSS Codes									*/
/* ---------------------------------------------------- */
/*
yp get css(false) : echo output CSS
yp get css(true) : return just CSS Codes.
*/
function yp_get_css($r = false) {
    
    $onlyCSS         = '';
    $get_type_option = '';
    $get_post_meta   = '';
    
    global $wp_query;
    if (isset($wp_query->queried_object)) {
        $id = @$wp_query->queried_object->ID;
    } else {
        $id = null;
    }
    
    if (class_exists('WooCommerce')) {
        if (is_shop()) {
            $id = wc_get_page_id('shop');
        }
    }
    
    $get_option = get_option("wt_css");

    // get post type
    $postType = get_post_type($id);

    // using "shop" type for shop page of woocommerce
    if (class_exists('WooCommerce')) {
        if (is_shop()) {
            $postType = "shop";
        }
    }

    if ($id != null) {
        $get_post_meta = get_post_meta($id, '_wt_css', true);
    }

    if($postType != null){
        $get_type_option = get_option("wt_" . $postType . "_css");
    }
    
    if ($get_option == 'false') {
        $get_option = false;
    }
    
    if ($get_type_option == 'false') {
        $get_type_option = false;
    }
    
    if ($get_post_meta == 'false') {
        $get_post_meta = false;
    }
    
    if (empty($get_option) == false) {
        $onlyCSS .= $get_option;
    }
        
    // Load type and id only on singular pages
    if(is_singular()){

        // dont load type on front and home page
        if(is_front_page() == false && is_home() == false){

            if (empty($get_type_option) == false) {
                $onlyCSS .= $get_type_option;
            }

        }
    
        if (empty($get_post_meta) == false) {
            $onlyCSS .= $get_post_meta;
        }

    }

    // special for shop page of woocommerce
    if (class_exists('WooCommerce')) {

        if (is_shop()) {

            if (empty($get_type_option) == false) {
                $onlyCSS .= $get_type_option;
            }

            if (empty($get_post_meta) == false) {
                $onlyCSS .= $get_post_meta;
            }

        }

    }
    
    if (is_author()) {
        $onlyCSS .= get_option("wt_author_css");
    } elseif (is_tag()) {
        $onlyCSS .= get_option("wt_tag_css");
    } elseif (is_category()) {
        $onlyCSS .= get_option("wt_category_css");
    } elseif (is_404()) {
        $onlyCSS .= get_option("wt_404_css");
    } elseif (is_search()) {
        $onlyCSS .= get_option("wt_search_css");
    } elseif (is_archive()) {
        $onlyCSS .= get_option("wt_archive_css");
    }
    
    // home.
    if (is_front_page() && is_home()) {
        $onlyCSS .= get_option("wt_home_css");
    }

    // blog
    $page_for_posts = get_option('page_for_posts');
    
    // Don't load type on front and posts page    
    if(is_home() && $page_for_posts != null){
        $get_post_meta   = get_post_meta($page_for_posts, '_wt_css', true);
        $onlyCSS .= $get_post_meta;
    }

    // Delete CSS Comments
    $onlyCSS = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!","", $onlyCSS);
    
    if ($onlyCSS != '' && $r == false) {
        
        $return = '<style id="yellow-pencil">';
        $return .= "\r\n/*\r\n\tThe following CSS generated by YellowPencil Plugin.\r\n\thttps://yellowpencil.waspthemes.com\r\n*/\r\n";
        
        // process
        $onlyCSS = yp_stripslashes(yp_auto_prefix($onlyCSS));
        
        // min and add
        $return .= str_replace(array(
            "\n",
            "\r",
            "\t"
        ), '', $onlyCSS);
        
        $return .= "\n" . '</style>';
        
        echo $return;
        
    }
    
    if ($r == true) {
        return $onlyCSS;
    }
    
}


// If is dynamic inline.
if (get_option('yp-output-option') != 'external') {
    
    // Adding all CSS codes to Website
    if (isset($_GET['yellow_pencil_frame']) == false && isset($_GET['yp_live_preview']) == false) {

        // Add action if not draft mode
        if(get_option('yp-draft-mode') != '1'){
            add_action('wp_head', 'yp_get_css', 999999999);
        }

    }    
    
}

// Adding all CSS animations to Website
if (isset($_GET['yellow_pencil_frame']) == false && isset($_GET['yp_live_preview']) == false) {

    // Add action if not draft mode
    if(get_option('yp-draft-mode') != '1'){
        add_action('wp_head', 'yp_get_custom_animations', 999999999);
    }

}

// Adding all CSS animations to WP Head if live preview or editor page.
if (isset($_GET['yellow_pencil_frame']) == true || isset($_GET['yp_live_preview']) == true) {
    add_action('wp_head', 'yp_get_custom_animations', 999999999);
}



/* ---------------------------------------------------- */
/* Getting Live Preview CSS                             */
/* ---------------------------------------------------- */
function yp_get_live_css() {
    
    // Get recent generated CSS codes.
    $css = get_option('yp_live_view_css_data');
    
    if (empty($css)) {
        return $css;
    }
    
    return yp_stripslashes(yp_auto_prefix($css));
    
}


/* ---------------------------------------------------- */
/* Used for find page details by URL                    */
/* ---------------------------------------------------- */
function show_page_details() {
        
    // only allowed users can see it
    if(current_user_can("edit_theme_options") || defined('YP_DEMO_MODE')){

        // getting informations
        $data = yp_get_page_ids();
        $page_id = $data[0];
        $page_type = $data[1];
        $edit_mode = $data[2];

        // This adding all informations to head of the page,
        // the plugin will get these information with javascript functions
        // for open the target page in the editor
        echo "<script id='yp_page_details'>".$page_id."|".$page_type."|".$edit_mode."</script>";

    }

}

// Hook only if get yp_get_details
if(isset($_POST['yp_get_details'])){
    add_action('wp_head', 'show_page_details', 999999999);
}



/* ---------------------------------------------------- */
/* Getting fonts for live preview                       */
/* ---------------------------------------------------- */
function yp_load_fonts_for_live() {
    $css = yp_get_live_css();
    yp_get_font_families($css, null);
}



/* ---------------------------------------------------- */
/* Getting fonts for admin                              */
/* ---------------------------------------------------- */
function yp_load_fonts_for_admin() {
    $css = yp_login_styles(true);
    yp_get_font_families($css, null);
}



/* ---------------------------------------------------- */
/* Generating Live Preview data 						*/
/* ---------------------------------------------------- */
function yp_get_live_preview() {
    
    $css = yp_get_live_css();
    
    if (empty($css) == false) {
        
        $css = '<style id="yp-live-preview">' . $css . '</style>';
        
        if ($css != '<style id="yp-live-preview"></style>') {
            echo $css;
        }
        
    }
    
}



/* ---------------------------------------------------- */
/* Adding generated live preview CSS data To WP HEAD	*/
/* ---------------------------------------------------- */
if (isset($_GET['yp_live_preview']) == true) {

    add_action('wp_head', 'yp_get_live_preview', 999999999);
    add_action('login_head', 'yp_get_live_preview', 999999999);
    add_action('init', 'yp_out_mode', 999999999);
    
}


/* ---------------------------------------------------- */
/* Adding Prefix To Some CSS Rules                      */
/* ---------------------------------------------------- */
function yp_auto_prefix($css) {
    
    // last 9 version of browsers
    // 10.1.2018

    // clean ms and webkit if available
    $css = preg_replace('@\t(-webkit-|-ms-)(.*?):(.*?);@si', "", $css);
    
    // Webkit prefixes
    $webkit = array(
        "background-size",
        "background-clip",
        "box-sizing",
        "animation-name",
        "animation-iteration-count",
        "animation-duration",
        "animation-delay",
        "animation-fill-mode",
        "box-shadow",
        "filter",
        "transform",
        "flex-direction",
        "flex-wrap",
        "justify-content",
        "align-items",
        "align-content",
        "flex-basis",
        "align-self",
        "flex-grow",
        "flex-shrink",
        "perspective",
        "transform-origin",
        "backface-visibility"
    );

    // Ms prefixes
    $ms = array(
        "transform",
        "flex-direction",
        "flex-wrap",
        "justify-content",
        "align-items",
        "align-content",
        "flex-basis",
        "align-self",
        "flex-grow",
        "flex-shrink",
        "transform-origin",
        "backface-visibility"
    );
    
    // Webkit
    foreach ($webkit as $prefix) {
        
        if($prefix == "justify-content"){
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-webkit-box-pack:$1;\r\t" . $prefix . ":$1;", $css);
        }else if($prefix == "align-items"){
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-webkit-box-align:$1;\r\t" . $prefix . ":$1;", $css);
        }else if($prefix == "flex-grow"){
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-webkit-box-flex:$1;\r\t" . $prefix . ":$1;", $css);
        }else{
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-webkit-" . $prefix . ":$1;\r\t" . $prefix . ":$1;", $css);
        }
        
    }

    // MS
    foreach ($ms as $prefix) {
        
        if($prefix == "justify-content"){
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-ms-flex-pack:$1;\r\t" . $prefix . ":$1;", $css);
        }else if($prefix == "align-items"){
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-ms-flex-align:$1;\r\t" . $prefix . ":$1;", $css);
        }else if($prefix == "align-content"){
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-ms-flex-line-pack:$1;\r\t" . $prefix . ":$1;", $css);
        }else if($prefix == "flex-basis"){
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-ms-flex-preferred-size:$1;\r\t" . $prefix . ":$1;", $css);
        }else if($prefix == "align-self"){
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-ms-flex-item-align:$1;\r\t" . $prefix . ":$1;", $css);
        }else if($prefix == "flex-grow"){
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-ms-flex-positive:$1;\r\t" . $prefix . ":$1;", $css);
        }else if($prefix == "flex-shrink"){
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-ms-flex-negative:$1;\r\t" . $prefix . ":$1;", $css);
        }else{
            $css = preg_replace('@(?<!-)' . $prefix . ':([^\{\;]+);@i', "-ms-" . $prefix . ":$1;\r\t" . $prefix . ":$1;", $css);
        }
        
    }

    // Display: flex
    $css = preg_replace('@display(\s+)?:(\s+)?flex(\s+)?(\!important)?;@i', "display:-webkit-box$3$4;\r\tdisplay:-webkit-flex$3$4;\r\tdisplay:-ms-flexbox$3$4;\r\tdisplay:flex$3$4;", $css);


    // Load Gradient one time only.
    if(!function_exists("yp_linear_gradient_support")){

        // Linear gradient prefix support
        function yp_linear_gradient_support(array $match){

            // only gradient content
            $gradientOriginal = $match[4];
            $gradient = $gradientOriginal;

            // get first part
            preg_match('/linear-gradient\(([^,]+)/i', "linear-gradient(".$gradientOriginal, $matches);

            // direction available
            if(isset($matches[1])){

                $direction = strtolower(trim($matches[1]));

                // is valid
                if(preg_match('/(deg|top|left|right|bottom)/i', $direction)){

                    // Is deg
                    if(preg_match('/deg/i', $direction)){

                        // get deg
                        $deg = preg_replace("/[^0-9.]/", "", $direction);

                        // reverse direction for o and webkit
                        if($deg == "0"){
                            $deg = "bottom";
                        }elseif($deg == "90"){
                            $deg = "left";
                        }elseif($deg == "180"){
                            $deg = "top";
                        }elseif($deg == "270"){
                            $deg = "right";
                        }elseif($deg == "360"){
                            $deg = "bottom";
                        }else if($deg < 90){
                            $deg = 90 - $deg."deg";
                        }else if($deg > 90){
                            $deg = 360 - ($deg - 90)."deg";
                        }

                        // Update gradient
                        $gradient = preg_replace("/linear-gradient\(([^,]+)/", $deg, "linear-gradient(".$gradient);

                    // top, left etc
                    }else{

                        // to left..
                        if(preg_match('/to /i', $direction)){

                            if($direction == "to left"){
                                $direction = "right";
                            }else if($direction == "to right"){
                                $direction = "left";
                            }else if($direction == "to top"){
                                $direction = "bottom";
                            }else if($direction == "to bottom"){
                                $direction = "top";
                            }

                            // Update Gradient
                            $gradient = preg_replace("/linear-gradient\(([^,]+)/", $direction, "linear-gradient(".$gradient);

                        }

                    }

                }

            }

            // Default no important
            $important = "";

            // Checks important tag
            if(isset($match[6])){
                $important = " ".$match[6];
            }

            // Generate result gradient
            $result = $match[1].":-webkit-linear-gradient(".$gradient.")".$important.";\r\t";
            $result .= $match[1].":-o-linear-gradient(".$gradient.")".$important.";\r\t";
            $result .= $match[1].":linear-gradient(".$gradientOriginal.")".$important.";";

            // return result
            return $result;

        }

    }
    
    // linear gradient support (-webkit-gradient is not supported)
    $css = preg_replace_callback("@(background-image|background)(\s+)?:(\s+)?linear-gradient\((.*?)\)(\s+)?(\!important)?;@i", 'yp_linear_gradient_support', $css);

    return $css;
    
}


/* ---------------------------------------------------- */
/* Prefix for Animations EXPORT							*/
/* ---------------------------------------------------- */
function yp_export_animation_prefix($outputCSS) {
    
    return str_replace(array(
        
        ".yp_hover",
        ".yp_focus"
        
    ), array(
        
        ":hover",
        ":focus"
        
    ), $outputCSS);
    
}



/* ---------------------------------------------------- */
/* Adding no-index meta to head for demo mode YP Links!	*/
/* ---------------------------------------------------- */
function yp_head_meta() {
    echo '<meta name="robots" content="noindex, follow">' . "\n";
}



/* ---------------------------------------------------- */
/* Shows the frame as visitor to logged user            */
/* ---------------------------------------------------- */
function yp_out_mode() {
    
    if (isset($_GET['yp_out']) && current_user_can("edit_theme_options")) {
        wp_set_current_user(-1);
    }
    
}



/* ---------------------------------------------------- */
/* Advanced link replacer                               */
/* ---------------------------------------------------- */
function yp_advanced_link_replace($match){

    // be sure this is stylesheet
    if(preg_match("/rel=(\"|\'|\s+)?stylesheet(\"|\'|\s+)?/", $match[0]) == false){
        return $match[0];
    }

    // getting protocol
    $protocol = is_ssl() ? 'https://' : 'http://';

    // getting domain / Getting editor URL, has WWW or no, must be same with iframe contents
    $domain = get_admin_url();

    $www = false;

    // has www
    if(strpos($domain, "://www.") == true){
        $www = true;
    }

    // The link href
    $href = $match[8];

    // not available
    if(isset($href) == false){
        return $match[0];
    }

    // Delete WWW from domain
    $domain = str_replace("://www.", "://", $domain);

    // check if href match
    if(strpos($href, "://".$domain) == false){

        // if domain still not match when have WWW too
        if(strpos($href, "://www.".$domain) == false){
            return $match[0];
        }

    }

    // If have any https or http protocol
    if(strpos($href, "https://") !== false || strpos($href, "http://") !== false){

        // is href doesnt use current protocol
        if(strpos($href, $protocol) === false){

            // convert http:// to https://
            if($protocol == "https://"){

                // Href
                $href = str_replace("http://", "https://", $href);

            // convert https:// to http://
            }else{
                $href = str_replace("https://", "http://", $href);
            }

        }

    }

    // If this link has www and current domain not have
    if(strpos($href, "://www.") == true && $www == false){
        $href = str_replace("://www.", "://", $href);
    }

    // If this link not has www and current domain have
    if(strpos($href, "://www.") == false && $www == true){
        $href = str_replace("://", "://www.", $href);
    }

    // Update href and return
    return preg_replace('@href=("|\')?(.*?)("|\'|\s\'|\s"|\s)@', "href=$1".$href."$3", $match[0]);

}


/* ---------------------------------------------------- */
/* Prepare the CSS links before load the page           */
/* ---------------------------------------------------- */
function yp_link_replace($buffer){

    // Replace links
    $buffer = preg_replace_callback('@\<link(\s+)?(.*?)?(\s+)?(\s+)?(.*?)?(\s+)?href=("|\')?(.*?)("|\'|\s\'|\s"|\s)(\s+)?(.*?)?(\s+)?(\s+)?(.*?)?(\s+)?(/>|>)@', "yp_advanced_link_replace", $buffer);

    return $buffer;

}


/* ---------------------------------------------------- */
/* Adding other CSS Data to Editor frame                */
/* ---------------------------------------------------- */
if (isset($_GET['yellow_pencil_frame']) == true) {
    add_action('wp_head', 'yp_head_meta', 9997);
    add_action('init', 'yp_out_mode', 9996);
    ob_start("yp_link_replace");
}


/* ------------------------------------------------------------------- */
/* Other CSS Codes (All CSS Codes excluding current editing type CSS)  */
/* ------------------------------------------------------------------- */
function yp_editor_styles($id, $type, $mode) {
    
    $get_type_option = '';
    $get_post_meta   = '';
    
    $id_is = false;
    $type_is = false;

    if($mode == 'template'){
        $type_is = true;
    }else if($mode == 'single'){
        $id_is = true;
    }

    $global = '';
    $template = '';
    $single = '';
    
    // Get Global, template, single data
    $get_option = get_option("wt_styles");
    $get_type_option = get_option("wt_" . $type . "_styles");

    if($type == "lostpassword" || $type == "register" || $type == "login"){
        $get_post_meta = get_option("wt_" . $type . "_styles");
    }else{
        $get_post_meta = get_post_meta($id, '_wt_styles', true);
    }
    
    // get global data
    if (empty($get_option) == false && $type != "lostpassword" && $type != "register" && $type != "login") {
        $global .= $get_option;
    }

    // Not load page template to Blog Page and Front Page
    $FrontPage = get_option('page_on_front');
    $BlogPage = get_option('page_for_posts');

    // get template data
    if (empty($get_type_option) == false) {

        if($type != 'author' && $type != 'tag' && $type != 'category' && $type != '404' && $type != 'search' && $type != 'home' && $type != 'archive' && $id != $FrontPage && $id != $BlogPage && $type != "lostpassword" && $type != "register" && $type != "login"){
            $template .= $get_type_option;
        }

    }
    
    // get single data
    if (empty($get_post_meta) == false) {
        $single .= $get_post_meta;
    }
    
    // Advanced types
    if ($type == 'author') {
        $template .= get_option("wt_author_styles");
    }
        
    if ($type == 'tag') {
        $template .= get_option("wt_tag_styles");
    }
        
    if ($type == 'category') {
        $template .= get_option("wt_category_styles");
    }

    if ($type == 'archive') {
        $template .= get_option("wt_archive_styles");
    }
        
    if ($type == '404') {
        $template .= get_option("wt_404_styles");
    }
        
    if ($type == 'search') {
        $template .= get_option("wt_search_styles");
    }
        
    if ($type == 'home') {
        $single .= get_option("wt_home_styles");
    }
    
    // Generated animations
    $animations = '';
    
    // getting all animations
    $all_options = wp_load_alloptions();

    // loop anims
    foreach ($all_options as $name => $value) {
        if (stristr($name, 'yp_anim')) {
            $animations .= $value;
        }
    }

    // empty vars
    $globalActive = '';
    $templateActive = '';
    $singleActive = '';

    // add yp-styles-area to active
    if($id_is && yp_type_is_available("single")){
        $singleActive = ' id="yp-styles-area"';
    }else if($type_is && yp_type_is_available("template")){
        $templateActive = ' id="yp-styles-area"';
    }else{
        $globalActive = ' id="yp-styles-area"';
    }

    // Data Layout
    $return = '<div id="yellow-pencil-iframe-data"><!-- 

    <style class="yp-inline-data"'.$globalActive.' data-source-mode="global">'.$global.'</style>
    <style class="yp-inline-data"'.$templateActive.' data-source-mode="template">'.$template.'</style>
    <style class="yp-inline-data"'.$singleActive.' data-source-mode="single">'.$single.'</style>';

    // return animations
    $return .= '<div id="yp-animate-data"><style>' . $animations . '</style></div> --></div>';
    
    // return editor data
    echo yp_stripslashes($return);
    
}




/* ---------------------------------------------------- */
/* Include options Library								*/
/* ---------------------------------------------------- */
include_once(WT_PLUGIN_DIR . 'base.php');




/*-------------------------------------------------------*/
/*  Ajax Preview Save CallBack                           */
/*-------------------------------------------------------*/
function yp_preview_data_save() {
    
    if (current_user_can("edit_theme_options") == true && check_admin_referer("yp_editor_nonce")) {
        
        $css = wp_strip_all_tags($_POST['yp_data']);
        
        if (!update_option('yp_live_view_css_data', $css)) {
            add_option('yp_live_view_css_data', $css);
        }
        
    }
    
    wp_die();
    
}

add_action('wp_ajax_yp_preview_data_save', 'yp_preview_data_save');


/*-------------------------------------------------------*/
/*	Creating an Custom.css file (Static)				 */
/*-------------------------------------------------------*/
function yp_create_custom_css($data) {
    
    // Revisions
    $rev = get_option('yp_revisions');
    
    if ($rev == false) {
        $rev = 700;
    }
    
    // Find all other old revisions
    $files = glob(WT_PLUGIN_DIR . 'custom-*.css');

    // then delete old revisions before create new.
    foreach($files as $file){
        wp_delete_file($file);
    }
    
    // get the upload directory and make a test.txt file
    $filename = WT_PLUGIN_DIR . 'custom-' . $rev . '.css';
    
    // by this point, the $wp_filesystem global should be working, so let's use it to create a file
    global $wp_filesystem;
    
    // Initialize the WP filesystem, no more using 'file-put-contents' function
    if (empty($wp_filesystem)) {
        require_once(ABSPATH . '/wp-admin/includes/file.php');
        WP_Filesystem();
    }
    
    if (!$wp_filesystem->put_contents($filename, $data, FS_CHMOD_FILE)) {
        echo 'Yellow Pencil: There was an error creating the custom.css file, please use "Dynamic Inline CSS" option.';
    }
    
}


/*-------------------------------------------------------*/
/*  Ajax Real Save Callback                              */
/*-------------------------------------------------------*/
function yp_ajax_save() {
    
    if (current_user_can("edit_theme_options") == true && check_admin_referer("yp_editor_nonce")) {
        
        // Revisions
        $currentRevision = get_option('yp_revisions');
        
        // Update revision.
        if ($currentRevision != false) {
            update_option('yp_revisions', $currentRevision + 1);
        } else {
            add_option('yp_revisions', "1");
        }

        // Getting data
        $css = wp_strip_all_tags($_POST['yp_data']);
        $styles = trim(wp_strip_all_tags($_POST['yp_editor_data']));

        // replace ] */ to fix ajax problems.
        $styles = str_replace("YPOGRP", "/* [", $styles);
        $styles = str_replace("YPEGRP", "] */", $styles);

        $id   = '';
        $type = '';
        
        if (isset($_POST['yp_page_id'])) {
            $id = intval($_POST['yp_page_id']);
        }
        
        if (isset($_POST['yp_page_type'])) {
            $type = trim(strip_tags($_POST['yp_page_type']));
            if (count(explode("#", $type)) == 2) {
                $type = explode("#", $type);
                $type = $type[0];
            }
        }
        
        // Global
        if ($id == '' && $type == '') {
            
            // CSS Data
            if (empty($css) == false) {
                if (!update_option('wt_css', $css)) {
                    add_option('wt_css', $css);
                }
            } else {
                delete_option('wt_css');
            }
            
            // Styles
            if (empty($css) == false) {
                if (!update_option('wt_styles', $styles)) {
                    add_option('wt_styles', $styles);
                }
            } else {
                delete_option('wt_styles');
            }
            
        // ID
        } elseif ($type == '') {
            
            // CSS Data
            if (empty($css) == false) {
                if (!update_post_meta($id, '_wt_css', $css)) {
                    add_post_meta($id, '_wt_css', $css, true);
                }
            } else {
                delete_post_meta($id, '_wt_css');
            }
            
            // Styles
            if (empty($css) == false) {
                if (!update_post_meta($id, '_wt_styles', $styles)) {
                    add_post_meta($id, '_wt_styles', $styles, true);
                }
            } else {
                delete_post_meta($id, '_wt_styles');
            }
            
        // Type 
        } else {
            
            // CSS Data
            if (empty($css) == false) {
                if (!update_option('wt_' . $type . '_css', $css)) {
                    add_option('wt_' . $type . '_css', $css);
                }
            } else {
                delete_option('wt_' . $type . '_css');
            }
            
            // Styles
            if (empty($css) == false) {
                if (!update_option('wt_' . $type . '_styles', $styles)) {
                    add_option('wt_' . $type . '_styles', $styles);
                }
            } else {
                delete_option('wt_' . $type . '_styles');
            }
            
        }
        
    }
    
    wp_die();
    
}

add_action('wp_ajax_yp_ajax_save', 'yp_ajax_save');


/*-------------------------------------------------------*/
/*  Ajax detect dynamic id and classes                   */
/*-------------------------------------------------------*/
function yp_ajax_detect_dynamic_id_class(){

    if(check_admin_referer("yp_editor_nonce")){

    // get protocol
    $protocol = ( is_ssl() ? 'https://' : 'http://' );

    // Espace the target URL
    $url = $protocol.urldecode($_POST["yp_page_href"]);

    // Random number to not load from cache
    $rand = rand(136900, 963100);

    // Update url with rand param
    $url = add_query_arg(array('yp_rand' => $rand), $url);

    // Get Response
    $response = wp_remote_get($url, array( 'sslverify' => false));

    // Get Content
    $html = wp_remote_retrieve_body($response);

    // Get response status
    $status = wp_remote_retrieve_response_code($response);

    // Return blank if no content
    if($html == false || $status != 200 || $html == ""){
        die();
    }

    // Current editor HTML
    $currentHTML = stripslashes($_POST["yp_page_content"]);



    // CURRENT EDITOR PAGE CONTENTS

    // ID Array
    $idArray = array();

    // Get ids
    preg_match_all("/id=(\"|\')(.*?)(\"|\')/", $currentHTML, $ids);

    // check if have the value
    if(isset($ids[2])){

        $idCount = count($ids[2]);

        // trim all id array
        for ($i = 0; $i < $idCount; $i++) {
            $ids[2][$i] = trim($ids[2][$i]);
        }

        // Add ids to idArray
        array_push($idArray, $ids[2]);

    }

    // Get classes
    preg_match_all("/class=(\"|\')(.*?)(\"|\')/", $currentHTML, $classes);

    // Class Array
    $classesArray = array();

    // check if have the value
    if(isset($classes[2])){

        // Each all classes
        foreach ($classes[2] as $thisClass){

            // trim
            $thisClass = trim($thisClass);
            
            // explode all one by one
            if(strpos($thisClass, ' ') !== false){

                // push each other one by one
                $thisClass = explode(" ", $thisClass);

                // each all classes
                foreach ($thisClass as $currentClass){

                    // trim
                    $currentClass = trim($currentClass);
                    
                    // Push this class
                    array_push($classesArray, $currentClass);

                }

            }else{

                // Push this class
                array_push($classesArray, $thisClass);

            }

        }

    }



    // REFLESHED PAGE CONTENTS

    // Get ids
    preg_match_all("/id=(\"|\')(.*?)(\"|\')/", $html, $ids);

    // ID Array
    $NewIdArray = array();

    // check if have the value
    if(isset($ids[2])){

        $idCount = count($ids[2]);

        // trim all id array
        for ($i = 0; $i < $idCount; $i++) {
            $ids[2][$i] = trim($ids[2][$i]);
        }

        // Add ids to output
        array_push($NewIdArray, $ids[2]);

    }

    // Get classes
    preg_match_all("/class=(\"|\')(.*?)(\"|\')/", $html, $classes);

    // Class Array
    $NewClassesArray = array();

    // check if have the value
    if(isset($classes[2])){

        // Each all classes
        foreach ($classes[2] as $thisClass){

            // trim
            $thisClass = trim($thisClass);
            
            // explode all one by one
            if(strpos($thisClass, ' ') !== false){

                // push each other one by one
                $thisClass = explode(" ", $thisClass);

                // each all classes
                foreach ($thisClass as $currentClass){

                    // trim
                    $currentClass = trim($currentClass);
                    
                    // Push this class
                    array_push($NewClassesArray, $currentClass);

                }

            }else{

                // Push this class
                array_push($NewClassesArray, $thisClass);

            }

        }

    }

    // Return Array
    $outputArray = array();

    // Push ID diff to return array
    array_push($outputArray, array_diff($idArray[0],$NewIdArray[0]));

    // Push class diff to return array
    array_push($outputArray, array_diff($classesArray,$NewClassesArray));

    // return the result
    echo json_encode($outputArray);

    // stop
    die();

    }

}

add_action('wp_ajax_yp_ajax_detect_dynamic_id_class', 'yp_ajax_detect_dynamic_id_class');
add_action('wp_ajax_nopriv_yp_ajax_detect_dynamic_id_class', 'yp_ajax_detect_dynamic_id_class');


/*-------------------------------------------------------*/
/*  Ajax Real Save Callback                              */
/*-------------------------------------------------------*/
function yp_ajax_update_css() {
    
    if (current_user_can("edit_theme_options") == true && check_admin_referer("yp_editor_nonce")) {
        
        // Get All CSS data as ready-to-use
        $output = yp_get_export_css("create");
        
        // Update custom.css file
        yp_create_custom_css($output);
        
    }
    
    wp_die();
    
}

add_action('wp_ajax_yp_ajax_update_css', 'yp_ajax_update_css');



/* ---------------------------------------------------- */
/* Getting customizing type                             */
/* ---------------------------------------------------- */
function yp_customizing_type() {

    $type = $_GET['yp_mode'];
    $typeSelf = strtolower(ucfirst(trim(strip_tags($_GET['yp_page_type']))));

    // Force Single
    if ($typeSelf == 'login' || $typeSelf == 'register' || $typeSelf == 'lostpassword') {
        return "single";
    }

    // requested "Single" if available
    if ($type == 'single' && yp_type_is_available('single')) {
        
        $result = "single";

    // requested "Single" but if not available so setup "template"
    } elseif ($type == 'single' && yp_type_is_available('template')) {
        
        $result = "template";    

    // requested "Template" if available
    } elseif ($type == 'template' && yp_type_is_available('template')) {
        
        $result = "template";

    // requested "Template" but if not available so setup "global"
    } elseif ($type == 'template' && yp_type_is_available('global')) {
        
        $result = "global";

    // use global
    } elseif ($type == 'global' && yp_type_is_available('global')){

        $result = "global";

    }

    return $result;
    
}


/* ---------------------------------------------------- */
/* Checking if these type is available in current page  */
/* ---------------------------------------------------- */
function yp_type_is_available($gtype){

    // Get page type
    $id = trim(strip_tags($_GET['yp_page_id']));
    $type = trim(strip_tags($_GET['yp_page_type']));

    // Single is disabled on these types
    if($gtype == 'single'){

        if($type == 'author' || $type == 'tag' || $type == 'category' || $type == '404' || $type == 'search' || $type == 'archive'){
            return false;
        }

        if($id == "0" && $type != 'home'){
            return false;
        }

    }

    // Template is disabled on these types
    if($gtype == 'template'){

        if($type == 'home' || $type == "general" || $type == "login" || $type == "register" || $type == "lostpassword"){
            return false;
        }

        // get post page id
        $page_for_posts = get_option('page_for_posts');
        $page_on_front = get_option('page_on_front');

        // Template disabled on posts page
        if($id == $page_for_posts && $id != 0){
            return false;
        }

        // Template disabled on front page
        if($id == $page_on_front && $id != 0){
            return false;
        }

    }

    // Global disabled on these types
    if($gtype == "global"){

        if($type == "lostpassword" || $type == "register" || $type == "login"){
            return false;
        }

    }

    return true;

}


/* ---------------------------------------------------- */
/* Getting customizing types like a list                */
/* ---------------------------------------------------- */
function yp_customizing_options(){

    // Get Current Type
    $type = yp_customizing_type();

    // nulls
    $singleSelected = '';
    $templateSelected = '';
    $globalSelected = '';

    // disable single
    if(yp_type_is_available('single') == false){
        $singleSelected = 'type-disabled';
    }

    // disable template
    if(yp_type_is_available('template') == false){
        $templateSelected = 'type-disabled';
    }

    // disable global
    if(yp_type_is_available('global') == false){
        $globalSelected = 'type-disabled';
    }

    // Select Current
    if($type == 'single' && $singleSelected != "type-disabled"){
        $singleSelected = 'active-customizing-list';
    }else if($type == 'template' && $templateSelected != "type-disabled"){
        $templateSelected = 'active-customizing-list';
    }else{
        $globalSelected = 'active-customizing-list';
    }

    // Options
    $result = "<li data-value='single' class='".$singleSelected."'><i class='manage-this-type'></i><i class='reset-this-type'></i><h6><span>Single Customization</span><small class='type-bayt'><span>empty</span><i>changed</i></small></h6><span class='current-type'>current</span><p>apply style just to the current page.</p></li>";
    $result .= '<li data-value="template" class="'.$templateSelected.'"><i class="manage-this-type"></i><i class="reset-this-type"></i><h6><span>Template Customization</span><small class="type-bayt"><span>empty</span><i>changed</i></small></h6><span class="current-type">current</span><p>apply style to all pages of the current post type.</p></li>';
    $result .= '<li data-value="global" class="'.$globalSelected.'"><i class="manage-this-type"></i><i class="reset-this-type"></i><h6><span>Global Customization</span><small class="type-bayt"><span>empty</span><i>changed</i></small></h6><span class="current-type">current</span><p>apply style to the entire website.</p></li>';

    return $result;

}


/* ---------------------------------------------------- */
/* Getting current name                                 */
/* ---------------------------------------------------- */
function yp_page_name($full){

    $limit = 24;

    if($full){
        $limit = 200;
    }

    $result = "Unknown";

    // if page id isset
    if (isset($_GET['yp_page_id'])) {
        
        // The id.
        $id = $_GET['yp_page_id'];

        if($id == "lostpassword"){
            return '"Lost Password Page"';
        }

        if($id == '404'){
            return '"404 Error Page"';
        }

        if($id == 'home'){
            return '"Homepage"';
        }

        if($id == 'search'){
            return '"Search Results"';
        }

        if(!is_numeric($id)){
            return '"'.ucfirst(strtolower($id)).' Page"';
        }

        // Only Int
        $id = intval($id);
        
        $title = get_the_title($id);
        $slug  = get_post_type($id);

        if (strlen($title) > $limit) {
            $result = '"' . mb_substr($title, 0, $limit, 'UTF-8') . '..' . '" '.$slug;
        } else {

            if ($title == '') {
                $title = "Untitled";
            }

            $result = '"' . $title . '" '.$slug;
        }
        
    }

    return $result;

}


/* ---------------------------------------------------- */
/* Adding helper style for wp-admin-bar                 */
/* ---------------------------------------------------- */
function yp_yellow_pencil_style() {
    echo '<style>#wp-admin-bar-yellow-pencil > .ab-item:before{content: "\f309";top:2px;}#wp-admin-bar-yp-update .ab-item:before{content: "\f316";top:3px;}</style>';
}


/* ---------------------------------------------------- */
/* Trying to find all page information                  */
/* ---------------------------------------------------- */
function yp_get_page_ids(){

    global $wp_query;

    // Defaults
    $page_id = 0;
    $edit_mode = 'single';
    $page_type = "general";

    // Trying to getting the id
    if (isset($_GET['page_id'])) {
        $page_id = intval($_GET['page_id']);
    } elseif (isset($_GET['post']) && is_admin() == true) {
        $page_id = intval($_GET['post']);
    } elseif (isset($wp_query->queried_object) == true) {
        $page_id = @$wp_query->queried_object->ID;
    }

    // Since 4.5.2
    // category,author,tag, 404 and archive page support.
    $page_type = get_post_type($page_id);
    
    // Getting specials pages
    if (is_author()) {

        $page_id = "author";
        $page_type = 'author';
        $edit_mode = 'template';

    } elseif (is_tag()) {

        $page_id = "tag";
        $page_type = 'tag';
        $edit_mode = 'template';

    } elseif (is_category()) {

        $page_id = "category";
        $page_type = 'category';
        $edit_mode = 'template';

    } elseif (is_404()) {

        $page_id = '404';
        $page_type = '404';
        $edit_mode = 'template';

    } elseif (is_archive()) {

        $page_id = 'archive';
        $page_type = 'archive';
        $edit_mode = 'template';

    } elseif (is_search()) {

        $page_id = 'search';
        $page_type = 'search';
        $edit_mode = 'template';

    }
    
    // Homepage
    if (is_front_page() && is_home()) {

        $page_id = 'home';
        $page_type = 'home';
        $edit_mode = 'single';

    }
    
    // WooCommerce Support
    if (class_exists('WooCommerce')) {
        
        // Shop Page
        if (is_shop()) {

            $page_id = wc_get_page_id('shop');
            $page_type = 'shop';
            $edit_mode = 'single';

        }
        
        // Product Category and tag
        if (is_product_category() || is_product_tag()) {

            $page_id = 0;
            $page_type = "general";
            $edit_mode = 'template';

        }
        
    }

    return array($page_id, $page_type, $edit_mode);

}


/* ---------------------------------------------------- */
/* Adding menu to wp-admin-bar							*/
/* ---------------------------------------------------- */
function yp_yellow_pencil_edit_admin_bar($bar) {
    
    // get data
    $data = yp_get_page_ids();

    // Getting page informations
    $page_id = $data[0];
    $page_type = $data[1];
    $edit_mode = $data[2];

    // get global option
    $global_option = get_option("yp-default-global");

    // use global instead of single
    if($edit_mode == "single" && $global_option == "1"){
        $edit_mode = "global";
    }

    // URL OF Editor
    $yellow_pencil_uri = yp_get_uri();

    // Getting current page
    $href = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $href = remove_query_arg("yp_rand", $href);
    $href = remove_query_arg("fl_builder", $href); // beaver builder param
    $href = remove_query_arg("et_fb", $href); // divi param

    // Append Menu
    $args = array(
        'id' => 'yellow-pencil',
        'title' => 'Edit With YellowPencil',
        'href' => add_query_arg(array(
        'href' => yp_urlencode($href),
        'yp_page_id' => $page_id,
        'yp_page_type' => $page_type,
        'yp_mode' => $edit_mode
    ),$yellow_pencil_uri),
        'meta' => array(
            'class' => 'yellow-pencil'
    ));

    // Add to wp admin bar
    $bar->add_node($args);
    
}


/* ---------------------------------------------------- */
/* Adding Body Classes									*/
/* ---------------------------------------------------- */
function yp_body_class($classes) {
    
    $classes[] = 'yp-yellow-pencil';
    
    if (current_user_can("edit_theme_options") == false) {
        if (defined('YP_DEMO_MODE')) {
            $classes[] = 'yp-yellow-pencil-demo-mode';
        }
    }
    
    if (!defined('WTFV')) {
        $classes[] = 'wtfv';
    }
    
    return $classes;
    
}


/* ---------------------------------------------------- */
/* Customizing Type Iframe                              */
/* ---------------------------------------------------- */
function yp_customize_type_frame() {
    
    add_submenu_page(null, "Customizing Type", "Customizing Type", 'edit_theme_options', 'yellow-pencil-customize-type', 'yp_customizing_type_frame');
    
}

add_action('admin_menu', 'yp_customize_type_frame');



/* ---------------------------------------------------- */
/*  We need an blank page (hack)                        */
/* ---------------------------------------------------- */
function yp_customizing_type_frame() {
    
}

add_action('load-admin_page_yellow-pencil-customize-type', 'yp_customize_type_content');



/* ---------------------------------------------------- */
/*  Customize Type Popup                                */
/* ---------------------------------------------------- */
function yp_customize_type_content(){

    include(WT_PLUGIN_DIR . '/library/php/customize-popup.php');

    exit();

}



/* ---------------------------------------------------- */
/* Install the plugin									*/
/* ---------------------------------------------------- */
function yp_init() {
    
    
    // See Developer Documentation for more info.
    if (defined('YP_DEMO_MODE')) {
        include(WT_PLUGIN_DIR . 'library/php/demo-mode.php');
    }
    
    
    // Iframe Settings.
    // Disable admin bar in iframe
    // Add Classes to iframe body.
    // Add Styles for iframe.
    if (yp_check_let_frame()) {
        show_admin_bar(false);
        add_filter('body_class', 'yp_body_class');
    }
    
    
    // If yellowpencil is active and theme support;
    // Adding Link to #wpadminbar.
    if (yp_check_let()) {
        
        // If not admin page, Add Customizer link.
        if (is_admin() === false) {
            
            add_action('admin_bar_menu', 'yp_yellow_pencil_edit_admin_bar', 999);
            
            // Adding CSS helper for admin bar link.
            add_action('wp_head', 'yp_yellow_pencil_style');
            
        }
        
    }
    
    
    // Getting Current font families.
    add_action('wp_enqueue_scripts', 'yp_load_fonts');
    
    
    // Live preview
    if (isset($_GET['yp_live_preview']) == true) {
        add_action('wp_enqueue_scripts', 'yp_load_fonts_for_live');
    }

    // To Login
    add_action('login_enqueue_scripts', 'yp_load_fonts_for_admin');

}

add_action("init", "yp_init");




/* ---------------------------------------------------- */
/* Uploader Style 										*/
/* ---------------------------------------------------- */
function yp_uploader_style() {
    
    if (isset($_GET['yp_uploader'])) {
        
        if ($_GET['yp_uploader'] == 1) {
            
            echo '<style>
				tr.url,tr.post_content,tr.post_excerpt,tr.field,tr.label,tr.align,tr.image-size,tr.post_title,tr.image_alt,.del-link,#tab-type_url{display:none !important;}
				.media-item-info > tr > td > p:last-child,.savebutton,.ml-submit{display:none !important;}
				#media-upload #filter{width:auto !important;}
                .subsubsub{display:none !important;}
                .tablenav .alignleft.actions{display:none !important;}
                .tablenav{height:auto !important;margin:0 !important;}
                .tablenav-pages{margin:0px !important;text-align: right !important;}
                .media-upload-form{margin-top:0px !important;}
                #filter{margin-bottom:10px !important;}
                #media-search{display:none !important;}
                .tablenav .tablenav-pages a, .tablenav-pages-navspan{min-width: auto !important;font-size: 13px !important;}
				.media-item .describe input[type="text"], .media-item .describe textarea{width:334px;}
                .max-upload-size{opacity:0.7 !important;}
			</style>';
            
        }
        
    }
    
}

add_action('admin_head', 'yp_uploader_style');



/* ---------------------------------------------------- */
/* CSS library for YellowPencil			      			*/
/* ---------------------------------------------------- */
function yp_register_styles() {
    
    // Animate library for live preview
    if (isset($_GET['yp_live_preview']) == true) {
        
        // Get CSS
        $css = yp_get_live_css();
        
        // Test Animation
        if (strstr($css, "animation-name:")) {
            wp_enqueue_style('yellow-pencil-animate', plugins_url('library/css/animate.css', __FILE__));
        }
        
    // No Live Preview
    }else{

        // Get CSS
        $css = yp_get_css(true);
    
        // Animate library.
        if (strstr($css, "animation-name:")) {

            // Website
            if(isset($_GET['yellow_pencil_frame']) == false){

                // Load CSS if not draft mode
                if(get_option('yp-draft-mode') != '1'){
                    wp_enqueue_style('yellow-pencil-animate', plugins_url('library/css/animate.css', __FILE__));
                }

            }

        }

    }
    
    // Add Custom.css to website.
    if (isset($_GET['yellow_pencil_frame']) == false && isset($_GET['yp_live_preview']) == false && get_option('yp-output-option') == 'external') {
            
        // If not draft mode
        if(get_option('yp-draft-mode') != '1'){

            // New ref URL parameters on every new update.
            $rev = get_option('yp_revisions');
            
            if ($rev == false) {
                $rev = 700;
            }
            
            // Custom CSS Href
            $href = add_query_arg('revision', $rev, plugins_url('custom-' . $rev . '.css', __FILE__));

            // First check if file is exists
            if(file_exists((WT_PLUGIN_DIR . 'custom-'.$rev.'.css'))){

                // Getting file size of custom css
                $customCSSSize = @filesize(WT_PLUGIN_DIR . 'custom-'.$rev.'.css');
                
                // Add
                if($customCSSSize > 0){
                    wp_enqueue_style('yp-custom', $href);
                }

            }

        }
        
    }
    
}

add_action('wp_enqueue_scripts', 'yp_register_styles', 999999999);



/* ---------------------------------------------------- */
/* Jquery plugins for CSS Engine						*/
/* ---------------------------------------------------- */
function yp_register_scripts() {
    
    // if Not editor page
    if(isset($_GET['yellow_pencil_frame']) == false){

        // Get live preview CSS
        if(isset($_GET["yp_live_preview"])){

            // add live preview CSS
            $css = get_option('yp_live_view_css_data');

        // Get direct saved CSS
        }else{

            // Get CSS data
            $css = yp_get_css(true);

        }

        // Check if there any animation
        if (strstr($css, "animation-name:") == true || strstr($css, "animation-duration:") == true || strstr($css, "animation-delay:") == true) {
            
            // Live Preview
            if(isset($_GET["yp_live_preview"])){

                // Loads
                wp_enqueue_script('yellow-pencil-library', plugins_url('library/js/library.js', __FILE__), 'jquery', '1.0', TRUE);
                wp_enqueue_script('jquery');

            // Website and If not draft mode
            }else if(get_option('yp-draft-mode') != '1'){

                // Loads
                wp_enqueue_script('yellow-pencil-library', plugins_url('library/js/library.js', __FILE__), 'jquery', '1.0', TRUE);
                wp_enqueue_script('jquery');

            }
            
        }
        
    }
    
}

add_action('wp_enqueue_scripts', 'yp_register_scripts');



/* ---------------------------------------------------- */
/* Iframe Admin Page									*/
/* ---------------------------------------------------- */
function yp_yellow_pencil_editor() {
    
    add_submenu_page(null, "YellowPencil Editor", "YellowPencil Editor", 'edit_theme_options', 'yellow-pencil-editor', 'yp_editor_func');
    
}

add_action('admin_menu', 'yp_yellow_pencil_editor');



/* ---------------------------------------------------- */
/*  We need an blank page (hack)						*/
/* ---------------------------------------------------- */
function yp_editor_func() {
    
}

add_action('load-admin_page_yellow-pencil-editor', 'yp_frame_output');



/* ---------------------------------------------------- */
/* Custom Action yp_editor_header                       */
/* ---------------------------------------------------- */
function yp_editor_header() {
    do_action('yp_editor_header');
}


/* ---------------------------------------------------- */
/* Custom Action yp_js_hook                             */
/* ---------------------------------------------------- */
function yp_js_hook() {
    do_action('yp_js_hook');
}


/* ---------------------------------------------------- */
/* Custom Action yp_editor_footer						*/
/* ---------------------------------------------------- */
function yp_editor_footer() {
    do_action('yp_editor_footer');
}


/* ---------------------------------------------------- */
/* Editor Page Markup 									*/
/* ---------------------------------------------------- */
function yp_frame_output() {
    
    // Get protocol        
    $protocol = is_ssl() ? 'https' : 'http';
    $protocol = $protocol . '://';

    // Fix WooCommerce shop page bug
    if (class_exists('WooCommerce')) {
        
        $currentID = 0;
        $href = '';
        
        // ID
        $currentID = intval($_GET['yp_page_id']);
        
        // href
        $href = $_GET['href'];
        $type = $_GET['yp_page_type'];
        $yp_mode = $_GET['yp_mode'];
        
        // get shop id
        $shopID = wc_get_page_id('shop');

        // If current id is shop
        if ($currentID == $shopID && $type != "shop") {
            
            // Redirect
            wp_safe_redirect(admin_url('admin.php?page=yellow-pencil-editor&href=' . yp_urlencode(get_post_type_archive_link("product")) . '&yp_page_id=' . $shopID . '&yp_page_type=shop&yp_mode='.$yp_mode));
            
        }
        
    }
    
    // Editor Markup
    include(WT_PLUGIN_DIR . 'editor.php');
    
    exit;
    
}



/* ---------------------------------------------------- */
/* Adding link to plugins page 							*/
/* ---------------------------------------------------- */
add_filter('plugin_row_meta', 'yp_plugin_links', 10, 2);
    
function yp_plugin_links($links, $file) {
        
    if ($file == plugin_basename(dirname(__FILE__) . '/yellow-pencil.php')) {
        $links[] = '<a href="https://yellowpencil.waspthemes.com/documentation/">Documentation</a>';
    }
        
    return $links;
        
}


/* ---------------------------------------------------- */
/* Ading Prefix to CSS selectors for global export		*/
/* ---------------------------------------------------- */
function yp_add_prefix_to_css_selectors($css, $prefix) {
    
    # Wipe all block comments
    $css = preg_replace('!/\*.*?\*/!s', '', $css);
    
    $parts             = explode('}', $css);
    $mediaQueryStarted = false;
    
    foreach ($parts as &$part) {
        $part = trim($part); # Wht not trim immediately .. ?
        
        if (empty($part)) {
            continue;
        } else { # This else is also required
            
            $partDetails = explode('{', $part);
            
            if (substr_count($part, "{") == 2) {
                $mediaQuery        = $partDetails[0] . "{";
                $partDetails[0]    = $partDetails[1];
                $mediaQueryStarted = true;
            }
            
            $subParts = explode(',', $partDetails[0]);
            
            foreach ($subParts as &$subPart) {
                if (strstr(trim($subPart), "@") || strstr(trim($subPart), "%")) {
                    continue;
                } else {
                    
                    // Selector
                    $subPart = trim($subPart);
                    
                    // Array
                    $subPartArray = explode(" ", $subPart);
                    $lov          = strtolower($subPart);
                    
                    $lovMach = str_replace("-", "US7XZX", $lov);
                    $lovMach = str_replace("_", "TN9YTX", $lovMach);
                    
                    preg_match_all("/\bbody\b/i", $lovMach, $bodyAll);
                    preg_match_all("/#body\b/i", $lovMach, $bodySlash);
                    preg_match_all("/\.body\b/i", $lovMach, $bodyDot);
                    
                    preg_match_all("/\bhtml\b/i", $lovMach, $htmlAll);
                    preg_match_all("/#html\b/i", $lovMach, $htmlSlash);
                    preg_match_all("/\.html\b/i", $lovMach, $htmlDot);
                    
                    // Get index of "body" term.
                    if (preg_match("/\bbody\b/i", $lovMach) && count($bodyAll[0]) > (count($bodyDot[0]) + count($bodySlash[0]))) {
                        
                        $i     = 0;
                        $index = 0;
                        foreach ($subPartArray as $term) {
                            $term = trim(strtolower($term));
                            if ($term == 'body' || preg_match("/^body\./i", $term) || preg_match("/^body\#/i", $term) || preg_match("/^body\[/i", $term)) {
                                $index = $i;
                                break;
                            }
                            $i++;
                        }
                        
                        // Adding prefix class to Body
                        $subPartArray[$index] = $subPartArray[$index] . $prefix;
                        
                        // Update Selector
                        $subPart = implode(" ", $subPartArray);
                        
                    } else if (preg_match("/\bhtml\b/i", $lovMach) && count($HtmlAll[0]) > (count($htmlDot[0]) + count($htmlSlash[0]))) {
                        
                        $i     = 0;
                        $index = 0;
                        foreach ($subPartArray as $term) {
                            $term = trim(strtolower($term));
                            if ($term == 'html' || preg_match("/^html\./i", $term) || preg_match("/^html\#/i", $term) || preg_match("/^html\[/i", $term)) {
                                $index = $i;
                                break;
                            }
                            $i++;
                        }
                        
                        // Adding prefix class to Body
                        if (count($subPartArray) <= 1) {
                            if ($subPart != 'html' && preg_match("/^html\./i", $subPart) && preg_match("/^html\#/i", $subPart) && preg_match("/^html\[/i", $subPart)) {
                                $subPartArray[$index] = $subPartArray[$index] . " body" . $prefix;
                            }
                        } else {
                            $subPartArray[$index] = $subPartArray[$index] . " body" . $prefix;
                        }
                        
                        // Update Selector
                        $subPart = implode(" ", $subPartArray);
                        
                    } else {
                        
                        // Adding prefix class to Body
                        $subPartArray[0] = "body" . $prefix . " " . $subPartArray[0];
                        
                        // Update Selector
                        $subPart = implode(" ", $subPartArray);
                        
                    }
                    
                }
            }
            
            if (substr_count($part, "{") == 2) {
                $part = $mediaQuery . "\n" . implode(', ', $subParts) . "{" . $partDetails[2];
            } elseif (empty($part[0]) && $mediaQueryStarted) {
                $mediaQueryStarted = false;
                $part              = implode(', ', $subParts) . "{" . $partDetails[2] . "}\n"; //finish media query
            } else {
                if (isset($partDetails[1])) {
                    # Sometimes, without this check,
                    # there is an error-notice, we don't need that..
                    $part = implode(', ', $subParts) . "{" . $partDetails[1];
                }
            }
            
            unset($partDetails, $mediaQuery, $subParts); # Kill those three..
            
        }
        unset($part); # Kill this one as well
    }
    
    // Delete spaces
    $output = preg_replace('/\s+/', ' ', implode("} ", $parts));
    
    // Delete all other spaces
    $output = str_replace("{ ", "{", $output);
    $output = str_replace(" {", "{", $output);
    $output = str_replace("} ", "}", $output);
    $output = str_replace("; ", ";", $output);
    
    // Beatifull >
    $output = str_replace("{", "{\n\t", $output);
    $output = str_replace("}", "\n}\n\n", $output);
    $output = str_replace("}\n\n\n", "}\n\n", $output);
    $output = str_replace("){", "){\n", $output);
    $output = str_replace(";", ";\n\t", $output);
    $output = str_replace("\t\n}", "}", $output);
    $output = str_replace("}\n\n}", "\t}\n\n}\n\n", $output);
    
    
    # Finish with the whole new prefixed string/file in one line
    return (trim($output));
    
}



/* --------------------------------------------------------- */
/* Encoding & Decoding the data; Used for import and export  */
/* --------------------------------------------------------- */
function yp_encode($value) {
    $func = 'base64' . '_encode';
    return $func($value);
}

function yp_decode($value) {
    $func = 'base64' . '_decode';
    return $func($value);
}



/* ---------------------------------------------------- */
/* Getting All plugin options by prefix					*/
/* ---------------------------------------------------- */
function yp_get_all_options($prefix = '', $en = false) {
    
    global $wpdb;
    $ret     = array();
    $options = $wpdb->get_results($wpdb->prepare("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $prefix . '%'), ARRAY_A);
    
    if (!empty($options)) {
        foreach ($options as $v) {
            if (strstr($v['option_name'], 'wt_theme') == false && strstr($v['option_name'], 'wt_available_version') == false && strstr($v['option_name'], 'wt_last_check_version') == false) {
                if ($en == true) {
                    $ret[$v['option_name']] = yp_encode(yp_stripslashes($v['option_value']));
                } else {
                    $ret[$v['option_name']] = yp_stripslashes($v['option_value']);
                }
            }
        }
    }
    
    return (!empty($ret)) ? $ret : false;
    
}



/* ---------------------------------------------------- */
/* Getting All post meta data by prefix					*/
/* ---------------------------------------------------- */
function yp_get_all_post_options($prefix = '', $en = false) {
    
    global $wpdb;
    $ret     = array();
    $options = $wpdb->get_results($wpdb->prepare("SELECT post_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $prefix . '%'), ARRAY_A);
    
    if (!empty($options)) {
        foreach ($options as $v) {
            if ($en == true) {
                $ret[$v['post_id'] . "." . $v['meta_key']] = yp_encode(yp_stripslashes($v['meta_value']));
            } else {
                $ret[$v['post_id'] . "." . $v['meta_key']] = yp_stripslashes($v['meta_value']);
            }
        }
    }
    
    return (!empty($ret)) ? $ret : false;
    
}



/* ---------------------------------------------------- */
/* Creating a json data for export data					*/
/* ---------------------------------------------------- */
function yp_get_export_data() {
    
    $allData       = array();
    $postmeta_CSS  = yp_get_all_post_options('_wt_css', true);
    $postmeta_HTML = yp_get_all_post_options('_wt_styles', true);
    $option_Data   = yp_get_all_options('wt_', true);
    $option_Anims  = yp_get_all_options('yp_anim', true);

    // @Ver 7.0.7
    $option_Output  = yp_get_all_options('yp-output-option', true); // output option
    $option_Comments  = yp_get_all_options('yp_selector_comments', true); // selector comments
    $option_Option  = yp_get_all_options('yp_op_', true); // Plugin options
    
    if (is_array($postmeta_CSS)) {
        array_push($allData, $postmeta_CSS);
    }
    
    if (is_array($postmeta_HTML)) {
        array_push($allData, $postmeta_HTML);
    }
    
    if (is_array($option_Data)) {
        array_push($allData, $option_Data);
    }
    
    if (is_array($option_Anims)) {
        array_push($allData, $option_Anims);
    }

    // @Ver 7.0.7
    if (is_array($option_Output)) {
        array_push($allData, $option_Output);
    }

    if (is_array($option_Comments)) {
        array_push($allData, $option_Comments);
    }

    if (is_array($option_Option)) {
        array_push($allData, $option_Option);
    }
    
    if (empty($allData) == false) {
        $data     = array_values($allData);
        $jsonData = json_encode($data);
        return $jsonData;
    }
    
    return false;
    
}



/* ---------------------------------------------------- */
/* Generate All CSS styles as ready-to-use				*/
/* ---------------------------------------------------- */
/* $method = 'export' / 'create' (string)				*/
/* ---------------------------------------------------- */
function yp_get_export_css($method) {
    
    // Array
    $allData = array();
    
    // Getting all from database
    $postmeta_CSS = yp_get_all_post_options('_wt_css', false);
    $option_Data  = yp_get_all_options('wt_', false);
    $option_Anims = yp_get_all_options('yp_anim', false);
    
    // Push option data to Array
    if (is_array($option_Data)) {
        array_push($allData, $option_Data);
    }
    
    // Push postmeta data to Array
    if (is_array($postmeta_CSS)) {
        array_push($allData, $postmeta_CSS);
    }
    
    // Check if there have animations
    if (is_array($option_Anims)) {
        
        // Push custom animations to Array
        array_push($allData, $option_Anims);
        
        // New Array for webkit prefix
        $option_AnimWebkit = array();
        
        // Copy animations as webkit
        foreach ($option_Anims as $key => $animate) {
            $option_AnimWebkit["Webkit " . $key] = str_replace("@keyframes", "@-webkit-keyframes", $animate);
        }
        
        // Push Animations
        array_push($allData, $option_AnimWebkit);
        
    }
    
    // Be sure The data not empty
    if (empty($allData) == false) {
        
        // Clean array
        $data = array_values($allData);
        
        // Variables
        $output     = null;
        $table      = array();
        $tableIndex = 0;
        $prefix     = '';
        
        // Adding WordPress Page, category etc classes to all CSS Selectors.
        foreach ($data as $nodes) {

            // set necessary order
            $orderArray = array(
                'wt_css' => '',
                'wt_post_css' => '',
                'wt_page_css' => '',
                'wt_search_css' => '',
                'wt_tag_css' => '',
                'wt_category_css' => '',
                'wt_archive_css' => '',
                'wt_author_css' => '',
                'wt_404_css' => '',
            );
            //apply it
            $nodes = array_filter(array_replace($orderArray, $nodes));
            
            foreach ($nodes as $key => $css) {
                $tableIndex++;

                // skip style data options
                if (strstr($key, '_styles')) {
                    continue;
                }

                // Skip admin CSS in custom.css
                if($key == "wt_login_css" || $key == "wt_lostpassword_css" || $key == "wt_register_css"){
                    continue;
                }

                // blog
                $page_for_posts = get_option('page_for_posts');

                // dont add default home style to static home page
                if($key == "wt_home_css" && $page_for_posts != 0){
                    continue;
                }

                // If post meta
                if (strstr($key, '._')) {
                    
                    $keyArray = explode(".", $key);
                    $postID   = $keyArray[0];
                    $type     = get_post_type($postID);
                    $title    = '"' . ucfirst(get_the_title($postID)) . '" ' . ucfirst($type) . '';
                        
                    // Single post types
                    if ($page_for_posts == $postID) {
                        $prefix = '.blog';
                    } elseif ($type == 'page') {
                        $prefix = '.page-id-' . $postID . '';
                    } else {
                        $prefix = '.postid-' . $postID . '';
                    }
                    
                    // not have page-id class in WooCommerce shop page.
                    if (class_exists('WooCommerce')) {
                        $shopID = wc_get_page_id('shop');
                        if ($postID == $shopID) {
                            $prefix = '.post-type-archive-product';
                        }
                    }
                    
                } else {
                    
                    if ($key == 'wt_css') {
                        $title  = 'Global Styles';
                        $prefix = '';
                    } else if ($key == 'wt_author_css') {
                        $title  = 'Author Page';
                        $prefix = '.author';
                    } else if ($key == 'wt_category_css') {
                        $title  = 'Category Page';
                        $prefix = '.category';
                    } else if ($key == 'wt_tag_css') {
                        $title  = 'Tag Page';
                        $prefix = '.tag';
                    } else if ($key == 'wt_404_css') {
                        $title  = '404 Error Page';
                        $prefix = '.error404';
                    } else if ($key == 'wt_search_css') {
                        $title  = 'Search Page';
                        $prefix = '.search';
                    } else if ($key == 'wt_home_css') {
                        $title  = 'Non-Static Homepage';
                        $prefix = '.home';
                    } else if ($key == 'wt_archive_css') {
                        $title  = 'Archive Page';
                        $prefix = '.archive';
                    }
                        
                    // If anim
                    else if (strstr($key, 'yp_anim')) {
                        $title = str_replace("yp_anim_", "", $key);
                        $title = $title . " Animate";

                    // if post type
                    } else if (strstr($key, 'wt_') && strstr($key, '_css')) {

                        $title = str_replace("wt_", "", $key);
                        $title = str_replace("_css", "", $title);
                        
                        if (strtolower($title) == 'page'){
                            $prefix = '.page:not(.home)';
                        } else if (strtolower($title) == 'shop'){
                            $prefix = '.post-type-archive-product';
                        } else {
                            $prefix = '.single-' . strtolower($title) . '';
                        }
                        
                        $title = $title . " Template";

                    }
                    
                }
                
                
                $len   = 48 - (strlen($title) + 2);
                $extra = null;
                    
                for ($i = 1; $i < $len; $i++) {
                    $extra .= ' ';
                }
                    
                array_push($table, ucfirst($title));
                $output .= "/*-----------------------------------------------*/\r\n";
                $output .= "/*  " . ucfirst($title) . "" . $extra . "*/\r\n";
                $output .= "/*-----------------------------------------------*/\r\n";
                $output .= yp_add_prefix_to_css_selectors($css, $prefix) . "\r\n\r\n\r\n\r\n";
                
            }
            
        }
        // Foreach end.
        
        
        // Create a table list for CSS codes
        $tableList   = null;
        $plusNumber  = 1;
        $googleFonts = array();
        
        // Get fonts from CSS output
        if ($method == 'export') {
            $googleFonts = yp_get_font_families($output, 'import');
        }
        
        // If has any Google Font; Add Font familes to first table list.
        if (count($googleFonts) > 0) {
            $tableList  = "    01. Font Families\r\n";
            $plusNumber = 2;
        }
        
        // Creating a table list.
        foreach ($table as $key => $value) {
            $tableList .= "    " . sprintf("%02d", $key + $plusNumber) . ". " . $value . "\r\n";
        }
        
        
        // Google Fonts
        if (count($googleFonts) > 0 && is_array($googleFonts)) {
            $FontsCSS = "/*-----------------------------------------------*/\r\n";
            $FontsCSS .= "/* Font Families                                 */\r\n";
            $FontsCSS .= "/*-----------------------------------------------*/\r\n";
            
            foreach ($googleFonts as $fontURL) {
                $FontsCSS .= "@import url('" . $fontURL . "');\r\n";
            }
            
            $FontsCSS .= "\r\n\r\n\r\n";
        }
        
        
        // All in.
        $allOutPut = "/*\r\n\r\n    These CSS codes generated by YellowPencil Editor.\r\n";
        $allOutPut .= "    https://yellowpencil.waspthemes.com\r\n\r\n\r\n";
        $allOutPut .= "    T A B L E   O F   C O N T E N T S\r\n";
        $allOutPut .= "    ........................................................................\r\n\r\n";
        $allOutPut .= $tableList;
        $allOutPut .= "\r\n*/\r\n\r\n\r\n\r\n";
        
        // Adding Google Fonts to OutPut.
        if (count($googleFonts) > 0) {
            $allOutPut .= $FontsCSS;
        }
        
        // Adding all CSS codues
        $allOutPut .= $output;
        
        // Process with some PHP functions and return Output CSS code.
        if ($method == 'export') {
            return yp_auto_prefix(yp_export_animation_prefix(trim($allOutPut)));
        } else {
            return yp_auto_prefix(trim($allOutPut));
        }
        
    }
    
}


/* ---------------------------------------------------- */
/* stripslashes data                                    */
/* ---------------------------------------------------- */
function yp_stripslashes($v){

    $v = preg_replace("/\\\\\\\\\\\(@|\.|\/|!|\*|#|\?|\+)/i", "\\\\$1", $v); // multiple \\\\
    $v = preg_replace("/\\\\\\\(@|\.|\/|!|\*|#|\?|\+)/i", "\\\\$1", $v); // multiple \\
    $v = preg_replace("/\\\\(@|\.|\/|!|\*|#|\?|\+)/i", "TP09BX$1", $v);
    $v = stripslashes($v);
    $v = preg_replace("/(TP09BX)/i", "\\", $v);

    return $v;

}


/* ---------------------------------------------------- */
/* Import Plugin data                                   */
/* ---------------------------------------------------- */
function yp_import_data($json) {
    
    $json = yp_stripslashes($json);
    
    if (empty($json)) {
        return false;
    }
    
    $array = json_decode($json, true);
    
    foreach ($array as $nodes) {
        
        foreach ($nodes as $key => $value) {
            
            $value = yp_decode($value);
            
            // If post meta
            if (strstr($key, '._')) {
                
                $keyArray = explode(".", $key);
                $postID   = $keyArray[0];
                $metaKey  = $keyArray[1];
                
                if (!add_post_meta($postID, $metaKey, $value, true)) {
                    update_post_meta($postID, $metaKey, $value);
                }
                
            } else { // else option
                if (!update_option($key, $value)) {
                    add_option($key, $value);
                }
            }
            
        }
        
    }
    
}



/* ---------------------------------------------------- */
/* Export CSS as style.css 	 							*/
/* ---------------------------------------------------- */
function yp_exportCSS_admin_header() {
    
    if (current_user_can("export") && isset($_GET['yp_exportCSS']) && check_admin_referer("yp_export_nonce")) {
            
        $data = yp_get_export_css("export");
            
        header('Content-Disposition: attachment; filename="style-' . strtolower(date("M-d")) . '.css"');
        header("Content-type: text/css; charset: UTF-8");
        header('Content-Length: ' . strlen($data));
        header('Connection: close');
            
        echo $data;
            
        die();
        
    }
    
}

add_action("admin_init", "yp_exportCSS_admin_header", 999999999);


// @WaspThemes.
// Coded With Love..
