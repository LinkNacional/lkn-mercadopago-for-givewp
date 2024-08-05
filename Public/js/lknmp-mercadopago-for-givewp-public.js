(function ($) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

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
			if(lknmpGlobals.advDebug == 'enabled'){
				console.log(amountGive.value);
			}

			let amount = amountGive.value;

			if(lknmpGlobals.advDebug == 'enabled'){
				console.log(amount)
			}

			const url = 'https://api.mercadopago.com/checkout/preferences';
			const preference = {
				"back_urls": {
					"success": `${lknmpGlobals.pageUrl}/wp-json/mercadopago/v1/payments/checkpayment?id=${lknmpGlobals.idUnique}&statusFront=1`,
					"pending": `${lknmpGlobals.pageUrl}/wp-json/mercadopago/v1/payments/checkpayment?id=${lknmpGlobals.idUnique}&statusFront=2`,
					"failure": `${lknmpGlobals.pageUrl}/wp-json/mercadopago/v1/payments/checkpayment?id=${lknmpGlobals.idUnique}&statusFront=3`
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
		if (document.querySelector('#wallet_container')) {
			criarPreferenciaDePagamento()
				.then(preferenceID => {
					if(lknmpGlobals.advDebug == 'enabled'){
						console.log('ID da preferência criada:', preferenceID);
					}
					const mp = new MercadoPago(lknmpGlobals.key);
					const bricksBuilder = mp.bricks();
					mp.bricks().create('wallet', 'wallet_container', {
						initialization: {
							preferenceId: preferenceID,
							redirectMode: checkSidebarExists() ? 'self' : 'blank'
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

			const verificadorDiv = document.createElement('div');
			verificadorDiv.id = 'verificador';
			document.body.appendChild(verificadorDiv);

			const observer = new MutationObserver(function(mutationsList, observer) {
				for (const mutation of mutationsList) {
					if (mutation.type === 'attributes' && mutation.attributeName === 'data-amount') {
						if(lknmpGlobals.advDebug == 'enabled'){
							console.log('Mudança no data-amount');
						}

						const nomeInput = document.querySelector('input[name=\"give_first\"]');
						const emailInput = document.querySelector('input[name=\"give_email\"]');

							const oldButton = document.querySelector('#wallet_container');

							if (oldButton) {
								oldButton.remove();
							}

							criarPreferenciaDePagamento().then(newPreferenceID => {
								if(lknmpGlobals.advDebug == 'enabled'){
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

								const mp = new MercadoPago(lknmpGlobals.key);
								const bricksBuilder = mp.bricks();
								mp.bricks().create('wallet', 'wallet_container', {
									initialization: {
										preferenceId: newPreferenceID,
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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializeMercadoPago);
	} else {
		initializeMercadoPago();
	}

})(jQuery);
