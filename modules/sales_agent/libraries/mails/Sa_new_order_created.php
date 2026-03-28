<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Sa_new_order_created extends App_mail_template
{
    protected $for = 'staff';

    protected $data;

    public $slug = 'sa-new-order-created';

    public function __construct($data)
    {
        parent::__construct();

        $this->data = $data;
        // For SMS and merge fields for email
        $this->set_merge_fields('sa_order_merge_fields', $this->data);
    }
    public function build()
    {
        $this->to($this->data->mail_to);
    }
}
