<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

final readonly class PeppolStatusTable
{
    public const NAME = 'superfaktura_peppol_status';

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
                generation CHAR(32) NOT NULL,
                operation_id CHAR(36) DEFAULT NULL,
                attempt_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                poll_outcome VARCHAR(16) NOT NULL DEFAULT 'retry',
                status_payload MEDIUMTEXT DEFAULT NULL,
                last_error VARCHAR(128) DEFAULT NULL,
                next_poll_at DATETIME DEFAULT NULL,
                checked_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id_shop, id_order, use_sandbox, sf_company_id, sf_invoice_id),
                KEY due_peppol_status (next_poll_at)
            ) ENGINE=InnoDB
            SQL);
    }
}