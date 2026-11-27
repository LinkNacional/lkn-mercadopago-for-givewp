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
 * @package    Lknmp_Gateway_Givewp
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

// Remove todos as options que possuem lknmp_gateway_
$lkn_array_remove_options = array_filter($lkn_array_remove_options, function ($key) {
    return strpos($key, 'lknmp_gateway_') === 0;
}, \ARRAY_FILTER_USE_KEY);

$lkn_array_remove_options = array_keys($lkn_array_remove_options);

// Verifica se as chaves existem
if ( ! empty($lkn_array_remove_options)) {
    foreach ($lkn_array_remove_options as $option_key) {
        give_delete_option($option_key);
    }
}

// As referencias e os transientes do gateway sao criados com add_option() e
// set_transient(), que escrevem direto em wp_options. O give_get_settings()
// nao os enxerga, entao sem isso ficam linhas orfas para sempre.
global $wpdb;

// Consulta direta e sem cache de proposito: o uninstall roda uma vez, e as
// options orfas nao tem chave conhecida para delete_option() individual.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$lknmp_option_names = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('lknmp_gateway_') . '%',
        $wpdb->esc_like('_transient_lknmp_mp_') . '%',
        $wpdb->esc_like('_transient_timeout_lknmp_mp_') . '%'
    )
);

foreach ((array) $lknmp_option_names as $lknmp_option_name) {
    delete_option($lknmp_option_name);
}