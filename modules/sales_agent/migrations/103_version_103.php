<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_103 extends App_module_migration
{
     public function up()
     {
         $CI = &get_instance();
        // FIX Add permission setting error
         if (!$CI->db->field_exists('shipping_fee' ,db_prefix() . 'invoices')) {
           $CI->db->query('ALTER TABLE `' . db_prefix() . 'invoices`
           ADD COLUMN `shipping_fee` DECIMAL(15,2) NULL DEFAULT "0.00"
           ');
         }
     }
}
