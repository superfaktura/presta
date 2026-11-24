<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Contract;

use DateTimeImmutable;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Application\Dto\PeppolInvoiceStatus;
use SuperFaktura\PrestaShop\Application\Dto\PeppolSendReceipt;
use SuperFaktura\PrestaShop\Application\Dto\PeppolStatusJob;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;

interface PeppolStatusTrackingRepositoryInterface
{
    public function begin(
        OrderReference $order,
        CreatedInvoice $invoice,
        DateTimeImmutable $now,
    ): PeppolStatusJob;

    public function recordSubmission(
        PeppolStatusJob $job,
        PeppolSendReceipt|PeppolInvoiceStatus $result,
        DateTimeImmutable $now,
    ): void;
}