<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Ensure index exists on tblleads.client_id for performance
$index_check = $CI->db->query("SHOW INDEX FROM " . db_prefix() . "leads WHERE Column_name = 'client_id'");
if ($index_check->num_rows() == 0) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "leads` ADD INDEX `client_id_idx` (`client_id`)");
}
