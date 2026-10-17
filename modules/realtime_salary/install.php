<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

if (!$CI->db->table_exists(db_prefix() . 'realtime_salary_settings')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "realtime_salary_settings` (
        `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `setting_name` varchar(100) NOT NULL,
        `setting_val` text NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_name` (`setting_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    // Default settings
    $defaults = [
        ['setting_name' => 'salary_calculation_mode', 'setting_val' => 'gross'],
        ['setting_name' => 'non_shift_code', 'setting_val' => 'NS'],
    ];

    foreach ($defaults as $item) {
        $CI->db->insert(db_prefix() . 'realtime_salary_settings', $item);
    }
}
