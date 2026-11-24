<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Contract;

use SuperFaktura\PrestaShop\Application\Dto\OrderPaymentDetails;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;

interface OrderPaymentDetailsRepositoryInterface
{
    public function find(OrderReference $order): ?OrderPaymentDetails;
}