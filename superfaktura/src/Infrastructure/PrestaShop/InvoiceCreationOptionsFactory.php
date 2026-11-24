<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use InvalidArgumentException;
use SensitiveParameter;
use SuperFaktura\PrestaShop\Application\Dto\InvoiceCreationOptions;

final class InvoiceCreationOptionsFactory
{
    /** @param array<string, string|int|bool> $values */
    public function fromConfiguration(#[SensitiveParameter] array $values): InvoiceCreationOptions
    {
        return new InvoiceCreationOptions(
            invoiceType: (string) $values['invoice_type'],
            useOrderIdAsVariable: 'order' === $values['variable_source'],
            alreadyPaid: 1 == $values['set_invoice_paid'],
            updateAddressBook: (bool) $values['update_addressbook'],
            addCashOnDeliveryRounding: (bool) $values['add_rounding'],
            language: (string) $values['invoice_language'],
            sequenceId: $this->identifier($values['sequence_id']),
            issuedBy: (string) ($values['issued_by'] ?: ''),
            issuedByPhone: (string) ($values['issued_by_phone'] ?: ''),
            issuedByWeb: (string) ($values['issued_by_web'] ?: ''),
            issuedByEmail: (string) ($values['issued_by_email'] ?: ''),
            logoId: $this->identifier($values['logo_id']),
            bankId: $values['bank_id'] ? $this->identifier($values['bank_id']) : null,
            productSyntheticAccount: $this->identifier($values['product_syntetic']),
            productAnalyticAccount: $this->identifier($values['product_analytic']),
            carrierSyntheticAccount: $this->identifier($values['carrier_syntetic']),
            carrierAnalyticAccount: $this->identifier($values['carrier_analytic']),
            bySquare: $values['by_square'],
            onlinePayment: $values['online_payment'],
            paypal: $values['paypal'],
            callbackPayment: $values['callback_payment'],
        );
    }

    private function identifier(string|int|bool $value): string|int
    {
        if (!$value) {
            return '';
        }

        if (is_bool($value)) {
            throw new InvalidArgumentException('An invoice configuration identifier must be a string or integer.');
        }

        return $value;
    }
}
