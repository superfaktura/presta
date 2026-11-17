<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\SuperFaktura;

use SuperFaktura\PrestaShop\Infrastructure\Api\HttpResponse;

interface ClientInterface
{
    public function get(string $path): HttpResponse;

    public function postJson(string $path, string $body): HttpResponse;
}