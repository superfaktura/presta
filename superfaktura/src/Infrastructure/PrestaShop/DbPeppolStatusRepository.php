<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;
use SuperFaktura\PrestaShop\Application\Contract\PeppolStatusTrackingRepositoryInterface;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Application\Dto\PeppolInvoiceStatus;
use SuperFaktura\PrestaShop\Application\Dto\PeppolSendReceipt;
use SuperFaktura\PrestaShop\Application\Dto\PeppolStatusJob;
use SuperFaktura\PrestaShop\Application\Dto\PeppolStatusPollResult;
use SuperFaktura\PrestaShop\Application\Handler\PollPeppolInvoiceStatusHandler;
use SuperFaktura\PrestaShop\Domain\ValueObject\InvoiceId;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;
use SuperFaktura\PrestaShop\Domain\ValueObject\PeppolOperationId;

final readonly class DbPeppolStatusRepository implements PeppolStatusTrackingRepositoryInterface
{
    public function __construct(private PrestaShopDb $db)
    {
    }

    public function begin(
        OrderReference $order,
        CreatedInvoice $invoice,
        DateTimeImmutable $now,
    ): PeppolStatusJob {
        $job = new PeppolStatusJob(
            $order,
            $invoice,
            bin2hex(random_bytes(16)),
            null,
            0,
        );

        $table = $this->db->table(PeppolStatusTable::NAME);
        $date = $this->date($now);
        $next = $this->date($now->add(
            new DateInterval('PT' . PollPeppolInvoiceStatusHandler::INTERVAL_SECONDS . 'S'),
        ));

        $this->execute(sprintf(
            'INSERT INTO %s
                (id_shop, id_order, use_sandbox, sf_company_id, sf_invoice_id, generation,
                 operation_id, attempt_count, poll_outcome, status_payload, last_error,
                 next_poll_at, checked_at, created_at, updated_at)
             VALUES (%d, %d, %d, %d, %d, %s, NULL, 0, \'retry\', NULL, NULL, %s, NULL, %s, %s)
             ON DUPLICATE KEY UPDATE generation = VALUES(generation), operation_id = NULL,
                attempt_count = 0, poll_outcome = \'retry\', status_payload = NULL, last_error = NULL,
                next_poll_at = VALUES(next_poll_at), checked_at = NULL,
                created_at = VALUES(created_at), updated_at = VALUES(updated_at)',
            $table,
            $order->shopId,
            $order->orderId,
            (int) $invoice->useSandbox,
            $invoice->companyId,
            $invoice->invoiceId->value,
            $this->quote($job->generation),
            $next,
            $date,
            $date,
        ));

        return $job;
    }

    public function recordSubmission(
        PeppolStatusJob $job,
        PeppolSendReceipt|PeppolInvoiceStatus $result,
        DateTimeImmutable $now,
    ): void {
        $this->execute(sprintf(
            'UPDATE %s SET operation_id = %s, status_payload = %s, updated_at = %s
             WHERE %s AND attempt_count = 0',
            $this->db->table(PeppolStatusTable::NAME),
            $this->quote($result->operationId?->value),
            $this->quote($this->payload($result)),
            $this->date($now),
            $this->where($job),
        ));
    }

    /** @return list<PeppolStatusJob> */
    public function findDue(DateTimeImmutable $now, int $limit): array
    {
        if (1 > $limit || 100 < $limit) {
            throw new InvalidArgumentException(
                'Polling batch size must be between 1 and 100.',
            );
        }

        $rows = $this->db->fetchAll(sprintf(
            'SELECT id_shop, id_order, use_sandbox, sf_company_id, sf_invoice_id,
                    generation, operation_id, attempt_count
             FROM %s
             WHERE next_poll_at IS NOT NULL AND next_poll_at <= %s AND attempt_count < %d
             ORDER BY next_poll_at, id_shop, id_order, sf_invoice_id LIMIT %d',
            $this->db->table(PeppolStatusTable::NAME),
            $this->date($now),
            PollPeppolInvoiceStatusHandler::MAX_ATTEMPTS,
            $limit,
        ));

        if (false === $rows) {
            throw new RuntimeException('Unable to load due Peppol status checks.');
        }

        $jobs = [];

        foreach ($rows as $row) {
            $jobs[] = new PeppolStatusJob(
                new OrderReference(
                    (int) $row['id_shop'],
                    (int) $row['id_order'],
                ),
                new CreatedInvoice(
                    new InvoiceId((int) $row['sf_invoice_id']),
                    (int) $row['sf_company_id'],
                    1 === (int) $row['use_sandbox'],
                ),
                (string) $row['generation'],
                null === $row['operation_id']
                    ? null
                    : new PeppolOperationId((string) $row['operation_id']),
                (int) $row['attempt_count'],
            );
        }

        return $jobs;
    }

    public function recordPoll(
        PeppolStatusJob $job,
        PeppolStatusPollResult $result,
        DateTimeImmutable $now,
        ?string $error = null,
    ): void {
        $assignments = sprintf(
            'attempt_count = %d, poll_outcome = %s, next_poll_at = %s,
             last_error = %s, checked_at = %s, updated_at = %s',
            $job->attemptCount + 1,
            $this->quote(strtolower($result->outcome->name)),
            null === $result->nextPollAt
                ? 'NULL'
                : $this->date($result->nextPollAt),
            $this->quote($error ?? (
            null === $result->status ? 'status_unavailable' : null
            )),
            $this->date($now),
            $this->date($now),
        );

        if (null !== $result->status) {
            $assignments .= ', operation_id = '
                            . $this->quote($result->status->operationId?->value)
                            . ', status_payload = '
                            . $this->quote($this->payload($result->status));
        }

        $this->execute(sprintf(
            'UPDATE %s SET %s WHERE %s AND attempt_count = %d',
            $this->db->table(PeppolStatusTable::NAME),
            $assignments,
            $this->where($job),
            $job->attemptCount,
        ));
    }

    public function acquireWorkerLock(): bool
    {
        $rows = $this->db->fetchAll(
            'SELECT GET_LOCK(' . $this->quote($this->lockName()) . ', 0) AS acquired',
        );
        $acquired = false === $rows ? null : ($rows[0]['acquired'] ?? null);

        return match ($acquired) {
            1, '1' => true,
            0, '0' => false,
            default => throw new RuntimeException(
                'Unable to acquire the Peppol polling lock.',
            ),
        };
    }

    public function releaseWorkerLock(): void
    {
        $rows = $this->db->fetchAll(
            'SELECT RELEASE_LOCK(' . $this->quote($this->lockName()) . ') AS released',
        );

        if (false === $rows || !in_array($rows[0]['released'] ?? null, [1, '1'], true)) {
            throw new RuntimeException('Unable to release the Peppol polling lock.');
        }
    }

    private function where(PeppolStatusJob $job): string
    {
        return sprintf(
            'id_shop = %d AND id_order = %d AND use_sandbox = %d
             AND sf_company_id = %d AND sf_invoice_id = %d AND generation = %s',
            $job->order->shopId,
            $job->order->orderId,
            (int) $job->invoice->useSandbox,
            $job->invoice->companyId,
            $job->invoice->invoiceId->value,
            $this->quote($job->generation),
        );
    }

    private function payload(PeppolSendReceipt|PeppolInvoiceStatus $result): string
    {
        $status = $result instanceof PeppolInvoiceStatus ? $result : null;

        return json_encode([
            'invoice_id' => $result->invoiceId->value,
            'operation_id' => $result->operationId?->value,
            'status' => $status?->status->value ?? 'queued',
            'outbound_document_id' => $status?->outboundDocumentId,
            'provider_document_id' => $status?->providerDocumentId,
            'provider_status' => $status?->providerStatus,
            'failure_code' => $status?->failureCode,
            'created_at' => $status?->createdAt?->format('Y-m-d\TH:i:s.uP'),
            'completed_at' => $status?->completedAt?->format('Y-m-d\TH:i:s.uP'),
        ], JSON_THROW_ON_ERROR);
    }

    private function lockName(): string
    {
        return 'sf.peppol.' . substr(hash(
                'sha256',
                _DB_NAME_ . ':' . $this->db->table(PeppolStatusTable::NAME),
            ), 0, 48);
    }

    private function date(DateTimeImmutable $date): string
    {
        return $this->quote(
            $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        );
    }

    private function quote(?string $value): string
    {
        return null === $value ? 'NULL' : "'" . \pSQL($value, true) . "'";
    }

    private function execute(string $sql): void
    {
        if (!$this->db->execute($sql)) {
            throw new RuntimeException('Unable to persist Peppol status tracking.');
        }
    }
}