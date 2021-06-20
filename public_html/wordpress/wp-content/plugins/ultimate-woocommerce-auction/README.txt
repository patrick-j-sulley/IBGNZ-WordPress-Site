=== Ultimate WooCommerce Auction Plugin ===
Contributors: nitesh_singh
Tags: woocommerce auction, woocommerce auction plugin, woocommerce auction theme, woocommerce bidding
Requires at least: 4.6
Tested up to: 5.1
Stable tag: trunk
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Awesome plugin to host auctions on your WooCommerce powered site and sell your products as auctions.

== Description ==

Ultimate WooCommerces Auction plugin allows easy and quick way to add your products as auctions on your site.
Simple and flexible, Lots of features, very configurable.  Easy to setup.  Great support.

*   [PRO version Coming Soon - Subscribe here!! &raquo;](http://auctionplugin.net/)

 
 = PRO Features =

	1. Users can add auctions
	2. Works with popular WC based Vendor plugins
	3. Automatic Time Extension to AVOID SNIPPING
	4. Enable Proxy Bidding for users
	5. Add Silent auctions
	6. Schedule auctions for future date
	7. Relist expired auctions
	8. Live Bidding update
	9. Manage Auction - End and Relist
	10. Support Virtual Products
	11. Delete User Bids
	12. Widgets - Expired, Future etc.
	13. Custom Emails
	14. Many Shortcodes & Filters 
	
= Free Features =

    1. Registered User can place bids 
	2. Ajax Admin panel for better management.
    3. Add standard auctions for bidding
    4. Buy Now option    
    5. Show auctions in your timezone        
    6. Set Reserve price for your product
	7. Set Bid incremental value for auctions
	8. Ability to edit, delete & end live auctions
	9. Re-activate Expired auctions
	10. Email notifications to bidders for placing bids
    11. Email notification to Admin for all activity
    12. Email Sent for Payment Alerts
	13. Outbid Email sent to all bidders who has been outbid.
	14. Count Down Timer for auctions.	
	15. Ability to Cancel last bid 
    and Much more...

== Installation ==
= Minimum Requirements =

* PHP version 5.2.4 or greater (PHP 7.2 or greater is recommended)
* MySQL version 5.0 or greater (MySQL 5.6 or greater is recommended)
* [Latest WooCommerce Plugin](https://wordpress.org/plugins/woocommerce)

= Automatic installation =

1. To do an automatic install of our plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.


2. In the search field type “WooCommerce Auction” and click Search Plugins. Once you’ve found "Ultimate WooCommerce Auction Plugin" by "Nitesh Singh", you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”. 


3. Once installed, simply click "Activate".


4.  After you have setup WooCommerce and activated our plugin, you should add a product. You can do it via Wordpress Dashboard, navigate to the Products menu and click Add New.


5. 	While adding product, choose "product data = Auction Product". Add data to relevant fields and publish it. 

Your auction product should now be ready and displayed under "Shop" page. If you have problems please open discussion in support forum.

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation). Kindly follow these instructions for [WooCommerce Plugin](https://wordpress.org/plugins/woocommerce) and then for our plugin.

Then kindly follow step 4 and 5 from "Automatic Installation". 

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

If on the off-chance you do encounter issues with the shop/category pages after an update you simply need to flush the permalinks by going to WordPress > Settings > Permalinks and hitting 'save'. That should return things to normal.

== Frequently Asked Questions ==

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the [Ultimate WooCommerce Auction Plugin Forum](https://wordpress.org/support/plugin/ultimate-woocommerce-auction/).

= Will this plugin work with my theme? =

Yes; It will work with any WooCommerce supported theme, but may require some styling to make it match nicely. 

= Where can I request new features, eCommerce themes and extensions? =

You can write to us: nitesh@auctionplugin.net. 

= What shortcodes are available for this plugin = 
1. Shortcode to display new auctions.

	[uwa_new_auctions days_when_added="10" columns="4" orderby="date" order="desc/asc" show_expired="yes/no"]

Field details:

days_when_added = Only display 10 days auctions on creation date..
columns = Default woocommerce
orderby = Default woocommerce
order = Default woocommerce
show_expired = Default is yes. if we select "no"  then expired auction will not be displayed.

= What Hooks are available for this plugin = 
1) If you are going to add some custom text before "Bidding Form",this hook should help you. 

	ultimate_woocommerce_auction_before_bid_form
 
Example of usage this hook
 
	add_action( 'ultimate_woocommerce_auction_before_bid_form', 'here_your_function_name');
	function here_your_function_name() 
	{   
		echo 'Some custom text here';   
	}

2) If you are going to add some custom text after "Bidding Form",this hook should help you. 

	ultimate_woocommerce_auction_after_bid_form

Example of usage this hook
 
	add_action( 'ultimate_woocommerce_auction_after_bid_form', 'here_your_function_name');
		function here_your_function_name() {   
			echo 'Some custom text here';   
		}

3) If you are going to add some custom text before "Bidding Button",this hook should help you. 

	ultimate_woocommerce_auction_before_bid_button
 
