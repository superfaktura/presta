<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Domain\ValueObject;

use InvalidArgumentException;

final readonly class PeppolOperationId
{
    public function __construct(public string $value)
    {
        if (1 !== preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/i', $value)) {
            throw new InvalidArgumentException('A valid Peppol operation UUID is required.');
        }
    }
}