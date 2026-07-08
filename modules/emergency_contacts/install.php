<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

if (!$CI->db->table_exists(db_prefix() . 'hr_staff_emergency_contacts')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "hr_staff_emergency_contacts` (
      `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `staffid` INT(11) UNSIGNED NOT NULL,
      `contact_name` VARCHAR(191) NOT NULL,
      `relationship` VARCHAR(100) NULL,
      `phone` VARCHAR(50) NOT NULL,
      `alt_phone` VARCHAR(50) NULL,
      `email` VARCHAR(191) NULL,
      `address` TEXT NULL,
      `is_primary` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
      `notes` TEXT NULL,
      `date_created` DATETIME NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}
