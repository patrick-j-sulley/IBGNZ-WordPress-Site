<?php
/*
*Plugin Name: Ultimate WooCommerce Auction Plugin*Plugin URI: http://auctionplugin.net*Description: Awesome plugin to host auctions with WooCommerce on your wordpress site and sell anything you want.*Author: Nitesh Singh*Version: 2.0.5
*Text Domain: woo_ua*Domain Path: languages*License: GPLv2*Copyright 2018 Nitesh Singh
* WC requires at least: 3.0.0
* WC tested up to: 3.6.0*/if ( ! defined( 'ABSPATH' ) ) {    exit;} // Exit if accessed directly	/**
	* Basic plugin definitions
	* 
	* @package Ultimate WooCommerce Auction
	* @author Nitesh Singh 
	* @since 1.0
	*/	if( !defined( 'WOO_UA_VERSION' ) ) {		define( 'WOO_UA_VERSION', '2.0.5' ); // plugin version	}	if( !defined( 'WOO_UA_DIR' ) ) {		define( 'WOO_UA_DIR', dirname( __FILE__ ) ); // plugin dir	}	if( !defined( 'WOO_UA_Main_File' ) ) {		define( 'WOO_UA_Main_File',WOO_UA_DIR.'/ultimate-woocommerce-auction.php' ); // plugin dir	}	if( !defined( 'WOO_UA_URL' ) ) {		define( 'WOO_UA_URL', plugin_dir_url( __FILE__ ) ); // plugin url	}	if( !defined( 'WOO_UA_ASSETS_URL' ) ) {		define( 'WOO_UA_ASSETS_URL', WOO_UA_URL . 'assets/' ); // plugin url	}	if( !defined( 'WOO_UA_ADMIN' ) ) {		define( 'WOO_UA_ADMIN', WOO_UA_DIR . '/includes/admin' ); // plugin admin dir	}	if( !defined( 'WOO_UA_PLUGIN_BASENAME' ) ) {		define( 'WOO_UA_PLUGIN_BASENAME', basename( WOO_UA_DIR ) ); // plugin base name		}	if( !defined( 'WOO_UA_TEMPLATE' ) ) {		define( 'WOO_UA_TEMPLATE', WOO_UA_DIR . '/templates/' ); // plugin admin dir	}	if( !defined( 'WOO_UA_WC_TEMPLATE' ) ) {		define( 'WOO_UA_WC_TEMPLATE', WOO_UA_DIR . '/templates/woocommerce/' ); // plugin admin dir	}	if( !defined( 'WOO_UA_POST_TYPE' ) ) {		define( 'WOO_UA_POST_TYPE', 'product' ); // plugin base name	}	if( !defined( 'WOO_UA_PRODUCT_TYPE' ) ) {		define( 'WOO_UA_PRODUCT_TYPE', 'auction' ); // plugin base name	}	/**	* Check if WooCommerce is activated	* @package Ultimate WooCommerce Auction	* @author Nitesh Singh 	* @since 1.0 	*/	add_action( 'plugins_loaded', 'uwa_check_wc_activation');		function uwa_check_wc_activation() {
		
		$blog_plugins = get_option( 'active_plugins', array() );
		$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option('active_sitewide_plugins' ) ) : array();
		
		if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
			
			  do_action( 'uwa_auction_init' );			
		} else {			
			add_action( 'admin_notices', 'uwa_install_woocommerce_admin_notice' );
		}
			}	/**	* Print an admin notice if WooCommerce is deactivated	*	* @package Ultimate WooCommerce Auction	* @author Nitesh Singh 	* @since 1.0 	*/	if( ! function_exists( 'uwa_install_woocommerce_admin_notice' ) ) {			 function uwa_install_woocommerce_admin_notice() { ?>			<div class="error">				<p>Ultimate WooCommerce Auction <?php _e( 'is enabled but not effective. It requires <a href="'.admin_url('plugin-install.php?s=WooCommerce&tab=search&type=term').'">WooCommerce</a> in order to work.', 'woo_ua'); ?></p>	
			</div>			<?php					}	}	/**	* Include Plugin Main Functions File.	*	* @package Ultimate WooCommerce Auction	* @author Nitesh Singh 	* @since 1.0 	*/	add_action( 'uwa_auction_init', 'uwa_auction_init' );		function uwa_auction_init() { 
		require_once ( WOO_UA_DIR . '/includes/uwa-main-functions.php' );		 	}	//Include Auction Scheduler file for cron job	require_once ( WOO_UA_DIR . '/includes/action-scheduler/action-scheduler.php' );