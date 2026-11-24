<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use Closure;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use SuperFaktura\PrestaShop\Application\Dto\PeppolStatusPollResult;
use SuperFaktura\PrestaShop\Application\Enum\PeppolStatusPollOutcome;
use SuperFaktura\PrestaShop\Application\Handler\PollPeppolInvoiceStatusHandler;
use Throwable;

final readonly class PeppolStatusPollingWorker
{
    /** @var Closure(): DateTimeImmutable */
    private Closure $clock;

    /** @param (Closure(): DateTimeImmutable)|null $clock */
    public function __construct(
        private DbPeppolStatusRepository $jobs,
        private PollPeppolInvoiceStatusHandler $poll,
        private OrderInvoiceLogger $logger,
        ?Closure $clock = null,
    ) {
        $this->clock = $clock ?? static fn (): DateTimeImmutable =>
        new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function run(int $limit = 25): int
    {
        if (!$this->jobs->acquireWorkerLock()) {
            return 0;
        }

        try {
            $processed = 0;

            foreach ($this->jobs->findDue(($this->clock)(), $limit) as $job) {
                $now = ($this->clock)();
                $attempt = $job->attemptCount + 1;
                $error = null;

                try {
                    $result = ($this->poll)(
                        $job->order,
                        $job->invoice,
                        $job->operationId,
                        $attempt,
                        $now,
                    );
                } catch (Throwable $exception) {
                    $this->logger->report(
                        $job->order,
                        'peppol_status_check_failed',
                        $job->invoice,
                        $exception,
                    );

                    $error = substr($exception::class, 0, 128);
                    $exhausted = PollPeppolInvoiceStatusHandler::MAX_ATTEMPTS <= $attempt;

                    $result = new PeppolStatusPollResult(
                        $exhausted
                            ? PeppolStatusPollOutcome::EXHAUSTED
                            : PeppolStatusPollOutcome::RETRY,
                        null,
                        $exhausted
                            ? null
                            : $now->add(new DateInterval(
                            'PT' . PollPeppolInvoiceStatusHandler::INTERVAL_SECONDS . 'S',
                        )),
                    );
                }

                try {
                    $this->jobs->recordPoll($job, $result, $now, $error);
                } catch (Throwable $exception) {
                    $this->logger->report(
                        $job->order,
                        'save_peppol_status_failed',
                        $job->invoice,
                        $exception,
                    );

                    continue;
                }

                $processed++;

                if (null !== $result->status) {
                    $this->logger->reportPeppolSubmission(
                        $job->order,
                        $job->invoice,
                        $result->status,
                    );
                }

                if (PeppolStatusPollOutcome::EXHAUSTED === $result->outcome) {
                    $this->logger->report(
                        $job->order,
                        'peppol_status_polling_exhausted',
                        $job->invoice,
                    );
                }
            }

            return $processed;
        } finally {
            $this->jobs->releaseWorkerLock();
        }
    }
}