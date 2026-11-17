<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Contract;

use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;

interface OrderInvoiceRepositoryInterface
{
    public function save(OrderReference $order, CreatedInvoice $invoice): void;

    /** @return iterable<int, CreatedInvoice> */
    public function findByOrder(OrderReference $order): iterable;
}