<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Contract;

interface PeppolInvoiceGatewayInterface
{
    /** @return array{httpStatus: int, errorCode: string|null, data: array} */
    public function submit(int $invoiceId): array;

    /** @return array{httpStatus: int, errorCode: string|null, data: array} */
    public function status(int $invoiceId): array;
}