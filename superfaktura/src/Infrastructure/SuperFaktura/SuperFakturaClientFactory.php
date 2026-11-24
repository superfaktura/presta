<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\SuperFaktura;

use InvalidArgumentException;
use SensitiveParameter;
use SuperFaktura\PrestaShop\Infrastructure\Api\Service\Authorization;
use SuperFaktura\PrestaShop\Infrastructure\Api\Service\CurlClient;

final class SuperFakturaClientFactory
{
    public function create(
        string $email,
        #[SensitiveParameter]
        string $apiKey,
        string $companyId,
        string $moduleName,
        bool $useSandbox,
    ): ClientInterface {
        $email = trim($email);

        if ('' === $email || '' === trim($apiKey)) {
            throw new InvalidArgumentException(
                'SuperFaktura API email and API key are required.',
            );
        }

        $authorization = (new Authorization())->create(
            $email,
            $apiKey,
            trim($companyId),
            $moduleName,
        );

        return new CurlClient($useSandbox, $authorization);
    }
}