<?php
namespace Lknmp\MercadoPagoForGiveWp\Admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://https://www.linknacional.com.br/wordpress/givewp/
 * @since      1.0.0
 *
 * @package    Lknmp_Mercadopago_For_Givewp
 * @subpackage Lknmp_Mercadopago_For_Givewp/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Lknmp_Mercadopago_For_Givewp
 * @subpackage Lknmp_Mercadopago_For_Givewp/admin
 * @author     Link Nacional <contato@linknacional>
 */
final class LknmpMercadoPagoForGiveWPAdmin {
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
         * defined in Lknmp_Mercadopago_For_Givewp_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Lknmp_Mercadopago_For_Givewp_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/lknmp-mercadopago-for-givewp-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook): void {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Lknmp_Mercadopago_For_Givewp_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Lknmp_Mercadopago_For_Givewp_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script("lkn-mercadopago-givewp-show-message.js", plugin_dir_url(__FILE__) . 'js/lknmp-mercadopago-for-givewp-show-message.js', null, $this->version, false );

        wp_localize_script("lkn-mercadopago-givewp-show-message.js", "varsPhp", array(
            "admin_url" => admin_url("edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=mercado_pago")
        ));
    }

    public function add_setting_into_new_section($settings) {
        switch (give_get_current_setting_section()) {
            case 'mercado_pago':

                $settings[] = array(
                    'type' => 'title',
                    'id' => 'mercado_pago',
                );

                $settings[] = array(
                    'name' => __('Mercado Pago Public Key', 'lknmp-mercadopago-for-givewp'),
                    'id' => 'mercado_pago_key',
                    'desc' => __('Mercado Pago Public Key.', 'lknmp-mercadopago-for-givewp'),
                    'type' => 'password',
                );

                $settings[] = array(
                    'name' => __('Mercado Pago Token', 'lknmp-mercadopago-for-givewp'),
                    'id' => 'mercado_pago_token',
                    'desc' => __('Mercado Pago Token Code.', 'lknmp-mercadopago-for-givewp'),
                    'type' => 'password',
                );

                $settings[] = array(
                    'name' => __('Title in Mercado Pago', 'lknmp-mercadopago-for-givewp'),
                    'id' => 'mercado_pago_tittle',
                    'desc' => __('This title will be used during checkout in Mercado Pago.', 'lknmp-mercadopago-for-givewp'),
                    'type' => 'text',
                );

                $settings[] = array(
                    'name' => __('Description in Mercado Pago', 'lknmp-mercadopago-for-givewp'),
                    'id' => 'mercado_pago_description',
                    'desc' => __('This description will be used during checkout in Mercado Pago.', 'lknmp-mercadopago-for-givewp'),
                    'type' => 'text',
                );

                $settings[] = array(
                    'name' => __('Advanced Debug Mode', 'lknmp-mercadopago-for-givewp'),
                    'id' => 'mercado_pago_advanced_debug',
                    'desc' => __('Enable advanced Debug environment (CONSOLE - JAVASCRIPT). Be careful enabling this option will leave your site vulnerable.', 'lknmp-mercadopago-for-givewp'),
                    'type' => 'radio',
                    'default' => 'disabled',
                    'options' => array(
                        'enabled' => __('Enable', 'lknmp-mercadopago-for-givewp'),
                        'disabled' => __('Disable', 'lknmp-mercadopago-for-givewp'),
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
