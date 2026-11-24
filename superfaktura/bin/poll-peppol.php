<?php

declare(strict_types=1);

if ('cli' !== PHP_SAPI) {
    http_response_code(404);
    exit;
}

$limit = isset($argv[1])
    ? filter_var(
        $argv[1],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 100]],
    )
    : 25;

if (false === $limit) {
    fwrite(STDERR, "Usage: php poll-peppol.php [batch-size: 1..100]\n");
    exit(2);
}

try {
    require_once dirname(__DIR__, 3) . '/config/config.inc.php';
    require_once dirname(__DIR__) . '/autoload.php';

    $module = \Module::getInstanceByName('superfaktura');

    if (!$module instanceof \Module || 0 >= (int) $module->id) {
        throw new \RuntimeException('SuperFaktura module is not installed.');
    }

    $factory = new \SuperFaktura\PrestaShop\Infrastructure\PrestaShop\ModuleServiceFactory(
        (string) $module->version,
        (string) _PS_VERSION_,
        '',
    );

    $processed = $factory->createPeppolStatusPollingWorker()->run($limit);

    fwrite(STDOUT, sprintf(
        "Processed %d Peppol status checks.\n",
        $processed,
    ));
} catch (\Throwable $exception) {
    fwrite(STDERR, 'Peppol polling failed: ' . $exception::class . PHP_EOL);
    exit(1);
}