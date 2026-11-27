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

    /**
     * Msgid tem que ser o texto de origem (inglês). String em português como
     * msgid nunca é traduzida: o doador lê português num site em inglês e o
     * site em pt_BR não casa a tradução. Era o defeito antigo destes plugins.
     */
    public function testMsgidsAreNeverPortuguese(): void
    {
        $accented = '/[áàâãéêíóôõúüçÁÀÂÃÉÊÍÓÔÕÚÜÇ]/';
        $sources = $this->phpSources();

        $this->assertNotEmpty($sources, 'Nenhum fonte PHP encontrado para varrer.');

        foreach ($sources as $file => $source) {
            preg_match_all(
                "/(?:esc_html__|esc_attr__|__|_e|esc_html_e)\(\s*'([^']*)'/",
                $source,
                $matches
            );

            foreach ($matches[1] as $msgid) {
                $this->assertDoesNotMatchRegularExpression(
                    $accented,
                    $msgid,
                    "Msgid em português em {$file}: \"{$msgid}\" — o texto de origem precisa estar em inglês."
                );
            }
        }
    }

    /**
     * Texto em português escapado direto com esc_html() não passa por
     * tradução nenhuma: fica cravado no idioma, sem text-domain.
     */
    public function testPortugueseTextIsNeverEscapedWithoutI18n(): void
    {
        $accented = '/[áàâãéêíóôõúüçÁÀÂÃÉÊÍÓÔÕÚÜÇ]/';
        $sources = $this->phpSources();

        // Guarda contra o scan casar nada e passar vazio.
        $this->assertNotEmpty($sources, 'Nenhum fonte PHP encontrado para varrer.');

        foreach ($sources as $file => $source) {
            preg_match_all("/(?<!_)esc_html\(\s*'([^']*)'/", $source, $matches);

            foreach ($matches[1] as $text) {
                $this->assertDoesNotMatchRegularExpression(
                    $accented,
                    $text,
                    "Texto em português sem i18n em {$file}: \"{$text}\" — use esc_html__() com o texto em inglês."
                );
            }
        }
    }

    /**
     * Texto visível no admin também precisa de text-domain, não string solta.
     */
    public function testAdminLabelsAreTranslatable(): void
    {
        $helper = self::read('Includes/LknmpGatewayGiveWPHelper.php');

        $this->assertStringNotContainsString(
            "'Configurações',",
            $helper,
            "O rótulo 'Configurações' precisa passar por esc_html__() com texto em inglês."
        );
    }

    /**
     * O JS do admin mostra o ambiente da chave. Texto fixo em português ali
     * nunca é traduzido e não acompanha o idioma do painel.
     */
    public function testAdminJsLabelsComeFromPhp(): void
    {
        $js = self::read('Admin/js/lknmp-gateway-givewp-show-message.js');

        $this->assertStringNotContainsString(
            'Ambiente Produç',
            $js,
            'O rótulo do ambiente precisa vir do PHP traduzido, não cravado no JS.'
        );

        $this->assertStringNotContainsString(
            'Chave incorreta',
            $js,
            'A mensagem de chave inválida precisa vir do PHP traduzido.'
        );
    }

    /**
     * @return array<string,string> arquivo => conteudo
     */
    private function phpSources(): array
    {
        $dir = self::pluginDir();
        $sources = array();

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ('php' !== $file->getExtension()) {
                continue;
            }

            $path = $file->getPathname();

            if (false !== strpos($path, '/vendor/')
                || false !== strpos($path, '/tests/')
                || false !== strpos($path, '/node_modules/')
            ) {
                continue;
            }

            $relative = ltrim(str_replace($dir, '', $path), '/');
            $sources[$relative] = (string) file_get_contents($path);
        }

        return $sources;
    }

    /**
     * O .pot é o contrato de tradução. Msgid em português aqui vira string que
     * nunca casa com a tradução e vaza em qualquer idioma. Inclui os metadados
     * do header (Plugin Name / Description), que o make-pot extrai direto.
     */
    public function testPotHasNoPortugueseMsgid(): void
    {
        $pot = self::read('languages/lknmp-gateway-givewp.pot');
        $accented = '/[áàâãéêíóôõúüçÁÀÂÃÉÊÍÓÔÕÚÜÇ]/';

        preg_match_all('/^msgid "(.*)"$/m', $pot, $matches);

        $this->assertNotEmpty($matches[1], 'Nenhum msgid encontrado no .pot.');

        foreach ($matches[1] as $msgid) {
            $this->assertDoesNotMatchRegularExpression(
                $accented,
                $msgid,
                "Msgid em português no .pot: \"{$msgid}\" — o texto de origem precisa estar em inglês."
            );
        }
    }

    /**
     * O .pot existe, tem header válido e cobre as strings do código. Um pot
     * sem referências (#:) é um pot gerado à mão que não serve de base.
     */
    public function testPotIsGeneratedFromSource(): void
    {
        $pot = self::read('languages/lknmp-gateway-givewp.pot');

        $this->assertStringContainsString('Content-Type: text/plain; charset=UTF-8', $pot);
        $this->assertStringContainsString('X-Domain: lknmp-gateway-givewp', $pot);
        $this->assertGreaterThan(
            30,
            preg_match_all('/^#:/m', $pot),
            'O .pot precisa ter referências de código (#:); sem elas não veio do make-pot.'
        );
    }

    /**
     * O readme traduzido acompanha o readme versionado: se existe no GlotPress
     * o projeto usa o fluxo e o arquivo precisa estar no repositório.
     */
    public function testReadmeTranslationExistsAndIsValid(): void
    {
        $readmePo = self::read('languages/lknmp-gateway-givewp-readme-pt_BR.po');

        $this->assertStringContainsString('Content-Type: text/plain; charset=UTF-8', $readmePo);
        $this->assertGreaterThan(
            40,
            preg_match_all('/^msgid /m', $readmePo),
            'O readme-pt_BR.po precisa cobrir o readme atual (mais de 40 msgids).'
        );
    }

    /**
     * O PHP mínimo suportado é 8.1 e precisa estar declarado em todo lugar que
     * o WordPress e o wp.org leem: header do plugin, readme, CI e composer.
     * Divergência aqui faz o wp.org liberar instalação em versão que quebra.
     */
    public function testDeclaresPhpMinimum81Everywhere(): void
    {
        $header = self::read('lknmp-gateway-givewp.php');

        $this->assertMatchesRegularExpression(
            '/^\s*\*\s*Requires PHP:\s*8\.1\s*$/m',
            $header,
            'O header do plugin precisa declarar "Requires PHP: 8.1".'
        );

        $readme = self::read('README.txt');

        $this->assertMatchesRegularExpression(
            '/^Requires PHP:\s*8\.1\s*$/m',
            $readme,
            'O README.txt precisa declarar "Requires PHP: 8.1".'
        );

        $composer = json_decode(self::read('composer.json'), true);

        $this->assertSame(
            '8.1.0',
            $composer['config']['platform']['php'] ?? null,
            'O composer.json precisa fixar config.platform.php em 8.1.0 para o lock resolver igual ao alvo.'
        );
    }

    /**
     * O CI tem que buildar e testar na versão mínima declarada. Se o CI roda
     * 7.4 e o plugin declara 8.1 (ou o contrário), nada é provado de verdade.
     */
    public function testCiUsesTheDeclaredMinimumPhp(): void
    {
        foreach (array('.github/workflows/main.yml', '.github/workflows/wordpress-release.yml') as $workflow) {
            $source = self::read($workflow);

            $this->assertMatchesRegularExpression(
                '/PHP_VERSION:\s*["\']8\.1["\']/',
                $source,
                "{$workflow} precisa usar PHP_VERSION 8.1."
            );

            $this->assertStringNotContainsString(
                '"7.4"',
                $source,
                "{$workflow} ainda referencia PHP 7.4."
            );
        }
    }

    /**
     * phan estava em require-dev e nunca era invocado: nem config, nem step no
     * CI, nem script. Dependência morta engorda o lock e quebra ao atualizar.
     */
    public function testHasNoUnusedDevDependencyPhan(): void
    {
        $composer = json_decode(self::read('composer.json'), true);

        $this->assertArrayNotHasKey(
            'phan/phan',
            $composer['require-dev'] ?? array(),
            'phan/phan não é usado em lugar nenhum — remover do require-dev.'
        );

        $lock = json_decode(self::read('composer.lock'), true);
        $names = array_column($lock['packages-dev'] ?? array(), 'name');

        $this->assertNotContains('phan/phan', $names, 'phan/phan ainda está no composer.lock.');
    }

    /**
     * A rota /lknmp/v1/payments era um hello world público, sem propósito e sem
     * permission_callback. Endpoint público que não serve a nada é superfície.
     */
    public function testHasNoDemoRestRoute(): void
    {
        $main = self::main();

        $this->assertStringNotContainsString(
            'mercadopago_get_endpoint_payments',
            $main,
            'A rota demo /payments precisa ser removida.'
        );

        $this->assertStringNotContainsString(
            'Hello World, this is the WordPress REST API',
            $main,
            'Texto da rota demo ainda presente.'
        );
    }

    /**
     * A rota de retorno confirma doação e chama a API do Mercado Pago com o
     * access token. Sem limite, uma referência vazada vira consumo de quota.
     */
    public function testPaymentReturnRouteIsRateLimited(): void
    {
        $main = self::main();

        $handler = $this->methodBody($main, 'get_handle_custom_payment_route');

        $this->assertStringContainsString(
            'get_transient',
            $handler,
            'A rota de retorno precisa de rate limit, como a de preferência.'
        );

        $this->assertStringContainsString(
            'rate_limited',
            $handler,
            'A rota de retorno precisa devolver 429 quando estourar o limite.'
        );
    }

    /**
     * As referências são criadas com add_option() (wp_options), mas o uninstall
     * só limpava give_get_settings(). Sobram linhas órfãs para sempre.
     */
    public function testUninstallRemovesGatewayReferenceOptions(): void
    {
        $uninstall = self::read('uninstall.php');

        // (?<!give_) evita casar com give_delete_option(), que é outra tabela.
        $this->assertMatchesRegularExpression(
            '/(?<!give_)delete_option\s*\(|DELETE FROM \{\$wpdb->options\}/i',
            $uninstall,
            'O uninstall precisa apagar as options lknmp_gateway_* criadas com add_option().'
        );

        $this->assertMatchesRegularExpression(
            '/lknmp_gateway_/',
            $uninstall,
            'O uninstall precisa filtrar o prefixo lknmp_gateway_.'
        );
    }

    /**
     * Recorta o corpo de um método público do fonte do plugin.
     */
    private function methodBody(string $source, string $method): string
    {
        $needle = 'function ' . $method;

        $start = strpos($source, $needle);

        $this->assertNotFalse($start, "Método {$method} não encontrado.");

        $end = strpos($source, "\n    }", (int) $start);

        $this->assertNotFalse($end, "Fim do método {$method} não encontrado.");

        return substr($source, (int) $start, (int) $end - (int) $start);
    }

    /**
     * O WordPress mínimo suportado é 6.5. Abaixo disso o requisito do próprio
     * GiveWP (6.9) já impede a ativação, então declarar 5.7 fazia o wp.org
     * oferecer o plugin para versões que nunca conseguiriam rodar.
     */
    public function testDeclaresWordPressMinimum65(): void
    {
        $readme = self::read('README.txt');

        $this->assertMatchesRegularExpression(
            '/^Requires at least:\s*6\.5\s*$/m',
            $readme,
            'O README.txt precisa declarar "Requires at least: 6.5".'
        );
    }

    /**
     * "Tested up to" tem que refletir a versão recente do WordPress: é ela que
     * o Plugin Check cobra na cobertura de testes. Declarar versão antiga
     * derruba a nota e esconde incompatibilidade real.
     */
    public function testTestedUpToIsRecent(): void
    {
        $readme = self::read('README.txt');

        $this->assertMatchesRegularExpression(
            '/^Tested up to:\s*(\d+)\.(\d+)\s*$/m',
            $readme,
            'O README.txt precisa de "Tested up to: X.Y".'
        );

        preg_match('/^Tested up to:\s*(\d+)\.(\d+)\s*$/m', $readme, $m);

        $tested = (int) $m[1] * 100 + (int) $m[2];

        preg_match('/^Requires at least:\s*(\d+)\.(\d+)\s*$/m', $readme, $r);

        $required = (int) $r[1] * 100 + (int) $r[2];

        $this->assertGreaterThan(
            $required,
            $tested,
            '"Tested up to" precisa ser maior que "Requires at least".'
        );

        $this->assertGreaterThanOrEqual(
            700,
            $tested,
            '"Tested up to" precisa acompanhar a versão recente do WordPress (7.x).'
        );
    }
}
