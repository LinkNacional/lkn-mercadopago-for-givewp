<?php

namespace Lknmp\Gateway\PublicView;

use Lknmp\Gateway\Includes\LknmpGatewayGiveWPHelper;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.linknacional.com.br/wordpress/givewp/
 * @since      1.0.0
 *
 * @package    Lknmp_Gateway_Givewp
 * @subpackage Lknmp_Gateway_Givewp/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Lknmp_Gateway_Givewp
 * @subpackage Lknmp_Gateway_Givewp/public
 * @author     Link Nacional <contato@linknacional>
 */
final class LknmpGatewayGiveWPPublic
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles(): void
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Lknmp_Gateway_Givewp_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Lknmp_Gateway_Givewp_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/lknmp-gateway-givewp-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts(): void
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Lknmp_Gateway_Givewp_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Lknmp_Gateway_Givewp_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/lknmp-gateway-givewp-observer.js', array('jquery'), $this->version, false);

        $configs = LknmpGatewayGiveWPHelper::get_configs();
        $url_pagina = site_url();
        $idUnique = uniqid();

        $MenssageErrorNameEmpty = __('The Name field is empty. Please fill in this field before proceeding.', 'lknmp-gateway-givewp');
        $MenssageErrorName = __('The Name field must be at least 3 letters.', 'lknmp-gateway-givewp');
        $MenssageErrorEmailEmpty = __('The Email field is empty. Please fill in this field before proceeding.', 'lknmp-gateway-givewp');
        $MenssageErrorEmailInvalid = __('The Email field is invalid. Please enter a valid email address.', 'lknmp-gateway-givewp');

        $lknmp_globals = array(
            'key' => $configs['key'],
            'token' => $configs['token'],
            'pageUrl' => $url_pagina,
            'idUnique' => $idUnique,
            'tittle' => $configs['tittle'],
            'description' => $configs['description'],
            'advDebug' => $configs['advDebug'],
            'translation' => array(
                'MenssageErrorNameEmpty' => $MenssageErrorNameEmpty,
                'MenssageErrorName' => $MenssageErrorName,
                'MenssageErrorEmailEmpty' => $MenssageErrorEmailEmpty,
                'MenssageErrorEmailInvalid' => $MenssageErrorEmailInvalid,
            ),
        );

        wp_localize_script($this->plugin_name, 'lknmpGlobals', $lknmp_globals);
    }
}
