<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use RuntimeException;
use SuperFaktura\PrestaShop\Application\Contract\OrderPaymentDetailsRepositoryInterface;
use SuperFaktura\PrestaShop\Application\Dto\OrderPaymentDetails;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;
use UnexpectedValueException;

final readonly class DbOrderPaymentDetailsRepository implements OrderPaymentDetailsRepositoryInterface
{
    public function __construct(private PrestaShopDb $db)
    {
    }

    public function find(OrderReference $order): ?OrderPaymentDetails
    {
        $rows = $this->db->fetchAll(sprintf(
            'SELECT o.total_paid, c.iso_code
               FROM %s AS o
               LEFT JOIN %s AS c ON c.id_currency = o.id_currency
              WHERE o.id_order = %d AND o.id_shop = %d
              LIMIT 1',
            $this->db->table('orders'),
            $this->db->table('currency'),
            $order->orderId,
            $order->shopId,
        ));

        if (false === $rows) {
            throw new RuntimeException(
                'Unable to load the order payment details.',
            );
        }

        $row = $rows[0] ?? null;

        if (null === $row) {
            return null;
        }

        $amount = $row['total_paid'] ?? null;
        $currency = $row['iso_code'] ?? null;

        if (
            (!is_int($amount) && !is_float($amount) && !is_string($amount))
            || !is_numeric($amount)
            || (is_float($amount) && !is_finite($amount))
            || !is_string($currency)
            || '' === trim($currency)
        ) {
            throw new UnexpectedValueException(
                'Invalid order payment details.',
            );
        }

        return new OrderPaymentDetails($amount, $currency);
    }
}