<?php

defined('BASEPATH') or exit('No direct script access allowed');

// tblkpi_rules
if (!$CI->db->table_exists(db_prefix() . 'kpi_rules')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "kpi_rules` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `rules_json` JSON NOT NULL,
  `description` LONGTEXT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `module` (`module`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// tblkpi_transactions
if (!$CI->db->table_exists(db_prefix() . 'kpi_transactions')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "kpi_transactions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT UNSIGNED NOT NULL,
  `kpi_rule_id` INT UNSIGNED NOT NULL,
  `rel_type` VARCHAR(50) NULL,
  `rel_id` INT UNSIGNED NULL,
  `points` INT NOT NULL,
  `transaction_date` DATE NOT NULL,
  `status` ENUM('pending','approved','rejected') DEFAULT 'approved',
  `remarks` VARCHAR(255) NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `staff_id` (`staff_id`),
  KEY `kpi_rule_id` (`kpi_rule_id`),
  KEY `rel_lookup` (`rel_type`, `rel_id`),
  KEY `transaction_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Insert sample rules if table is empty
$CI->db->select('id')->from(db_prefix() . 'kpi_rules')->limit(1);
$exists = $CI->db->get()->row_array();

if (!$exists) {
    $earlyOnTime = [
        'rule'       => 'early_and_on_time_arrival',
        'based_on'   => 'shift_start_time',
        'type'       => 'time_difference',
        'conditions' => [
            ['from' => -15, 'to' => 0, 'points' => 5],
            ['from' => 1, 'to' => 5, 'points' => 4],
            ['from' => 6, 'to' => 15, 'points' => 2],
        ],
    ];

    $lateArrival = [
        'rule'       => 'late_arrival',
        'based_on'   => 'shift_start_time',
        'type'       => 'time_difference',
        'conditions' => [
            ['from' => 16, 'to' => 29, 'points' => 0],
            ['from' => 30, 'to' => 59, 'points' => -2],
            ['from' => 60, 'to' => 999, 'points' => -4],
        ],
    ];

    $CI->db->insert(db_prefix() . 'kpi_rules', [
        'title'       => 'Early / On Time',
        'module'      => 'attendance',
        'rules_json'  => json_encode($earlyOnTime),
        'description' => 'Sample rule: early / on-time arrival points.',
        'is_active'   => 1,
    ]);

    $CI->db->insert(db_prefix() . 'kpi_rules', [
        'title'       => 'Late Arrival',
        'module'      => 'attendance',
        'rules_json'  => json_encode($lateArrival),
        'description' => 'Sample rule: late arrival points/penalties.',
        'is_active'   => 1,
    ]);

    $absent = [
        'rule'        => 'absent',
        'type'        => 'fixed',
        'points'      => -6,
        'description' => 'Automatic deduction for unmarked attendance / absence without approved leave.',
    ];

    $CI->db->insert(db_prefix() . 'kpi_rules', [
        'title'       => 'Absent',
        'module'      => 'attendance',
        'rules_json'  => json_encode($absent),
        'description' => 'Deduction of 6 points for absence / unmarked attendance.',
        'is_active'   => 1,
    ]);
}

