<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Dto;

final readonly class InvoiceCreationOptions
{
    public function __construct(
        public string $invoiceType,
        public bool $useOrderIdAsVariable,
        public bool $alreadyPaid,
        public bool $updateAddressBook,
        public bool $addCashOnDeliveryRounding,
        public string $language,
        public int|string $sequenceId,
        public string $issuedBy,
        public string $issuedByPhone,
        public string $issuedByWeb,
        public string $issuedByEmail,
        public int|string $logoId,
        public int|string|null $bankId,
        public int|string $productSyntheticAccount,
        public int|string $productAnalyticAccount,
        public int|string $carrierSyntheticAccount,
        public int|string $carrierAnalyticAccount,
        public bool|int|string|null $bySquare,
        public bool|int|string|null $onlinePayment,
        public bool|int|string|null $paypal,
        public bool|int|string|null $callbackPayment,
    ) {
    }
}