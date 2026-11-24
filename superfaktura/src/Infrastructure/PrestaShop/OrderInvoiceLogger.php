<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;
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
        try {
            if (
                is_callable([\PrestaShopLogger::class, 'addLog'])
                && true === \PrestaShopLogger::addLog(
                    $message,
                    3,
                    null,
                    'Order',
                    $order->orderId,
                    true,
                )
            ) {
                return;
            }
        } catch (Throwable) {
            // PrestaShop logger failed we use native PHP error log
        }

        error_log($message);
    }
}