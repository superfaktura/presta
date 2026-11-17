<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\Api\Service;

use RuntimeException;
use SensitiveParameter;
use SuperFaktura\PrestaShop\Application\Exception\SuperFakturaTransportException;
use SuperFaktura\PrestaShop\Infrastructure\Api\HttpResponse;
use SuperFaktura\PrestaShop\Infrastructure\SuperFaktura\ClientInterface;

final readonly class CurlClient implements ClientInterface
{
    private const PRODUCTION_BASE_URL = 'https://moja.superfaktura.sk';
    private const SANDBOX_BASE_URL = 'https://sandbox.superfaktura.sk';

    private string $baseUrl;

    public function __construct(
        bool $useSandbox,
        #[SensitiveParameter]
        private string $authorizationHeader,
    ) {
        $this->baseUrl = $useSandbox
            ? self::SANDBOX_BASE_URL
            : self::PRODUCTION_BASE_URL;
    }

    public function get(string $path): HttpResponse
    {
        return $this->request('GET', $path, null);
    }

    public function postJson(string $path, string $body): HttpResponse
    {
        return $this->request('POST', $path, $body);
    }

    private function request(
        string $method,
        string $path,
        ?string $body,
    ): HttpResponse {
        $headers = [
            'Authorization: ' . $this->authorizationHeader,
            'Accept: application/json',
        ];

        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ];

        if (null !== $body) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        $options[CURLOPT_HTTPHEADER] = $headers;
        $handle = curl_init($this->baseUrl . $path);

        if (false === $handle || false === curl_setopt_array($handle, $options)) {
            throw new RuntimeException('Could not configure the SuperFaktura request.');
        }

        $responseBody = curl_exec($handle);

        if (!is_string($responseBody)) {
            throw new SuperFakturaTransportException('SuperFaktura request failed.', curl_errno($handle));
        }

        return new HttpResponse(
            statusCode: (int) curl_getinfo($handle, CURLINFO_HTTP_CODE),
            body: $responseBody,
        );
    }
}