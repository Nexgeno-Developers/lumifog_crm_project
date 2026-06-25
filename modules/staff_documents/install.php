<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'staff_documents')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "staff_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_type` varchar(10) NOT NULL,
  `file_size` int(11) NOT NULL DEFAULT '0',
  `description` text,
  `dateadded` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

_maybe_create_upload_path(STAFF_DOCUMENTS_UPLOAD_PATH);

$htaccess_path = STAFF_DOCUMENTS_UPLOAD_PATH . '.htaccess';
if (!file_exists($htaccess_path)) {
    file_put_contents($htaccess_path, "Options -Indexes\n");
}

$index_path = STAFF_DOCUMENTS_UPLOAD_PATH . 'index.html';
if (!file_exists($index_path)) {
    file_put_contents($index_path, '');
}
