<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Exception;

use Generator;
use RuntimeException;
use SuperFaktura\PrestaShop\Domain\Enum\PeppolSendStatus;
use SuperFaktura\PrestaShop\Domain\ValueObject\PeppolOperationId;

final class PeppolApiRejectedRequest extends RuntimeException
{
    /** @var list<string> */
    private readonly array $issues;

    public function __construct(
        public readonly int $httpStatus,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
        public readonly ?string $reason,
        public readonly ?PeppolOperationId $operationId,
        public readonly ?PeppolSendStatus $operationStatus,
        string ...$issues,
    ) {
        parent::__construct(
            sprintf('SuperFaktura Peppol API returned HTTP %d.', $httpStatus),
        );

        $this->issues = array_values($issues);
    }

    /** @return Generator<int, string> */
    public function issues(): Generator
    {
        yield from $this->issues;
    }
}