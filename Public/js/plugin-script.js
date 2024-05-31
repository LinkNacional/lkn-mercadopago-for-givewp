let preferenceID = null;
let hasRenderedComponents = false;
let OneLoad = true;
let showMP = true;

function renderComponentsOnce() {
    if (!hasRenderedComponents) {
        const tryRender = () => {
            criarPreferenciaDePagamento()
                .then(preferenceID => {
                    console.log('ID da preferência criada:', preferenceID);
                    preferenceID = preferenceID;
                    const mp = new MercadoPago('TEST-c4abbb26-f793-4baf-a4a4-7e132e2350cb');
                    const bricksBuilder = mp.bricks();
                    mp.bricks().create("wallet", "wallet_container", {
                        initialization: {
                            preferenceId: preferenceID,
                            redirectMode: "blank"
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
    const token = 'TEST-4103642140602972-050610-67d0c5a5cccd4907b1208fded2115f5c-1052089223';

    // Obter valores dos campos HTML
    const nome = sanitizeInput(document.querySelector('input[name="firstName"]').value);
    const sobrenome = sanitizeInput(document.querySelector('input[name="lastName"]').value);
    const email = sanitizeInput(document.querySelector('input[name="email"]').value);

    const valorText = document.querySelector('.givewp-elements-donationSummary__list__item__value').textContent;
    const valorNumerico = parseFloat(valorText.replace(/[^\d.,]/g, ''));
    //console.log(valorNumerico)

    const preference = {
        "back_urls": {
            "success": `${urlPag}/wp-json/mercadopago/v1/payments/checkpayment?id=${idUnique}&statusFront=1`,
            "pending": `${urlPag}/wp-json/mercadopago/v1/payments/checkpayment?id=${idUnique}&statusFront=2`,
            "failure": `${urlPag}/wp-json/mercadopago/v1/payments/checkpayment?id=${idUnique}&statusFront=3` //ALTERAR AQUI DEPOIS!!!
        },
        "auto_return": "approved",
        "items": [{
            "id": "Doação X",
            "title": "Doação via Mercado Pago",
            "description": "Sua doação foi de ",
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
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(preference)
        });
        if (!response.ok) {
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
        const myGateway = document.querySelector('.givewp-fields-gateways__gateway.givewp-fields-gateways__gateway--lnk-mercadopago-forgivewp.givewp-fields-gateways__gateway--active');

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
                const oldButton = document.querySelector('#wallet_container');

                if (oldButton) {
                    oldButton.remove();
                    console.log('botao removido')
                }

                criarPreferenciaDePagamento().then(newPreferenceID => {

                    console.log('Nova preferência criada:', newPreferenceID);

                    preferenceID = newPreferenceID;

                    const newButton = document.createElement('div');
                    newButton.id = 'wallet_container';
                    const fieldset = document.querySelector('.no-fields');

                    if (showMP) {
                        newButton.style.display = 'block';
                    } else {
                        newButton.style.display = 'none';
                    }

                    fieldset.appendChild(newButton);

                    const mp = new MercadoPago('TEST-c4abbb26-f793-4baf-a4a4-7e132e2350cb');
                    const bricksBuilder = mp.bricks();
                    mp.bricks().create("wallet", "wallet_container", {
                        initialization: {
                            preferenceId: preferenceID,
                            redirectMode: "blank"
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
const gateway = {
    id: 'lnk-mercadopago-forgivewp',
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

            const oldButton = document.querySelector('#wallet_container');
            if (oldButton) {
                oldButton.remove();
            }
        }

        return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("fieldset", {
            className: "no-fields"
        }, /*#__PURE__*/React.createElement("h1", null, "Doa\xE7\xE3o de ", /*#__PURE__*/React.createElement("span", {
            id: "donation-amount"
        })), /*#__PURE__*/React.createElement("h3", {
            id: "warning-text"
        }), /*#__PURE__*/React.createElement("div", {
            id: "wallet_container"
        })));
    }
};
window.givewp.gateways.register(gateway);