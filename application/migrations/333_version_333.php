<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Optional BOQ line reference on purchase request detail (Purchase module table).
 *
 * @property-read CI_DB_mysql_driver $db
 */
class Migration_Version_333 extends CI_Migration
{
    public function up(): void
    {
        $t = db_prefix() . 'pur_request_detail';

        if ($this->db->table_exists($t) && ! $this->db->field_exists('boq_item_id', $t)) {
            $this->db->query(
                'ALTER TABLE `' . $t . '` ADD `boq_item_id` INT UNSIGNED NULL DEFAULT NULL AFTER `item_code`'
            );
        }
    }
}
