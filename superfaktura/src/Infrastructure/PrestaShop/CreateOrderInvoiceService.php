<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use Cart;
use Order;
use SuperFaktura\PrestaShop\Application\Contract\OrderInvoiceRepositoryInterface;
use SuperFaktura\PrestaShop\Application\Contract\PeppolInvoiceGatewayResolverInterface;
use SuperFaktura\PrestaShop\Application\Dto\InvoiceCreationOptions;
use SuperFaktura\PrestaShop\Application\Handler\SubmitPeppolInvoiceHandler;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;
use SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\InvoiceGateway;
use Throwable;
use DateTimeImmutable;
use DateTimeZone;
use SuperFaktura\PrestaShop\Application\Contract\PeppolStatusTrackingRepositoryInterface;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoice;

final readonly class CreateOrderInvoiceService
{
    public function __construct(
        private InvoicePayloadBuilder $payloads,
        private InvoiceGateway $gateway,
        private OrderInvoiceRepositoryInterface $invoices,
        private OrderInvoiceLogger $logger,
        private InvoiceCreationOptions $options,
        private bool $sendEmail,
        private PeppolInvoiceGatewayResolverInterface $peppolGateways,
        private bool $sendViaPeppol,
        private PeppolStatusTrackingRepositoryInterface $peppolStatus,
    ) {
    }

    public function create(Order $order, Cart $cart): void
    {
        $reference = new OrderReference(
            (int) $order->id_shop,
            (int) $order->id,
        );

        try {
            $payloadJson = json_encode(
                $this->payloads->build($order, $cart, $this->options),
                JSON_THROW_ON_ERROR,
            );
        } catch (Throwable $exception) {
            $this->logger->report(
                $reference,
                'prepare_invoice_failed',
                exception: $exception,
            );

            return;
        }

        try {
            $created = $this->gateway->create($payloadJson);
        } catch (Throwable $exception) {
            $this->logger->report(
                $reference,
                'invoice_creation_unconfirmed',
                exception: $exception,
            );

            return;
        }

        if (null === $created) {
            $this->logger->report($reference, 'invoice_creation_unconfirmed');

            return;
        }

        $referenceSaved = false;

        try {
            $this->invoices->save($reference, $created->invoice);
            $referenceSaved = true;
        } catch (Throwable $exception) {
            $this->logger->report(
                $reference,
                'save_invoice_reference_failed',
                $created->invoice,
                $exception,
            );
        }

        if (
            $referenceSaved
            && $this->sendViaPeppol
            && 'regular' === $this->options->invoiceType
        ) {
            $this->submitViaPeppol($reference, $created->invoice);
        }

        if (!$this->sendEmail) {
            return;
        }

        try {
            $sent = $this->gateway->sendEmail($created);
        } catch (Throwable $exception) {
            $this->logger->report(
                $reference,
                'invoice_email_unconfirmed',
                $created->invoice,
                $exception,
            );

            return;
        }

        if (!$sent) {
            $this->logger->report(
                $reference,
                'invoice_email_unconfirmed',
                $created->invoice,
            );
        }
    }

    private function submitViaPeppol(
        OrderReference $order,
        CreatedInvoice $invoice,
    ): void {
        try {
            $job = $this->peppolStatus->begin(
                $order,
                $invoice,
                new DateTimeImmutable('now', new DateTimeZone('UTC')),
            );

            $gateway = $this->peppolGateways->forInvoice($order, $invoice);
            $result = (new SubmitPeppolInvoiceHandler($gateway))($invoice->invoiceId);
        } catch (Throwable $exception) {
            $this->logger->report(
                $order,
                'peppol_submission_unconfirmed',
                $invoice,
                $exception,
            );

            return;
        }

        try {
            $this->peppolStatus->recordSubmission(
                $job,
                $result,
                new DateTimeImmutable('now', new DateTimeZone('UTC')),
            );
        } catch (Throwable $exception) {
            $this->logger->report(
                $order,
                'save_peppol_submission_failed',
                $invoice,
                $exception,
            );
        }

        $this->logger->reportPeppolSubmission($order, $invoice, $result);
    }
}