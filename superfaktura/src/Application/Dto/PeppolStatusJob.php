<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Dto;

use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;
use SuperFaktura\PrestaShop\Domain\ValueObject\PeppolOperationId;

final readonly class PeppolStatusJob
{
    public function __construct(
        public OrderReference $order,
        public CreatedInvoice $invoice,
        public string $generation,
        public ?PeppolOperationId $operationId,
        public int $attemptCount,
    ) {
    }
}