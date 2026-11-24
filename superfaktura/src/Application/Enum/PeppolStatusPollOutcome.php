<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Application\Enum;

enum PeppolStatusPollOutcome
{
    case FINISHED;
    case RETRY;
    case EXHAUSTED;
}