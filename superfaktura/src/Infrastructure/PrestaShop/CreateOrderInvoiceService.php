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
            try {
                $gateway = $this->peppolGateways->forInvoice(
                    $reference,
                    $created->invoice,
                );

                $result = (new SubmitPeppolInvoiceHandler($gateway))(
                    $created->invoice->invoiceId,
                );

                $this->logger->reportPeppolSubmission(
                    $reference,
                    $created->invoice,
                    $result,
                );
            } catch (Throwable $exception) {
                $this->logger->report(
                    $reference,
                    'peppol_submission_unconfirmed',
                    $created->invoice,
                    $exception,
                );
            }
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
}