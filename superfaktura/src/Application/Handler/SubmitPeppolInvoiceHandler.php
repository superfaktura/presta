<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Handler;

use SuperFaktura\PrestaShop\Application\Contract\PeppolInvoiceGatewayInterface;
use SuperFaktura\PrestaShop\Application\Dto\PeppolInvoiceStatus;
use SuperFaktura\PrestaShop\Application\Dto\PeppolSendReceipt;
use SuperFaktura\PrestaShop\Application\Exception\PeppolApiRejectedRequest;
use SuperFaktura\PrestaShop\Domain\ValueObject\InvoiceId;

final readonly class SubmitPeppolInvoiceHandler
{
    public function __construct(private PeppolInvoiceGatewayInterface $gateway)
    {
    }

    public function __invoke(InvoiceId $invoiceId): PeppolSendReceipt|PeppolInvoiceStatus
    {
        try {
            return $this->gateway->submit($invoiceId);
        } catch (PeppolApiRejectedRequest $exception) {
            return match ($exception->errorCode) {
                'peppol.send_already_pending',
                'peppol.invoice_already_sent' => $this->gateway->status($invoiceId),
                default => throw $exception,
            };
        }
    }
}