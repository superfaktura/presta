<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\Api\Service;

use SensitiveParameter;

final class Authorization
{
    public function create(
        string $email,
        #[SensitiveParameter]
        string $apiKey,
        string $companyId,
        string $moduleName,
    ): string {
        $parameters = [
            'email' => $email,
            'apikey' => $apiKey,
            'module' => $moduleName,
        ];

        if ('' !== $companyId) {
            $parameters['company_id'] = $companyId;
        }

        return 'SFAPI ' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    }
}