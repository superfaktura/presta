<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Dto;

use SuperFaktura\PrestaShop\Domain\ValueObject\PeppolOperationId;
use SuperFaktura\PrestaShop\Domain\ValueObject\SuperFakturaInvoiceId;

final readonly class PeppolSendReceipt
{
    public function __construct(
        public SuperFakturaInvoiceId $invoiceId,
        public PeppolOperationId $operationId,
    ) {
    }
}