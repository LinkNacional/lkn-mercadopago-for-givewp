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
})(jQuery);
