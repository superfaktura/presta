<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

final class ModuleConfigurationFormRenderer
{
    /**
     * @param array<string, string|int|bool> $values
     * @param array<array{id_order_state: int|string, name: string}> $states
     * @param array<string, string> $labels
     */
    public function render(
        string $action,
        array $values,
        array $states,
        array $labels,
    ): string {
        ob_start();

        try {
            require __DIR__ . '/../../../views/templates/admin/configuration.php';

            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}