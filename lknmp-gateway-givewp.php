<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.linknacional.com.br/wordpress/givewp/
 * @since             1.2.0
 * @package           LKNMP_Mercadopago_For_Givewp
 *
 * @wordpress-plugin
 * Plugin Name:       Link Nacional Payment Gateway for MercadoPago and GiveWP
 * Requires Plugins: give
 * Plugin URI:        https://www.linknacional.com.br/wordpress/givewp/
 * Description:       MercadoPago Payment Gateway for GiveWP donation plugin for WordPress.
 * Version:           1.2.4
 * Author:            Link Nacional
 * Author URI:        https://www.linknacional.com.br/wordpress/givewp//
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       lknmp-gateway-givewp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
    die;
}

require_once 'vendor/autoload.php';

use Lknmp\Gateway\Includes\LknmpGatewayGiveWP;
use Lknmp\Gateway\Includes\LknmpGatewayGiveWPActivator;
use Lknmp\Gateway\Includes\LknmpGatewayGiveWPDeactivator;

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if (! defined('LKNMP_GATEWAY_GIVEWP_VERSION')) {
    define('LKNMP_GATEWAY_GIVEWP_VERSION', '1.2.4');
}

if (! defined('LKNMP_GATEWAY_MIN_GIVE_VERSION')) {
    define('LKNMP_GATEWAY_MIN_GIVE_VERSION', '3.0.0');
}

if (! defined('LKNMP_GATEWAY_GIVEWP_FILE')) {
    define('LKNMP_GATEWAY_GIVEWP_FILE', __FILE__);
}

if (! defined('LKNMP_GATEWAY_GIVEWP_DIR')) {
    define('LKNMP_GATEWAY_GIVEWP_DIR', plugin_dir_path(LKNMP_GATEWAY_GIVEWP_FILE));
}

if (! defined('LKNMP_GATEWAY_GIVEWP_URL')) {
    define('LKNMP_GATEWAY_GIVEWP_URL', plugin_dir_url(LKNMP_GATEWAY_GIVEWP_FILE));
}

if (! defined('LKNMP_GATEWAY_GIVEWP_BASENAME')) {
    define('LKNMP_GATEWAY_GIVEWP_BASENAME', plugin_basename(LKNMP_GATEWAY_GIVEWP_FILE));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-lknmp-gateway-givewp-activator.php
 */
function lknmp_gateway_givewp_activate(): void
{
    LknmpGatewayGiveWPActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-lknmp-gateway-givewp-deactivator.php
 */
function lknmp_gateway_givewp_deactivate(): void
{
    LknmpGatewayGiveWPDeactivator::deactivate();
}

register_activation_hook(__FILE__, 'lknmp_gateway_givewp_activate');
register_deactivation_hook(__FILE__, 'lknmp_gateway_givewp_deactivate');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function lknmp_gateway_givewp_run(): void
{
    $plugin = new LknmpGatewayGiveWP();
}
lknmp_gateway_givewp_run();
