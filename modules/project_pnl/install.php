<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Link an expense to the purchase order it was spent against (staff buying PO items),
// so the P&L draws that money from the PO budget instead of counting it twice.
if (!$CI->db->field_exists('pur_order_id', db_prefix() . 'expenses')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'expenses` ADD `pur_order_id` INT(11) NULL DEFAULT NULL;');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'expenses` ADD INDEX `pur_order_id` (`pur_order_id`);');
}

// POs closed for costing: their unused balance no longer counts as committed cost.
if (!$CI->db->table_exists(db_prefix() . 'project_pnl_po_closures')) {
    $CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . 'project_pnl_po_closures` (
        `pur_order_id` INT(11) NOT NULL,
        `closed_by` INT(11) NOT NULL,
        `closed_at` DATETIME NOT NULL,
        PRIMARY KEY (`pur_order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

// Expense categories (ids) where choosing a PO is mandatory
if (get_option('project_pnl_po_required_categories') === '') {
    add_option('project_pnl_po_required_categories', '[]');
}
