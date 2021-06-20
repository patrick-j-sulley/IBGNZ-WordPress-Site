<?php
/*
*Plugin Name: Ultimate WooCommerce Auction Plugin
*Text Domain: woo_ua
* WC requires at least: 3.0.0
* WC tested up to: 3.6.0
	* Basic plugin definitions
	* 
	* @package Ultimate WooCommerce Auction
	* @author Nitesh Singh 
	* @since 1.0
	*/
		
		$blog_plugins = get_option( 'active_plugins', array() );
		$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option('active_sitewide_plugins' ) ) : array();
		
		if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
			
			  do_action( 'uwa_auction_init' );			
		} else {			
			add_action( 'admin_notices', 'uwa_install_woocommerce_admin_notice' );
		}
		
			</div>
