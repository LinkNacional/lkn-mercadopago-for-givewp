let preferenceID = null;
let hasRenderedComponents = false;
let OneLoad = true;
let showMP = true;
let confirmPayment = false
let buttonValue = false
let mercadoPagoWindow = null

window.onload = () => {
    const observer = new MutationObserver((mutationsList, observer) => {
        const customInput = document.getElementById('amount-custom');

        if (customInput) {
            customInput.addEventListener('input', () => {
                let value = customInput.value;

                if (value.slice(-1) === '.' || value.slice(-1) === ',') {
                    customInput.value = value.slice(0, -1);
                }
            });
        }

        const buttonFormValue = document.querySelector('.givewp-fields-amount__level');
        if (buttonFormValue) {
            buttonValue = buttonFormValue.textContent;

            observer.disconnect();
        }
    });

    const config = { childList: true, subtree: true };

    // Começa a observar o `document.body`
    observer.observe(document.body, config);
};

function renderComponentsOnce() {
    if (!hasRenderedComponents) {
        const tryRender = () => {
            criarPreferenciaDePagamento()
                .then(async preferenceID => {
                    if (configData.advDebug == 'enabled') {
                        console.log('ID da preferência criada:', preferenceID);
                    }
                    const mp = new MercadoPago(configData.key);
                    //TODO se houver erro 400, temos que retornar ao usuário???
                    const bricksBuilder = mp.bricks();

                    await bricksBuilder.create("wallet", "wallet_container", {
                        initialization: {
                            preferenceId: preferenceID,
                            redirectMode: 'blank'
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

            if (OneLoad) {
                setTimeout(updateDonationAmount, 0);
                setTimeout(checkInputs, 0);
                observeDonationChanges();
                observeFormChanges();
                observeMetodoChanges();
                if (document.querySelector('.givewp-donation-form__steps-header-previous')) {
                    observeButtonChanges();
                }
                OneLoad = false;
            }
            hasRenderedComponents = true;
        };

        const interval = setInterval(() => {
            if (document.readyState === 'complete') {
                clearInterval(interval);
                if (OneLoad || !hasRenderedComponents) {
                    tryRender();
                }
            }
        }, 100);
    }
}

function sanitizeInput(input) {
    if (typeof input === 'string') {
        return input.replace(/<[^>]*>/g, '').trim();
    }
    else if (typeof input === 'number') {
        return parseFloat(input.toString().replace(/\D/g, ''));
    }
    else {
        return input;
    }
}

async function criarPreferenciaDePagamento() {
    const url = 'https://api.mercadopago.com/checkout/preferences';

    let valorText = 1
    const proAmount = document.querySelector('input[name="custom_amount"]');
    if (proAmount) {
        valorText = proAmount.value
    } else {
        valorText = document.querySelector('.givewp-elements-donationSummary__list__item__value')?.textContent;
    }

    if (valorText.includes('NaN')) {
        valorText = document.getElementById('amount-custom')?.getAttribute('value')
    }

    valorText = valorText.replace(/[.,]$/, '')

    let replaceValue = ','
    if (buttonValue.includes(',')) {
        replaceValue = '.'
    }

    const valorFormatado = await valorText.replace(/[^\d.,]/g, '').replace(new RegExp(`\\${replaceValue}`, 'g'), '').replace(',', '.')
    const valorNumerico = parseFloat(valorFormatado);

    if (configData.advDebug == 'enabled') {
        console.log(valorNumerico)
    }

    const preference = {
        "back_urls": {
            "success": `${urlPag}/wp-json/lknmp/v1/payments/checkpayment?id=${idUnique}&statusFront=1`,
            "pending": `${urlPag}/wp-json/lknmp/v1/payments/checkpayment?id=${idUnique}&statusFront=2`,
            "failure": `${urlPag}/wp-json/lknmp/v1/payments/checkpayment?id=${idUnique}&statusFront=3` //ALTERAR AQUI DEPOIS!!!
        },
        "auto_return": "approved",
        "items": [{
            "id": `Doação X ${idUnique}`,
            "title": `${configData.tittle}`,
            "description": `${configData.description}`,
            "quantity": 1,
            "currency_id": "BRL",
            "unit_price": valorNumerico
        }]
    };
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${configData.token}`
            },
            body: JSON.stringify(preference)
        });
        if (!response) {
            throw new Error(`Erro ao criar preferência de pagamento: undefined`);
        } else if (!response.ok) {
            throw new Error(`Erro ao criar preferência de pagamento: ${response.status}`);
        }
        const data = await response.json();
        return data.id;
    } catch (error) {
        console.error('Erro ao criar preferência de pagamento:', error);
        throw error;
    }
}

function mutationBack() {
    const button = document.querySelector('.givewp-donation-form__steps-header-previous-button');

    if (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            location.reload();
        });
    }
}

function observeButtonChanges() {
    const observer = new MutationObserver((mutationsList, observer) => {
        mutationsList.forEach(mutation => {
            if (mutation.type === 'childList') {
                mutationBack();
            }
        });
    });

    const config = { childList: true, subtree: true };
    const targetNode = document.body;
    observer.observe(targetNode, config);
    mutationBack();
}

function updateDonationAmount() {
    const fieldValueElement = document.querySelector('.givewp-elements-donationSummary__list__item__value');
    const donationAmountElement = document.querySelector('#donation-amount');
    if (fieldValueElement && donationAmountElement) {
        const fieldValue = fieldValueElement.textContent;
        donationAmountElement.textContent = fieldValue;
    }
}

function observeMetodoChanges() {
    const checkGateways = () => {
        const myGateway = document.querySelector('.givewp-fields-gateways__gateway.givewp-fields-gateways__gateway--lknmp-gateway-givewp.givewp-fields-gateways__gateway--active');

        // Se ele estiver ativo, habilita o botão Donate Now
        if (myGateway) {
            document.querySelector('button[type="submit"]').disabled = true;
        } else {
            document.querySelector('button[type="submit"]').disabled = false;
            hasRenderedComponents = false;
        }
        updateDonationAmount();
        checkInputs();
    };

    checkGateways();

    const observer = new MutationObserver((mutationsList, observer) => {
        checkGateways();
    });

    const targetNode = document.querySelector('.givewp-fields-gateways');

    const config = { childList: true, subtree: true };

    observer.observe(targetNode, config);
}

function debounce(func, delay) {
    let timer;
    return function () {
        const context = this;
        const args = arguments;
        clearTimeout(timer);
        timer = setTimeout(() => {
            func.apply(context, args);
        }, delay);
    };
}

function observeDonationChanges() {
    const targetNode = document.querySelector('.givewp-elements-donationSummary__list__item__value');
    if (!targetNode) return;

    const observer = new MutationObserver(debounce(function (mutationsList, observer) {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                updateDonationAmount();

                const nomeInput = document.querySelector('input[name="firstName"]');
                const emailInput = document.querySelector('input[name="email"]');
                const oldButton = document.getElementById('wallet_container');

                if (oldButton) {
                    oldButton.remove();
                    if (configData.advDebug == 'enabled') {
                        console.log('botao removido')
                    }
                }

                criarPreferenciaDePagamento().then(newPreferenceID => {

                    if (configData.advDebug == 'enabled') {
                        console.log('Nova preferência criada:', newPreferenceID);
                    }

                    preferenceID = newPreferenceID;

                    const newButton = document.createElement('div');
                    newButton.id = 'wallet_container';
                    const fieldset = document.querySelector('.no-fields-lknmp');

                    if (showMP) {
                        newButton.style.display = 'block';
                    } else {
                        newButton.style.display = 'none';
                    }

                    fieldset.appendChild(newButton);

                    const mp = new MercadoPago(configData.key);
                    const bricksBuilder = mp.bricks();
                    bricksBuilder.create("wallet", "wallet_container", {
                        initialization: {
                            preferenceId: preferenceID,
                            redirectMode: 'blank'
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
    }, 500)); // Atraso de 500 milissegundos

    observer.observe(targetNode, {
        attributes: true,
        childList: true,
        subtree: true,
        characterData: true
    });
}

function observeFormChanges() {
    const nomeInput = document.querySelector('input[name="firstName"]');
    const emailInput = document.querySelector('input[name="email"]');
    if (nomeInput && emailInput) {
        nomeInput.addEventListener('input', checkInputs);
        emailInput.addEventListener('input', checkInputs);
    }
}
function checkInputs() {
    const nomeInput = document.querySelector('input[name="firstName"]');
    const emailInput = document.querySelector('input[name="email"]');
    const walletContainer = document.getElementById('wallet_container');
    let warningText = document.querySelector('#warning-text');

    function isValidEmail(email) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailPattern.test(email);
    }

    if (nomeInput && emailInput && walletContainer) {
        const mercadoPagoButton = document.querySelector('button[aria-label*="Mercado Pago"]');
        const handleClick = function () {
            const messageElement = document.createElement('div');
            messageElement.textContent = lknMercadoPagoGlobals.MenssageSuccess;
            messageElement.style.color = 'green';
            messageElement.style.marginBottom = '14px';

            const donationForm = document.querySelector('.no-fields-lknmp');
            if (donationForm) {
                donationForm.innerHTML = '';
                donationForm.appendChild(messageElement);
            }

            let submitDonationButton = document.querySelector('.givewp-layouts-section button[type="submit"]');
            if (submitDonationButton) {
                if (!confirmPayment) {
                    submitDonationButton.removeAttribute('disabled');
                    submitDonationButton.click();
                    confirmPayment = true
                }
            }

            mercadoPagoButton.removeEventListener('click', handleClick);
        };

        if (mercadoPagoButton) {
            mercadoPagoButton.addEventListener('click', handleClick);
        }
        if (!nomeInput.value.trim()) {
            walletContainer.style.display = 'none';
            warningText.textContent = lknMercadoPagoGlobals.MenssageErrorNameEmpty;
            showMP = false;
        } else if (nomeInput.value.trim().length < 3) {
            walletContainer.style.display = 'none';
            warningText.textContent = lknMercadoPagoGlobals.MenssageErrorName;
            showMP = false;
        } else if (!emailInput.value.trim()) {
            walletContainer.style.display = 'none';
            warningText.textContent = lknMercadoPagoGlobals.MenssageErrorEmailEmpty;
            showMP = false;

        } else if (!isValidEmail(emailInput.value)) {
            walletContainer.style.display = 'none';
            warningText.textContent = lknMercadoPagoGlobals.MenssageErrorEmailInvalid;
            showMP = false;
        } else {
            walletContainer.style.display = 'block';
            if (warningText?.textContent) {
                warningText.textContent = '';
            }
            showMP = true;
        }
    }
}
const LknmpGatewayGiveWP = {
    id: 'lknmp-gateway-givewp',
    async initialize() {
        // Aqui vai todas as funções necessárias ao carregar a página de pagamento
    },
    async beforeCreatePayment(values) {
        // Aqui vai tudo que precisa rodar depois de submeter o formulário e antes do pagamento ser completado
        // Ponha validações e adicione atributos que você vai precisar no back-end aqui

        // Caso detecte algum erro de validação você pode adicionar uma exceção
        // A mensagem de erro aparecerá para o cliente já formatada


        // Retorna os atributos usados pelo back-end
        // Atributos do objeto value já são passados por padrão

        if (values.firstname === 'error') {
            throw new Error('Gateway failed');
        }

        return {
            gatewayId: idUnique
        };

    },
    async afterCreatePayment(response) {
        //Depois da criação do pagamento
    },
    // Função onde os campos HTML são criados
    Fields() {
        function lknMercadoPagoprintFrontendNotice(title, message) {
            return /*#__PURE__*/React.createElement("div", {
                className: "error-notice"
            }, /*#__PURE__*/React.createElement("strong", null, title), " ", message);
        }

        if (lknMercadoPagoGlobals.token == 'false') {
            return lknMercadoPagoprintFrontendNotice('Erro:', lknMercadoPagoGlobals.MenssageErrorToken);
        } else if (lknMercadoPagoGlobals.publicKey == 'false') {
            return lknMercadoPagoprintFrontendNotice('Erro:', lknMercadoPagoGlobals.MenssageErrorPublicKey);
        } else {
            if (!hasRenderedComponents) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function () {
                        renderComponentsOnce();
                    });
                } else {
                    renderComponentsOnce();
                }
            } else {
                //observeButtonChanges já está cumprindo a função de recarregar o formulário
                updateDonationAmount();

                const oldButton = document.getElementById('wallet_container');
                if (oldButton) {
                    oldButton.remove();
                }
            }

            return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("fieldset", {
                className: "no-fields-lknmp"
            }, /*#__PURE__*/React.createElement("h1", null, lknMercadoPagoGlobals.MenssageDonation, /*#__PURE__*/React.createElement("span", {
                id: "donation-amount"
            })), /*#__PURE__*/React.createElement("h3", {
                id: "warning-text"
            }), /*#__PURE__*/React.createElement("div", {
                id: "wallet_container"
            })));
        }
    }
};
var script = document.createElement('script');
script.src = 'https://sdk.mercadopago.com/js/v2';
script.async = true;
document.head.appendChild(script);

window.givewp.gateways.register(LknmpGatewayGiveWP);

