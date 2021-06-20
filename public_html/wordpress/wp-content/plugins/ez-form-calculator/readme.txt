=== EZ Form Calculator ===
Contributors: keksdieb
Donate link: http://ez-form-calculator.ezplugins.de/
Tags: calculator, customer, form, generator, marketing, form builder, form generator, price calculator, price, form calculator, quote generator, quote calculator
Requires at least: 4.7
Tested up to: 5.1.1
Stable tag: 2.11.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

ez Form Calculator is a WordPress premium form-generating plugin. You can simply create a form calculator for both yourself and your customers.

== Description ==

ez Form Calculator is a WordPress premium form-generating plugin. You can simply create a form calculator for both yourself and your customers. Easily add basic form elements like checkboxes, dropdown menus, radio buttons etc. with only a few clicks. Each form element can be assigned a value which will automatically be calculated.

= Live Demo =
[Frontend](http://ez-form-calculator.ezplugins.de)

= Features =
* Generate basic forms with values
* Plugin automatically calculates form values
* Easily add form elements with just one click
* 4 form elements
* Drag and drop elements to change their positions
* Backend verification for increased security
* Translation ready (e.g. WPML)

= Premium Features =
* Unlimited Forms for one site
* 30+ different Elements
* Unlimited Elements per form
* Unlimited file uploads
* 18 predefined templates (and counting)
* No watermarks
* PDF file generation
* PayPal integration
* WooCommerce integration
* Mailchimp integration
* Email support
* ... and many more features!

= A few examples =
* Media agencies: customers can submit their needs (like logo creation, websites etc.) directly.
* Photography studios: portraits, outdoor or indoor shooting – let your customers decide online.
* Event managers: save time by adding basic form elements your customers can choose from.
* Freight costs: users can determine their freight costs without searching through an endless table.

And many more! Increase your customers satisfaction with ez Form Calculator.

== Installation ==

First, log in to your WordPress admin panel and head over to the Plugins section. Click on “Install” » “Upload” and select the downloaded zip-file to install.

After the installation was successful, you should see a new section labeled “EZ Form Calculator” in the control panel.

More documentation at http://ez-form-calculator.ezplugins.de/documentation/

== Frequently Asked Questions ==
http://ez-form-calculator.ezplugins.de/documentation/faq/

== Screenshots ==
1. Frontend form
2. Backend form
3. Email notification (premium only)

== Changelog ==

= Version 2.11.2.1 =
- Updated to the latest version of the premium plugin with lots of new features (some remain premium only)

= Version 2.9.6.1 =
- Added: email font can be changed (global settings)
- Added: HTML elements can be shown as HTML or HTML code in emails (global settings)
- Added: option to automatically hide required text tooltips
- Added: filters for email header/footer (ezfc_email_header, ezfc_email_header_after, ezfc_email_footer_before, ezfc_email_footer)
- Fixed: backend calculation fix when using comma as decimal point
- Fixed: preselect values for checkbox/radio elements fixed
- Fixed: shortcode can be used for HTML elements in emails
- Fixed: tinyMCE editor in form options is now disabled when deactivated in the global options

= Version 2.9.5.5 =
- Fixed: factors were sometimes calculated incorrectly
- Fixed: form placeholders can be used in email subjects
- Fixed: "calculate_when_hidden" had no effect when element was positioned in a hidden group
- Fixed: spinner / slider function for numbers didn't work properly when using comma as decimal point
- Fixed: groups can be activated/deactivated conditionally

= Version 2.9.5.4 =
- Added: add elements from template to current form
- Fixed: subtotal calculation fix when using comma as decimal point
- Fixed: PayPal email text is now used correctly
- Fixed: PDF directory wasn't saved in the options when it wasn't successful after first try. Check "manual update" in the global settings to update the PDF directory (this is not guaranteed to work since some server environments prohibit the plugin to create directories)
- Fixed: redirect timer fix (labelled as seconds but were calculated in milliseconds)
- Fixed: submitting forms by pressing enter in last step caused the form to both submit and move to previous step
- Removed: unnecessary pdf fonts to reduce file size

= Version 2.9.5.3 =
- Added: add linebreaks to HTML elements
- Added: add_to_price option for Subtotal elements (e.g. show price only but do not add calculated price to the total)
- Added: decimal point in input values will now be detected automatically (depending on the selected decimal point in the global settings)
- Added: max length for input / textarea elements
- Added: star rating element
- Changed: form option "Add linebreaks" changed from nl2br to wpautop in email texts
- Fixed: form was sometimes prevented from calculation when using multi conditionals
- Fixed: PDF files were only attached when the form option "Send PDF to admin" was enabled
- Fixed: WooCommerce didn't calculate the correct price with certain cart items

= Version 2.9.5.2 =
- Added: invoice ID generation
- Added: max submission value
- Improved: better UI when using large data editor
- Fixed: pdf attachments weren't sent to customer when no admin email address was set
- Fixed: Set element didn't calculate properly

== Upgrade Notice ==
In order to update the plugin, you need to go to the "Global settings" page, check the "Manual update" checkbox and click on "Save".