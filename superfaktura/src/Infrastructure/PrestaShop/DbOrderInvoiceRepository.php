<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use Db;
use InvalidArgumentException;
use RuntimeException;
use SuperFaktura\PrestaShop\Application\Contract\OrderInvoiceRepositoryInterface;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Domain\ValueObject\InvoiceId;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;
use UnexpectedValueException;

final readonly class DbOrderInvoiceRepository implements OrderInvoiceRepositoryInterface
{
    public function __construct(private Db $db)
    {
    }

    public function save(OrderReference $order, CreatedInvoice $invoice): void
    {
        if (0 >= $invoice->companyId) {
            throw new InvalidArgumentException('A positive SuperFaktura company ID is required.');
        }

        $table = _DB_PREFIX_ . OrderInvoiceTable::NAME;
        $sql = sprintf(
            'INSERT INTO `%s` (id_shop, id_order, use_sandbox, sf_company_id, sf_invoice_id)
             VALUES (%d, %d, %d, %d, %d)
             ON DUPLICATE KEY UPDATE sf_invoice_id = sf_invoice_id',
            $table,
            $order->shopId,
            $order->orderId,
            (int) $invoice->useSandbox,
            $invoice->companyId,
            $invoice->invoiceId->value,
        );

        if (false === $this->db->execute($sql)) {
            throw new RuntimeException('Unable to save the SuperFaktura invoice reference.');
        }
    }

    /** @return iterable<int, CreatedInvoice> */
    public function findByOrder(OrderReference $order): iterable
    {
        $table = _DB_PREFIX_ . OrderInvoiceTable::NAME;
        $sql = sprintf(
            'SELECT use_sandbox, sf_company_id, sf_invoice_id
             FROM `%s`
             WHERE id_shop = %d AND id_order = %d
             ORDER BY use_sandbox, sf_company_id, sf_invoice_id',
            $table,
            $order->shopId,
            $order->orderId,
        );

        $rows = $this->db->executeS($sql, true, false);

        if (false === $rows) {
            throw new RuntimeException('Unable to load the SuperFaktura invoice references.');
        }

        foreach ($rows as $row) {
            $invoiceId = filter_var($row['sf_invoice_id'] ?? null, FILTER_VALIDATE_INT);
            $companyId = filter_var($row['sf_company_id'] ?? null, FILTER_VALIDATE_INT);
            $sandbox = $row['use_sandbox'] ?? null;

            if (
                false === $invoiceId || 0 >= $invoiceId
                || false === $companyId || 0 >= $companyId
                || !in_array($sandbox, [0, 1, '0', '1'], true)
            ) {
                throw new UnexpectedValueException('Invalid SuperFaktura invoice reference in storage.');
            }

            yield new CreatedInvoice(new InvoiceId($invoiceId), $companyId, 1 === (int) $sandbox);
        }
    }
}