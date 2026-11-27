<?php
/**
 * PHPUnit bootstrap — Mercado Pago para Give.
 *
 * Os testes unitários validam o fonte sem subir o WordPress: bastam as
 * constantes do plugin e o autoloader do Composer.
 *
 * @package LKNMP_Mercadopago_For_Givewp
 */

if (! defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

$lknmp_autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (! file_exists($lknmp_autoload)) {
    exit('[PHPUnit] vendor/autoload.php ausente. Rode composer install.');
}

require_once $lknmp_autoload;
