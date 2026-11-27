<?php
/**
 * Invariantes de segurança do gateway Mercado Pago para GiveWP.
 *
 * Testes de regra estática do fonte: travam os defeitos que permitem
 * confirmar doação sem pagamento e vazar a credencial da conta.
 *
 * @package LKNMP_Mercadopago_For_Givewp
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PaymentSecurityTest extends TestCase
{
    private static function pluginDir(): string
    {
        $dir = realpath(__DIR__ . '/../..');

        if (false === $dir) {
            self::fail('Raiz do plugin não resolvida.');
        }

        return $dir;
    }

    private static function read(string $relative): string
    {
        $path = self::pluginDir() . '/' . $relative;

        if (! file_exists($path)) {
            self::fail("Arquivo ausente: {$relative}");
        }

        return (string) file_get_contents($path);
    }

    private static function main(): string
    {
        return self::read('Includes/LknmpGatewayGiveWP.php');
    }

    /**
     * O access token dá controle total da conta Mercado Pago. Ele nunca pode
     * chegar ao HTML público: o navegador só precisa da public key.
     */
    public function testAccessTokenIsNeverLocalizedToFrontend(): void
    {
        $gateway = self::read('Public/LknmpGatewayGiveWPGateway.php');

        $this->assertDoesNotMatchRegularExpression(
            "/wp_localize_script\s*\([^;]*'token'\s*=>\s*\\\$configs\s*\[\s*'token'\s*\]/s",
            $gateway,
            'O access token não pode ser enviado ao navegador via wp_localize_script.'
        );
    }

    /**
     * Havia um segundo caminho de vazamento: o global lknmpGlobals da classe
     * public enviava o token e nenhum script o consumia.
     */
    public function testPublicClassNeverLocalizesAccessToken(): void
    {
        $public = self::read('Public/LknmpGatewayGiveWPPublic.php');

        $this->assertStringNotContainsString(
            "'token' => \$configs['token']",
            $public,
            'A classe public não pode expor o access token no lknmpGlobals.'
        );
    }

    /**
     * O status da doação só pode virar COMPLETE depois de consultar a API do
     * Mercado Pago. O parâmetro statusFront da URL de retorno é controlado
     * pelo navegador e não pode decidir nada.
     */
    public function testCompletionRequiresMercadoPagoVerification(): void
    {
        $source = self::main();

        $this->assertMatchesRegularExpression(
            '/api\.mercadopago\.com\/v1\/payments/',
            $source,
            'A confirmação precisa consultar a API do Mercado Pago antes de concluir a doação.'
        );

        $this->assertStringNotContainsString(
            'statusFront',
            $source,
            'O status não pode ser decidido por um parâmetro da própria URL de retorno.'
        );
    }

    /**
     * A criação da preferência precisa ser feita no servidor com o access
     * token. O JavaScript nunca pode carregar essa credencial.
     */
    public function testPreferenceCreationIsServerSide(): void
    {
        $source = self::main();

        $this->assertMatchesRegularExpression(
            "/register_rest_route\s*\(\s*'lknmp\/v1'\s*,\s*'\/payments\/preference'/",
            $source,
            'A preferência precisa ser criada por uma rota de servidor.'
        );

        $js = self::read('Public/js/lkn-mercadopago-checkout.js');

        $this->assertStringNotContainsString(
            'Bearer',
            $js,
            'O JavaScript não pode montar Authorization: Bearer com o access token.'
        );
    }

    /**
     * A moeda precisa sair do Give. Fixar BRL cobra o valor na moeda errada
     * quando a loja usa outra.
     */
    public function testCurrencyComesFromGive(): void
    {
        $source = self::main();

        // Com multimoeda, give_get_currency() lê $_POST e esta rota recebe
        // JSON — usá-la aqui ignoraria a escolha do doador. O valor aceito
        // precisa vir do cliente e ser validado contra give_get_currencies().
        $this->assertMatchesRegularExpression(
            "/give_get_currencies\s*\(/",
            $source,
            'A moeda precisa ser validada contra give_get_currencies().'
        );

        $this->assertDoesNotMatchRegularExpression(
            "/'currency_id'\s*=>\s*'BRL'/",
            $source,
            "currency_id não pode ser fixo em 'BRL'."
        );
    }

    /**
     * Sem comparar o valor pago com o valor da doação, dá para pagar centavos
     * e concluir uma doação de qualquer valor.
     */
    public function testReturnVerifiesPaidAmountMatchesDonation(): void
    {
        $source = self::main();

        $this->assertMatchesRegularExpression(
            '/transaction_amount/',
            $source,
            'O retorno precisa ler transaction_amount do pagamento.'
        );

        $this->assertMatchesRegularExpression(
            '/amount_mismatch/',
            $source,
            'Valor divergente precisa ser recusado explicitamente.'
        );
    }

    /**
     * A rota pública cria objetos cobráveis na conta. Sem limite, qualquer um
     * usa o site como proxy para criar preferências em massa.
     */
    public function testPreferenceRouteIsRateLimited(): void
    {
        $source = self::main();

        $this->assertMatchesRegularExpression(
            '/lknmp_mp_rl_/',
            $source,
            'A rota de preferência precisa de limite por IP.'
        );
    }

    /**
     * Quando o Mercado Pago recusa a preferência (ex.: moeda da loja diferente
     * da da conta), o erro precisa dizer o motivo, não um 502 genérico.
     */
    public function testPreferenceErrorSurfacesMercadoPagoReason(): void
    {
        $source = self::main();

        $this->assertMatchesRegularExpression(
            '/cause/',
            $source,
            'O motivo devolvido pelo Mercado Pago precisa ser lido e repassado.'
        );
    }

    /**
     * O multimoeda troca a moeda pelo filtro give_currency, que lê $_POST. A
     * rota REST recebe JSON, então $_POST fica vazio e o filtro nunca roda:
     * a preferência sairia sempre na moeda base com o valor já convertido na
     * tela — cobrando o valor errado. A moeda escolhida precisa vir do cliente
     * e ser validada contra as moedas do Give.
     */
    public function testPreferenceUsesClientCurrencyValidatedAgainstGive(): void
    {
        $source = self::main();

        $this->assertMatchesRegularExpression(
            '/give_get_currencies\s*\(/',
            $source,
            'A moeda enviada pelo cliente precisa ser validada contra give_get_currencies().'
        );

        $this->assertDoesNotMatchRegularExpression(
            "/'currency_id'\s*=>\s*give_get_currency\s*\(\s*\)/",
            $source,
            'Não pode usar give_get_currency() sem argumento: com multimoeda ela ignora a escolha do doador.'
        );
    }

    /**
     * A moeda da cobrança precisa ficar travada na referência. Sem isso o
     * retorno não tem como saber em que moeda o pagamento foi feito.
     */
    public function testReferencePinsTheCurrency(): void
    {
        $source = self::main();

        $this->assertMatchesRegularExpression(
            '/\'currency\'\s*=>\s*\$currency/s',
            $source,
            'A referência precisa guardar a moeda junto do valor.'
        );

        $this->assertMatchesRegularExpression(
            '/currency_mismatch/',
            $source,
            'Pagamento em moeda diferente da referência precisa ser recusado.'
        );
    }

    /**
     * O JavaScript precisa enviar a moeda que o doador escolheu no formulário.
     */
    public function testCheckoutSendsSelectedCurrency(): void
    {
        $js = self::read('Public/js/lkn-mercadopago-checkout.js');

        // O gateway é JS puro (registrado via givewp.gateways.register), não um
        // componente React: lê o select do form do Give direto do DOM.
        $this->assertMatchesRegularExpression(
            '/givewp-fields-amount__currency-select/',
            $js,
            'O JS do gateway precisa ler a moeda selecionada no form do Give.'
        );

        $this->assertMatchesRegularExpression(
            '/currency:\s*lknSelectedCurrency\s*\(\s*\)/',
            $js,
            'O corpo da requisição de preferência precisa incluir a moeda escolhida.'
        );
    }

    /**
     * O form pode oferecer só algumas moedas. O cliente não pode pedir uma
     * moeda que o form não habilita, mesmo sendo uma moeda válida do Give.
     * Vale para o multimoeda da Link Nacional e para o nativo do Give, porque
     * os dois registram CurrencySwitcherSetting no schema do form.
     */
    public function testPreferenceValidatesCurrencyAgainstFormSettings(): void
    {
        $source = self::main();

        $this->assertMatchesRegularExpression(
            '/getCurrencySwitcherSettings\s*\(/',
            $source,
            'A moeda precisa ser validada contra as moedas do próprio form.'
        );
    }

    /**
     * Cada caminho de multimoeda renderiza um markup diferente: v3 usa
     * .givewp-fields-amount__currency-select, o legado do Give usa
     * select.give-cs-select-currency e o da Link Nacional usa #give-mc-select.
     * O helper precisa cobrir os três.
     */
    public function testCheckoutReadsEveryCurrencySelector(): void
    {
        $js = self::read('Public/js/lkn-mercadopago-checkout.js');

        foreach (array(
            'givewp-fields-amount__currency-select',
            'give-cs-select-currency',
            'give-mc-select',
        ) as $selector) {
            $this->assertStringContainsString(
                $selector,
                $js,
                "O helper de moeda precisa cobrir o seletor {$selector}."
            );
        }
    }

    /**
     * Trocar a moeda muda o valor cobrado. Sem observar o seletor, a
     * preferência fica com o par valor/moeda antigo.
     */
    public function testCheckoutWatchesCurrencyChanges(): void
    {
        $js = self::read('Public/js/lkn-mercadopago-checkout.js');

        $this->assertMatchesRegularExpression(
            '/addEventListener\s*\(\s*[\'"]change[\'"]/',
            $js,
            'O checkout precisa reagir à troca de moeda.'
        );

        $this->assertMatchesRegularExpression(
            '/MutationObserver/',
            $js,
            'A moeda também muda sem evento change (label do modo leitura).'
        );
    }

    /**
     * O servidor precisa saber de qual form veio o pedido para validar a
     * moeda contra as moedas daquele form.
     */
    public function testCheckoutSendsFormId(): void
    {
        $js = self::read('Public/js/lkn-mercadopago-checkout.js');

        $this->assertMatchesRegularExpression(
            '/form_id\s*:/',
            $js,
            'O corpo da preferência precisa enviar o id do form.'
        );

        $gateway = self::read('Public/LknmpGatewayGiveWPGateway.php');

        $this->assertMatchesRegularExpression(
            "/'formId'\s*=>/",
            $gateway,
            'O formId precisa chegar ao JS via localize.'
        );
    }
}
