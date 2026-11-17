<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Domain\ValueObject;

use InvalidArgumentException;

final readonly class SuperFakturaInvoiceId
{
    public function __construct(public int $value)
    {
        if (0 >= $value) {
            throw new InvalidArgumentException('A positive SuperFaktura invoice ID is required.');
        }
    }
}