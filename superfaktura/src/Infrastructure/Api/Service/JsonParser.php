<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\Api\Service;

use JsonException;
use stdClass;

final class JsonParser
{
    public function parse(string $body): ?stdClass
    {
        try {
            $payload = json_decode($body, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return $payload instanceof stdClass ? $payload : null;
    }
}