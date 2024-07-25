console.log("teste");

document.addEventListener("DOMContentLoaded", () => {
    if (window.location.href === varsPhp.admin_url) {
        initialize();
    }
});

function initialize() {
    const input = document.querySelector("#mercado_pago_token");
    if (input) {
        const text = formatValue(input.value);
        input.parentElement.insertAdjacentHTML("beforeend", `<div><strong>${text}</strong></div>`);
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
