<?php

defined('BASEPATH') or exit('No direct script access allowed');

$tables = db_prefix() . 'project_boq_items';
if (!$CI->db->table_exists($tables)) {
    $CI->db->query("CREATE TABLE `" . $tables . "` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `item_id` INT(11) NOT NULL,
        `boq_description` TEXT NULL,
        `boq_unit` VARCHAR(100) NULL,
        `planned_qty` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
        `revised_qty` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
        `order_quantity` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
        `supply_rate` DECIMAL(25,4) NULL,
        `itc_rate` DECIMAL(25,4) NULL,
        `supply_amount` DECIMAL(25,4) NULL,
        `itc_amount` DECIMAL(25,4) NULL,
        `tax_rate` DECIMAL(25,4) NULL,
        `is_summary_item` TINYINT(1) NOT NULL DEFAULT 0,
        `purchased_qty` DECIMAL(25,4) NOT NULL DEFAULT 0.0000,
        `internal_ref_no` VARCHAR(191) NULL,
        `client_ref_no` VARCHAR(191) NULL,
        `internal_client_ref_no` VARCHAR(191) NULL,
        `internal_description` TEXT NULL,
        `internal_unit` VARCHAR(100) NULL,
        `internal_quantity` DECIMAL(25,4) NULL,
        `internal_material_rate` DECIMAL(25,4) NULL,
        `internal_labour_rate` DECIMAL(25,4) NULL,
        `internal_supply_amount` DECIMAL(25,4) NULL,
        `internal_itc_amount` DECIMAL(25,4) NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `project_id` (`project_id`),
        KEY `item_id` (`item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
} else {
    $boq_item_columns = [
        'boq_description'        => 'TEXT NULL',
        'boq_unit'               => 'VARCHAR(100) NULL',
        'order_quantity'         => 'DECIMAL(25,4) NOT NULL DEFAULT 0.0000',
        'supply_rate'            => 'DECIMAL(25,4) NULL',
        'itc_rate'               => 'DECIMAL(25,4) NULL',
        'supply_amount'          => 'DECIMAL(25,4) NULL',
        'itc_amount'             => 'DECIMAL(25,4) NULL',
        'tax_rate'               => 'DECIMAL(25,4) NULL',
        'is_summary_item'        => 'TINYINT(1) NOT NULL DEFAULT 0',
        'is_manual_added'        => 'TINYINT(1) NOT NULL DEFAULT 0',
        'is_nt_item'             => 'TINYINT(1) NOT NULL DEFAULT 0',
        'is_billable'            => 'TINYINT(1) NOT NULL DEFAULT 1',
        'internal_ref_no'        => 'VARCHAR(191) NULL',
        'client_ref_no'          => 'VARCHAR(191) NULL',
        'internal_client_ref_no' => 'VARCHAR(191) NULL',
        'internal_description'   => 'TEXT NULL',
        'internal_unit'          => 'VARCHAR(100) NULL',
        'internal_quantity'      => 'DECIMAL(25,4) NULL',
        'internal_material_rate' => 'DECIMAL(25,4) NULL',
        'internal_labour_rate'   => 'DECIMAL(25,4) NULL',
        'internal_supply_amount' => 'DECIMAL(25,4) NULL',
        'internal_itc_amount'    => 'DECIMAL(25,4) NULL',
        'updated_at'             => 'DATETIME NULL',
    ];
    foreach ($boq_item_columns as $column => $definition) {
        if (!$CI->db->field_exists($column, $tables)) {
            $CI->db->query('ALTER TABLE `' . $tables . '` ADD `' . $column . '` ' . $definition);
        }
    }
}

$tables = db_prefix() . 'project_boq_edit_requests';
if (!$CI->db->table_exists($tables)) {
    $CI->db->query("CREATE TABLE `" . $tables . "` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `project_id` INT(11) NOT NULL,
        `boq_item_id` INT(11) NULL,
        `requested_by` INT(11) NOT NULL,
        `request_type` VARCHAR(50) NOT NULL,
        `new_data` LONGTEXT NOT NULL,
        `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
        `reviewed_by` INT(11) NULL,
        `reviewed_at` DATETIME NULL,
        `review_notes` TEXT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `project_id` (`project_id`),
        KEY `boq_item_id` (`boq_item_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

$tables = db_prefix() . 'boq_purchase_links';
if (!$CI->db->table_exists($tables)) {
    $CI->db->query("CREATE TABLE `" . $tables . "` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `purchase_item_id` INT(11) NOT NULL,
        `boq_item_id` INT(11) NOT NULL,
        `purchase_id` INT(11) NOT NULL,
        `qty` DECIMAL(25,4) NOT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `purchase_item_id` (`purchase_item_id`),
        KEY `boq_item_id` (`boq_item_id`),
        KEY `purchase_id` (`purchase_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

$tables = db_prefix() . 'boq_consumption_log';
if (!$CI->db->table_exists($tables)) {
    $CI->db->query("CREATE TABLE `" . $tables . "` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `boq_item_id` INT(11) NOT NULL,
        `pur_request_id` INT(11) NOT NULL,
        `qty` DECIMAL(25,4) NOT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `boq_item_id` (`boq_item_id`),
        KEY `pur_request_id` (`pur_request_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

$pur_orders = db_prefix() . 'pur_orders';
if ($CI->db->table_exists($pur_orders) && !$CI->db->field_exists('boq_overage_items', $pur_orders)) {
    $CI->db->query('ALTER TABLE `' . $pur_orders . '` ADD `boq_overage_items` LONGTEXT NULL');
}

$sa_pur_orders = db_prefix() . 'sa_pur_orders';
if ($CI->db->table_exists($sa_pur_orders) && !$CI->db->field_exists('boq_overage_items', $sa_pur_orders)) {
    $CI->db->query('ALTER TABLE `' . $sa_pur_orders . '` ADD `boq_overage_items` LONGTEXT NULL');
}

$boq_email_templates = [
    [
        'slug'    => 'boq_po_pending_approval',
        'name'    => 'BOQ PO Pending Approval (Sent to Admin)',
        'subject' => 'BOQ Purchase Order Pending Approval - PO #{pur_order_id}',
        'message' => '<p>Dear Admin,</p><p>User {creator_name} has submitted a purchase order (#{pur_order_id}) that exceeds BOQ limits for the following items:</p><p>{item_list}</p><p>Please review and approve or reject this purchase order.</p><p><a href="{pur_order_link}">View Purchase Order</a></p><p>Kind Regards,<br />{email_signature}</p>',
    ],
    [
        'slug'    => 'boq_po_rejected',
        'name'    => 'BOQ PO Rejected (Sent to Creator)',
        'subject' => 'Purchase Order #{pur_order_id} Rejected - BOQ Overage',
        'message' => '<p>Dear {creator_name},</p><p>Your purchase order (#{pur_order_id}) has been rejected because it exceeds the BOQ limits for the following items:</p><p>{item_list}</p><p>Please update your purchase order or contact an administrator for assistance.</p><p>Kind Regards,<br />{email_signature}</p>',
    ],
    [
        'slug'    => 'boq_edit_approved',
        'name'    => 'BOQ Edit Request Approved (Sent to Requester)',
        'subject' => 'BOQ Edit Request Approved',
        'message' => '<p>Dear {requester_name},</p><p>Your BOQ edit request for project {project_name} has been approved.</p><p>Request Type: {request_type}</p><p>{item_details}</p><p>Kind Regards,<br />{email_signature}</p>',
    ],
    [
        'slug'    => 'boq_edit_rejected',
        'name'    => 'BOQ Edit Request Rejected (Sent to Requester)',
        'subject' => 'BOQ Edit Request Rejected',
        'message' => '<p>Dear {requester_name},</p><p>Your BOQ edit request for project {project_name} has been rejected.</p><p>Request Type: {request_type}</p><p>Review Notes: {review_notes}</p><p>Kind Regards,<br />{email_signature}</p>',
    ],
];

foreach ($boq_email_templates as $template) {
    $CI->db->where('slug', $template['slug']);
    $CI->db->where('language', 'english');
    if ($CI->db->count_all_results(db_prefix() . 'emailtemplates') == 0) {
        $CI->db->insert(db_prefix() . 'emailtemplates', [
            'type'      => 'boq',
            'slug'      => $template['slug'],
            'language'  => 'english',
            'name'      => $template['name'],
            'subject'   => $template['subject'],
            'message'   => $template['message'],
            'fromname'  => '{companyname} | CRM',
            'fromemail' => '',
            'plaintext' => 0,
            'active'    => 1,
            'order'     => 1,
        ]);
    }
}
