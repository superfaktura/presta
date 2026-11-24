<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use Access;
use Context;
use Employee;
use Order;
use Shop;

final readonly class AdminPeppolAccess
{
    public const CONTROLLER = 'AdminSuperFakturaPeppol';

    public function __construct(
        private int $moduleId,
        private PrestaShopDb $db,
    ) {
    }

    public function loadOrder(int $orderId, bool $refresh = false): ?Order
    {
        $employee = Context::getContext()->employee;

        if (
            0 >= $orderId
            || 0 >= $this->moduleId
            || !$employee instanceof Employee
            || 0 >= (int) $employee->id
            || 1 !== (int) $employee->active
            || !$employee->isLoggedBack()
            || !$this->allowed($employee, 'ROLE_MOD_TAB_ADMINORDERS_READ')
        ) {
            return null;
        }

        $order = new Order($orderId);
        $shopId = (int) $order->id_shop;

        if (
            $orderId !== (int) $order->id
            || 0 >= $shopId
            || !$employee->hasAuthOnShop($shopId)
            || !in_array(
                $shopId,
                array_map('intval', Shop::getContextListShopID()),
                true,
            )
        ) {
            return null;
        }

        if ($refresh && !$this->canRefresh()) {
            return null;
        }

        $enabled = $this->db->fetchAll(sprintf(
            'SELECT id_module FROM %s
             WHERE id_module = %d AND id_shop = %d LIMIT 1',
            $this->db->table('module_shop'),
            $this->moduleId,
            $shopId,
        ));

        return false !== $enabled && [] !== $enabled ? $order : null;
    }

    public function canRefresh(): bool
    {
        $employee = Context::getContext()->employee;
        $prefix = 'ROLE_MOD_TAB_' . strtoupper(self::CONTROLLER);

        return $employee instanceof Employee
               && $this->allowed($employee, $prefix . '_READ')
               && $this->allowed($employee, $prefix . '_UPDATE');
    }

    private function allowed(Employee $employee, string $role): bool
    {
        return $employee->isSuperAdmin()
               || Access::isGranted([$role], (int) $employee->id_profile);
    }
}