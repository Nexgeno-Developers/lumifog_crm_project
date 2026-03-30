<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Create BOQ <-> Purchase detail synchronization link table.
 *
 * @property-read CI_DB_mysql_driver $db
 */
class Migration_Version_334 extends CI_Migration
{
    public function up(): void
    {
        $t = db_prefix() . 'boq_purchase_links';

        if (! $this->db->table_exists($t)) {
            $this->db->query(
                'CREATE TABLE `' . $t . '` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `purchase_item_id` INT UNSIGNED NOT NULL,
                `boq_item_id` INT UNSIGNED NOT NULL,
                `purchase_id` INT UNSIGNED NOT NULL,
                `qty` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `purchase_item_id` (`purchase_item_id`),
                KEY `purchase_id` (`purchase_id`),
                KEY `boq_item_id` (`boq_item_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ' COLLATE ' . $this->db->dbcollat . ';'
            );
        }
    }
}
