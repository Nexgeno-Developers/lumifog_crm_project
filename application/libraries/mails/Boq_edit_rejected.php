<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Boq_edit_rejected extends App_mail_template
{
    protected $for = 'staff';

    protected $recipient_email;

    protected $requester;

    protected $data;

    public $slug = 'boq_edit_rejected';

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
        $requester_name = '';
        if (is_object($this->requester)) {
            $requester_name = $this->requester->firstname . ' ' . $this->requester->lastname;
        } elseif (is_array($this->requester)) {
            $requester_name = ($this->requester['firstname'] ?? '') . ' ' . ($this->requester['lastname'] ?? '');
        }

        $this->set_merge_fields([
            '{requester_name}' => $requester_name,
            '{project_name}'   => $this->data['project_name'] ?? '',
            '{request_type}'   => $this->data['request_type'] ?? '',
            '{review_notes}'   => $this->data['review_notes'] ?? '',
        ]);

        $this->to($this->recipient_email);
    }
}
