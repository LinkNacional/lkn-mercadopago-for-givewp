let checkPaymentStatus = null;
let totalElement = ''

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

        return {
            gatewayId: idUnique // substitua por algo relevante
        };
    },

    async afterCreatePayment(response) {
        // Aqui pode ser adicionado o redirecionamento ou a exibição de uma resposta
    },

    Fields() {
        let floatValue

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
                    const response = await fetch('https://api.mercadopago.com/checkout/preferences', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${lknGlobalData.token}`
                        },
                        body: JSON.stringify({
                            items: [{
                                id: `Doação X ${idUnique}`,
                                title: lknGlobalData.title,
                                description: lknGlobalData.description,
                                quantity: 1,
                                unit_price: floatValue,
                                currency_id: 'BRL',
                            }],
                            back_urls: {
                                "success": `${urlPag}/wp-json/lknmp/v1/payments/checkpayment?id=${idUnique}&statusFront=1`,
                                "pending": `${urlPag}/wp-json/lknmp/v1/payments/checkpayment?id=${idUnique}&statusFront=2`,
                                "failure": `${urlPag}/wp-json/lknmp/v1/payments/checkpayment?id=${idUnique}&statusFront=3`
                            },
                            auto_return: "approved"
                        })
                    });

                    const data = await response.json();

                    const preferenceId = data.id;

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
