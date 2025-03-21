=== Link Nacional Payment Gateway for MercadoPago and GiveWP ===
Contributors: linknacional
Donate link: https://www.linknacional.com.br/wordpress/givewp/
Tags: givewp, payment, mercadopago, card
Requires at least: 5.7
Tested up to: 6.7
Stable tag: 1.4.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Link Nacional MercadoPago payment option for GiveWP.

== Description ==

The [MercadoPago Payment Gateway for GiveWP](https://www.linknacional.com.br/wordpress/givewp/) seamlessly integrates MercadoPago with the GiveWP donation plugin for WordPress, providing a secure and efficient solution for receiving online donations. This plugin supports multiple payment methods, recurring donations, and customizable donation forms. Enhance your donor's experience with a trusted payment gateway, boost your fundraising efforts, and manage donations effortlessly with detailed reporting and a user-friendly interface.

[MercadoPago](https://www.mercadopago.com.br) is a leading online payment solution in Latin America, offering a comprehensive suite of services for secure and convenient financial transactions. As the fintech arm of Mercado Libre, one of the region’s largest e-commerce platforms, Mercado Pago provides a reliable and user-friendly payment gateway for businesses and individuals alike.

**Dependencies**

This plugin adds Mercado Pago as a payment option for the GiveWP donation plugin. It uses the [Mercado Pago API](https://www.mercadopago.com.br/developers/pt/reference) to process transactions. To use this plugin, you must have an active Mercado Pago account.

As a payment gateway, this plugin communicates with an external API to function properly. Specifically, it connects to the following endpoint: [https://api.mercadopago.com](https://api.mercadopago.com), which is used to process transactions securely.

[GiveWP](https://wordpress.org/plugins/give/) is needed for the plugin to work.

JS Libraries used:
The [MercadoPago sdk-js](https://github.com/mercadopago/sdk-js) is needed for the plugin to work. [Learn more](https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/integrate-checkout-pro/web).

As a Payment Gateway this plugin contacts these external resources to complete the payment:
[Mercado Pago Checkout API](https://api.mercadopago.com/checkout/preferences)
[Mercado Pago Checkout JS SDK](https://sdk.mercadopago.com/js/v2)

**User instructions**

1. Search the WordPress sidebar for 'Link Nacional MercadoPago for GiveWP'.

2. Download and activate the plugin.

3. In the GiveWP, navigate to settings, then 'Payments Gateways', and activate 'Mercado Pago'.

4. Save your settings.

You have successfully configured the MercadoPago National Link for GiveWP and allowed your customers to choose to pay for donations with Mercado Pago.

== Installation ==

1. Access GiveWP settings from the WordPress admin dashboard.

2. Under "Payment Gateways", locate "Mercado Pago" and configure settings.

3. Enter the necessary configuration information, such as Mercado Pago TOKEN and PUBLIC KEY. Additionally, also customize the title and description that will appear at checkout.

4. Save  your settings.

The Link Nacional MercadoPago for GiveWP plugin is now live and working.

= Payments Settings =

1. After installing the plugin, access the WordPress admin dashboard and navigate to the GiveWP settings.
2. In the sidebar menu, click on "Donations" and then on "Settings".
3. In the "Payments Gateways" tab, you'll see a list of available payment methods.
4. Locate “Mercado Pago” in the list of payment methods and go to settings.
5. Enter the necessary configuration information, such as Mercado Pago TOKEN and PUBLIC KEY. Additionally, also customize the title and description that will appear at checkout.

== Frequently Asked Questions ==

= What is the license of the plugin? =

* This plugin is released under a GPLv3 license.

= What do I need to use this plugin? =

* Have installed the GiveWP plugin.

= Is it necessary to edit the settings? Which are they? =

* Yes, it is necessary to configure the token and public key according to your Mercado Pago account.

== Screenshots ==

1. Nothing yet.

== Changelog ==

= 1.4.0 = *2025/03/19*
* Feedback pages for the MercadoPago sandbox environment.

= 1.3.2 = *2025/03/21*
* Fix in custom input.

= 1.3.1 = *2025/03/12*
* Fix in the conversion of the value to float.

= 1.3.0 = *2025/01/08*
* Add configuration to enable debug.

= 1.2.4 = *2024/11/26*
* Fixed redirect URL.
* Improved payment flow.

= 1.2.3 = *2024/09/27*
* Fixed bug on Mercado Pago SDK load.

= 1.2.2 = *2024/09/25*
* Update documentation;
* Fix translation mismatch issues;
* Change option names to avoid collisions.

= 1.2.1 = *2024/09/10*
* Refactor with requested name and slug changes.

= 1.2.0 = *2024/08/15*
* Plugin rendering fixed
* Payment method change correction
* WordPress guidelines applied
* Notice of plugin inapplicability in the classic and multistep form

= 1.0.1 = *2024/08/05*
* Message added when inserting the Mercado Pago token;
* Plugin namespace updated;
* Refactor with new WordPress guidelines;
* Update README.

= 1.0.0 = *2024/05/31*
* Plugin launch.

== Upgrade Notice ==

= 1.0.0 =
* Plugin launch.