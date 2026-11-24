<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use Closure;
use InvalidArgumentException;
use LogicException;
use Shop;

final class ShopContextRunner
{
    /** @param Closure(): void $operation */
    public function runForShop(int $shopId, Closure $operation): void
    {
        if (0 >= $shopId) {
            throw new InvalidArgumentException('A positive shop ID is required.');
        }

        if (!Shop::isFeatureActive()) {
            $operation();

            return;
        }

        $previousContext = Shop::getContext();
        $previousId = match ($previousContext) {
            Shop::CONTEXT_SHOP => Shop::getContextShopID(),
            Shop::CONTEXT_GROUP => Shop::getContextShopGroupID(),
            Shop::CONTEXT_ALL => null,
            default => throw new LogicException('The current shop context is unavailable.'),
        };

        try {
            Shop::setContext(Shop::CONTEXT_SHOP, $shopId);
            $operation();
        } finally {
            Shop::setContext($previousContext, $previousId);
        }
    }
}