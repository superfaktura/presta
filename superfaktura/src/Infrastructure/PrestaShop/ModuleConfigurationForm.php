<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use InvalidArgumentException;
use Tools;

final class ModuleConfigurationForm
{
    /**
     * @param array<string, string|int|bool> $currentValues
     *
     * @return array<string, string|int>
     */
    public function read(array $currentValues): array
    {
        $values = [];

        foreach (ModuleConfigurationFields::definitions() as $field => $definition) {
            $default = $definition['checkbox']
                ? 0
                : ($currentValues[$field] ?? $definition['default']);

            $value = Tools::getValue($field, $default);

            if (!is_string($value) && !is_int($value) && !is_bool($value)) {
                throw new InvalidArgumentException('Invalid configuration form field: ' . $field);
            }

            if ($definition['checkbox']) {
                if (!in_array($value, [0, 1, '0', '1', false, true], true)) {
                    throw new InvalidArgumentException('Invalid configuration checkbox: ' . $field);
                }

                $values[$field] = in_array($value, [1, '1', true], true) ? 1 : 0;
            } else {
                $values[$field] = (string) $value;
            }
        }

        return $values;
    }
}