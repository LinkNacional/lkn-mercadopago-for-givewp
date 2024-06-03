<?php
namespace Lkn\LknMercadoPagoForGiveWp\Admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://www.linknacional.com.br/wordpress/givewp/
 * @since      1.0.0
 *
 * @package    Lkn_Mercadopago_For_Givewp
 * @subpackage Lkn_Mercadopago_For_Givewp/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Lkn_Mercadopago_For_Givewp
 * @subpackage Lkn_Mercadopago_For_Givewp/admin
 * @author     Link Nacional <contato@linknacional>
 */
final class LknMercadoPagoForGiveWPAdmin {
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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles(): void {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Lkn_Mercadopago_For_Givewp_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Lkn_Mercadopago_For_Givewp_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/lkn-mercadopago-for-givewp-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts(): void {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Lkn_Mercadopago_For_Givewp_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Lkn_Mercadopago_For_Givewp_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/lkn-mercadopago-for-givewp-admin.js', array('jquery'), $this->version, false );
    }

    public function add_setting_into_new_section($settings) {
        switch (give_get_current_setting_section()) {
            case 'mercado_pago':

                $settings[] = array(
                    'type' => 'title',
                    'id' => 'mercado_pago',
                );

                $settings[] = array(
                    'name' => 'Token do Mercado Pago (Acess Token)',
                    'id' => 'mercado_pago_token',
                    'desc' => 'Código Token do Mercado Pago',
                    'type' => 'text',
                );

                $settings[] = array(
                    'name' => 'Chave Pública do Mercado Pago (Public Key)',
                    'id' => 'mercado_pago_key',
                    'desc' => 'Chave Pública do Mercado Pago',
                    'type' => 'text',
                );

                $settings[] = array(
                    'name' => 'Título no Mercado Pago',
                    'id' => 'mercado_pago_tittle',
                    'desc' => 'Esse título será utilizado durante o checkout no Mercado Pago',
                    'type' => 'text',
                );

                $settings[] = array(
                    'name' => 'Descrição no Mercado Pago',
                    'id' => 'mercado_pago_description',
                    'desc' => 'Essa descrição será utilizada durante o checkout no Mercado Pago',
                    'type' => 'text',
                );

                $settings[] = array(
                    'name' => 'Ambiente de desenvolvimento',
                    'id' => 'mercado_pago_ambiente',
                    'desc' => 'Habilite o ambiente de desenvolvimento desejado: Sandbox ou Produção.',
                    'type' => 'radio',
                    'default' => 'producao',
                    'options' => array(
                        'producao' => 'Produção',
                        'sandbox' => 'Sandbox',
                    ),
                );

                $settings[] = array(
                    'name' => 'Depuração',
                    'id' => 'mercado_pago_debug',
                    'desc' => 'Habilitar ambiente para Debug.',
                    'type' => 'radio',
                    'default' => 'disabled',
                    'options' => array(
                        'enabled' => 'Habilitar',
                        'disabled' => 'Desabilitar',
                    ),
                );
                
                $settings[] = array(
                    'name' => 'Modo de Depuração avançada',
                    'id' => 'mercado_pago_advanced_debug',
                    'desc' => 'Habilitar ambiente para Debug avançado (CONSOLE - JAVASCRIPT). Atenção habilitar essa opção deixará seu site vulnerável.',
                    'type' => 'radio',
                    'default' => 'disabled',
                    'options' => array(
                        'enabled' => 'Habilitar',
                        'disabled' => 'Desabilitar',
                    ),
                );

                $settings[] = array(
                    'id' => 'mercado_pago',
                    'type' => 'sectionend',
                );

                break;
        }// // End switch()

        return $settings;
    }

    /**
     * Add new section to "General" setting tab
     *
     * @param $sections
     *
     * @return array
     */
    public function new_setting_section($sections) {
        $sections['mercado_pago'] = 'Mercado Pago';
        return $sections;
    }
}
