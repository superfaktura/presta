<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

final class PaymentMethodMapper
{
    public function toApiPaymentType(string $payment): string
    {
        return 'Bank wire' === $payment ? 'transfer' : $payment;
    }

    public function isCashOnDelivery(string $payment): bool
    {
        return 'Cash on delivery' === $payment
               || str_contains(strtolower($payment), 'dobier');
    }
}