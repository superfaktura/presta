<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use Address;
use Carrier;
use Cart;
use Currency;
use Customer;
use Order;
use SuperFaktura\PrestaShop\Application\Dto\InvoiceCreationOptions;

/**
 * @phpstan-import-type ClientPayload from InvoiceClientMapper
 * @phpstan-type Product array{
 *     product_reference?: string|null,
 *     product_upc?: string|null,
 *     product_ean13?: string|null,
 *     attributes_small?: string|null,
 *     product_name: string,
 *     product_quantity: int|string,
 *     unit_price_tax_excl: int|float|string,
 *     tax_rate: int|float|string
 * }
 * @phpstan-type InvoiceItem array{
 *     name: string,
 *     description?: string,
 *     quantity: int|string,
 *     unit: string,
 *     unit_price: int|float|string,
 *     tax: int|float|string,
 *     sku?: string|null,
 *     AccountingDetail?: array{
 *         analytics_account: int|string,
 *         synthetic_account: int|string
 *     }
 * }
 * @phpstan-type InvoicePayload array{
 *     InvoiceItem: list<InvoiceItem>,
 *     Client: ClientPayload,
 *     Invoice: array{
 *         import_type: string,
 *         type: string,
 *         import_id: int|string,
 *         variable: int|string|null,
 *         order_no: int|string,
 *         already_paid: bool,
 *         delivery_type: string,
 *         payment_type: string,
 *         rounding: string,
 *         sequence_id: int|string,
 *         issued_by: string,
 *         issued_by_phone: string,
 *         issued_by_web: string,
 *         issued_by_email: string,
 *         discount_total: int|float|string|null,
 *         logo_id: int|string,
 *         bank_accounts?: list<array{id: int|string}>,
 *         add_rounding_item?: bool,
 *         invoice_currency?: string
 *     },
 *     InvoiceSetting: array{settings: string}
 * }
 */
final readonly class InvoicePayloadBuilder
{
    public function __construct(
        private InvoiceClientMapper $clients,
        private PaymentMethodMapper $payments,
    ) {
    }

    /** @return InvoicePayload */
    public function build(
        Order $order,
        Cart $cart,
        InvoiceCreationOptions $options,
    ): array {
        $customer = new Customer($cart->id_customer);
        $billing = new Address($cart->id_address_invoice);
        $delivery = new Address($cart->id_address_delivery);
        $carrier = new Carrier($order->id_carrier);
        $currency = new Currency($order->id_currency);
        $payment = (string) ($order->payment ?? '');

        /** @var array<int, Product> $products */
        $products = $order->getCartProducts();

        $data = [
            'InvoiceItem' => $this->productItems($products, $options),
            'Client' => $this->clients->map(
                $customer,
                $billing,
                $delivery,
                $options->updateAddressBook,
            ),
            'Invoice' => [
                'import_type' => 'prestashop',
                'type' => $options->invoiceType,
                'import_id' => $order->id,
                'variable' => $options->useOrderIdAsVariable ? $order->id : null,
                'order_no' => $order->id,
                'already_paid' => $options->alreadyPaid,
                'delivery_type' => $carrier->name ?? '',
                'payment_type' => $this->payments->toApiPaymentType($payment),
                'rounding' => 'item_ext',
                'sequence_id' => $options->sequenceId,
                'issued_by' => $options->issuedBy,
                'issued_by_phone' => $options->issuedByPhone,
                'issued_by_web' => $options->issuedByWeb,
                'issued_by_email' => $options->issuedByEmail,
                'discount_total' => isset($order->total_discounts) && 0 < $order->total_discounts
                    ? $order->total_discounts
                    : null,
                'logo_id' => $options->logoId,
            ],
        ];

        if (null !== $options->bankId) {
            $data['Invoice']['bank_accounts'] = [['id' => $options->bankId]];
        }

        if ($options->addCashOnDeliveryRounding && $this->payments->isCashOnDelivery($payment)) {
            $data['Invoice']['add_rounding_item'] = true;
        }

        $data['InvoiceSetting']['settings'] = json_encode([
            'language' => $options->language,
            'signature' => true,
            'payment_info' => true,
            'bysquare' => $options->bySquare,
            'online_payment' => $options->onlinePayment,
            'paypal' => $options->paypal,
            'callback_payment' => $options->callbackPayment,
        ], JSON_THROW_ON_ERROR);

        if (isset($currency->iso_code)) {
            $data['Invoice']['invoice_currency'] = $currency->iso_code;
        }

        $shipping = $this->shippingItem($order, $carrier, $options);

        if (null !== $shipping) {
            $data['InvoiceItem'][] = $shipping;
        }

        return $data;
    }

    /**
     * @param iterable<Product> $products
     * @return list<InvoiceItem>
     */
    private function productItems(
        iterable $products,
        InvoiceCreationOptions $options,
    ): array {
        $items = [];

        foreach ($products as $product) {
            $sku = $this->sku($product);
            $attributes = isset($product['attributes_small'])
                ? strip_tags($product['attributes_small'])
                : '';

            $item = [
                'name' => $product['product_name'],
                'description' => trim($attributes . PHP_EOL . $sku),
                'quantity' => $product['product_quantity'],
                'unit' => 'ks',
                'unit_price' => $product['unit_price_tax_excl'],
                'tax' => $product['tax_rate'],
                'sku' => $sku,
            ];

            if ('' !== $options->productAnalyticAccount || '' !== $options->productSyntheticAccount) {
                $item['AccountingDetail'] = [
                    'analytics_account' => $options->productAnalyticAccount,
                    'synthetic_account' => $options->productSyntheticAccount,
                ];
            }

            $items[] = $item;
        }

        return $items;
    }

    /** @param Product $product */
    private function sku(array $product): ?string
    {
        foreach (['product_reference', 'product_upc', 'product_ean13'] as $field) {
            $value = $product[$field] ?? null;

            if ($value) {
                return $value;
            }
        }

        return null;
    }

    /** @return InvoiceItem|null */
    private function shippingItem(
        Order $order,
        Carrier $carrier,
        InvoiceCreationOptions $options,
    ): ?array {
        if (!isset($order->total_shipping) || 0 >= $order->total_shipping) {
            return null;
        }

        $tax = $order->carrier_tax_rate ?? 0;
        $item = [
            'name' => $carrier->name,
            'quantity' => 1,
            'unit' => 'ks',
            'unit_price' => $order->total_shipping / (1 + ($tax / 100)),
            'tax' => $tax,
        ];

        if ('' !== $options->carrierAnalyticAccount || '' !== $options->carrierSyntheticAccount) {
            $item['AccountingDetail'] = [
                'analytics_account' => $options->carrierAnalyticAccount,
                'synthetic_account' => $options->carrierSyntheticAccount,
            ];
        }

        return $item;
    }
}