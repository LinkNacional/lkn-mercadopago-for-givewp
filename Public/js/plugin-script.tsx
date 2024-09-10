async function criarPreferenciaDePagamento() {
    const url = 'https://api.mercadopago.com/checkout/preferences';
    const token = 'TEST-4103642140602972-050610-67d0c5a5cccd4907b1208fded2115f5c-1052089223';

    // Obter valores dos campos HTML
    const nome = document.querySelector('input[name="firstName"]').value;
    const sobrenome = document.querySelector('input[name="lastName"]').value;
    const email = document.querySelector('input[name="email"]').value;
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

    const valor = parseFloat(document.querySelector('.givewp-elements-donationSummary__list__item__value').textContent);
    const preference = {
        "back_urls": {
            "success": "http://test.com/success",
            "pending": "http://test.com/pending",
            "failure": "http://test.com/failure"
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
        return data;
    } catch (error) {
        console.error('Erro ao criar preferência de pagamento:', error);
        throw error;
    }
}
async function getPreferenceID() {
    try {
        const preferenceData = await criarPreferenciaDePagamento();
        if (preferenceData && preferenceData.id) {
            console.log('ID da preferência de pagamento:', preferenceData.id);
            return preferenceData.id;
        } else {
            throw new Error('Erro ao obter o ID da preferência de pagamento');
        }
    } catch (error) {
        console.error('Erro ao obter o ID da preferência de pagamento:', error);
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

function observeDonationChanges() {
    const targetNode = document.querySelector('.givewp-elements-donationSummary__list__item__value');

    if (!targetNode) return; // Sair se o elemento não for encontrado

    const observer = new MutationObserver(function (mutationsList, observer) {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                // Chamando a função para atualizar o valor do campo de doação
                updateDonationAmount();
            }
        }
    });

    observer.observe(targetNode, { attributes: true, childList: true, subtree: true, characterData: true });
}

function observeFormChanges() {
    const nomeInput = document.querySelector('input[name="firstName"]');
    const emailInput = document.querySelector('input[name="email"]');

    // Verifica se os campos foram encontrados antes de adicionar ouvintes de eventos
    if (nomeInput && emailInput) {
        // Adiciona ouvintes de eventos de entrada aos campos de nome e e-mail
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
            // Esconder a mensagem de aviso
            warningText.textContent = ''; // Limpa o texto da mensagem de aviso
        } else {
            // Se algum campo estiver vazio, desabilitar o container e exibir a mensagem de aviso
            walletContainer.style.display = 'none';
            // Define o texto da mensagem de aviso
            warningText.textContent = 'Email ou Email não foram preenchidos. Por favor, preencha todos os campos antes de prosseguir.';
        }
    }
}

const gateway = {
    id: 'lknmp-gateway-givewp',
    async initialize() {
        // Aqui vai todas as funções necessárias ao carregar a página de pagamento
    },
    async beforeCreatePayment(values) {
        // Aqui vai tudo que precisa rodar depois de submeter o formulário e antes do pagamento ser completado
        // Ponha validações e adicione atributos que você vai precisar no back-end aqui

        // Caso detecte algum erro de validação você pode adicionar uma exceção
        // A mensagem de erro aparecerá para o cliente já formatada
        if (values.firstname === 'error') {
            throw new Error('Gateway failed');
        }

        // Retorna os atributos usados pelo back-end
        // Atributos do objeto value já são passados por padrão
        return {
            pluginIntent: 'lkn-plugin-intent',
            custom: 'anything'
        };
    },
    async afterCreatePayment(response) {
        // Aqui roda tudo que você precisa após o formulário ser submetido
        // Antes de ir para a tela do comprovante de pagamento
    },
    // Função onde os campos HTML são criados
    Fields() {

        // Chamando a função updateFieldValue() após os elementos terem sido renderizados

        setTimeout(updateDonationAmount, 0);
        setTimeout(checkInputs, 0);
        observeDonationChanges();
        observeFormChanges();

        criarPreferenciaDePagamento();


        getPreferenceID()
            .then(preferenceID => {
                const id = preferenceID;
                const mp = new MercadoPago('TEST-c4abbb26-f793-4baf-a4a4-7e132e2350cb');
                const bricksBuilder = mp.bricks();
                mp.bricks().create("wallet", "wallet_container", {
                    initialization: {
                        preferenceId: id
                    },
                    customization: {
                        texts: {
                            valueProp: 'smart_option'
                        }
                    }
                });

            })
            .catch(error => {
                // Lida com erros, se ocorrerem
            });

        return (
            <>
                <fieldset className="no-fields">
                    <h1>
                        Doação de <span id="donation-amount"></span>
                    </h1>
                    <h3 id="warning-text"></h3>
                    <div id="wallet_container"></div>
                </fieldset>
            </>
        );
    }
};

window.givewp.gateways.register(gateway);
