<?php
/*
 * Plugin Name: Footer Putter
 * Plugin URI: https://www.diywebmastery.com/plugins/footer-putter/
 * Description: Put a footer on your site that boosts your credibility with both search engines and human visitors.
 * Version: 1.16
 * Author: Russell Jamieson
 * Author URI: https://www.diywebmastery.com/about/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */
define('FOOTER_PUTTER_VERSION','1.16');
define('FOOTER_PUTTER_NAME', 'Footer Putter') ;
define('FOOTER_PUTTER_SLUG', plugin_basename(dirname(__FILE__))) ;
define('FOOTER_PUTTER_PATH', FOOTER_PUTTER_SLUG.'/main.php');
define('FOOTER_PUTTER_ICON','dashicons-arrow-down-alt');
define('FOOTER_PUTTER_DOMAIN', 'FOOTER_PUTTER_DOMAIN') ;
define('FOOTER_PUTTER_HOME','https://www.diywebmastery.com/plugins/footer-putter/');
define('FOOTER_PUTTER_HELP','https://www.diywebmastery.com/help/');

if (!defined('DIYWEBMASTERY_NEWS')) define('DIYWEBMASTERY_NEWS', 'https://www.diywebmastery.com/tags/newsfeed/feed/?images=1&featured_only=1');
require_once(dirname(__FILE__) . '/classes/class-plugin.php');
Footer_Putter_Plugin::get_instance();
