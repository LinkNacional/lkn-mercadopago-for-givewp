<?php

namespace Lkn\LknMercadoPagoForGiveWp\PublicView;

use Give\Donations\Models\Donation;
use Give\Donations\Models\DonationNote;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\Exceptions\Primitives\Exception;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Commands\PaymentPending;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Framework\PaymentGateways\PaymentGateway;
use Lkn\LknMercadoPagoForGiveWp\Includes\LknMercadoPagoForGiveWPHelper;

/**
 * @inheritDoc
 */
final class LknMercadoPagoForGiveWPGateway extends PaymentGateway {
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
        $configs = LknMercadoPagoForGiveWPHelper::get_configs();
        
        $html = "
        <!DOCTYPE html>    
        <body>
            <script src=\"https://sdk.mercadopago.com/js/v2\"></script>
        
            <fieldset class=\"no-fields\">
                <h3 id=\"warning-text\"></h3>
                <div id=\"wallet_container\"></div>
                <input type=\"hidden\" name=\"gatewayData[gatewayId]\" value=\"$this->idUnique\"></input>
                </fieldset>
        
            <script>
            function initializeMercadoPago() {
                let showMP = true;
                document.querySelector('input[type=\"submit\"]').disabled = true;

                //Enter não aciona wallet
                const walletButton = document.querySelector('#wallet_container');
                if (walletButton) {
                    walletButton.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                    });
                }

                function checkSidebarExists() {
                    const sidebarDiv = document.getElementById('give-sidebar-left');
                    return sidebarDiv !== null;
                }
        
                async function criarPreferenciaDePagamento() {
                    const amountGive = document.getElementsByName('give-amount')[0];
                    if(\"{$configs['advDebug']}\" == 'enabled'){
                        console.log(amountGive.value);
                    }
        
                    let valor = amountGive.value;
                    
                    if(\"{$configs['advDebug']}\" == 'enabled'){
                        console.log(valor)           
                    }
                    
                    const url = 'https://api.mercadopago.com/checkout/preferences';
                    const preference = {
                        \"back_urls\": {
                            \"success\": `${url_pagina}/wp-json/mercadopago/v1/payments/checkpayment?id={$this->idUnique}&statusFront=1`,
                            \"pending\": `${url_pagina}/wp-json/mercadopago/v1/payments/checkpayment?id={$this->idUnique}&statusFront=2`,
                            \"failure\": `${url_pagina}/wp-json/mercadopago/v1/payments/checkpayment?id={$this->idUnique}&statusFront=3`
                        },
                        \"items\": [{
                            \"id\": \"{$this->idUnique}\",
                            \"title\": \"{$configs['tittle']}\",
                            \"description\": \"{$configs['description']}\",
                            \"quantity\": 1,
                            \"currency_id\": \"BRL\",
                            \"unit_price\": parseFloat(valor)
                        }]
                    };
        
                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer {$configs['token']}`
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
                if (document.querySelector('#wallet_container')) {
                    criarPreferenciaDePagamento()
                        .then(preferenceID => {
                            if(\"{$configs['advDebug']}\" == 'enabled'){
                                console.log('ID da preferência criada:', preferenceID);
                            }
                            const mp = new MercadoPago('{$configs['key']}');
                            const bricksBuilder = mp.bricks();
                            mp.bricks().create(\"wallet\", \"wallet_container\", {
                                initialization: {
                                    preferenceId: preferenceID,
                                    redirectMode: checkSidebarExists() ? \"self\" : \"blank\"
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
        
                        checkInputs();
                        if(!document.querySelector('#verificador')){  
                            observeDonationChanges();
                        }
                        observeFormChanges();
                }
        
                function observeDonationChanges() {
                    const targetNode = document.querySelector('input[name=\"give-amount\"]');
                    if (!targetNode) return;

                    const verificadorDiv = document.createElement(\"div\");
                    verificadorDiv.id = \"verificador\";
                    document.body.appendChild(verificadorDiv);
                
                    const observer = new MutationObserver(function(mutationsList, observer) {
                        for (const mutation of mutationsList) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'data-amount') {
                                if(\"{$configs['advDebug']}\" == 'enabled'){
                                    console.log('Mudança no data-amount');
                                }
        
                                const nomeInput = document.querySelector('input[name=\"give_first\"]');
                                const emailInput = document.querySelector('input[name=\"give_email\"]');
                              
                                    const oldButton = document.querySelector('#wallet_container');
        
                                    if (oldButton) {
                                        oldButton.remove();
                                    }
        
                                    criarPreferenciaDePagamento().then(newPreferenceID => {
                                        if(\"{$configs['advDebug']}\" == 'enabled'){
                                            console.log('Nova preferência criada:', newPreferenceID);
                                        }
        
                                        const newButton = document.createElement('div');
                                        newButton.id = 'wallet_container';
                                        const fieldset = document.querySelector('.no-fields');

                                        if (showMP) {
                                            newButton.style.display = 'block';
                                        } else {
                                            newButton.style.display = 'none';
                                        }

                                        fieldset.appendChild(newButton);
        
                                        const mp = new MercadoPago('{$configs['key']}');
                                        const bricksBuilder = mp.bricks();
                                        mp.bricks().create(\"wallet\", \"wallet_container\", {
                                            initialization: {
                                                preferenceId: newPreferenceID,
                                                redirectMode: \"blank\"
                                            },
                                            customization: {
                                                texts: {
                                                    valueProp: 'smart_option'
                                                }
                                            }
                                        });
                                    }).catch(error => {
                                        console.error('Erro ao criar nova preferência de pagamento:', error);
                                    });
                            }
                        }
                    });
        
                    observer.observe(targetNode, {
                        attributes: true
                    });
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

                    function isValidEmail(email) {
                        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        return emailPattern.test(email);
                    }
                  
                    if (nomeInput && emailInput && walletContainer) {
                        if (!nomeInput.value.trim()) {
                            walletContainer.style.display = 'none';
                            warningText.textContent = 'O campo Nome está vazio. Por favor, preencha este campo antes de prosseguir.';
                            showMP = false;
                        } else if (nomeInput.value.trim().length < 3) {
                            walletContainer.style.display = 'none';
                            warningText.textContent = 'O campo Nome deve ter no mínimo 3 letras.';
                            showMP = false;
                        } else if (!emailInput.value.trim()) {
                            walletContainer.style.display = 'none';
                            warningText.textContent = 'O campo Email está vazio. Por favor, preencha este campo antes de prosseguir.';
                            showMP = false;
                
                        } else if (!isValidEmail(emailInput.value)) {
                            walletContainer.style.display = 'none';
                            warningText.textContent = 'O campo Email está inválido. Por favor, insira um endereço de email válido.';
                            showMP = false;
                        } else {
                            walletContainer.style.display = 'block';
                            warningText.textContent = '';
                            showMP = true;
                        }
                    }
                }
            }
        
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeMercadoPago);
            } else {
                initializeMercadoPago();
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
            $idTeste = $gatewayData['gatewayId'];
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
     * // TO DO needs this function to appear in v3 forms
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

        $configs = LknMercadoPagoForGiveWPHelper::get_configs();

        wp_localize_script(self::id(), 'configData', $configs);
    }
}