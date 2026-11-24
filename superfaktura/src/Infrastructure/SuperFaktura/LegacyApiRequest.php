<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\SuperFaktura;

use JsonException;
use RuntimeException;
use SuperFaktura\PrestaShop\Infrastructure\Api\Service\JsonParser;

final readonly class LegacyApiRequest
{
    public function __construct(
        private ClientInterface $client,
        private JsonParser $parser,
        private string $applicationTitle,
        private string $moduleIdentification,
    ) {
    }

    public function post(string $path, ?string $payloadJson = null): string|false
    {
        $fields = [];

        if (null !== $payloadJson) {
            $payload = $this->parser->parse($payloadJson);

            if (null === $payload) {
                return false;
            }

            $payload->apptitle = $this->applicationTitle;
            $payload->module = $this->moduleIdentification;

            try {
                $fields['data'] = json_encode($payload, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return false;
            }
        }

        try {
            $response = $this->client->postForm($path, $fields);
        } catch (RuntimeException) {
            return false;
        }

        return 200 === $response->statusCode ? $response->body : false;
    }
}