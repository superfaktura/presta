<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use DateTimeImmutable;
use DateTimeZone;
use OutOfBoundsException;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Application\Enum\ManualPeppolStatusRefreshOutcome;
use SuperFaktura\PrestaShop\Application\Handler\GetOrderInvoicePeppolStatusHandler;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;
use Throwable;

final readonly class ManualPeppolStatusRefreshService
{
    public function __construct(
        private DbPeppolStatusRepository $states,
        private GetOrderInvoicePeppolStatusHandler $getStatus,
        private OrderInvoiceLogger $logger,
    ) {
    }

    public function refresh(
        OrderReference $order,
        CreatedInvoice $invoice,
    ): ManualPeppolStatusRefreshOutcome {
        if (!$this->states->acquireWorkerLock()) {
            return ManualPeppolStatusRefreshOutcome::BUSY;
        }

        try {
            $state = null;

            foreach ($this->states->findByOrder($order) as $candidate) {
                if (
                    $candidate->invoice->invoiceId->value === $invoice->invoiceId->value
                    && $candidate->invoice->companyId === $invoice->companyId
                    && $candidate->invoice->useSandbox === $invoice->useSandbox
                ) {
                    $state = $candidate;
                    break;
                }
            }

            if (null === $state) {
                throw new OutOfBoundsException(
                    'No matching invoice is linked to this order.',
                );
            }

            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

            if (
                null !== $state->job
                && null === $state->job->operationId
                && 0 === $state->job->attemptCount
                && null !== $state->nextPollAt
                && $now < $state->nextPollAt
            ) {
                return ManualPeppolStatusRefreshOutcome::BUSY;
            }

            $job = $this->states->ensureManualJob($order, $invoice, $now);

            try {
                $status = ($this->getStatus)($order, $invoice);
            } catch (Throwable $exception) {
                $this->states->recordManual(
                    $job,
                    null,
                    $now,
                    substr($exception::class, 0, 128),
                );
                $this->logger->report(
                    $order,
                    'manual_peppol_status_failed',
                    $invoice,
                    $exception,
                );

                return ManualPeppolStatusRefreshOutcome::FAILED;
            }

            $this->states->recordManual($job, $status, $now);

            if (null === $status) {
                return ManualPeppolStatusRefreshOutcome::NOT_FOUND;
            }

            $this->logger->reportPeppolSubmission($order, $invoice, $status);

            return ManualPeppolStatusRefreshOutcome::UPDATED;
        } finally {
            $this->states->releaseWorkerLock();
        }
    }
}