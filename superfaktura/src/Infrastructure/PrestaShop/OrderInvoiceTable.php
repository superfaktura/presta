<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use Db;

final readonly class OrderInvoiceTable
{
    public const NAME = 'superfaktura_order_invoice';

    public function __construct(private PrestaShopDb $db)
    {
    }

    public function create(): bool
    {
        $table = $this->db->table(self::NAME);

        return $this->db->execute(<<<SQL
            CREATE TABLE IF NOT EXISTS {$table} (
                id_shop INT UNSIGNED NOT NULL,
                id_order INT UNSIGNED NOT NULL,
                use_sandbox TINYINT UNSIGNED NOT NULL,
                sf_company_id BIGINT UNSIGNED NOT NULL,
                sf_invoice_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (id_shop, id_order, use_sandbox, sf_company_id, sf_invoice_id)
            ) ENGINE=InnoDB
            SQL);
    }
}