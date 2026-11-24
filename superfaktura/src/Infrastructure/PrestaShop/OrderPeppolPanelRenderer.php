<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use DateTimeZone;
use SuperFaktura\PrestaShop\Application\Dto\OrderInvoicePeppolState;
use SuperFaktura\PrestaShop\Application\Enum\ManualPeppolStatusRefreshOutcome;
use SuperFaktura\PrestaShop\Domain\ValueObject\OrderReference;

final class OrderPeppolPanelRenderer
{
    /**
     * @param list<OrderInvoicePeppolState> $states
     * @param array<string, string> $labels
     */
    public function render(
        OrderReference $order,
        array $states,
        string $action,
        string $token,
        bool $canRefresh,
        array $labels,
        ?ManualPeppolStatusRefreshOutcome $notice,
        DateTimeZone $timezone,
    ): string {
        ob_start();

        try {
            require __DIR__ . '/../../../views/templates/admin/order-peppol.php';

            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}