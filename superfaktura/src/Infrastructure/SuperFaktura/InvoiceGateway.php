<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\SuperFaktura;

use JsonException;
use stdClass;
use SuperFaktura\PrestaShop\Application\Dto\CreatedInvoiceResult;
use SuperFaktura\PrestaShop\Infrastructure\Api\Service\JsonParser;

final readonly class InvoiceGateway
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

        $body = $this->request->post('/invoices/send', $payloadJson);

        if (false === $body) {
            return false;
        }

        $response = $this->parser->parse($body);

        return in_array($response?->error ?? null, [0, '0'], true);
    }
}