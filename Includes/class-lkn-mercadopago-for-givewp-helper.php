<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://https://www.linknacional.com.br/wordpress/givewp/
 * @since      1.0.0
 *
 * @package    Lkn_Mercadopago_For_Givewp
 * @subpackage Lkn_Mercadopago_For_Givewp/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Lkn_Mercadopago_For_Givewp
 * @subpackage Lkn_Mercadopago_For_Givewp/includes
 * @author     Link Nacional <contato@linknacional>
 */
abstract class Lkn_Mercadopago_For_Givewp_Helper {
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
        if ($advanced && 'enabled' === $configs['debugAdvanced']) {
            $log = true;
        } 
        if ($advanced && 'enabled' === $configs['debug']) {
            $log = true;
        }
        
        if ($log) {
            $jsonMsg = json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

            error_log($jsonMsg, 3, $configs['base']);
            chmod($configs['base'], 0600);
        }
    }

    /**
     * Delete the log files older than 5 days
     *
     * @return void
     */

    //TODO
    //Criei a helper a partir de outro repositório
    //Faltando a class Actions (?)

    final public static function delete_old_logs(): void {
        //TODO criar função get_Configs
        $configs = LknGiveGetnetPaymentActions::get_configs();
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
                $now = new DateTime(date('Y-m-d'));

                $interval = $logDate->diff($now);
                $logAge = $interval->format('%a');

                if ($logAge >= 5) {
                    unlink($logsPath . '/' . $logFilename);
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
            '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s" target="_blank">%4$s</a>  %5$s %6$s+ %7$s.</div>',
            'Erro de ativação:',
            'Você precisa ter o',
            'https://givewp.com',
            'Give',
            'instalado e ativo versão',
            LKN_GIVE_MERCADOPAGO_MIN_GIVE_VERSION,
            'para o plugin Give Getnet ativar.'
        );

        echo $message;
    } 

    /**
     * Notice for No Core Activation
     *
     * @since 1.0.0
     */
    final public static function inactive_notice(): void {
        // Admin notice.
        $message = sprintf(
            '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s" target="_blank">%4$s</a> %5$s.</p></div>',
            'Erro de Ativação:',
            'give-getnet',
            'Você deve ter',
            'give-getnet',
            'https://givewp.com',
            'Give',
            'give-getnet',
            ' plugin instalado e ativado para o plugin Give Getnet ser ativado',
            'give-getnet'
        );

        echo esc_html($message);
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
            admin_url('edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=lkn_getnet'),
            'Configurações',
            'give-getnet'
        );

        return array_merge($plugin_meta, $new_meta_links);
    }
}