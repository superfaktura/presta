<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

/**
 * @phpstan-type Field array{
 *     key: string,
 *     default: int|string,
 *     checkbox: bool
 * }
 */
final class ModuleConfigurationFields
{
    /** @var array<string, Field> */
    private const FIELDS = [
        'email' => ['key' => 'SUPERFAKTURA_EMAIL', 'default' => '', 'checkbox' => false],
        'apikey' => ['key' => 'SUPERFAKTURA_APIKEY', 'default' => '', 'checkbox' => false],
        'company_id' => ['key' => 'SUPERFAKTURA_COMPANY_ID', 'default' => '', 'checkbox' => false],
        'id_order_state_invoice' => ['key' => 'SUPERFAKTURA_ORDER_STATE_INVOICE', 'default' => -1, 'checkbox' => false],
        'id_order_state_refund' => ['key' => 'SUPERFAKTURA_ORDER_STATE_REFUND', 'default' => 0, 'checkbox' => false],
        'set_invoice_paid' => ['key' => 'SUPERFAKTURA_SET_INVOICE_PAID', 'default' => 0, 'checkbox' => true],
        'variable_source' => ['key' => 'SUPERFAKTURA_VARIABLE_SOURCE', 'default' => 'order', 'checkbox' => false],
        'sequence_id' => ['key' => 'SUPERFAKTURA_SEQUENCE_ID', 'default' => '', 'checkbox' => false],
        'send_invoice' => ['key' => 'SUPERFAKTURA_SEND_INVOICE', 'default' => 0, 'checkbox' => true],
        'invoice_type' => ['key' => 'SUPERFAKTURA_INVOICE_TYPE', 'default' => 'regular', 'checkbox' => false],
        'invoice_language' => ['key' => 'SUPERFAKTURA_INVOICE_LANGUAGE', 'default' => 'slo', 'checkbox' => false],
        'issued_by' => ['key' => 'SUPERFAKTURA_ISSUED_BY', 'default' => '', 'checkbox' => false],
        'issued_by_phone' => ['key' => 'SUPERFAKTURA_ISSUED_BY_PHONE', 'default' => '', 'checkbox' => false],
        'issued_by_web' => ['key' => 'SUPERFAKTURA_ISSUED_BY_WEB', 'default' => '', 'checkbox' => false],
        'issued_by_email' => ['key' => 'SUPERFAKTURA_ISSUED_BY_EMAIL', 'default' => '', 'checkbox' => false],
        'by_square' => ['key' => 'SUPERFAKTURA_BY_SQUARE', 'default' => '', 'checkbox' => true],
        'logo_id' => ['key' => 'SUPERFAKTURA_LOGO_ID', 'default' => '', 'checkbox' => false],
        'bank_id' => ['key' => 'SUPERFAKTURA_BANK_ID', 'default' => '', 'checkbox' => false],
        'cancel_sequence_id' => ['key' => 'SUPERFAKTURA_CANCEL_SEQUENCE_ID', 'default' => '', 'checkbox' => false],
        'product_syntetic' => ['key' => 'SUPERFAKTURA_PRODUCT_SYNTETIC', 'default' => '', 'checkbox' => false],
        'product_analytic' => ['key' => 'SUPERFAKTURA_PRODUCT_ANALYTIC', 'default' => '', 'checkbox' => false],
        'carrier_syntetic' => ['key' => 'SUPERFAKTURA_CARRIER_SYNTETIC', 'default' => '', 'checkbox' => false],
        'carrier_analytic' => ['key' => 'SUPERFAKTURA_CARRIER_ANALYTIC', 'default' => '', 'checkbox' => false],
        'online_payment' => ['key' => 'SUPERFAKTURA_ONLINE_PAYMENT', 'default' => '', 'checkbox' => true],
        'paypal' => ['key' => 'SUPERFAKTURA_PAYPAL', 'default' => '', 'checkbox' => true],
        'callback_payment' => ['key' => 'SUPERFAKTURA_CALLBACK_PAYMENT', 'default' => '', 'checkbox' => false],
        'update_addressbook' => ['key' => 'SUPERFAKTURA_UPDATE_ADDRESSBOOK', 'default' => '', 'checkbox' => true],
        'use_sandbox' => ['key' => 'SUPERFAKTURA_USE_SANDBOX', 'default' => '', 'checkbox' => true],
        'add_rounding' => ['key' => 'SUPERFAKTURA_ADD_ROUNDING', 'default' => '', 'checkbox' => true],
    ];

    /** @return array<string, Field> */
    public static function definitions(): array
    {
        return self::FIELDS;
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_column(self::FIELDS, 'key');
    }
}