<?php

namespace Lkn\LknMercadoPagoForGiveWp\Includes;

use Give\Donations\Models\Donation;
use Give\Donations\ValueObjects\DonationStatus;
use Lkn\LknMercadoPagoForGiveWp\Admin\LknMercadoPagoForGiveWPAdmin;
use Lkn\LknMercadoPagoForGiveWp\PublicView\LknMercadoPagoForGiveWPPublic;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://https://www.linknacional.com.br/wordpress/givewp/
 * @since      1.0.0
 *
 * @package    Lkn_Mercadopago_For_Givewp
 * @subpackage Lkn_Mercadopago_For_Givewp/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Lkn_Mercadopago_For_Givewp
 * @subpackage Lkn_Mercadopago_For_Givewp/includes
 * @author     Link Nacional <contato@linknacional>
 */
final class LknMercadoPagoForGiveWP {
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      LknMercadoPagoForGiveWPLoader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string();ing    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('LKN_MERCADOPAGO_FOR_GIVEWP_VERSION')) {
            $this->version = LKN_MERCADOPAGO_FOR_GIVEWP_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'lkn-mercadopago-for-givewp';

        $this->init();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Lkn_Mercadopago_For_Givewp_Loader. Orchestrates the hooks of the plugin.
     * - Lkn_Mercadopago_For_Givewp_i18n. Defines internationalization functionality.
     * - Lkn_Mercadopago_For_Givewp_Admin. Defines all hooks for the admin area.
     * - Lkn_Mercadopago_For_Givewp_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies(): void {
        $this->loader = new LknMercadoPagoForGiveWPLoader();
    }

    public function init(): void {
        $dependency = LknMercadoPagoForGiveWPHelper::check_environment();
        if ($dependency) {
            $this->load_dependencies();
            $this->set_locale();
            $this->define_admin_hooks();
            $this->define_public_hooks();
            $this->run();
        }
    }    

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Lkn_Mercadopago_For_Givewp_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale(): void {
        $plugin_i18n = new LknMercadoPagoForGiveWPi18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks(): void {
        $plugin_admin = new LknMercadoPagoForGiveWPAdmin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('givewp_register_payment_gateway', $this, 'new_gateway_register');
        $this->loader->add_action('rest_api_init', $this, 'register_payment_routes');
        $this->loader->add_action('rest_api_init', $this, 'register_rest_route');
        $this->loader->add_filter('give_get_settings_gateways', $plugin_admin, 'add_setting_into_new_section');
        $this->loader->add_filter('give_get_sections_gateways', $plugin_admin, 'new_setting_section');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks(): void {
        $plugin_public = new LknMercadoPagoForGiveWPPublic($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('givewp_register_payment_gateway', $this, 'new_gateway_register');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run(): void {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    LknMercadoPagoForGiveWPLoader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Register gateway to new GiveWP v3
     *
     * @since 3.0.0
     *
     * @param  PaymentGatewayRegister $paymentGatewayRegister 
     *
     * @return void
     */ 
    public function new_gateway_register($paymentGatewayRegister) :void {
        $paymentGatewayRegister->registerGateway('Lkn\LknMercadoPagoForGiveWp\PublicView\LknMercadoPagoForGiveWPGateway');
    }

    final public function mercadopago_get_endpoint_payments() {
        // rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
        return rest_ensure_response( 'Hello World, this is the WordPress REST API' );
    }
    
    /**
     * This function is where we register our routes for our example endpoint.
     */
    final public function register_payment_routes(): void {
        // register_rest_route() handles more arguments but we are going to stick to the basics for now.
        register_rest_route('mercadopago/v1', '/payments', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'mercadopago_get_endpoint_payments'),
        ) );
    }

    public function register_rest_route(): void {
        register_rest_route( 'mercadopago/v1', '/payments/checkpayment', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_handle_custom_payment_route'),
            'permission_callback' => '__return_true',
        ) );
    }

    public function get_handle_custom_payment_route($request) {
        $id = $request->get_param('id');
        $statusFront = $request->get_param('statusFront');
        if (empty($id)) {
            return new WP_Error('missing_params', 'Missing parameter id', array('status' => 422));
        }

        switch ($statusFront) {
            case '1':
                $donation_id = get_option("lkn_mercadopago_" . $id);
                if ( ! $donation_id) {
                    return new WP_Error('no_donation_id', 'No donation ID found', array('status' => 404));
                }
                
                $donation = Donation::find($donation_id);
                if ( ! $donation) {
                    return new WP_Error('donation_not_found', 'Donation not found', array('status' => 404));
                }
                
                $donation->status = DonationStatus::COMPLETE();
                $donation->save();
                if ( ! $donation) {
                    return new WP_Error('save_failed', 'Failed to update donation status', array('status' => 500));
                }
                
                $url_pagina = give_get_success_page_uri();

                header("Location: $url_pagina", true, 302);
                exit; 
                break;
            case '2':
                $donation_id = get_option("lkn_mercadopago_" . $id);
                if ( ! $donation_id) {
                    return new WP_Error('no_donation_id', 'No donation ID found', array('status' => 404));
                }
                $donation = Donation::find($donation_id);
                if ( ! $donation) {
                    return new WP_Error('donation_not_found', 'Donation not found', array('status' => 404));
                }
                $donation->status = DonationStatus::PENDING();
                $donation->save();
                if ( ! $donation) {
                    return new WP_Error('save_failed', 'Failed to update donation status', array('status' => 500));
                }
            
                $url_pagina = give_get_success_page_uri();

                header("Location: $url_pagina", true, 302);
                exit; 
                break;
            case '3':
                $donation_id = get_option("lkn_mercadopago_" . $id);
                if ( ! $donation_id) {
                    return new WP_Error('no_donation_id', 'No donation ID found', array('status' => 404));
                }
                $donation = Donation::find($donation_id);
                if ( ! $donation) {
                    return new WP_Error('donation_not_found', 'Donation not found', array('status' => 404));
                }
                
                $donation->status = DonationStatus::FAILED();
                $donation->save();
                if ( ! $donation) {
                    return new WP_Error('save_failed', 'Failed to update donation status', array('status' => 500));
                }
            
                $response_data = array(
                    'id' => $id,
                    'donation_id' => $donation_id,
                );
            
                $url_pagina = give_get_failed_transaction_uri();

                header("Location: $url_pagina", true, 302);
                exit; 
                break;
            default:
                return new WP_REST_Response(array('message' => 'Houve erro no pagamento'), 200);
                break;
        }
    }
}