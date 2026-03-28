<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property-read CI_DB_mysql_driver $db
 */
class Migration_Version_331 extends CI_Migration
{
    public function up(): void
    {
        if (! $this->db->table_exists(db_prefix() . 'project_boq_items')) {
            $this->db->query(
                'CREATE TABLE `' . db_prefix() . 'project_boq_items` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `project_id` INT NOT NULL,
                `item_id` INT NOT NULL,
                `planned_qty` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                `purchased_qty` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `project_id` (`project_id`),
                KEY `item_id` (`item_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ' COLLATE ' . $this->db->dbcollat . ';'
            );
        }
    }
}
