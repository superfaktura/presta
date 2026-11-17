<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Dto;

use SuperFaktura\PrestaShop\Domain\ValueObject\PeppolOperationId;
use SuperFaktura\PrestaShop\Domain\ValueObject\InvoiceId;

final readonly class PeppolSendReceipt
{
    public function __construct(
        public InvoiceId $invoiceId,
        public PeppolOperationId $operationId,
    ) {
    }
}