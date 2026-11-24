<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Handler;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Application\Dto\PeppolInvoiceStatus;
use SuperFaktura\PrestaShop\Application\Dto\PeppolStatusPollResult;
use SuperFaktura\PrestaShop\Application\Enum\PeppolStatusPollOutcome;
use SuperFaktura\PrestaShop\Application\Exception\PeppolApiRejectedRequest;
use SuperFaktura\PrestaShop\Application\Exception\SuperFakturaTransportException;
use SuperFaktura\PrestaShop\Domain\Enum\PeppolSendStatus;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;
use SuperFaktura\PrestaShop\Domain\ValueObject\PeppolOperationId;

final readonly class PollPeppolInvoiceStatusHandler
{
    public const MAX_ATTEMPTS = 10;
    public const INTERVAL_SECONDS = 120;
    public function __construct(
        private GetOrderInvoicePeppolStatusHandler $getStatus,
        private int $maxAttempts = self::MAX_ATTEMPTS,
        private int $intervalSeconds = self::INTERVAL_SECONDS,
    ) {
        if (1 > $maxAttempts || 1 > $intervalSeconds) {
            throw new InvalidArgumentException('Polling attempts and interval must be positive.');
        }
    }

    public function __invoke(
        OrderReference $order,
        CreatedInvoice $invoice,
        ?PeppolOperationId $operationId,
        int $attempt,
        DateTimeImmutable $checkedAt,
    ): PeppolStatusPollResult {
        if (1 > $attempt || $this->maxAttempts < $attempt) {
            throw new InvalidArgumentException('Polling attempt is out of range.');
        }

        $status = $this->readStatus($order, $invoice);
        $observedOperationId = $status?->operationId;

        if (
            null === $observedOperationId
            || (
                null !== $operationId
                && 0 !== strcasecmp($observedOperationId->value, $operationId->value)
            )
        ) {
            $status = null;
        }

        if (null !== $status && $status->isFinal()) {
            return new PeppolStatusPollResult(
                PeppolStatusPollOutcome::FINISHED,
                $status,
            );
        }

        if ($this->maxAttempts === $attempt) {
            return new PeppolStatusPollResult(PeppolStatusPollOutcome::EXHAUSTED, $status);
        }

        return new PeppolStatusPollResult(
            PeppolStatusPollOutcome::RETRY,
            $status,
            $checkedAt->add(new DateInterval('PT' . $this->intervalSeconds . 'S')),
        );
    }

    private function readStatus(OrderReference $order, CreatedInvoice $invoice): ?PeppolInvoiceStatus
    {
        try {
            return ($this->getStatus)($order, $invoice);
        } catch (SuperFakturaTransportException) {
            return null;
        } catch (PeppolApiRejectedRequest $exception) {
            if (500 > $exception->httpStatus || 599 < $exception->httpStatus) {
                throw $exception;
            }

            return null;
        }
    }

    private function isFinal(PeppolInvoiceStatus $status): bool
    {
        return PeppolSendStatus::FAILED === $status->status
               || in_array($status->providerStatus, ['accepted', 'rejected', 'failed_delivery'], true);
    }
}