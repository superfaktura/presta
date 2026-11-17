<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Handler;

use OutOfBoundsException;
use SuperFaktura\PrestaShop\Application\Contract\OrderInvoiceRepositoryInterface;
use SuperFaktura\PrestaShop\Application\Contract\PeppolInvoiceGatewayResolverInterface;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Application\Dto\PeppolInvoiceStatus;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;

final readonly class GetOrderInvoicePeppolStatusHandler
{
    public function __construct(
        private OrderInvoiceRepositoryInterface $invoices,
        private PeppolInvoiceGatewayResolverInterface $gateways,
    ) {
    }

    public function __invoke(OrderReference $order, CreatedInvoice $requested): ?PeppolInvoiceStatus
    {
        foreach ($this->invoices->findByOrder($order) as $invoice) {
            if (
                $requested->invoiceId->value === $invoice->invoiceId->value
                && $requested->companyId === $invoice->companyId
                && $requested->useSandbox === $invoice->useSandbox
            ) {
                $gateway = $this->gateways->forInvoice($order, $invoice);

                return (new GetPeppolInvoiceStatusHandler($gateway))($invoice->invoiceId);
            }
        }

        throw new OutOfBoundsException('No matching SuperFaktura invoice is linked to this order.');
    }
}