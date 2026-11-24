<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\Service;

use SuperFaktura\PrestaShop\Application\Contract\PeppolInvoiceGatewayInterface;
use SuperFaktura\PrestaShop\Application\Dto\PeppolInvoiceStatus;
use SuperFaktura\PrestaShop\Application\Dto\PeppolSendReceipt;
use SuperFaktura\PrestaShop\Domain\ValueObject\InvoiceId;
use SuperFaktura\PrestaShop\Infrastructure\Api\Service\JsonParser;
use SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\ClientInterface;

final readonly class PeppolInvoiceGateway implements PeppolInvoiceGatewayInterface
{
    public function __construct(
        private ClientInterface $client,
        private JsonParser $parser,
        private PeppolPayloadMapper $mapper,
    ) {
    }

    public function submit(InvoiceId $invoiceId): PeppolSendReceipt
    {
        $response = $this->client->postJson('/invoices/sendPeppol', $this->mapper->submitRequest($invoiceId));

        return $this->mapper->submitResponse(
            $invoiceId,
            $response->statusCode,
            $this->parser->parse($response->body),
        );
    }

    public function status(InvoiceId $invoiceId): PeppolInvoiceStatus
    {
        $response = $this->client->get('/invoices/getPeppolStatus/' . $invoiceId->value);

        return $this->mapper->statusResponse(
            $invoiceId,
            $response->statusCode,
            $this->parser->parse($response->body),
        );
    }
}