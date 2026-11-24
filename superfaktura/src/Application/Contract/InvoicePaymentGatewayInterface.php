<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Contract;

use DateTimeImmutable;
use SuperFaktura\PrestaShop\Application\Dto\OrderPaymentDetails;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;

interface InvoicePaymentGatewayInterface
{
    public function recordPayment(
        OrderReference $order,
        OrderPaymentDetails $payment,
        DateTimeImmutable $paidAt,
    ): bool;
}