<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use stdClass;
use SuperFaktura\PrestaShop\Application\Dto\PeppolInvoiceStatus;
use SuperFaktura\PrestaShop\Application\Dto\PeppolSendReceipt;
use SuperFaktura\PrestaShop\Application\Exception\PeppolApiRejectedRequest;
use SuperFaktura\PrestaShop\Domain\Enum\PeppolSendStatus;
use SuperFaktura\PrestaShop\Domain\ValueObject\InvoiceId;
use SuperFaktura\PrestaShop\Domain\ValueObject\PeppolOperationId;
use UnexpectedValueException;

final class PeppolPayloadMapper
{
    public function submitRequest(InvoiceId $invoiceId): string
    {
        return json_encode(['invoice_id' => $invoiceId->value], JSON_THROW_ON_ERROR);
    }

    public function submitResponse(
        InvoiceId $invoiceId,
        int $httpStatus,
        ?stdClass $payload,
    ): PeppolSendReceipt {
        $data = $this->successfulData($invoiceId, 202, $httpStatus, $payload);
        $operationId = $this->operationId($this->nullableString($data, 'operation_id'));

        if (PeppolSendStatus::QUEUED->value !== ($data->status ?? null) || null === $operationId) {
            throw new UnexpectedValueException('Invalid SuperFaktura Peppol submission.');
        }

        return new PeppolSendReceipt($invoiceId, $operationId);
    }

    public function statusResponse(
        InvoiceId $invoiceId,
        int $httpStatus,
        ?stdClass $payload,
    ): PeppolInvoiceStatus {
        $data = $this->successfulData($invoiceId, 200, $httpStatus, $payload);
        $statusValue = $this->nullableString($data, 'status');
        $status = null === $statusValue ? null : PeppolSendStatus::tryFrom($statusValue);

        if (null === $status) {
            throw new UnexpectedValueException('Unknown SuperFaktura Peppol status.');
        }

        $outboundDocumentId = $data->outbound_document_id ?? null;

        if (
            !property_exists($data, 'outbound_document_id')
            || (null !== $outboundDocumentId && (!is_int($outboundDocumentId) || 0 >= $outboundDocumentId))
        ) {
            throw new UnexpectedValueException('Invalid SuperFaktura outbound document ID.');
        }

        return new PeppolInvoiceStatus(
            invoiceId: $invoiceId,
            operationId: $this->operationId($this->nullableString($data, 'operation_id')),
            status: $status,
            outboundDocumentId: $outboundDocumentId,
            providerDocumentId: $this->nullableString($data, 'provider_document_id'),
            providerStatus: $this->nullableString($data, 'provider_status'),
            failureCode: $this->nullableString($data, 'failure_code'),
            createdAt: $this->nullableDateTime($data, 'created_at'),
            completedAt: $this->nullableDateTime($data, 'completed_at'),
        );
    }

    private function successfulData(
        InvoiceId $invoiceId,
        int $expectedStatus,
        int $httpStatus,
        ?stdClass $payload,
    ): stdClass {
        if (400 <= $httpStatus) {
            throw $this->rejection($httpStatus, $payload);
        }

        $data = $payload?->data ?? null;

        if (
            $expectedStatus !== $httpStatus
            || 0 !== ($payload?->error ?? null)
            || !$data instanceof stdClass
            || $invoiceId->value !== ($data->invoice_id ?? null)
        ) {
            throw new UnexpectedValueException('Invalid SuperFaktura Peppol response.');
        }

        return $data;
    }

    private function rejection(int $httpStatus, ?stdClass $payload): PeppolApiRejectedRequest
    {
        $data = $payload?->data ?? null;
        $details = $data instanceof stdClass ? $data : null;
        $errorCode = $payload?->error_code ?? null;
        $errorMessage = $payload?->error_message ?? null;
        $reason = $details?->reason ?? null;
        $operationId = $details?->operation_id ?? null;
        $status = $details?->status ?? null;
        $issues = $details?->issues ?? [];
        $issueStrings = is_array($issues) ? array_values(array_filter($issues, 'is_string')) : [];

        try {
            $existingOperation = $this->operationId(is_string($operationId) ? $operationId : null);
        } catch (UnexpectedValueException) {
            $existingOperation = null;
        }

        return new PeppolApiRejectedRequest(
            $httpStatus,
            is_string($errorCode) ? $errorCode : null,
            is_string($errorMessage) ? $errorMessage : null,
            is_string($reason) ? $reason : null,
            $existingOperation,
            is_string($status) ? PeppolSendStatus::tryFrom($status) : null,
            ...$issueStrings,
        );
    }

    private function nullableString(stdClass $data, string $field): ?string
    {
        $value = $data->$field ?? null;

        if (!property_exists($data, $field) || (null !== $value && !is_string($value))) {
            throw new UnexpectedValueException(sprintf('Invalid SuperFaktura Peppol field "%s".', $field));
        }

        return $value;
    }

    private function operationId(?string $value): ?PeppolOperationId
    {
        if (null === $value) {
            return null;
        }

        try {
            return new PeppolOperationId($value);
        } catch (InvalidArgumentException $exception) {
            throw new UnexpectedValueException('Invalid SuperFaktura Peppol operation ID.', previous: $exception);
        }
    }

    private function nullableDateTime(stdClass $data, string $field): ?DateTimeImmutable
    {
        $value = $this->nullableString($data, $field);

        if (null === $value) {
            return null;
        }

        if (1 !== preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})\z/', $value)) {
            throw new UnexpectedValueException(sprintf('Invalid SuperFaktura Peppol date "%s".', $field));
        }

        $date = date_create_immutable($value);

        if (false === $date || false !== DateTimeImmutable::getLastErrors()) {
            throw new UnexpectedValueException(sprintf('Invalid SuperFaktura Peppol date "%s".', $field));
        }

        return $date;
    }
}