Example of usage this hook
 
	add_action( 'ultimate_woocommerce_auction_before_bid_button', 'here_your_function_name');
		function here_your_function_name() {   
			echo 'Some custom text here';   
		}

4) If you are going to add some custom text after "Bidding Button",this hook should help you. 

	ultimate_woocommerce_auction_after_bid_button
Example of usage this hook
 
		add_action( 'ultimate_woocommerce_auction_after_bid_button', 'here_your_function_name');
			function here_your_function_name() {   
				echo 'Some custom text here';   
			}

5)You can use this hook while auction is closing

	ultimate_woocommerce_auction_close


		add_action( 'ultimate_woocommerce_auction_close', 'here_your_function_name', 50 );
			function here_your_function_name($auction_id) {
   
				$product = wc_get_product($auction_id);
  
				//Your Custom Code here
   
			}

6) You can use this hook while admin deletes bid.

	ultimate_woocommerce_auction_delete_bid



		add_action( 'ultimate_woocommerce_auction_delete_bid', 'here_your_function_name', 50 );
			function here_your_function_name($auction_id) {
   
				$product = wc_get_product($auction_id);
  
				//Your Custom Code here
   
			}

= What Filters are available for this plugin = 

1) Product Condition

	ultimate_woocommerce_auction_product_condition

How to use this filter ?

Copy paste in your functions.php file of theme as per your requirement.

	add_filter('ultimate_woocommerce_auction_product_condition', 'here_your_function_name' );
		function here_your_function_name( $array ){
			/*
			 Exiting array keys. 1)new 2)used
			*/
			$array['new']='New2';
			$array['used']='Used2';
			
			/*
			 You can Add New Condition to Auction Product Like below.
			*/
			$arr2 = array('new_factory' => 'Factory Sealed Packaging');
			$arr3 = array('vintage' => 'Vintage');
			$arr4 = array('appears_new' => 'Appears New');
			$array = $array + $arr2 + $arr3 + $arr4;

			return $array;
		} 


2) Bid Button Text

	ultimate_woocommerce_auction_bid_button_text

How to use this filter ?
Answer : Copy paste in your functions.php file of theme as per your requirement.

	add_filter('ultimate_woocommerce_auction_bid_button_text', 'here_your_function_name' );
	function here_your_function_name(){

		 return __('Button Text', 'woo_ua');
	} 
-----------------------------------------------------------------
3) Heading for Total Bids

	ultimate_woocommerce_auction_total_bids_heading

How to use this filter ?

Answer: Copy paste in your functions.php file of theme as per your requirement.
	
	add_filter('ultimate_woocommerce_auction_total_bids_heading', 'here_your_function_name1' );
	function here_your_function_name1(){

		 return __('Total Bids Placed:', 'woo_ua');
	} 
-----------------------------------------------------------------
4) Pay Now Button 

	ultimate_woocommerce_auction_pay_now_button_text

How to use this filter ?

Answer : Copy paste in your functions.php file of theme as per your requirement.

	add_filter('ultimate_woocommerce_auction_pay_now_button_text', 'here_your_function_name' );
	function here_your_function_name(){

		return __('Pay Now Text', 'woo_ua');
	} 
