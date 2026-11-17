<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Dto;

use DateTimeImmutable;
use SuperFaktura\PrestaShop\Application\Enum\PeppolStatusPollOutcome;

final readonly class PeppolStatusPollResult
{
    public function __construct(
        public PeppolStatusPollOutcome $outcome,
        public ?PeppolInvoiceStatus $status,
        public ?DateTimeImmutable $nextPollAt = null,
    ) {
    }
}