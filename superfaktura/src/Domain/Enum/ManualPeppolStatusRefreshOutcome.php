<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Enum;

enum ManualPeppolStatusRefreshOutcome: string
{
    case UPDATED = 'updated';
    case NOT_FOUND = 'not_found';
    case BUSY = 'busy';
    case FAILED = 'failed';
}