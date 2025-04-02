(function ($) {
	'use strict';

	let mercadoPagoWindow = null
	let lkninterval = null
	let contentMercadoIFrame = null

	document.addEventListener('DOMContentLoaded', () => {
		let mercadoIFrame = document.querySelector('iframe[title="Donation Form"')
		if (mercadoIFrame) {
			contentMercadoIFrame = mercadoIFrame
			mercadoIFrame = mercadoIFrame.contentWindow.window
			const originalWindowOpen = mercadoIFrame.open;

			document.cookie = `lkn_mercado_pago_url=empty; path=/; max-age=3600`;

			mercadoIFrame.open = function (...args) {
				if (Array.isArray(args) && args.length > 0) {
					const url = args[0];

					if (url && url.includes('mercadopago.com.br/checkout')) {
						const newWindow = originalWindowOpen.apply(this, args);
						mercadoPagoWindow = newWindow

						lkninterval = setInterval(() => {
							if (mercadoPagoWindow.closed) {
								clearInterval(lkninterval);
								window.location.reload();
							}
						}, 500);

						return newWindow;
					}
				}

				// Caso a URL não seja do MercadoPago ou args não seja um array válido, segue o comportamento normal
				return originalWindowOpen.apply(this, args);
			};
		}


		document.cookie = "lkn_payment_url=empty; path=/;";

		// Função para obter o valor do cookie
		function getCookie(name) {
			const value = `; ${document.cookie}`;
			const parts = value.split(`; ${name}=`);
			if (parts.length === 2) return parts.pop().split(';').shift();
		}

		if (contentMercadoIFrame) {
			// Monitora o valor do cookie a cada 1 segundo
			const checkPaymentStatus = setInterval(() => {
				const paymentUrl = getCookie('lkn_payment_url');

				if (paymentUrl !== 'empty') {
					clearInterval(checkPaymentStatus);
					clearInterval(lkninterval);
					document.cookie = "lkn_payment_url=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
					if (mercadoPagoWindow) {
						mercadoPagoWindow.close();
					}

					window.location.href = decodeURIComponent(paymentUrl);
				}
			}, 1000);
		}
	});


	function initializeMercadoPago() {

		let showMP = true;
		document.querySelector('input[type=\"submit\"]').disabled = true;

		// Criar o input hidden após inicializar o MercadoPago para armazenar o idUniqueE
		const hiddenInput = document.createElement('input');
		hiddenInput.type = 'hidden';
		hiddenInput.name = 'gatewayData[gatewayId]';
		hiddenInput.value = lknmpGlobals.idUnique;

		const fieldset = document.querySelector('fieldset.no-fields-lknmp'); // Seleciona o fieldset onde o input será adicionado
		if (fieldset) {
			fieldset.appendChild(hiddenInput);
		}
		//Enter não aciona wallet
		const walletButton = document.querySelector('#wallet_container');
		if (walletButton) {
			walletButton.addEventListener('keydown', function (event) {
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
			let valorText
			const proAmount = document.querySelector('input[name="custom_amount"]')
			if (proAmount) {
				valorText = proAmount.value
			} else {
				valorText = document.getElementsByName('give-amount')
				if (valorText[0]) {
					valorText = valorText[0].value
				}
			}

			if (lknmpGlobals.advDebug == 'enabled') {
				console.log(valorText);
			}

			const valorFormatado = await valorText.replace(/[^\d.,]/g, '').replace(/\./g, '').replace(',', '.')
			let amount = valorFormatado

			if (lknmpGlobals.advDebug == 'enabled') {
				console.log(amount)
			}

			const url = 'https://api.mercadopago.com/checkout/preferences';
			const preference = {
				"back_urls": {
					"success": `${lknmpGlobals.pageUrl}/wp-json/lknmp/v1/payments/checkpayment?id=${lknmpGlobals.idUnique}&statusFront=1`,
					"pending": `${lknmpGlobals.pageUrl}/wp-json/lknmp/v1/payments/checkpayment?id=${lknmpGlobals.idUnique}&statusFront=2`,
					"failure": `${lknmpGlobals.pageUrl}/wp-json/lknmp/v1/payments/checkpayment?id=${lknmpGlobals.idUnique}&statusFront=3`
				},
				"items": [{
					"id": `${lknmpGlobals.idUnique}`,
					"title": `${lknmpGlobals.tittle}`,
					"description": `${lknmpGlobals.description}`,
					"quantity": 1,
					"currency_id": "BRL",
					"unit_price": parseFloat(amount)
				}]
			};

			try {
				const response = await fetch(url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'Authorization': `Bearer ${lknmpGlobals.token}`
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

		function criarBotaoWallet(preferenceID) {
			const fieldset = document.querySelector('.no-fields-lknmp');

			// Remova o botão antigo se existir
			const oldButton = document.querySelector('#wallet_container');
			if (oldButton) {
				oldButton.remove();
			}

			// Crie o novo botão
			const newButton = document.createElement('div');
			newButton.id = 'wallet_container';

			if (showMP) {
				newButton.style.display = 'block';
			} else {
				newButton.style.display = 'none';
			}

			fieldset.appendChild(newButton);

			const mp = new MercadoPago(lknmpGlobals.key);
			const bricksBuilder = mp.bricks();
			bricksBuilder.create('wallet', 'wallet_container', {
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

			document.querySelector('input[type="submit"]').disabled = false; // Reabilita o botão de submit
		}

		if (document.querySelector('#wallet_container')) {
			criarPreferenciaDePagamento()
				.then(preferenceID => {
					if (lknmpGlobals.advDebug == 'enabled') {
						console.log('ID da preferência criada:', preferenceID);
					}
					const mp = new MercadoPago(lknmpGlobals.key);
					const bricksBuilder = mp.bricks();
					bricksBuilder.create('wallet', 'wallet_container', {
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

			checkInputs();
			if (!document.querySelector('#verificador')) {
				observeDonationChanges();
			}
			observeFormChanges();
		}

		function observeDonationChanges() {
			const targetNode = document.querySelector('input[name=\"give-amount\"]');
			if (!targetNode) return;

			const verificadorDiv = document.createElement('div');
			verificadorDiv.id = 'verificador';
			document.body.appendChild(verificadorDiv);

			const observer = new MutationObserver(function (mutationsList, observer) {
				for (const mutation of mutationsList) {
					if (mutation.type === 'attributes' && mutation.attributeName === 'data-amount') {
						if (lknmpGlobals.advDebug == 'enabled') {
							console.log('Mudança no data-amount');
						}

						const nomeInput = document.querySelector('input[name=\"give_first\"]');
						const emailInput = document.querySelector('input[name=\"give_email\"]');

						const oldButton = document.querySelector('#wallet_container');

						if (oldButton) {
							oldButton.remove();
						}

						criarPreferenciaDePagamento()
							.then(preferenceID => {
								criarBotaoWallet(preferenceID);
							})
							.catch(error => {
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
					warningText.textContent = lknmpGlobals.translation.MenssageErrorNameEmpty;
					showMP = false;
				} else if (nomeInput.value.trim().length < 3) {
					walletContainer.style.display = 'none';
					warningText.textContent = lknmpGlobals.translation.MenssageErrorName;
					showMP = false;
				} else if (!emailInput.value.trim()) {
					walletContainer.style.display = 'none';
					warningText.textContent = lknmpGlobals.translation.MenssageErrorEmailEmpty;
					showMP = false;

				} else if (!isValidEmail(emailInput.value)) {
					walletContainer.style.display = 'none';
					warningText.textContent = lknmpGlobals.translation.MenssageErrorEmailInvalid;
					showMP = false;
				} else {
					walletContainer.style.display = 'block';
					warningText.textContent = '';
					showMP = true;
				}
			}
		}
	}

	function waitForWalletContainer(callback) {
		const observer = new MutationObserver(function (mutationsList, observer) {
			// Verifica se o elemento #wallet_container foi adicionado ao DOM
			if (document.querySelector('#wallet_container')) {
				observer.disconnect(); // Para o observador após encontrar o elemento
				callback();
			}
		});

		// Começa a observar mudanças no corpo do documento
		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}

	$(function () {
		// Função para esperar até que um elemento esteja presente no DOM
		function waitForElement(selector, callback) {
			const interval = setInterval(() => {
				const element = document.querySelector(selector);
				if (element) {
					clearInterval(interval);
					callback(element);
				}
			}, 100); // Verifica a cada 100 ms
		}

		function observeClassChange(selector, targetClass, callback) {
			const targetNode = document.querySelector(selector);
			if (!targetNode) return;

			const observer = new MutationObserver(function (mutationsList) {
				for (let mutation of mutationsList) {
					if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
						if (targetNode.classList.contains(targetClass)) {
							callback();
						}
					}
				}
			});

			observer.observe(targetNode, { attributes: true });
		}

		// Carregue o SDK do MercadoPago
		var script = document.createElement('script');
		script.src = 'https://sdk.mercadopago.com/js/v2';
		script.async = true;
		document.head.appendChild(script);

		// Inicialize o MercadoPago após carregar o SDK e esperar pelo fieldset
		script.onload = function () {
			function initializeIfFieldsetExists() {
				const fieldset = document.querySelector('fieldset.no-fields-lknmp');
				if (fieldset) {
					initializeMercadoPago();
				}
			}
			// Verifique o estado do documento
			if (document.readyState === 'complete' || document.readyState !== 'loading') {
				initializeIfFieldsetExists();
			} else {
				document.addEventListener('DOMContentLoaded', initializeIfFieldsetExists);
			}

			// Observa o elemento <li> que contém o input com valor "lknmp-gateway-givewp"
			observeClassChange('li:has(input[value="lknmp-gateway-givewp"])', 'give-gateway-option-selected', function () {
				if (lknmpGlobals.advDebug == 'enabled') {
					console.log('Classe give-gateway-option-selected adicionada, inicializando MercadoPago novamente.');
				}
				waitForElement('fieldset.no-fields-lknmp', function () {
					initializeMercadoPago();
				});
			});
		};

		script.onerror = function () {
			if (lknmpGlobals.advDebug == 'enabled') {
				console.error('Failed to load the MercadoPago SDK.');
			}
		};
	});
})(jQuery);
