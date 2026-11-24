<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use Address;
use Customer;

/**
 * @phpstan-type ClientPayload array{
 *     ico: string,
 *     name: string,
 *     address: string,
 *     city: string,
 *     zip: string,
 *     country: string,
 *     country_id: int|null,
 *     delivery_name: string,
 *     delivery_address: string,
 *     delivery_city: string,
 *     delivery_zip: string,
 *     delivery_country: string,
 *     delivery_country_id: int|null,
 *     phone: string,
 *     email: string,
 *     ic_dph: string,
 *     dic: string,
 *     update_addressbook: bool
 * }
 */
final readonly class InvoiceClientMapper
{
    public function __construct(private LegacyCountryIdMapper $countries)
    {
    }

    /** @return ClientPayload */
    public function map(
        Customer $customer,
        Address $billing,
        Address $delivery,
        bool $updateAddressBook,
    ): array {
        $taxId = '';
        $vatId = '';
        $vatNumber = (string) ($billing->vat_number ?? '');

        if ($vatNumber) {
            if (is_numeric(substr($vatNumber, 0, 2))) {
                $taxId = $vatNumber;
            } else {
                $vatId = $vatNumber;
                $taxId = substr($vatNumber, 2);
            }
        }

        if (5 > strlen($vatId)) {
            $vatId = '';
        }

        return [
            'ico' => $billing->dni ?? '',
            'name' => $this->name($billing),
            'address' => $this->addressLine($billing),
            'city' => $billing->city,
            'zip' => $billing->postcode,
            'country' => $billing->country,
            'country_id' => $this->countries->map((int) $billing->id_country),
            'delivery_name' => $this->name($delivery),
            'delivery_address' => $this->addressLine($delivery),
            'delivery_city' => $delivery->city,
            'delivery_zip' => $delivery->postcode,
            'delivery_country' => $delivery->country,
            'delivery_country_id' => $this->countries->map((int) $delivery->id_country),
            'phone' => '' != $billing->phone_mobile ? $billing->phone_mobile : $billing->phone,
            'email' => $customer->email,
            'ic_dph' => $vatId,
            'dic' => $taxId,
            'update_addressbook' => $updateAddressBook,
        ];
    }

    private function name(Address $address): string
    {
        $name = (string) $address->company;

        if (!$name && ('' != $address->firstname || '' != $address->lastname)) {
            $name .= ('' != $name ? ', ' : '') . $address->firstname;
            $name .= ('' != $address->firstname ? ' ' : '') . $address->lastname;
        }

        return $name;
    }

    private function addressLine(Address $address): string
    {
        return $address->address1
               . ('' != $address->address2 ? ', ' : '')
               . $address->address2;
    }
}