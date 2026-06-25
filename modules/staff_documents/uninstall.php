<?php

defined('BASEPATH') or exit('No direct script access allowed');

if ($CI->db->table_exists(db_prefix() . 'staff_documents')) {
    $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'staff_documents`');
}
