<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use Closure;
use LogicException;

final readonly class PrestaShopDb
{
    private string $prefix;
    private Closure $execute;
    private Closure $executeS;

    public function __construct()
    {
        if (!defined('_DB_PREFIX_')) {
            throw new LogicException('PrestaShop has not defined _DB_PREFIX_.');
        }

        $prefix = constant('_DB_PREFIX_');

        if (!is_string($prefix)) {
            throw new LogicException('PrestaShop database prefix must be a string.');
        }

        $dbClass = 'Db';
        $getInstance = [$dbClass, 'getInstance'];

        if (!class_exists($dbClass) || !is_callable($getInstance)) {
            throw new LogicException('PrestaShop Db::getInstance() is unavailable.');
        }

        $db = $getInstance();
        $execute = [$db, 'execute'];
        $executeS = [$db, 'executeS'];

        if (!$db instanceof $dbClass || !is_callable($execute) || !is_callable($executeS)) {
            throw new LogicException('PrestaShop did not provide a usable Db connection.');
        }

        $this->prefix = $prefix;
        $this->execute = Closure::fromCallable($execute);
        $this->executeS = Closure::fromCallable($executeS);
    }

    public function table(string $name): string
    {
        return '`' . str_replace('`', '``', $this->prefix . $name) . '`';
    }

    public function execute(string $sql): bool
    {
        return true === ($this->execute)($sql);
    }

    /** @return list<array<string, mixed>>|false */
    public function fetchAll(string $sql): array|false
    {
        $rows = ($this->executeS)($sql, true, false);

        return is_array($rows) ? $rows : false;
    }
}