<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Sa_order_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
            [
                'name'      => 'Order number',
                'key'       => '{order_number}',
                'available' => [
                    
                ],
                'templates' => [
                    'sa-new-order-created',
                ],
            ],
            [
                'name'      => 'Order link',
                'key'       => '{order_detail_link}',
                'available' => [
                    
                ],
                'templates' => [
                    'sa-new-order-created',
                ],
            ],
            [
                'name'      => 'Sale Agent Name',
                'key'       => '{sale_agent_name}',
                'available' => [
                    
                ],
                'templates' => [
                    'sa-new-order-created',
                ],
            ],
        ];
    }

    /**
     * Merge field for appointments
     * @param  mixed $teampassword 
     * @return array
     */
    public function format($data)
    {
        $order_id = $data->order_id;
        $this->ci->load->model('sales_agent/sales_agent_model');


        $fields = [];

        $order = $this->ci->sales_agent_model->get_pur_order($order_id);

        $fields['{order_detail_link}']                  = admin_url('sales_agent/order_detail/' . $order->id);
        $fields['{sale_agent_name}']                  =  get_company_name($order->agent_id);
        $fields['{order_number}']                  =  $order->order_number;


        return $fields;
    }
}
