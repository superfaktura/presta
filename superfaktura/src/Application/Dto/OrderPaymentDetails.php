<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Dto;

final readonly class OrderPaymentDetails
{
    public function __construct(
        public int|float|string $amount,
        public string $currency,
    ) {
    }
}