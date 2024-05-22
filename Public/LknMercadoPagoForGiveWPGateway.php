<?php

namespace Lkn\LknMercadoPagoForGiveWp\PublicView;

use Give\Donations\Models\Donation;
use Give\Donations\Models\DonationNote;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\Exceptions\Primitives\Exception;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Commands\PaymentComplete;
use Give\Framework\PaymentGateways\Commands\PaymentPending;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Framework\PaymentGateways\PaymentGateway;

/**
 * @inheritDoc
 */
final class LknMercadoPagoForGiveWPGateway extends PaymentGateway {
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
        return __('Mercado Pago', 'example-give');
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethodLabel(): string {
        return __('Mercado Pago', 'example-give');
    }

    /**
     * @inheritDoc
     */
    public function getLegacyFormFieldMarkup(int $formId, array $args): string {
        // Step 1: add any gateway fields to the form using html.  In order to retrieve this data later the name of the input must be inside the key gatewayData (name='gatewayData[input_name]').
        // Step 2: you can alternatively send this data to the $gatewayData param using the filter `givewp_create_payment_gateway_data_{gatewayId}`.

        $url_pagina = site_url();
        $idUnique = uniqid();

        $html = "
        <!DOCTYPE html>    
        <body>
            <script src=\"https://sdk.mercadopago.com/js/v2\"></script>
        
            <fieldset class=\"no-fields\">
                <h3 id=\"warning-text\"></h3>
                <div id=\"wallet_container\"></div>
                </fieldset>
        
            <script>
            if (typeof hasRender === 'undefined'){
            
                document.querySelector('input[type=\"submit\"]').disabled = true;


                async function criarPreferenciaDePagamento() {
                    const amountGive = document.getElementsByName('give-amount')[0]
                    console.log(amountGive.value)
                        
                    
                    const url = 'https://api.mercadopago.com/checkout/preferences';
                    const token = 'TEST-4103642140602972-050610-67d0c5a5cccd4907b1208fded2115f5c-1052089223';
                    const preference = {
                        \"back_urls\": {
                            \"success\": `${url_pagina}/wp-json/mercadopago/v1/payments/checkpayment?id=${idUnique}&statusFront=1`,
                            \"pending\": `${url_pagina}/wp-json/mercadopago/v1/payments/checkpayment?id=${idUnique}&statusFront=2`, //site_url()
                            \"failure\": `${url_pagina}/wp-json/mercadopago/v1/payments/checkpayment?id=${idUnique}&statusFront=3` //site_url()
                        },
                        \"items\": [{
                            \"id\": \"Doação X\",
                            \"title\": \"Doação via Mercado Pago\",
                            \"description\": \"Sua doação foi de \",
                            \"quantity\": 1,
                            \"currency_id\": \"BRL\",
                            \"unit_price\": parseFloat(amountGive.value) 
                        }]
                    };
                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer TEST-4103642140602972-050610-67d0c5a5cccd4907b1208fded2115f5c-1052089223`
                            },
                            body: JSON.stringify(preference)
                        });
                        if (!response.ok) {
                            throw new Error(`Erro ao criar preferência de pagamento:{}`);
                        }
                        const data = await response.json();
                        return data.id;
                    } catch (error) {
                        console.error('Erro ao criar preferência de pagamento:', error);
                        throw error;
                    }
                }

                if (document.querySelector('input[name=\"give_first\"]') && document.querySelector('input[name=\"give_email\"]') && document.querySelector('#wallet_container')) {
                    criarPreferenciaDePagamento()
                        .then(preferenceID => {
                            console.log('ID da preferência criada:', preferenceID);
                            preferenceID = preferenceID;
                            const mp = new MercadoPago('TEST-c4abbb26-f793-4baf-a4a4-7e132e2350cb');
                            const bricksBuilder = mp.bricks();
                            mp.bricks().create(\"wallet\", \"wallet_container\", {
                                initialization: {
                                    preferenceId: preferenceID,
                                    redirectMode: \"blank\"
                                },
                                customization: {
                                    texts: {
                                        valueProp: 'smart_option'
                                    }
                                }
                            });
                        })
                        .catch(error => {
                            console.error('Erro ao criar preferência de pagamento:', error);
                        });
                    if (document.querySelector('input[name=\"give_first\"]').value) {
                        // Se ambos os campos de nome e e-mail estiverem preenchidos, habilitar o container
                        document.querySelector('#wallet_container').style.display = 'block';
                        document.querySelector('#warning-text').textContent = '';
                        
                    } else {
                        // Se algum campo estiver vazio, desabilitar o container e exibir a mensagem de aviso
                        document.querySelector('#wallet_container').style.display = 'none';
                        document.querySelector('#warning-text').textContent = 'Nome ou Email não foram preenchidos. Por favor, preencha todos os campos antes de prosseguir.';
                    }
                }

                function observeFormChanges() {
                    const nomeInput = document.querySelector('input[name=\"give_first\"]');
                    const emailInput = document.querySelector('input[name=\"give_email\"]');
                    if (nomeInput && emailInput) {
                        nomeInput.addEventListener('input', checkInputs);
                        emailInput.addEventListener('input', checkInputs);
                    }
                }

                function checkInputs() {
                    const nomeInput = document.querySelector('input[name=\"give_first\"]');
                    const emailInput = document.querySelector('input[name=\"give_email\"]');
                    const walletContainer = document.querySelector('#wallet_container');
                    let warningText = document.querySelector('#warning-text');
                    if (nomeInput && emailInput && walletContainer) {
                        if (nomeInput.value && emailInput.value) {
                            // Se ambos os campos de nome e e-mail estiverem preenchidos, habilitar o container
                            walletContainer.style.display = 'block';
                            warningText.textContent = '';
                        } else {
                            // Se algum campo estiver vazio, desabilitar o container e exibir a mensagem de aviso
                            walletContainer.style.display = 'none';
                            warningText.textContent = 'Nome ou Email não foram preenchidos. Por favor, preencha todos os campos antes de prosseguir.';
                        }
                    }
                }

                //observeDonationChanges();
                observeFormChanges();
                //observeMetodoChanges();
            } else{
                //render já foi declarada
                console.log('renderiza denovo')
            }

            </script>
        </body>
        </html>
        ";
        return $html;
    }

    /**
     * @inheritDoc
     */
    public function createPayment(Donation $donation, $gatewayData): GatewayCommand {
        try {
            // Step 1: Validate any data passed from the gateway fields in $gatewayData.  Throw the PaymentGatewayException if the data is invalid.
            // if (empty($gatewayData['example-gateway-id'])) {
            //     throw new PaymentGatewayException(__('Example payment ID is required.', 'example-give' ) );
            // }

            // // Step 2: Create a payment with your gateway.
            //$response = $this->exampleRequest(array('transaction_id' => $gatewayData['example-gateway-id']));

            // // Step 3: Return a command to complete the donation. You can alternatively return PaymentProcessing for gateways that require a webhook or similar to confirm that the payment is complete. PaymentProcessing will trigger a Payment Processing email notification, configurable in the settings.
            
            // return new PaymentComplete($response['transaction_id']);

            $idTeste = $gatewayData['idUniqueAlterar'];
            add_option($idTeste, $donation->id);

            return new PaymentPending();
        } catch (Exception $e) {
            // Step 4: If an error occurs, you can update the donation status to something appropriate like failed, and finally throw the PaymentGatewayException for the framework to catch the message.
            $errorMessage = $e->getMessage();
    
            $donation->status = DonationStatus::FAILED();
            $donation->save();
    
            DonationNote::create(array(
                'donationId' => $donation->id,
                'content' => sprintf(esc_html__('Donation failed. Reason: %s', 'example-give'), $errorMessage) // Translators: %s é um espaço reservado para a mensagem de erro
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
     * Example request to gateway
     */
    private function exampleRequest(array $data): array {
        return array_merge(array(
            'success' => true,
            'transaction_id' => '1234567890',
            'subscription_id' => '0987654321',
        ), $data);
    }

    /**
     * // TODO needs this function to appear in v3 forms
     * @since 3.0.0
     */
    public function enqueueScript(int $formId): void {
        // wp_enqueue_script(
        //     self::id(),
        //     LKN_MERCADOPAGO_FOR_GIVEWP_URL . 'Public/js/pluginScript.js',
        //     array('wp-element', 'wp-i18n'),
        //     LKN_MERCADOPAGO_FOR_GIVEWP_VERSION,
        //     true
        // );
        
        wp_enqueue_script( self::id(), plugin_dir_url( __FILE__ ) . 'js/plugin-script.js', array('jquery', self::id() . 'MercadoPago'), LKN_MERCADOPAGO_FOR_GIVEWP_VERSION, true );

        wp_enqueue_script( self::id() . 'MercadoPago', plugin_dir_url( __FILE__ ) . 'js/MercadoPago.js', array(), LKN_MERCADOPAGO_FOR_GIVEWP_VERSION, false);

        $url_pagina = site_url();
        wp_localize_script(self::id(), 'urlPag', $url_pagina);
        wp_localize_script(self::id(), 'idUnique', uniqid());
    }
}