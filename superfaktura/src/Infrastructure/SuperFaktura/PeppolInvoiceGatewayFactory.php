<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\SuperFaktura;

use InvalidArgumentException;
use SensitiveParameter;
use SuperFaktura\PrestaShop\Application\Contract\PeppolInvoiceGatewayInterface;
use SuperFaktura\PrestaShop\Infrastructure\Api\Service\Authorization;
use SuperFaktura\PrestaShop\Infrastructure\Api\Service\CurlClient;
use SuperFaktura\PrestaShop\Infrastructure\Api\Service\JsonParser;
use SuperFaktura\PrestaShop\Infrastructure\Service\PeppolInvoiceGateway;
use SuperFaktura\PrestaShop\Infrastructure\Service\PeppolPayloadMapper;

final class PeppolInvoiceGatewayFactory
{
    public function create(
        string $email,
        #[SensitiveParameter]
        string $apiKey,
        string $companyId,
        string $moduleName,
        bool $useSandbox,
    ): PeppolInvoiceGatewayInterface {
        $email = trim($email);

        if ('' === $email || '' === trim($apiKey)) {
            throw new InvalidArgumentException('SuperFaktura API email and API key are required.');
        }

        $authorization = (new Authorization())->create(
            $email,
            $apiKey,
            trim($companyId),
            $moduleName,
        );

        return new PeppolInvoiceGateway(
            new CurlClient($useSandbox, $authorization),
            new JsonParser(),
            new PeppolPayloadMapper(),
        );
    }
}