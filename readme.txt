=== Pósturinn\'s Shipping with WooCommerce ===
Contributors: posturinn
Tags: shipping, posturinn, icelandic post shipping, shipping rates, woocommerce
Requires at least: 4.3
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: trunk
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Pósturinn Shipping with WooCommerce is a plugin that adds support to WooCommerce for Pósturinn postal service.

== Description ==
Pósturinn Shipping with WooCommerce is a plugin that adds support to WooCommerce for Pósturinn postal service.

After installing the plugin your customers will have the option to choose shipping methods provided by Pósturinn as a shipping method during the checkout. The plugin automatically calculates parcel shipping rates for your customers by using the product attributes you provide on each of your products.

This plugin is only for companies that have a registered user in Mappan.

== Installation ==
1. Download the plugin and activate it
2. Go to WooCommerce > Settings > Shipping
3. Enable the Pósturinn shipping method
4. Enter your postal code
5. insert your api key

== Changelog ==
= 1.3.1 =
show shipping methods before customer adds details
send user agent with api requests
fix for duplicated description in shipping methods

= 1.3.0 =
improved error handling for endpoints

= 1.2.9 =
new endpoint for tracking 
new endpoint for validation

= 1.2.8 =
improved UI
reduced api requests for calculated shipping
add wc and plugin version to api requests
add more control and functionality to create shipment in the create shipment modal

= 1.2.7 =
fix for timeouts
improved error handling for api requests in create shipment calls.

= 1.2.6 =
adjusted free shipping value

= 1.2.5 =
added placeholder to postbox checkout field
changed the tracking email

= 1.2.4 =
fixed - error handling when api returns an error
added - option to send shipment tracking to customer
added - option for new shipping labels.

= 1.2.3 =
Quick fix where bulk action change order status to Completed will trigger a PDF output.

= 1.2.2 =
fixed bulk actions
fixed bulk actions print pdf in legacy Woocommerce
fixed some minor translation issues
adjusted admin service table
added some translations

= 1.2.1 =
fixed shipping label only created with our shipping methods.

= 1.2.0 =
add option for generate shipping slip on specific status
add option for display shipping rate description
fixed load fpdi only when needed

= 1.1.8 =
add support for hpos
updated fpdi
updated tcpdf

= 1.1.7 =
added description to DPT and DPO shipping label

= 1.1.6 =
added string to shipping label

= 1.1.5 =
fixed return for postboxes

= 1.1.4 =
add processing to if shipment is ready function
fixed dynamic function arguments for php 8.0
fixed undefined array

= 1.1.3 =
fixed country code on receipient phone number
fixed int and floats in weight and size
added language switcher

= 1.1.2 =
add option to remove tariff line item in international shipping
add new shipping method (Pakki til útlanda)
removed old shipping methods (Heimspakki,Hagkvæmur pakki,Evrópupakki)
combined shipping state with postcode in shippings to US

= 1.1.1 =
fixed postbox issue on cart
add support for multilanguage shipping methods in cart
changed the pdf to open on successful shipment create

= 1.1.0 =
add addressLine2 to createShipment
add new shipping method DPT Pakkaport
add Google map style to Postboxes

= 1.0.9 =
fixed issue for missing town
fixed issue for too long name
fixed issue for too long town
fixed issue for too long address
add api.class

= 1.0.8 =
Add bulk actions for createShipment
Add bulk actions for printing shipments
Add bulk actions for marking shipments as printed or not printed
Add filter by printed or not printed
Add admin class
Fixed phoneNumber issue on DPP and DPH shipments


= 1.0.7 =
Add postbox sort by nearest postcode

= 1.0.6 =
Removed service code showing on settings table on plugin fresh install
Fixed shipment key missing
Fixed shipment tracking issue
Add 2 shipment tracking features
Fixed address for PostBox
Add contents array to international shipments
Add custom fields TarrifNumber and Description to Shipping tab on Product Page
Add contants array pull TarrifNumber and Description from product Page.

= 1.0.5 =
Fixed JS issue
Fixed table issue in settings
Add description field for shipment
Add options for PDF sizes in user friendly dropdown.

= 1.0.4 =
Fixed Postcode issue for priceChecks on international shipments. PostCodes have been removed from priceChecks.
Add option to choose choose bruttoPrice or nettoPrice from priceChecks
Add option to include Woocommerce VAT calculations or disable them
Fixed issue when phoneNumber is not supported by API
Add option in createShipment modal to change phoneNumber if API response is = wrong Phone number / invalid Phone Number

= 1.0.3 =
Add option for automatic printing through PostStod
Add option  - force our shipping methods to be first choice in cart.
Add option  - drag and drop to sort our shipping methods in cart page.
Add option  - open shipping slip on new tab in browser
Fixed issue - Set default shipping weight to 0 if product does not have any weight.
Fixed Postbox issue by adding disabled postbox entry in dropdown as first choice.
Fixed VAT issue, and prepare for option in settings.
Add domain_name in header
Fixed phoneNumber issue

= 1.0.2 =
Fixed issue where weight was not calculating correctly if woocommerce weight unit settings was set to other than kg.

= 1.0.1 =
Fixed some minor issues

= 1.0.0 =
Initial Release
