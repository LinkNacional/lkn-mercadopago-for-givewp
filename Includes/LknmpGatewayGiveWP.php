<?php

namespace Lknmp\Gateway\Includes;

use Give\Donations\Models\Donation;
use Give\Donations\ValueObjects\DonationStatus;
use Lknmp\Gateway\Admin\LknmpGatewayGiveWPAdmin;
use Lknmp\Gateway\PublicView\LknmpGatewayGiveWPPublic;
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
 * @link       https://www.linknacional.com.br/wordpress/givewp/
 * @since      1.0.0
 *
 * @package    Lknmp_Gateway_Givewp
 * @subpackage Lknmp_Gateway_Givewp/includes
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
 * @package    Lknmp_Gateway_Givewp
 * @subpackage Lknmp_Gateway_Givewp/includes
 * @author     Link Nacional <contato@linknacional>
 */
final class LknmpGatewayGiveWP
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      LknmpGatewayGiveWPLoader    $loader    Maintains and registers all hooks for the plugin.
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
    public function __construct()
    {
        if (defined('LKNMP_GATEWAY_GIVEWP_VERSION')) {
            $this->version = LKNMP_GATEWAY_GIVEWP_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'lknmp-gateway-givewp';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->run();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Lknmp_Gateway_Givewp_Loader. Orchestrates the hooks of the plugin.
     * - Lknmp_Gateway_Givewp_i18n. Defines internationalization functionality.
     * - Lknmp_Gateway_Givewp_Admin. Defines all hooks for the admin area.
     * - Lknmp_Gateway_Givewp_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies(): void
    {
        $this->loader = new LknmpGatewayGiveWPLoader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Lknmp_Gateway_Givewp_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale(): void
    {
        $plugin_i18n = new LknmpGatewayGiveWPi18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks(): void
    {
        $plugin_admin = new LknmpGatewayGiveWPAdmin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_filter('plugin_action_links_' . LKNMP_GATEWAY_GIVEWP_BASENAME, 'Lknmp\Gateway\Includes\LknmpGatewayGiveWPHelper', 'plugin_row_meta', 10, 2);
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
    private function define_public_hooks(): void
    {
        $plugin_public = new LknmpGatewayGiveWPPublic($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('givewp_register_payment_gateway', $this, 'new_gateway_register');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run(): void
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    LknmpGatewayGiveWPLoader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
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
    public function new_gateway_register($paymentGatewayRegister): void
    {
        $paymentGatewayRegister->registerGateway('Lknmp\Gateway\PublicView\LknmpGatewayGiveWPGateway');
    }

    final public function mercadopago_get_endpoint_payments()
    {
        // rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
        return rest_ensure_response('Hello World, this is the WordPress REST API');
    }

    /**
     * This function is where we register our routes for our example endpoint.
     */
    final public function register_payment_routes(): void
    {
        // register_rest_route() handles more arguments but we are going to stick to the basics for now.
        register_rest_route('lknmp/v1', '/payments', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'mercadopago_get_endpoint_payments'),
        ));
    }

    public function register_rest_route(): void
    {
        register_rest_route('lknmp/v1', '/payments/preference', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_preference'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('lknmp/v1', '/payments/checkpayment', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_handle_custom_payment_route'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Cria a preferência de checkout no Mercado Pago usando o access token do
     * servidor. O navegador nunca recebe essa credencial.
     */
    public function create_preference($request)
    {
        $configs = LknmpGatewayGiveWPHelper::get_configs();
        $accessToken = trim((string) $configs['token']);

        if ('' === $accessToken) {
            return new WP_Error('missing_token', 'Mercado Pago token not configured', array('status' => 500));
        }

        // A rota é pública e cria objeto cobrável na conta. Sem limite, vira
        // proxy para criar preferências em massa.
        // REMOTE_ADDR vem do servidor, mas o Plugin Check exige sanitização
        // explícita. Sem IP, a chave cai em 'unknown'.
        $rawIp = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash((string) $_SERVER['REMOTE_ADDR'])) : '';
        $rateKey = 'lknmp_mp_rl_' . md5('' !== $rawIp ? $rawIp : 'unknown');
        $hits = (int) get_transient($rateKey);

        if ($hits >= 20) {
            return new WP_Error('rate_limited', 'Too many requests', array('status' => 429));
        }

        set_transient($rateKey, $hits + 1, MINUTE_IN_SECONDS);

        $amount = (float) $request->get_param('amount');
        $currency = strtoupper(sanitize_text_field((string) $request->get_param('currency')));

        if ($amount <= 0) {
            return new WP_Error('invalid_amount', 'Invalid amount', array('status' => 422));
        }

        // O multimoeda troca a moeda pelo filtro give_currency, que lê $_POST.
        // Esta rota recebe JSON, então $_POST fica vazio e o filtro nunca roda:
        // a moeda escolhida precisa vir do cliente. Só aceita moeda do Give.
        if ('' === $currency) {
            $currency = strtoupper((string) give_get_option('currency'));
        }

        $knownCurrencies = give_get_currencies();

        if (! isset($knownCurrencies[$currency])) {
            return new WP_Error('invalid_currency', 'Unsupported currency', array('status' => 422));
        }

        $reference = wp_generate_password(32, false, false);
        set_transient(
            'lknmp_mp_ref_' . $reference,
            array('amount' => round($amount, 2), 'currency' => $currency),
            2 * HOUR_IN_SECONDS
        );
        $returnUrl = rest_url('lknmp/v1/payments/checkpayment');

        $response = wp_remote_post(
            'https://api.mercadopago.com/checkout/preferences',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ),
                'body' => wp_json_encode(array(
                    'items' => array(
                        array(
                            'id' => $reference,
                            'title' => (string) $configs['tittle'],
                            'description' => (string) $configs['description'],
                            'quantity' => 1,
                            'unit_price' => round($amount, 2),
                            'currency_id' => $currency,
                        ),
                    ),
                    'external_reference' => $reference,
                    'back_urls' => array(
                        'success' => add_query_arg('ref', $reference, $returnUrl),
                        'pending' => add_query_arg('ref', $reference, $returnUrl),
                        'failure' => add_query_arg('ref', $reference, $returnUrl),
                    ),
                    'auto_return' => 'approved',
                )),
            )
        );

        if (is_wp_error($response)) {
            return new WP_Error('api_unreachable', 'Could not reach Mercado Pago', array('status' => 502));
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if ($statusCode < 200 || $statusCode >= 300 || ! is_array($body) || empty($body['id'])) {
            // Repassa o motivo real (ex.: currency_id invalid) em vez de um
            // 502 mudo que não diz o que corrigir.
            $reason = '';

            if (is_array($body)) {
                if (! empty($body['message'])) {
                    $reason = (string) $body['message'];
                }

                if (! empty($body['cause'][0]['description'])) {
                    $reason .= ' ' . (string) $body['cause'][0]['description'];
                }
            }

            return new WP_Error(
                'preference_failed',
                'Mercado Pago refused the preference' . ('' !== trim($reason) ? ': ' . trim($reason) : ''),
                array('status' => 502)
            );
        }

        return new WP_REST_Response(
            array(
                'preference_id' => (string) $body['id'],
                'reference' => $reference,
                'currency' => $currency,
            ),
            200
        );
    }

    /**
     * Trata o retorno do checkout. O status nunca vem da URL: é consultado na
     * API do Mercado Pago e comparado ao external_reference guardado.
     */
    public function get_handle_custom_payment_route($request)
    {
        $reference = sanitize_text_field((string) $request->get_param('ref'));
        $paymentId = sanitize_text_field((string) $request->get_param('payment_id'));

        if ('' === $reference || '' === $paymentId) {
            return new WP_Error('missing_params', 'Missing parameter ref or payment_id', array('status' => 422));
        }

        $configs = LknmpGatewayGiveWPHelper::get_configs();
        $accessToken = trim((string) $configs['token']);

        if ('' === $accessToken) {
            return new WP_Error('missing_token', 'Mercado Pago token not configured', array('status' => 500));
        }

        $donationId = (int) get_option('lknmp_gateway_' . $reference);

        if ($donationId <= 0) {
            return new WP_Error('no_donation_id', 'No donation ID found', array('status' => 404));
        }

        $apiResponse = wp_remote_get(
            'https://api.mercadopago.com/v1/payments/' . rawurlencode($paymentId),
            array(
                'timeout' => 30,
                'headers' => array('Authorization' => 'Bearer ' . $accessToken),
            )
        );

        if (is_wp_error($apiResponse) || 200 !== (int) wp_remote_retrieve_response_code($apiResponse)) {
            return new WP_Error('api_unreachable', 'Could not verify payment with Mercado Pago', array('status' => 502));
        }

        $payment = json_decode((string) wp_remote_retrieve_body($apiResponse), true);

        if (! is_array($payment)
            || empty($payment['external_reference'])
            || ! hash_equals($reference, (string) $payment['external_reference'])
        ) {
            return new WP_Error('reference_mismatch', 'Payment does not belong to this donation', array('status' => 403));
        }

        $donation = Donation::find($donationId);

        if (! $donation) {
            return new WP_Error('donation_not_found', 'Donation not found', array('status' => 404));
        }

        $mpStatus = (string) ($payment['status'] ?? '');

        // A moeda cobrada precisa ser a mesma da referência. O multimoeda pode
        // trocar a moeda do doador, e o valor só faz sentido junto dela.
        $expectedCurrency = strtoupper((string) give_get_payment_meta($donationId, 'lknmp_mp_currency'));

        if ('' === $expectedCurrency) {
            $expectedCurrency = strtoupper((string) $donation->amount->getCurrency());
        }

        $paidCurrency = strtoupper((string) ($payment['currency_id'] ?? ''));

        if ('' !== $paidCurrency && ! hash_equals($expectedCurrency, $paidCurrency)) {
            return new WP_Error(
                'currency_mismatch',
                'Paid currency does not match the donation currency',
                array('status' => 409)
            );
        }

        if ('approved' === $mpStatus) {
            // Sem comparar valores, dá para pagar centavos e concluir uma
            // doação de qualquer valor. Compara em centavos para evitar float.
            $paidCents = (int) round(((float) ($payment['transaction_amount'] ?? 0)) * 100);
            $dueCents = (int) round(((float) $donation->amount->formatToDecimal()) * 100);

            if ($paidCents !== $dueCents) {
                return new WP_Error(
                    'amount_mismatch',
                    'Paid amount does not match the donation amount',
                    array('status' => 409)
                );
            }

            $donation->status = DonationStatus::COMPLETE();
        } elseif ('rejected' === $mpStatus || 'cancelled' === $mpStatus) {
            $donation->status = DonationStatus::FAILED();
        } else {
            $donation->status = DonationStatus::PENDING();
        }

        $donation->save();

        $receiptId = give_get_payment_meta($donationId, '_give_payment_purchase_key');
        $redirect = home_url('/?givewp-route=donation-confirmation-receipt-view&receipt-id=' . sanitize_text_field($receiptId));

        wp_safe_redirect($redirect);
        exit;
    }
}
