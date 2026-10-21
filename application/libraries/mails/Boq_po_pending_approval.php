<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Boq_po_pending_approval extends App_mail_template
{
    protected $for = 'staff';

    protected $recipient_email;

    protected $requester;

    protected $data;

    public $slug = 'boq_po_pending_approval';

    public $rel_type = 'project';

    public function __construct($recipient_email, $requester = null, $data = [])
    {
        parent::__construct();

        $this->recipient_email = $recipient_email;
        $this->requester       = $requester;
        $this->data            = $data;
    }

    public function build()
    {
        $creator_name = '';
        if (is_object($this->requester)) {
            $creator_name = $this->requester->firstname . ' ' . $this->requester->lastname;
        } elseif (is_array($this->requester)) {
            $creator_name = ($this->requester['firstname'] ?? '') . ' ' . ($this->requester['lastname'] ?? '');
        }

        $this->set_merge_fields([
            '{creator_name}'    => $creator_name,
            '{pur_order_id}'    => $this->data['pur_order_id'] ?? '',
            '{item_list}'       => $this->data['item_names'] ?? ($this->data['message'] ?? ''),
            '{pur_order_link}'  => admin_url('projects'),
        ]);

        $this->to($this->recipient_email);
    }
}
