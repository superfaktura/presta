<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Domain\ValueObject;

use InvalidArgumentException;

final readonly class OrderReference
{
    public function __construct(
        public int $shopId,
        public int $orderId,
    ) {
        if (0 >= $shopId || 0 >= $orderId) {
            throw new InvalidArgumentException('Positive shop and order IDs are required.');
        }
    }
}