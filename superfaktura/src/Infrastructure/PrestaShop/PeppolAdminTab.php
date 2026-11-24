<?php

declare(strict_types=1);

namespace SuperFaktura\PrestaShop\Infrastructure\PrestaShop;

use Language;
use Tab;

final class PeppolAdminTab
{
    public function install(string $moduleName): bool
    {
        $id = (int) Tab::getIdFromClassName(AdminPeppolAccess::CONTROLLER);
        $tab = new Tab(0 < $id ? $id : null);

        if (0 < $id && $moduleName !== $tab->module) {
            return false;
        }

        $tab->class_name = AdminPeppolAccess::CONTROLLER;
        $tab->module = $moduleName;
        $tab->id_parent = -1;
        $tab->active = 1;
        $tab->name = [];

        foreach (Language::getLanguages(false) as $language) {
            $tab->name[(int) $language['id_lang']] = 'SuperFaktúra Peppol';
        }

        return (bool) $tab->save();
    }

    public function uninstall(string $moduleName): bool
    {
        $id = (int) Tab::getIdFromClassName(AdminPeppolAccess::CONTROLLER);

        if (0 >= $id) {
            return true;
        }

        $tab = new Tab($id);

        return $moduleName === $tab->module && (bool) $tab->delete();
    }
}