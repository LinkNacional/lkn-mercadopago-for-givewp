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

        $this->assertMatchesRegularExpression(
            "/'currency_id'\s*=>\s*give_get_currency\s*\(/",
            $source,
            'A preferência deve usar give_get_currency() como currency_id.'
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
}
