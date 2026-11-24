<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Dto;

use DateTimeImmutable;
use SuperFaktura\PrestaShop\Application\Enum\PeppolStatusPollOutcome;

final readonly class OrderInvoicePeppolState
{
    public function __construct(
        public CreatedInvoice $invoice,
        public ?PeppolStatusJob $job,
        public ?PeppolInvoiceStatus $status,
        public ?DateTimeImmutable $checkedAt,
        public ?DateTimeImmutable $nextPollAt,
        public ?PeppolStatusPollOutcome $pollOutcome,
        public ?string $lastError,
    ) {
    }
}