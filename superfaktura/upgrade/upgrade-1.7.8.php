<?php

declare(strict_types=1);

function upgrade_module_1_7_8($module): bool
{
    return $module->installPeppolAdminUi();
}