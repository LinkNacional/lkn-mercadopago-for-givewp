document.addEventListener("DOMContentLoaded", () => {
    if (window.location.href === varsPhp.admin_url) {
        initialize();
    }
});

function initialize() {
    const input = document.querySelector("#mercado_pago_token");
    const input2 = document.querySelector("#mercado_pago_key");

    if (input && input2) {
        const text = formatValue(input.value);
        const text2 = formatValue(input2.value);
        input.parentElement.insertAdjacentHTML("beforeend", `<div><strong>${text}</strong></div>`);
        input2.parentElement.insertAdjacentHTML("beforeend", `<div><strong>${text2}</strong></div>`);
    }
}

function formatValue(value) {
    const formatedValue = value.split("-");
    switch (formatedValue[0].toUpperCase()) {
        case "APP_USR":
            return "Ambiente Produção";
        case "TEST":
            return "Ambiente Sandbox";
        default:
            return "Chave incorreta";
    }
}
