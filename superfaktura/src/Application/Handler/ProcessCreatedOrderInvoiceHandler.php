<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Handler;

use SuperFaktura\PrestaShop\Application\Contract\OrderInvoiceRepositoryInterface;
use SuperFaktura\PrestaShop\Application\Contract\PeppolInvoiceGatewayResolverInterface;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Application\Dto\PeppolInvoiceStatus;
use SuperFaktura\PrestaShop\Application\Dto\PeppolSendReceipt;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;

final readonly class ProcessCreatedOrderInvoiceHandler
{
    public function __construct(
        private OrderInvoiceRepositoryInterface $invoices,
        private PeppolInvoiceGatewayResolverInterface $gateways,
    ) {
    }

    public function __invoke(
        OrderReference $order,
        CreatedInvoice $invoice,
        bool $sendViaPeppol,
    ): PeppolSendReceipt|PeppolInvoiceStatus|null {
        $this->invoices->save($order, $invoice);

        if (!$sendViaPeppol) {
            return null;
        }

        $gateway = $this->gateways->forInvoice($order, $invoice);

        return (new SubmitPeppolInvoiceHandler($gateway))($invoice->invoiceId);
    }
}