-----------------------------------------------------------------

== Screenshots ==

1. Admin: Create auction product
2. Admin: Create auction product with data
3. Admin: Main Plugin Settings
4. Admin: Live Listing
5. Admin: Expired Listing
6. Frontend: Shop Page
7. Frontend: Single product page example


== Changelog ==
= 2.0.5 = 

1. New Feature - Added two new HTML CLASSES which developers can use for CSS. 
Expired Auction class = uwa_auction_status_expired
Live Auction class = uwa_auction_status_live

2. New Feature - WooCommerce compatibility versions have been added inside our plugin description to show it inside Admin Panel -> Installed plugins

3. Fix - For expired auctions, if we were browsing to Admin -> WC -> Products page and editing the end date to future date for a specific product then it wont change state of auction to Live. We have now fixed this problem by disabling the end date for editing.


= 2.0.4 = 

1. Fix - Plugin will display message to login/register when any non-logged in visitor tries to place bid. In few themes we received queries that "Login/register" message was not being displayed when non-logged in visitor tries to place bid. This problem is now fixed.

2. Fix - Description tab will be displayed in 1st position of auction detail page.

3. Fix - Shop Manger Role was not able to see user's full name. This issue has been fixed.

4. Fix - GOTMLS.NET was detecting malware in 2 files due to code commenting style (/* ...*/). We have changed the commenting style just for the sake of no detection as we think its a false positive.

5. New Feature - Added a new filter "ultimate_woocommerce_auction_pay_now_button_text". Details of its usage are mentioned in FAQs.


= 2.0.3 = 

1. New Feature - Added hooks & filters. Full documentation available in README FAQ section.


= 2.0.2 = 

1. New Feature - Plugin now allows to add your "buy now" and "won by bids" item to checkout page. So, if a user has added simple products to his cart and also won products via auction then all his products will be added to his checkout page.

2. New Feature - New Shortcode to display "Latest Auction". Shortcode format is [uwa_new_auctions days_when_added="10" columns="4" orderby="date" order="desc/asc" show_expired="yes/no"].

3. New Feature - User can hide their names from bidding page. User can go to their My Account Page -> Auction Setting to access this setting.

4. Fix - Bid Value field now accepts amount in same currency format as defined in WooCommerce.

= 2.0.1 =

1. Fix - Decimal pricing is now supported for auction products. Also, WooCommerce normal products will have decimal pricing.


= 2.0.0 = 

1. New Feature - Plugin has a new layout and is accessible from WP Admin -> LHS bar -> Ultimate Woo Auction. It has Settings page and auctions list which shows live and expired auctions.

2. Fix - My Auction / My Auctions Watchlist are now added to plugin's text domain i.e. woo_ua. Previously they were in "woocommerce" text domain.

= 1.0.6 = 

1. Fix - Customer noticed an issue that when we translate using WPML then Viewing Auction Watchlist slug does not change. This has been fixed.

= 1.0.5 = 

1. Fix - End Date Issue where date picker was not working with latest WP has been fixed.


= 1.0.4 =

1. New Feature - Layout for Settings page has been changed for better readability and more configurations have been added.

2. Fix - Configuration for Ajax update of bid information was not working previously. This has now been fixed.

= 1.0.3 =

1. New Feature - New column "Auction Status" added under Products -> All Products. This shows whether auction is "Live" or "Expired".

2. Fix - Edition in bidding logic where in user can now increase their bid. Previously if any user had highest bid then he was not able to increase his bid. This modification will help user to reach reserve price. 

3. Fix - "Add to Watchlist" was not working. This has now been fixed.


= 1.0.2 =

* Fix: Text Domain added  which will enable to work with LocoTranslate.

= 1.0.1 =

* Fix: Bid field's width has been increased to handle 9 digits in auction detail page.
* Fix: Plugin's settings are now consolidated under single link.
* Fix: Minor design changes has been done in Auction Detail page for better representation of data.

= 1.0 =
Initial Release