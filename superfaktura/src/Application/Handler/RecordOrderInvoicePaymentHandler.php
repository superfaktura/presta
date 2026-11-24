<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Handler;

use DateTimeImmutable;
use SuperFaktura\PrestaShop\Application\Contract\InvoicePaymentGatewayInterface;
use SuperFaktura\PrestaShop\Application\Contract\OrderPaymentDetailsRepositoryInterface;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;

final readonly class RecordOrderInvoicePaymentHandler
{
    public function __construct(
        private OrderPaymentDetailsRepositoryInterface $payments,
        private InvoicePaymentGatewayInterface $gateway,
    ) {
    }

    public function __invoke(
        OrderReference $order,
        DateTimeImmutable $paidAt,
    ): ?bool {
        $payment = $this->payments->find($order);

        if (null === $payment) {
            return null;
        }

        return $this->gateway->recordPayment($order, $payment, $paidAt);
    }
}