<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Contract;

use SuperFaktura\PrestaShop\Application\Dto\PeppolInvoiceStatus;
use SuperFaktura\PrestaShop\Application\Dto\PeppolSendReceipt;
use SuperFaktura\PrestaShop\Application\Exception\PeppolApiRejectedRequest;
use SuperFaktura\PrestaShop\Domain\ValueObject\InvoiceId;

interface PeppolInvoiceGatewayInterface
{
    /** @throws PeppolApiRejectedRequest */
    public function submit(InvoiceId $invoiceId): PeppolSendReceipt;

    /** @throws PeppolApiRejectedRequest */
    public function status(InvoiceId $invoiceId): PeppolInvoiceStatus;
}