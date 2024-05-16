let preferenceID = null;
let hasRenderedComponents = false;

async function getPaymentIdFromAPI() {
    try {
        // Faz a requisição GET para obter o ID de pagamento da API
        const response = await fetch('URL_DA_API', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                // Se necessário, adicione cabeçalhos de autorização ou outros cabeçalhos aqui
            }
        });

        if (!response.ok) {
            throw new Error(`Erro ao obter ID de pagamento da API: ${response.status}`);
        }

        // Extrai o ID de pagamento da resposta da API
        const data = await response.json();
        return data.payment_id;
    } catch (error) {
        // Se houver um erro na requisição GET, trate-o aqui
        console.error('Erro ao obter ID de pagamento da API:', error);
        throw error;
    }
}

function renderComponentsOnce() {
    if (!hasRenderedComponents) {
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

        // Chamando funções após os elementos terem sido renderizados
        setTimeout(updateDonationAmount, 0);
        setTimeout(checkInputs, 0);

        // Observers
        observeDonationChanges();
        observeFormChanges();
        observeMetodoChanges();

        hasRenderedComponents = true;
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
    //const dd = document.querySelector('input[name="dd"]').value;
    const dd = "84";
    //const numero = document.querySelector('input[name="numero"]').value;
    const numero = 998224031
    //const cpf = document.querySelector('input[name="cpf"]').value;
    const cpf = "000.432.234-54";
    //const cep = document.querySelector('input[name="cep"]').value;
    const cep = "59330-000"
    //const rua = document.querySelector('input[name="rua"]').value;
    const rua = "Benjamin Constant"
    const numerocasa = "07"

    const valorText = document.querySelector('.givewp-elements-donationSummary__list__item__value').textContent;
    const valorNumerico = parseFloat(valorText.replace(/[^\d.,]/g, ''));
    console.log(valorNumerico)

    // URL da rota da API REST com o ID da preferência de pagamento

    var id = 52;
    var $url = 'https://wordpress.local/wp-json/mercadopago/v1/payments/' + id;

    const preference = {
        "back_urls": {
            "success": `https://wordpress.local/wp-json/mercadopago/v1/payments/${id}`,
            "pending": `https://wordpress.local/wp-json/mercadopago/v1/payments/${id}`, //site_url()
            "failure": `https://wordpress.local/wp-json/mercadopago/v1/payments/checkpayment?id=${id}}` //site_url()
        },
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
        const offlineGateway = document.querySelector('.givewp-fields-gateways__gateway--offline.givewp-fields-gateways__gateway--active');
        const manualGateway = document.querySelector('.givewp-fields-gateways__gateway--manual.givewp-fields-gateways__gateway--active');

        // Se algum dos gateways estiver ativo, habilita o botão Donate Now
        if (offlineGateway || manualGateway) {
            document.querySelector('button[type="submit"]').disabled = false;
            hasRenderedComponents = false;
        } else {
            document.querySelector('button[type="submit"]').disabled = true;
        }
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
                if (nomeInput.value && emailInput.value) {
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

        // try {
        //     const paymentId = await getPaymentIdFromAPI();

        //     return {
        //         pluginIntent: 'lkn-plugin-intent',
        //         custom: 'anything'
        //     };

        // } catch (error) {
        //     console.error('Erro ao obter ID de pagamento da API:', error);
        //     throw error;
        // }

    },
    async afterCreatePayment(response) {
        // Aqui roda tudo que você precisa após o formulário ser submetido
        // Antes de ir para a tela do comprovante de pagamento
    },
    // Função onde os campos HTML são criados
    Fields() {

        //console.log(preferencia.back_urls.success)

        renderComponentsOnce();

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