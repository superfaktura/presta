<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Domain\Enum;

enum PeppolSendStatus: string
{
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case FAILED = 'failed';
}