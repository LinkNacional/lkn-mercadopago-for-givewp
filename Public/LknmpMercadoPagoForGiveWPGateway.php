<?php

namespace Lknmp\MercadoPagoForGiveWp\PublicView;

use Give\Donations\Models\Donation;
use Give\Donations\Models\DonationNote;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\Exceptions\Primitives\Exception;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Commands\PaymentPending;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Framework\PaymentGateways\PaymentGateway;
use Lknmp\MercadoPagoForGiveWp\Includes\LknmpMercadoPagoForGiveWPHelper;

/**
 * @inheritDoc
 */
final class LknmpMercadoPagoForGiveWPGateway extends PaymentGateway {
    public $idUnique;

    public function __construct() {
        $this->idUnique = uniqid();
    }

    /**
     * @inheritDoc
     */
    public static function id(): string {
        return 'lnk-mercadopago-forgivewp';
    }

    /**
     * @inheritDoc
     */
    public function getId(): string {
        return self::id();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return 'Mercado Pago';
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethodLabel(): string {
        return 'Mercado Pago';
    }

    /**
     * @inheritDoc
     */
    public function getLegacyFormFieldMarkup(int $formId, array $args): string {
        // Step 1: add any gateway fields to the form using html.  In order to retrieve this data later the name of the input must be inside the key gatewayData (name='gatewayData[input_name]').
        // Step 2: you can alternatively send this data to the $gatewayData param using the filter `givewp_create_payment_gateway_data_{gatewayId}`.

        $url_pagina = site_url();
        $configs = LknmpMercadoPagoForGiveWPHelper::get_configs();
        $MenssageErrorNameEmpty = __('The Name field is empty. Please fill in this field before proceeding.', 'lknmp-mercadopago-for-givewp');
        $MenssageErrorName = __('The Name field must be at least 3 letters.', 'lknmp-mercadopago-for-givewp');
        $MenssageErrorEmailEmpty = __('The Email field is empty. Please fill in this field before proceeding.', 'lknmp-mercadopago-for-givewp');
        $MenssageErrorEmailInvalid = __('The Email field is invalid. Please enter a valid email address.', 'lknmp-mercadopago-for-givewp');

        if (empty($configs['token']) && strlen($configs['token']) <= 5) {
            Give()->notices->print_frontend_notice(
                sprintf(
                    '%1$s %2$s',
                    esc_html__('Erro:', 'give'),
                    esc_html__('Mercado Pago Token was not provided or is invalid!', 'give')
                )
            );
        } elseif (empty($configs['key']) && strlen($configs['token']) <= 5) {
            Give()->notices->print_frontend_notice(
                sprintf(
                    '%1$s %2$s',
                    esc_html__('Erro:', 'give'),
                    esc_html__('Mercado Pago Public Key was not provided or is invalid!', 'give')
                )
            );
        }

        $html = "
            <fieldset class=\"no-fields\">
                <h3 id=\"warning-text\"></h3>
                <div id=\"wallet_container\"></div>
                <input type=\"hidden\" name=\"gatewayData[gatewayId]\" value=\"$this->idUnique\"></input>
            </fieldset>";

        return $html;
    }

    /**
     * @inheritDoc
     */
    public function createPayment(Donation $donation, $gatewayData): GatewayCommand {
        try {
            $idTeste = $gatewayData['gatewayId'];
            add_option("lkn_mercadopago_" . $idTeste, $donation->id);

            return new PaymentPending();
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();

            $donation->status = DonationStatus::FAILED();
            $donation->save();

            DonationNote::create(array(
                'donationId' => $donation->id,
                'content' => esc_html('Falha na doação. Razão: ' . $errorMessage) // Translators: %s é um espaço reservado para a mensagem de erro
            ));

            throw new PaymentGatewayException(esc_html($errorMessage));
        }
    }

    /**
     * @inerhitDoc
     */
    public function refundDonation(Donation $donation): PaymentRefunded {
        // Step 1: refund the donation with your gateway.
        // Step 2: return a command to complete the refund.
        return new PaymentRefunded();
    }

    /**
     * // TO DO needs this function to appear in v3 forms
     * @since 3.0.0
     */
    public function enqueueScript(int $formId): void {
        $configs = LknmpMercadoPagoForGiveWPHelper::get_configs();
        $url_pagina = site_url();

        $MenssageErrorNameEmpty = __('The Name field is empty. Please fill in this field before proceeding.', 'lknmp-mercadopago-for-givewp');
        $MenssageErrorName = __('The Name field must be at least 3 letters.', 'lknmp-mercadopago-for-givewp');
        $MenssageErrorEmailEmpty = __('The Email field is empty. Please fill in this field before proceeding.', 'lknmp-mercadopago-for-givewp');
        $MenssageErrorEmailInvalid = __('The Email field is invalid. Please enter a valid email address.', 'lknmp-mercadopago-for-givewp');
        $MenssageDonation = __('Donation of ', 'lknmp-mercadopago-for-givewp');
        $MenssageErrorToken = __('Mercado Pago Token was not provided or is invalid!');
        $MenssageErrorPublicKey = __('Mercado Pago Public Key was not provided or is invalid!');

        $hastoken = ! empty($configs['token']) && strlen($configs['token']) > 10 ? 'true' : 'false';
        $haspublicKey = ! empty($configs['key']) && strlen($configs['key']) > 10 ? 'true' : 'false';

        wp_enqueue_script( self::id(), plugin_dir_url( __FILE__ ) . 'js/plugin-script.js', array('jquery', self::id() . 'MercadoPago'), LKNMP_MERCADOPAGO_FOR_GIVEWP_VERSION, true );
        wp_enqueue_script( self::id() . 'MercadoPago', plugin_dir_url( __FILE__ ) . 'js/MercadoPago.js', array(), LKNMP_MERCADOPAGO_FOR_GIVEWP_VERSION, false);

        wp_localize_script(self::id(), 'urlPag', $url_pagina);
        wp_localize_script(self::id(), 'idUnique', uniqid());
        wp_localize_script(self::id(), 'configData', array(
            'advDebug' => $configs['advDebug'],
            'key' => $configs['key'],
            'tittle' => $configs['tittle'],
            'description' => $configs['description'],
            'token' => $configs['token']
        ));

        wp_localize_script(self::id(), 'lknMercadoPagoGlobals', array(
            'MenssageErrorNameEmpty' => $MenssageErrorNameEmpty,
            'MenssageErrorName' => $MenssageErrorName,
            'MenssageErrorEmailEmpty' => $MenssageErrorEmailEmpty,
            'MenssageErrorEmailInvalid' => $MenssageErrorEmailInvalid,
            'MenssageDonation' => $MenssageDonation,
            'MenssageErrorToken' => $MenssageErrorToken,
            'MenssageErrorPublicKey' => $MenssageErrorPublicKey,
            'token' => $hastoken,
            'publicKey' => $haspublicKey
        ));
    }
}