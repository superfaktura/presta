<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Dto;

use DateTimeImmutable;
use SuperFaktura\PrestaShop\Domain\Enum\PeppolSendStatus;
use SuperFaktura\PrestaShop\Domain\ValueObject\PeppolOperationId;
use SuperFaktura\PrestaShop\Domain\ValueObject\SuperFakturaInvoiceId;

final readonly class PeppolInvoiceStatus
{
    public function __construct(
        public SuperFakturaInvoiceId $invoiceId,
        public ?PeppolOperationId $operationId,
        public PeppolSendStatus $status,
        public ?int $outboundDocumentId,
        public ?string $providerDocumentId,
        public ?string $providerStatus,
        public ?string $failureCode,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $completedAt,
    ) {
    }
}