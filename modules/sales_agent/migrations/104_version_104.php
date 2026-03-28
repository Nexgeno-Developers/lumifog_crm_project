<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_104 extends App_module_migration
{
     public function up()
     {
        
        add_option('allow_agent_can_create_order_with_item_0_inventory', 1);

        create_email_template('New Order Created(Sent to staff)', '<span style=\"font-size: 12pt;\"> Hello !. </span><br /><br /><span style=\"font-size: 12pt;\">A new order {order_number} has just been created by the agent {sale_agent_name}</span><br /><br /><span style=\"font-size: 12pt;\"><br />Please click on the link to view information: {order_detail_link}
          </span><br /><br />', 'sale_agent', 'New Order Created(Sent to staff)', 'sa-new-order-created');
     }
}
