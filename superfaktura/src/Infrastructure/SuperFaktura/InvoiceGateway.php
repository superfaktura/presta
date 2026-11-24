<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\SuperFaktura;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use stdClass;
use SuperFaktura\PrestaShop\Application\Contract\InvoicePaymentGatewayInterface;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoiceResult;
use SuperFaktura\PrestaShop\Application\Dto\OrderPaymentDetails;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;
use SuperFaktura\PrestaShop\Infrastructure\Api\Service\JsonParser;

final readonly class InvoiceGateway implements InvoicePaymentGatewayInterface
{
    public function __construct(
        private LegacyApiRequest $request,
        private JsonParser $parser,
        private CreatedInvoiceResponseMapper $mapper,
        private bool $useSandbox,
    ) {
    }

    public function create(string $payloadJson): ?CreatedInvoiceResult
    {
        $body = $this->request->post('/invoices/create', $payloadJson);

        if (false === $body) {
            return null;
        }

        $response = $this->parser->parse($body);

        if (null === $response) {
            return null;
        }

        $invoice = $this->mapper->map($response, $this->useSandbox);

        if (null === $invoice) {
            return null;
        }

        $data = $response->data ?? null;
        $client = $data instanceof stdClass ? ($data->Client ?? null) : null;
        $email = $client instanceof stdClass ? ($client->email ?? null) : null;

        $recipientEmail = is_string($email) && '' !== trim($email)
            ? trim($email)
            : null;

        return new CreatedInvoiceResult($invoice, $recipientEmail);
    }

    public function sendEmail(CreatedInvoiceResult $created): bool
    {
        if (null === $created->recipientEmail || '' === trim($created->recipientEmail)) {
            return false;
        }

        try {
            $payloadJson = json_encode([
                'Email' => [
                    'invoice_id' => $created->invoice->invoiceId->value,
                    'to' => $created->recipientEmail,
                ],
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        return $this->successfulResponse(
            $this->request->post('/invoices/send', $payloadJson),
        );
    }

    public function recordPayment(
        OrderReference $order,
        OrderPaymentDetails $payment,
        DateTimeImmutable $paidAt,
    ): bool {
        try {
            $payloadJson = json_encode([
                'InvoicePayment' => [
                    'import_type' => 'prestashop',
                    'import_id' => $order->orderId,
                    'payment_type' => 'transfer',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'created' => $paidAt->format('Y-m-d'),
                ],
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        $path = '/invoice_payments/add/ajax:1/api:1/import_type:prestashop/import_id:'
                . $order->orderId;

        return $this->successfulResponse(
            $this->request->post($path, $payloadJson),
        );
    }

    public function createCreditNote(
        OrderReference $order,
        ?string $sequenceId = null,
    ): bool {
        $path = '/invoices/cancelFromRegular/0/import_type:prestashop/import_id:'
                . $order->orderId;

        if (null !== $sequenceId && '' !== $sequenceId && '0' !== $sequenceId) {
            if (1 !== preg_match('/\A[1-9][0-9]*\z/', $sequenceId)) {
                throw new InvalidArgumentException(
                    'A positive credit note sequence ID is required.',
                );
            }

            $path .= '/sequence_id:' . $sequenceId;
        }

        return $this->successfulResponse($this->request->post($path));
    }

    private function successfulResponse(string|false $body): bool
    {
        if (false === $body) {
            return false;
        }

        $response = $this->parser->parse($body);

        return in_array($response?->error ?? null, [0, '0'], true);
    }
}