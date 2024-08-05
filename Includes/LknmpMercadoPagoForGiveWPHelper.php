<?php

namespace Lknmp\MercadoPagoForGiveWp\Includes;
use DateTime;

/**
 * Fired during plugin deactivation
 *
 * @link       https://https://www.linknacional.com.br/wordpress/givewp/
 * @since      1.0.0
 *
 * @package    Lknmp_Mercadopago_For_Givewp
 * @subpackage Lknmp_Mercadopago_For_Givewp/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Lknmp_Mercadopago_For_Givewp
 * @subpackage Lknmp_Mercadopago_For_Givewp/includes
 * @author     Link Nacional <contato@linknacional>
 */
abstract class LknmpMercadoPagoForGiveWPHelper {
    /**
     * @since 1.0.0
     *
     * @package    Lkn_Give_Getnet
     * @subpackage Lkn_Give_Getnet/includes
     */

    // Exit, if accessed directly.

    /**
     *
     * Makes a .log file for each donation
     *
     * @param  bool $advanced
     * @param  array|string $message
     * @param  array $configs
     *
     * @return void
     */
    final public static function reg_log($advanced, $message, $configs): void {
        global $wp_filesystem;
        WP_Filesystem();

        if ( ! $wp_filesystem ) {
            return;
        }

        if ($advanced && 'enabled' === $configs['debugAdvanced']) {
            $log = true;
        }
        if ($advanced && 'enabled' === $configs['debug']) {
            $log = true;
        }

        if ($log) {
            $jsonMsg = wp_json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

            $file_path = $configs['base'];
            $wp_filesystem->put_contents( $file_path, $jsonMsg, FS_CHMOD_FILE );
            $wp_filesystem->chmod( $file_path, 0600 );
        }
    }

    /**
     * Delete the log files older than 5 days
     *
     * @return void
     */
    final public static function delete_old_logs(): void {
        $configs = LknmpMercadoPagoForGiveWPHelper::get_configs();
        $logsPath = $configs['basePath'];

        foreach (scandir($logsPath) as $logFilename) {
            if ('.' !== $logFilename && '..' !== $logFilename && 'index.php' !== $logFilename) {
                $logDate = explode('-', $logFilename)[0];
                $logDate = explode('.', $logDate);

                $logDay = $logDate[0];
                $logMonth = $logDate[1];
                $logYear = $logDate[2];

                $logDate = $logYear . '-' . $logMonth . '-' . $logDay;

                $logDate = new DateTime($logDate);
                $now = new DateTime(gmdate('Y-m-d'));

                $interval = $logDate->diff($now);
                $logAge = $interval->format('%a');

                if ($logAge >= 5) {
                    wp_delete_file($logsPath . '/' . $logFilename);
                }
            }
        }
    }

    /**
     * Show plugin dependency notice
     *
     * @since
     */
    final public static function dependency_notice(): void {
        // Admin notice.
        $message = sprintf(
            '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s" target="_blank">%4$s</a> %5$s %6$s+ %7$s.</p></div>',
            esc_html('Erro de ativação:'),
            esc_html('Você precisa ter o'),
            esc_url('https://givewp.com'),
            esc_html('Give'),
            esc_html('instalado e ativo versão'),
            esc_html(LKNMP_MERCADOPAGO_MIN_GIVE_VERSION),
            esc_html('para o plugin Mercado Pago para GiveWP ativar')
        );

        echo $message;
    }

    /**
     * Plugin row meta links.
     *
     * @since 1.0.0
     *
     * @param array $plugin_meta An array of the plugin's metadata.
     *
     * @return array
     */
    final public static function plugin_row_meta($plugin_meta) {
        $new_meta_links['setting'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            admin_url('edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=mercado_pago'),
            'Configurações',
            'lknmp-mercadopago-for-givewp'
        );

        return array_merge($plugin_meta, $new_meta_links);
    }

    final public static function get_configs() {
        $configs = array();

        $configs['basePath'] = LKNMP_MERCADOPAGO_FOR_GIVEWP_DIR . 'Includes/logs';
        $configs['base'] = $configs['basePath'] . '/' . gmdate('d.m.Y-H.i.s') . '.log';

        $configs['token'] = give_get_option('mercado_pago_token');
        $configs['key'] = give_get_option('mercado_pago_key');
        $configs['tittle'] = give_get_option('mercado_pago_tittle');
        $configs['description'] = give_get_option('mercado_pago_description');
        $configs['advDebug'] = give_get_option('mercado_pago_advanced_debug');

        return $configs;
    }

    final public static function check_environment() {
        // Load plugin helper functions.
        if ( ! function_exists('deactivate_plugins') || ! function_exists('is_plugin_active')) {
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        // Flag to check whether deactivate plugin or not.
        $is_deactivate_plugin = null;

        $is_give_active = is_plugin_active('give/give.php');

        // Verify if Free plugin is actived.
        if ( ! $is_give_active) {
            // Show admin notice.
            self::dependency_notice();

            $is_deactivate_plugin = true;
        }

        // Deactivate plugin.
        if ($is_deactivate_plugin) {
            deactivate_plugins(LKNMP_MERCADOPAGO_FOR_GIVEWP_BASENAME);

            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }

            return false;
        }

        return true;
    }
}