<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Contract;

use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;

interface PeppolInvoiceGatewayResolverInterface
{
    public function forInvoice(OrderReference $order, CreatedInvoice $invoice): PeppolInvoiceGatewayInterface;
}