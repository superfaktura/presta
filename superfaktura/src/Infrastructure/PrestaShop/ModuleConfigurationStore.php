<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use Configuration;
use InvalidArgumentException;
use RuntimeException;
use Shop;
use UnexpectedValueException;

final class ModuleConfigurationStore
{
    /** @return array<string, string|int|bool> */
    public function load(): array
    {
        return $this->read();
    }

    /** @return array<string, string|int|bool> */
    public function loadForShop(int $shopId): array
    {
        if (0 >= $shopId) {
            throw new InvalidArgumentException('A positive shop ID is required.');
        }

        $shop = new Shop($shopId);
        $groupId = (int) ($shop->id_shop_group ?? 0);

        if ($shopId !== (int) ($shop->id ?? 0) || 0 >= $groupId) {
            throw new RuntimeException('The shop configuration is unavailable.');
        }

        return $this->read($groupId, $shopId);
    }

    /** @param array<string, string|int|bool> $values */
    public function save(array $values): void
    {
        $definitions = ModuleConfigurationFields::definitions();

        foreach ($definitions as $field => $definition) {
            if (
                !array_key_exists($field, $values)
                || (!is_string($values[$field]) && !is_int($values[$field]) && !is_bool($values[$field]))
            ) {
                throw new InvalidArgumentException('Invalid module configuration field: ' . $field);
            }
        }

        $groupId = (int) Shop::getContextShopGroupID(true);
        $shopId = (int) Shop::getContextShopID(true);

        foreach ($definitions as $field => $definition) {
            if (!Configuration::updateValue($definition['key'], $values[$field], false, $groupId, $shopId)) {
                throw new RuntimeException('Unable to save module configuration: ' . $definition['key']);
            }
        }
    }

    public function deleteAll(): bool
    {
        $success = true;

        foreach (ModuleConfigurationFields::keys() as $key) {
            if (!Configuration::deleteByName($key)) {
                $success = false;
            }
        }

        return $success;
    }

    /** @return array<string, string|int|bool> */
    private function read(?int $groupId = null, ?int $shopId = null): array
    {
        $stored = Configuration::getMultiple(
            ModuleConfigurationFields::keys(),
            null,
            $groupId,
            $shopId,
        );

        if (!is_array($stored)) {
            throw new RuntimeException('Unable to load module configuration.');
        }

        $values = [];

        foreach (ModuleConfigurationFields::definitions() as $field => $definition) {
            $value = $stored[$definition['key']] ?? null;

            if (null === $value || false === $value) {
                $value = $definition['default'];
            }

            if (!is_string($value) && !is_int($value) && !is_bool($value)) {
                throw new UnexpectedValueException(
                    'Invalid module configuration value: ' . $definition['key'],
                );
            }

            $values[$field] = $value;
        }

        return $values;
    }
}