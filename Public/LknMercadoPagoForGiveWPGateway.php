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

    //     $htmlJS = "
        // <script>
        // let preferenceID = null;
        // let hasRenderedComponents = false;
    
        // function renderComponentsOnce() {
    //     if (!hasRenderedComponents) {
    //         criarPreferenciaDePagamento()
    //             .then(preferenceID => {
    //                 console.log('ID da preferência criada:', preferenceID);
    //                 preferenceID = preferenceID;
    //                 const mp = new MercadoPago('TEST-c4abbb26-f793-4baf-a4a4-7e132e2350cb');
    //                 const bricksBuilder = mp.bricks();
    //                 mp.bricks().create(\"wallet\", \"wallet_container\", {
    //                     initialization: {
    //                         preferenceId: preferenceID,
    //                         redirectMode: \"blank\"
    //                     },
    //                     customization: {
    //                         texts: {
    //                             valueProp: 'smart_option'
    //                         }
    //                     }
    //                 });
    //             })
    //             .catch(error => {
    //                 console.error('Erro ao criar preferência de pagamento:', error);
    //             });
    
    //         setTimeout(updateDonationAmount, 0);
    //         setTimeout(checkInputs, 0);
    
    //         observeDonationChanges();
    //         observeFormChanges();
    //         observeMetodoChanges();
    
    //         hasRenderedComponents = true;
    //     }
        // }
    
        // function sanitizeInput(input) {
    //     if (typeof input === 'string') {
    //         return input.replace(/<[^>]*>/g, '').trim();
    //     }
    //     else if (typeof input === 'number') {
    //         return parseFloat(input.toString().replace(/\D/g, ''));
    //     }
    //     else {
    //         return input;
    //     }
        // }
    
        // async function criarPreferenciaDePagamento() {
    //     const url = 'https://api.mercadopago.com/checkout/preferences';
    //     const token = 'TEST-4103642140602972-050610-67d0c5a5cccd4907b1208fded2115f5c-1052089223';
    
    //     const nome = sanitizeInput(document.querySelector('input[name=\"firstName\"]').value);
    //     const sobrenome = sanitizeInput(document.querySelector('input[name=\"lastName\"]').value);
    //     const email = sanitizeInput(document.querySelector('input[name=\"email\"]').value);
    
    //     const valorText = document.querySelector('.givewp-elements-donationSummary__list__item__value').textContent;
    //     const valorNumerico = parseFloat(valorText.replace(/[^\d.,]/g, ''));
    //     console.log(valorNumerico)
        
    //     const preference = {
    //         \"back_urls\": {
    //             \"success\": `https://wordpress.local/wp-json/mercadopago/v1/payments/{colocarformID}`,
    //             \"pending\": `https://wordpress.local/wp-json/mercadopago/v1/payments/{colocarformID}`,
    //             \"failure\": `https://wordpress.local/wp-json/mercadopago/v1/payments/{colocarformID}`
    //         },
    //         \"items\": [{
    //             \"id\": \"Doação X\",
    //             \"title\": \"Doação via Mercado Pago\",
    //             \"description\": \"Sua doação foi de \",
    //             \"quantity\": 1,
    //             \"currency_id\": \"BRL\",
    //             \"unit_price\": valorNumerico
    //         }]
    //     };
    //     try {
    //         const response = await fetch(url, {
    //             method: 'POST',
    //             headers: {
    //                 'Content-Type': 'application/json',
    //                 'Authorization': `Bearer {colocartoken}`
    //             },
    //             body: JSON.stringify(preference)
    //         });
    //         if (!response.ok) {
    //             throw new Error(`Erro ao criar preferência de pagamento: {colocar responde e status}`);
    //         }
    //         const data = await response.json();
    //         return data.id;
    //     } catch (error) {
    //         console.error('Erro ao criar preferência de pagamento:', error);
    //         throw error;
    //     }
        // }
    
        // function updateDonationAmount() {
    //     const fieldValueElement = document.querySelector('.givewp-elements-donationSummary__list__item__value');
    //     const donationAmountElement = document.querySelector('#donation-amount');
    //     if (fieldValueElement && donationAmountElement) {
    //         const fieldValue = fieldValueElement.textContent;
    //         donationAmountElement.textContent = fieldValue;
    //     }
        // }
    
        // function observeMetodoChanges() {
    //     const checkGateways = () => {
    //         const offlineGateway = document.querySelector('.givewp-fields-gateways__gateway--offline.givewp-fields-gateways__gateway--active');
    //         const manualGateway = document.querySelector('.givewp-fields-gateways__gateway--manual.givewp-fields-gateways__gateway--active');
    
    //         if (offlineGateway || manualGateway) {
    //             document.querySelector('button[type=\"submit\"]').disabled = false;
    //             hasRenderedComponents = false;
    //         } else {
    //             document.querySelector('button[type=\"submit\"]').disabled = true;
    //         }
    //     };
    
    //     checkGateways();
    
    //     const observer = new MutationObserver((mutationsList, observer) => {
    //         checkGateways();
    //     });
    
    //     const targetNode = document.querySelector('.givewp-fields-gateways');
    
    //     const config = { childList: true, subtree: true };
    
    //     observer.observe(targetNode, config);
        // }
    
        // function debounce(func, delay) {
    //     let timer;
    //     return function () {
    //         const context = this;
    //         const args = arguments;
    //         clearTimeout(timer);
    //         timer = setTimeout(() => {
    //             func.apply(context, args);
    //         }, delay);
    //     };
        // }
    
        // function observeDonationChanges() {
    //     const targetNode = document.querySelector('.givewp-elements-donationSummary__list__item__value');
    //     if (!targetNode) return;
    
    //     const observer = new MutationObserver(debounce(function (mutationsList, observer) {
    //         for (const mutation of mutationsList) {
    //             if (mutation.type === 'childList' || mutation.type === 'characterData') {
    //                 updateDonationAmount();
    
    //                 const nomeInput = document.querySelector('input[name=\"firstName\"]');
    //                 const emailInput = document.querySelector('input[name=\"email\"]');
    //                 if (nomeInput.value && emailInput.value) {
    //                     const oldButton = document.querySelector('#wallet_container');
    
    //                     if (oldButton) {
    //                         oldButton.remove();
    //                         console.log('botao removido')
    //                     }
                        
    //                     criarPreferenciaDePagamento().then(newPreferenceID => {
    
    //                         console.log('Nova preferência criada:', newPreferenceID);
    
    //                         preferenceID = newPreferenceID;
    
    //                         const newButton = document.createElement('div');
    //                         newButton.id = 'wallet_container';
    //                         const fieldset = document.querySelector('.no-fields');
    
    //                         fieldset.appendChild(newButton);
    
    //                         const mp = new MercadoPago('TEST-c4abbb26-f793-4baf-a4a4-7e132e2350cb');
    //                         const bricksBuilder = mp.bricks();
    //                         mp.bricks().create(\"wallet\", \"wallet_container\", {
    //                             initialization: {
    //                                 preferenceId: preferenceID,
    //                                 redirectMode: \"blank\"
    //                             },
    //                             customization: {
    //                                 texts: {
    //                                     valueProp: 'smart_option'
    //                                 }
    //                             }
    //                         });
    
    //                     }).catch(error => {
    //                         console.error('Erro ao criar nova preferência de pagamento:', error);
    //                     });
    //                 }
    //             }
    //         }
    //     }, 500));
    
    //     observer.observe(targetNode, {
    //         attributes: true,
    //         childList: true,
    //         subtree: true,
    //         characterData: true
    //     });
        // }
    
        // function observeFormChanges() {
    //     const nomeInput = document.querySelector('input[name=\"firstName\"]');
    //     const emailInput = document.querySelector('input[name=\"email\"]');
    //     if (nomeInput && emailInput) {
    //         nomeInput.addEventListener('input', checkInputs);
    //         emailInput.addEventListener('input', checkInputs);
    //     }
        // }
        // function checkInputs() {
    //     const nomeInput = document.querySelector('input[name=\"firstName\"]');
    //     const emailInput = document.querySelector('input[name=\"email\"]');
    //     const walletContainer = document.querySelector('#wallet_container');
    //     let warningText = document.querySelector('#warning-text');
    //     if (nomeInput && emailInput && walletContainer) {
    //         if (nomeInput.value && emailInput.value) {
    //             walletContainer.style.display = 'block';
    //             warningText.textContent = '';
    //         } else {
    //             walletContainer.style.display = 'none';
    //             warningText.textContent = 'Nome ou Email não foram preenchidos. Por favor, preencha todos os campos antes de prosseguir.';
    //         }
    //     }
        // }
    
        // const gateway = {
    //     id: 'lnk-mercadopago-forgivewp',
    //     async initialize() {
    //     },
    //     async beforeCreatePayment(values) {
    //         if (values.firstname === 'error') {
    //             throw new Error('Gateway failed');
    //         }
    //     },
    //     async afterCreatePayment(response) {
    //         fetch('https://wordpress.local/wp-json/mercadopago/v1/payments/checkpayment', {
    //             method: 'POST',
    //             headers: {
    //                 'Content-Type': 'application/json'
    //             },
    //             body: JSON.stringify({
    //                 id: 123,
    //                 status: 'pendente'
    //             })
    //         })
    //             .then(response => response.json())
    //             .then(data => console.log(data))
    //             .catch(error => console.error('Erro:', error));
    //     },
    //     Fields() {
    //         renderComponentsOnce();
    
    //         return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(\"fieldset\", {
    //             className: \"no-fields\"
    //         }, /*#__PURE__*/React.createElement(\"h1\", null, \"Doa\xE7\xE3o de \", /*#__PURE__*/React.createElement(\"span\", {
    //             id: \"donation-amount\"
    //         })), /*#__PURE__*/React.createElement(\"h3\", {
    //             id: \"warning-text\"
    //         })));
    //     }
        // };
        // </script>";
        $html = "
        <!DOCTYPE html>    
        <body>
            <script src=\"https://sdk.mercadopago.com/js/v2\"></script>
        
            <fieldset class=\"no-fields\">
                <h3 id=\"warning-text\"></h3>
                <div id=\"wallet_container\"></div>
                </fieldset>
        
            <script>
                async function criarPreferenciaDePagamento() {
                    const url = 'https://api.mercadopago.com/checkout/preferences';
                    const token = 'TEST-4103642140602972-050610-67d0c5a5cccd4907b1208fded2115f5c-1052089223';
                    const preference = {
                        \"back_urls\": {
                            \"success\": `a`,
                            \"pending\": `a`, //site_url()
                            \"failure\": `a` //site_url()
                        },
                        \"items\": [{
                            \"id\": \"Doação X\",
                            \"title\": \"Doação via Mercado Pago\",
                            \"description\": \"Sua doação foi de \",
                            \"quantity\": 1,
                            \"currency_id\": \"BRL\",
                            \"unit_price\": 100
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

                const nomeInput = document.querySelector('input[name=\"give_first\"]');
                const emailInput = document.querySelector('input[name=\"give_email\"]');
                const walletContainer = document.querySelector('#wallet_container');
                let warningText = document.querySelector('#warning-text');
                if (nomeInput && emailInput && walletContainer) {
                    if (nomeInput.value) {
                        // Se ambos os campos de nome e e-mail estiverem preenchidos, habilitar o container
                        walletContainer.style.display = 'block';
                        warningText.textContent = '';

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
                        
                    } else {
                        // Se algum campo estiver vazio, desabilitar o container e exibir a mensagem de aviso
                        walletContainer.style.display = 'none';
                        console.log(warningText)
                        warningText.textContent = 'Nome ou Email não foram preenchidos. Por favor, preencha todos os campos antes de prosseguir.';
                    }
                }

               
        
                    const totalElement = document.querySelector('.give-donation-summary-table-wrapper table tfoot tr th[data-tag=\"total\"]');
                    if (totalElement) {
                        const totalText = totalElement.textContent.trim();
                        console.log('Texto total:', totalText);
                        const totalText2 = totalText.replace(/\s+/g, '');
                        console.log('Texto total sem espaços:', totalText2);
                        console.log('totalElement.innerHTML:', totalElement.innerHTML);
                        console.log('totalElement.outerText:', totalElement.outerText);
                    } else {
                        console.log('Elemento de total não encontrado.');
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
                            criarPreferenciaDePagamento()
                            .then(preferenceID => {
                                console.log('ID da preferência criada:', preferenceID);
                                const mp = new MercadoPago('TEST-c4abbb26-f793-4baf-a4a4-7e132e2350cb');
                                const bricksBuilder = mp.bricks();
                                bricksBuilder.create(\"wallet\", \"wallet_container\", {
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
            $idTeste = $gatewayData['idTestAlterar'];
            add_option($idTeste, $donation->id);
            // Step 1: Validate any data passed from the gateway fields in $gatewayData.  Throw the PaymentGatewayException if the data is invalid.
            // if (empty($gatewayData['example-gateway-id'])) {
            //     throw new PaymentGatewayException(__('Example payment ID is required.', 'example-give' ) );
            // }

            // // Step 2: Create a payment with your gateway.
            // $response = $this->exampleRequest(array('transaction_id' => $gatewayData['example-gateway-id']));

            // // Step 3: Return a command to complete the donation. You can alternatively return PaymentProcessing for gateways that require a webhook or similar to confirm that the payment is complete. PaymentProcessing will trigger a Payment Processing email notification, configurable in the settings.
            
            // return new PaymentComplete($response['transaction_id']);
            
            // $idDonation = $donation->id;
            // wp_localize_script(self::id(), 'idDonation', $idDonation);

            // sleep(3);

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

        $url_pagina = admin_url();
        $url_pagina_personalizada = add_query_arg(array(
            'post_type' => 'give_forms',
            'page' => 'givewp-form-builder',
            'donationFormID' => $formId
        ), $url_pagina);

        $urlsPreferences = array(
            'back_urls' => array(
                'success' => $url_pagina_personalizada,
                'pending' => $url_pagina_personalizada,
                'failure' => $url_pagina_personalizada
            )
        );

        wp_localize_script(self::id(), 'preferencia', $urlsPreferences);
        wp_localize_script(self::id(), 'idTeste', uniqid());
    }
}