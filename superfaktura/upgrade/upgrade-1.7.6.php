<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_7_6($module): bool
{
    return $module->installOrderInvoiceStorage();
}