<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://https://www.linknacional.com.br/wordpress/givewp/
 * @since             1.0.0
 * @package           Lkn_Mercadopago_For_Givewp
 *
 * @wordpress-plugin
 * Plugin Name:       Link Nacional MercadoPago for GiveWP
 * Plugin URI:        https://https://www.linknacional.com.br/wordpress/givewp/
 * Description:       MercadoPago Payment Gateway for GiveWP donation plugin for WordPress.
 * Version:           1.0.0
 * Author:            Link Nacional
 * Author URI:        https://https://www.linknacional.com.br/wordpress/givewp//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lkn-mercadopago-for-givewp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LKN_MERCADOPAGO_FOR_GIVEWP_VERSION', '1.0.0' );

if ( ! defined('LKN_GIVE_MERCADOPAGO_MIN_GIVE_VERSION')) {
    define('LKN_GIVE_MERCADOPAGO_MIN_GIVE_VERSION', '3.0.0');
}

if ( ! defined('LKN_MERCADOPAGO_FOR_GIVEWP_FILE')) {
    define('LKN_MERCADOPAGO_FOR_GIVEWP_FILE', __DIR__ . '/lkn-mercadopago-for-givewp.php');
}

if ( ! defined('LKN_MERCADOPAGO_FOR_GIVEWP_DIR')) {
    define('LKN_MERCADOPAGO_FOR_GIVEWP_DIR', plugin_dir_path(LKN_MERCADOPAGO_FOR_GIVEWP_FILE));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-lkn-mercadopago-for-givewp-activator.php
 */
function activate_lkn_mercadopago_for_givewp(): void {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-lkn-mercadopago-for-givewp-activator.php';
    Lkn_Mercadopago_For_Givewp_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-lkn-mercadopago-for-givewp-deactivator.php
 */
function deactivate_lkn_mercadopago_for_givewp(): void {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-lkn-mercadopago-for-givewp-deactivator.php';
    Lkn_Mercadopago_For_Givewp_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_lkn_mercadopago_for_givewp' );
register_deactivation_hook( __FILE__, 'deactivate_lkn_mercadopago_for_givewp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-lkn-mercadopago-for-givewp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_lkn_mercadopago_for_givewp(): void {
    $plugin = new Lkn_Mercadopago_For_Givewp();
    $plugin->run();
}
run_lkn_mercadopago_for_givewp();
