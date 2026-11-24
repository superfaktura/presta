<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
use SuperFaktura\PrestaShop\Application\Dto\PeppolInvoiceStatus;
use SuperFaktura\PrestaShop\Application\Dto\PeppolSendReceipt;
use SuperFaktura\PrestaShop\Domain\Enum\PeppolSendStatus;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;
use Throwable;

final class OrderInvoiceLogger
{
    public function report(
        OrderReference $order,
        string $operation,
        ?CreatedInvoice $invoice = null,
        ?Throwable $exception = null,
    ): void {
        $message = sprintf(
            'SuperFaktura: %s; shop_id=%d; order_id=%d; sf_invoice_id=%s; sf_company_id=%s; sandbox=%s; exception=%s.',
            $operation,
            $order->shopId,
            $order->orderId,
            null !== $invoice ? (string) $invoice->invoiceId->value : 'unknown',
            null !== $invoice ? (string) $invoice->companyId : 'unknown',
            null !== $invoice ? (string) (int) $invoice->useSandbox : 'unknown',
            null !== $exception ? $exception::class : 'none',
        );

        $this->write($message, 3, $order->orderId);
    }

    public function reportPeppolSubmission(
        OrderReference $order,
        CreatedInvoice $invoice,
        PeppolSendReceipt|PeppolInvoiceStatus $result,
    ): void {
        $status = $result instanceof PeppolSendReceipt
            ? PeppolSendStatus::QUEUED
            : $result->status;

        $message = sprintf(
            'SuperFaktura: peppol_submission_observed; shop_id=%d; order_id=%d; sf_invoice_id=%d; sf_company_id=%d; sandbox=%d; peppol_operation_id=%s; peppol_status=%s.',
            $order->shopId,
            $order->orderId,
            $invoice->invoiceId->value,
            $invoice->companyId,
            (int) $invoice->useSandbox,
            $result->operationId?->value ?? 'unknown',
            $status->value,
        );

        $this->write(
            $message,
            PeppolSendStatus::FAILED === $status ? 3 : 1,
            $order->orderId,
        );
    }

    private function write(string $message, int $severity, int $orderId): void
    {
        try {
            if (
                is_callable([\PrestaShopLogger::class, 'addLog'])
                && true === \PrestaShopLogger::addLog(
                    $message,
                    $severity,
                    null,
                    'Order',
                    $orderId,
                    true,
                )
            ) {
                return;
            }
        } catch (Throwable) {
            // Logging must not abort processing of an already created invoice.
        }

        error_log($message);
    }
}