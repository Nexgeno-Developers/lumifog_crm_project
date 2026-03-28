<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Adds revised_qty to project_boq_items; backfill from planned_qty.
 *
 * @property-read CI_DB_mysql_driver $db
 */
class Migration_Version_332 extends CI_Migration
{
    public function up(): void
    {
        $t = db_prefix() . 'project_boq_items';

        $added_column = false;

        if (! $this->db->field_exists('revised_qty', $t)) {
            $this->db->query(
                'ALTER TABLE `' . $t . '` ADD `revised_qty` DECIMAL(15,4) NOT NULL DEFAULT 0.0000 AFTER `planned_qty`'
            );
            $added_column = true;
        }

        if ($added_column) {
            $this->db->query('UPDATE `' . $t . '` SET `revised_qty` = `planned_qty`');
        }
    }
}
