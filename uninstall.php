<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://www.linknacional.com.br/wordpress/givewp/
 * @since      1.0.0
 *
 * @package    Lknmp_Mercadopago_For_Givewp
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

give_delete_option('mercado_pago_token');
give_delete_option('mercado_pago_key');
give_delete_option('mercado_pago_tittle');
give_delete_option('mercado_pago_description');
give_delete_option('mercado_pago_advanced_debug');

$lkn_array_remove_options = give_get_settings();

// Remove todos as options que possuem lkn_mercadopago_
$lkn_array_remove_options = array_filter($lkn_array_remove_options, function ($key) {
    return strpos($key, 'lkn_mercadopago_') === 0;
}, \ARRAY_FILTER_USE_KEY);

$lkn_array_remove_options = array_keys($lkn_array_remove_options);

// Verifica se as chaves existem
if ( ! empty($lkn_array_remove_options)) {
    foreach ($lkn_array_remove_options as $option_key) {
        give_delete_option($option_key);
    }
}