<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Dto;

use SuperFaktura\PrestaShop\Domain\ValueObject\InvoiceId;

final readonly class CreatedInvoice
{
    public function __construct(
        public InvoiceId $invoiceId,
        public int $companyId,
        public bool $useSandbox,
    ) {
    }
}