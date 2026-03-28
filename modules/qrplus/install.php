<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!$CI->db->table_exists(db_prefix() . 'qrplus_list')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "qrplus_list` (
  `id` int(11) NOT NULL,
  `user_id` int,
  `title` text,
  `data` text,
  `qr_code_content` text,
  `created_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'qrplus_list`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'qrplus_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
