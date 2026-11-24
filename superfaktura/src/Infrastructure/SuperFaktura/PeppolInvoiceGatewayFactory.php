<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\SuperFaktura;

use SensitiveParameter;
use SuperFaktura\PrestaShop\Application\Contract\PeppolInvoiceGatewayInterface;
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
        $client = (new SuperFakturaClientFactory())->create(
            email: $email,
            apiKey: $apiKey,
            companyId: $companyId,
            moduleName: $moduleName,
            useSandbox: $useSandbox,
        );

        return new PeppolInvoiceGateway(
            $client,
            new JsonParser(),
            new PeppolPayloadMapper(),
        );
    }
}