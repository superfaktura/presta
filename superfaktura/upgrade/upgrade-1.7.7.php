<?php

declare(strict_types=1);

function upgrade_module_1_7_7($module): bool
{
    return $module->installPeppolStatusStorage();
}