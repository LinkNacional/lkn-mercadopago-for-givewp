<?php

namespace Lkn\lkn_mercadopago_for_givewp\Includes;

use Give\Log\Log;

abstract class LknMercadoPagoForGiveWPActions {
    final public static function get_description() {
        $description = give_get_option('getnet_description_setting_field');

        $description = preg_replace('/\W/', '', $description);

        return $description;
    }

    /**
     * Get credit card valid installments
     *
     * @return int
     */
    final public static function get_installments() {
        $installments = give_get_option('getnet_installments_setting_field');

        if ($installments < 0) {
            $installments = 0;
            give_update_option('getnet_installments_setting_field', $installments);
        }

        return $installments;
    }

    /**
     * Function that validates the module key
     *
     * @return mixed $result
     */
    final public static function get_module_key() {
        $moduleKey = trim(give_get_option('getnet_setting_field'));

        $result = LknMercadoPagoForGiveWPLicenseFunctions::save_license($moduleKey);

        return $result;
    }

    /**
     * This function centralizes the data in one spot for ease mannagment
     *
     * @return array
     */
    final public static function get_configs() {
        $configs = array();
    
        $configs['basePath'] = LKN_MERCADOPAGO_FOR_GIVEWP_DIR . 'Includes/logs';
        $configs['base'] = $configs['basePath'] . '/' . date('d.m.Y-H.i.s') . '.log';
    
        $configs['debug'] = give_get_option('lkn_getnet_debug');
        $configs['debugAdvanced'] = give_get_option('lkn_getnet_debug_advanced');
        $configs['env'] = give_get_option('lkn_getnet_env_details');
    
        if ('production' === $configs['env']) {
            $configs['urlQuery'] = 'https://api.getnet.com.br/';
            $configs['urlPost'] = 'https://api.getnet.com.br/';
            $configs['urlCheckout'] = 'https://checkout.getnet.com.br';
        } else {
            $configs['urlQuery'] = 'https://api-sandbox.getnet.com.br/';
            $configs['urlPost'] = 'https://api-sandbox.getnet.com.br/';
            $configs['urlCheckout'] = 'https://checkout-sandbox.getnet.com.br';
        }
    
        $configs['installments'] = self::get_installments();
        $configs['moduleKey'] = self::get_module_key();
        $configs['sellerId'] = trim(give_get_option('lkn_getnet_seller_id_setting_field'));
        $configs['clientId'] = trim(give_get_option('lkn_getnet_client_id_setting_field'));
        $configs['clientSecret'] = trim(give_get_option('lkn_getnet_client_secret_setting_field'));
        $configs['description'] = self::get_description();
    
        return $configs;
    }        

    /**
     * Function that builds and executes a wp_remote Post
     *
     * @param array $header
     * @param array $body
     * @param string $endpoint
     *
     * @return string $response
     */
    final public static function connect_request($header, $body, $configs, $endpoint) {
        $args = array(
            'headers' => $header,
            'body' => $body,
        );
        
        $response = wp_remote_post($configs['urlPost'] . $endpoint, $args);
        
        $result = wp_remote_retrieve_body($response);
        
        LknMercadoPagoForGiveWPHelper::reg_log(true, array(
            'response' => $result,
        ), $configs);

        return $result;
    }
}