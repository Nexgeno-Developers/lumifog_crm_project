<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 1.0.2: link expenses to purchase orders (expenses.pur_order_id), PO closure table
 * and the "PO required" expense categories option. install.php is idempotent.
 */
class Migration_Version_102 extends App_module_migration
{
    public function up()
    {
        require_once __DIR__ . '/../install.php';
    }
}
