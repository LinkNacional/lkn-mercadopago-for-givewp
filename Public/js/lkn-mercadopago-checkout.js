let checkPaymentStatus = null;
let totalElement = ''
let paymentReference = ''

const LknmpGatewayGiveWP = {
    id: 'lknmp-gateway-givewp',
    name: 'Mercado Pago Checkout',
    supports: ['one-time'],

    async initialize() {
        let buttonFound = false;

        window.onload = () => {
            const observer = new MutationObserver((mutationsList, observer) => {
                const customInput = document.getElementById('amount-custom');

                if (!customInput) {
                    buttonFound = false;
                }

                if (customInput && !buttonFound) {
                    buttonFound = true;
                    customInput.addEventListener('input', () => {
                        let value = customInput.value;

                        if (value.slice(-1) === '.' || value.slice(-1) === ',') {
                            customInput.value = value.slice(0, -1);
                        }
                    });
                }
            });

            const config = { childList: true, subtree: true };

            // Começa a observar o `document.body`
            observer.observe(document.body, config);
        };
    },

    async beforeCreatePayment(values) {
        if (values.firstname === 'error') {
            throw new Error('Gateway failed');
        }

        if (!paymentReference) {
            throw new Error(lknMercadoPagoGlobals.MessageErrorToken);
        }

        return {
            gatewayId: paymentReference
        };
    },

    async afterCreatePayment(response) {
        // Aqui pode ser adicionado o redirecionamento ou a exibição de uma resposta
    },

    Fields() {
        let floatValue

        // A moeda escolhida vive no form do Give (multimoeda). O servidor nao
        // consegue ve-la: a rota REST recebe JSON e o filtro give_currency le
        // $_POST. Entao ela vai no corpo da requisicao.
        //
        // Cada caminho de multimoeda usa um markup diferente:
        //   - GiveWP v3:        .givewp-fields-amount__currency-select
        //   - GiveWP legado:    select.give-cs-select-currency + input give-cs-form-currency
        //   - Link Nacional:    #give-mc-select + input give-mc-selected-currency
        function lknCurrencyInputs() {
            return [
                '.givewp-fields-amount__currency-select',
                'select.give-cs-select-currency',
                'input[name="give-cs-form-currency"]',
                '#give-mc-select',
                'input[name="give-mc-selected-currency"]'
            ];
        }

        function lknSelectedCurrency() {
            const v3Select = document.querySelector('.givewp-fields-amount__currency-select');
            if (v3Select && v3Select.value) {
                return String(v3Select.value).toUpperCase();
            }

            // Os inputs escondidos sao os mais confiaveis: sao eles que os
            // plugins atualizam e que vao no POST do Give.
            const hidden = document.querySelector('input[name="give-cs-form-currency"], input[name="give-mc-selected-currency"]');
            if (hidden && hidden.value) {
                return String(hidden.value).toUpperCase();
            }

            const legacySelect = document.querySelector('select.give-cs-select-currency, #give-mc-select');
            if (legacySelect && legacySelect.value) {
                return String(legacySelect.value).toUpperCase();
            }

            const label = document.querySelector('.givewp-fields-amount__currency-container span');
            if (label && label.textContent.trim()) {
                return label.textContent.trim().toUpperCase();
            }

            return (typeof lknGlobalData !== 'undefined' && lknGlobalData.baseCurrency) || '';
        }

        function lknCurrentFormId() {
            if (typeof lknGlobalData !== 'undefined' && lknGlobalData.formId) {
                return parseInt(lknGlobalData.formId, 10) || 0;
            }

            const field = document.querySelector('input[name="give-form-id"]');
            if (field && field.value) {
                return parseInt(field.value, 10) || 0;
            }

            return 0;
        }

        // Função para exibir mensagens de erro
        function lknNotice(title, message) {
            return React.createElement("div", { className: "error-notice" },
                React.createElement("strong", null, title), " ", message);
        }

        const firstName = document.querySelector('input[name="firstName"]');
        const lastName = document.querySelector('input[name="lastName"]');
        const emailField = document.querySelector('input[name="email"]');

        if (!firstName || !lastName || !emailField) {
            return lknNotice('Erro:', lknMercadoPagoGlobals.MessageNotFoundFields);
        }

        if (firstName) {
            const name = firstName.value.trim(); // remove espaços antes e depois

            if (name === '') {
                return lknNotice('Erro:', lknMercadoPagoGlobals.MessageErrorNameEmpty);
            }

            if (name.length < 3) {
                return lknNotice('Erro:', lknMercadoPagoGlobals.MessageErrorName);
            }
        }

        if (lastName) {
            const name = lastName.value.trim(); // remove espaços antes e depois

            if (name === '') {
                return lknNotice('Erro:', lknMercadoPagoGlobals.MessageErrorLastNameEmpty);
            }

            if (name.length < 3) {
                return lknNotice('Erro:', lknMercadoPagoGlobals.MessageErrorLastName);
            }
        }

        if (emailField) {
            const email = emailField.value.trim();

            if (email === '') {
                return lknNotice('Erro:', lknMercadoPagoGlobals.MessageErrorEmailEmpty);
            }

            if (!email.includes('@')) {
                return lknNotice('Erro:', lknMercadoPagoGlobals.MessageErrorEmailInvalid);
            }
        }

        if (lknMercadoPagoGlobals.token === 'false') {
            return lknNotice('Erro:', lknMercadoPagoGlobals.MessageErrorToken);
        } else if (lknMercadoPagoGlobals.publicKey === 'false') {
            return lknNotice('Erro:', lknMercadoPagoGlobals.MessageErrorPublicKey);
        }

        React.useEffect(() => {

            const mp = new MercadoPago(lknGlobalData.key, { locale: 'pt-BR' });

            let debounceTimeout = null
            let currentAmount = null
            let amountField = document.querySelector('input[name="amount"]');
            if (!amountField) {
                document.getElementById("warning-text").textContent = lknMercadoPagoGlobals.MessageNotFoundField;
                return;
            } else {
                if (isNumeric(amountField.value)) {
                    currentAmount = amountField.value
                } else {
                    let customAmount = document.querySelector('#amount-custom');
                    if (customAmount && customAmount.value) {
                        let customValue = customAmount.value.replace(/[.,]$/, '')

                        currentAmount = customValue
                    }
                }
            }

            // Função para gerar a preferência do MercadoPago
            async function preferenceGeneration() {
                const amount = currentAmount;
                const warningEl = document.getElementById("warning-text");
                const walletContainer = document.getElementById("wallet_container");

                // Limpa mensagens de erro anteriores
                if (warningEl) {
                    warningEl.textContent = "";
                }

                let replaceValue = ','
                if (decimalSeparator.includes(',')) {
                    replaceValue = '.'
                }

                const valueFormated = await amount.replace(/[^\d.,]/g, '').replace(new RegExp(`\\${replaceValue}`, 'g'), '').replace(',', '.')
                floatValue = parseFloat(valueFormated);

                const donationOf = document.querySelector('.fields-lknmp h1');
                if (donationOf) {
                    totalElement = document.querySelector('#total .givewp-elements-donationSummary__list__item__value');
                    if (totalElement) {
                        donationOf.textContent = lknMercadoPagoGlobals.MessageDonation + ' ' + totalElement.textContent
                    }
                }

                let customAmount = document.querySelector('#amount-custom');
                if (customAmount && customAmount.value) {
                    const elAmount = document.querySelector('#amount .givewp-elements-donationSummary__list__item__value');
                    if (elAmount) {
                        elAmount.textContent = customAmount.value
                    }

                    // Para o elemento com ID "total"
                    const elTotal = document.querySelector('#total .givewp-elements-donationSummary__list__item__value');
                    if (elTotal) {
                        elTotal.textContent = customAmount.value
                    }
                }

                // Verifica se o valor é válido
                if (!isNumeric(floatValue)) {
                    warningEl.textContent = lknMercadoPagoGlobals.MessageNotInvalidValue;
                    walletContainer.innerHTML = ""; // remove botão anterior se houver
                    return;
                }

                // Remove o botão anterior
                if (walletContainer) {
                    walletContainer.innerHTML = "";
                }

                try {
                    const response = await fetch(lknGlobalData.preferenceEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': lknGlobalData.nonce
                        },
                        body: JSON.stringify({
                            amount: floatValue,
                            currency: lknSelectedCurrency(),
                            form_id: lknCurrentFormId()
                        })
                    });

                    const data = await response.json();

                    if (!data || !data.preference_id || !data.reference) {
                        if (warningEl) {
                            warningEl.textContent = lknMercadoPagoGlobals.MessageErrorToken;
                        }
                        return;
                    }

                    paymentReference = data.reference;
                    const preferenceId = data.preference_id;

                    const bricksBuilder = mp.bricks();
                    const marcadoPagoButton = await bricksBuilder.create("wallet", "wallet_container", {
                        initialization: {
                            preferenceId: preferenceId,
                            redirectMode: 'blank'
                        },
                        customization: {
                            texts: {
                                valueProp: 'smart_option'
                            }
                        }
                    });

                    if (!marcadoPagoButton) {
                        warningEl.textContent = lknMercadoPagoGlobals.MessageErrorPublicKey;
                    }

                } catch (error) {
                    console.error("Erro ao gerar preferência:", error);
                    if (warningEl) {
                        warningEl.textContent = lknMercadoPagoGlobals.MessageErrorToken;
                    }
                }
            }

            // Função para atualizar o botão quando o valor mudar
            function updateButton() {
                let newAmount = null
                let amountField = document.querySelector('input[name="amount"]');
                if (!amountField) {
                    document.getElementById("warning-text").textContent = lknMercadoPagoGlobals.MessageNotFoundField;
                    return;
                } else {
                    if (isNumeric(amountField.value)) {
                        newAmount = amountField.value
                    } else {
                        let customAmount = document.querySelector('#amount-custom');
                        if (customAmount && customAmount.value) {
                            let customValue = customAmount.value.replace(/[.,]$/, '')

                            newAmount = customValue
                        }
                    }
                }

                if (newAmount !== currentAmount) {
                    clearTimeout(debounceTimeout);

                    debounceTimeout = setTimeout(() => {
                        currentAmount = newAmount;
                        preferenceGeneration(); // Regerar a preferência com o novo valor
                    }, 500); // Espera 500ms sem mudanças para executar
                }
            }

            function isNumeric(value) {
                return !isNaN(parseFloat(value)) && isFinite(value);
            }

            // Usar MutationObserver para monitorar mudanças no valor
            const observer = new MutationObserver(updateButton);
            observer.observe(amountField, {
                attributes: true,
                attributeFilter: ['value'] // Observa mudanças no atributo 'value'
            });

            // Trocar a moeda muda o valor cobrado: a preferência precisa ser
            // refeita com o novo par valor/moeda. Vale para os três markups.
            function lknRegenerateForCurrencyChange() {
                clearTimeout(debounceTimeout);
                debounceTimeout = setTimeout(preferenceGeneration, 600);
            }

            document.querySelectorAll('select.give-cs-select-currency, #give-mc-select, .givewp-fields-amount__currency-select').forEach((el) => {
                el.addEventListener('change', lknRegenerateForCurrencyChange);
            });

            // O Give atualiza os inputs escondidos depois do change (e o modo
            // leitura nem dispara change), então observa o valor deles.
            lknCurrencyInputs().forEach((selector) => {
                const el = document.querySelector(selector);
                if (!el) {
                    return;
                }

                el.addEventListener('change', lknRegenerateForCurrencyChange);
                new MutationObserver(lknRegenerateForCurrencyChange).observe(el, {
                    attributes: true,
                    attributeFilter: ['value']
                });
            });

            // Inicializa a preferência assim que o componente é montado
            preferenceGeneration();

        }, []); // Executa a função uma vez, ao carregar o componente

        return React.createElement(React.Fragment, null,
            React.createElement("fieldset", { className: "fields-lknmp" },
                React.createElement("h1", null, lknMercadoPagoGlobals.MessageDonation,
                    React.createElement("span", { id: "donation-amount" })),
                React.createElement("h3", { id: "warning-text" }),
                React.createElement("div", { id: "wallet_container" })
            )
        );

    }
};

// Carregar o SDK do MercadoPago, se ainda não foi carregado
if (!window.MercadoPago) {
    const script = document.createElement('script');
    script.src = 'https://sdk.mercadopago.com/js/v2';
    script.async = true;
    document.head.appendChild(script);
}

window.givewp.gateways.register(LknmpGatewayGiveWP);
