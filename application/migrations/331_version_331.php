<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_331 extends CI_Migration
{
    public function up(): void
    {
        if (! $this->db->field_exists('paid', db_prefix() . 'estimates')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'estimates` ADD `paid` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `adjustment`');
        }
    }
}
