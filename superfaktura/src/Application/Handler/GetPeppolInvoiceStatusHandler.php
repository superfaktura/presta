<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Handler;

use SuperFaktura\PrestaShop\Application\Contract\PeppolInvoiceGatewayInterface;
use SuperFaktura\PrestaShop\Application\Dto\PeppolInvoiceStatus;
use SuperFaktura\PrestaShop\Application\Exception\PeppolApiRejectedRequest;
use SuperFaktura\PrestaShop\Domain\ValueObject\InvoiceId;

final readonly class GetPeppolInvoiceStatusHandler
{
    public function __construct(private PeppolInvoiceGatewayInterface $gateway)
    {
    }

    public function __invoke(InvoiceId $invoiceId): ?PeppolInvoiceStatus
    {
        try {
            return $this->gateway->status($invoiceId);
        } catch (PeppolApiRejectedRequest $exception) {
            if ('peppol.send_status_not_found' === $exception->errorCode) {
                return null;
            }

            throw $exception;
        }
    }
}