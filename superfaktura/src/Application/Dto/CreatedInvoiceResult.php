<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Dto;

final readonly class CreatedInvoiceResult
{
    public function __construct(
        public CreatedInvoice $invoice,
        public ?string $recipientEmail,
    ) {
    }
}