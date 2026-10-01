<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Lead_customer_linker extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('leads_model');
        $this->load->model('clients_model');
        $this->load->model('misc_model');
    }

    /**
     * Convert and link lead to an existing customer
     */
    public function convert_to_existing()
    {
        if (!is_staff_member()) {
            access_denied('Lead Convert to Existing Customer');
        }

        if (!$this->input->post()) {
            redirect(admin_url('leads'));
        }

        $leadid    = (int) $this->input->post('leadid');
        $client_id = (int) $this->input->post('client_id');

        if ($leadid <= 0 || $client_id <= 0) {
            set_alert('danger', 'Please select a valid Lead and Customer.');
            redirect(admin_url('leads'));
        }

        $lead = $this->leads_model->get($leadid);
        if (!$lead) {
            set_alert('danger', 'Lead not found.');
            redirect(admin_url('leads'));
        }

        $client = $this->clients_model->get($client_id);
        if (!$client) {
            set_alert('danger', 'Customer not found.');
            redirect(admin_url('leads/index/' . $leadid));
        }

        // Get default converted customer status
        $this->db->where('isdefault', 1);
        $status_row = $this->db->get(db_prefix() . 'leads_status')->row();
        $status_client_id = $status_row ? (int) $status_row->id : (int) $lead->status;

        // 1. Update lead record
        $this->db->where('id', $leadid);
        $this->db->update(db_prefix() . 'leads', [
            'client_id'      => $client_id,
            'date_converted' => date('Y-m-d H:i:s'),
            'status'         => $status_client_id,
            'junk'           => 0,
            'lost'           => 0,
        ]);

        // 2. Transfer Notes if selected
        if ($this->input->post('transfer_notes')) {
            $notes = $this->misc_model->get_notes($leadid, 'lead');
            foreach ($notes as $note) {
                $this->misc_model->add_note([
                    'description' => '[Transferred from Lead #' . $leadid . ' - ' . $lead->name . '] ' . $note['description'],
                    'rel_id'      => $client_id,
                    'rel_type'    => 'customer',
                ]);
            }
        }

        // 3. Transfer Proposals if selected
        if ($this->input->post('transfer_proposals')) {
            $this->db->where('rel_id', $leadid);
            $this->db->where('rel_type', 'lead');
            $this->db->update(db_prefix() . 'proposals', [
                'rel_id'   => $client_id,
                'rel_type' => 'customer',
            ]);
        }

        // 4. Transfer Tasks if selected
        if ($this->input->post('transfer_tasks')) {
            $this->db->where('rel_id', $leadid);
            $this->db->where('rel_type', 'lead');
            $this->db->update(db_prefix() . 'tasks', [
                'rel_id'   => $client_id,
                'rel_type' => 'customer',
            ]);
        }

        // 5. Transfer Reminders if selected
        if ($this->input->post('transfer_reminders')) {
            $this->db->where('rel_id', $leadid);
            $this->db->where('rel_type', 'lead');
            $this->db->update(db_prefix() . 'reminders', [
                'rel_id'   => $client_id,
                'rel_type' => 'customer',
            ]);
        }

        // Log activities
        $client_name = !empty($client->company) ? $client->company : ($client->firstname . ' ' . $client->lastname);
        $client_link = '<a href="' . admin_url('clients/client/' . $client_id . '?group=leads') . '">' . html_escape($client_name) . '</a>';

        $this->leads_model->log_lead_activity(
            $leadid,
            'not_lead_activity_converted',
            false,
            serialize([get_staff_full_name(), $client_link])
        );

        log_activity('Lead #' . $leadid . ' (' . $lead->name . ') linked to existing Customer #' . $client_id . ' (' . $client_name . ') [Staff: ' . get_staff_full_name() . ']');

        // Fire hook
        hooks()->do_action('lead_converted_to_customer', [
            'lead_id'            => $leadid,
            'customer_id'        => $client_id,
            'is_existing_client' => true,
        ]);

        if ($this->input->is_ajax_request()) {
            echo json_encode([
                'success'      => true,
                'message'      => 'Lead successfully linked and converted to ' . $client_name,
                'redirect_url' => admin_url('clients/client/' . $client_id . '?group=leads'),
            ]);
            die();
        }

        set_alert('success', 'Lead #' . $leadid . ' successfully converted and linked to ' . $client_name);
        redirect(admin_url('clients/client/' . $client_id . '?group=leads'));
    }

    /**
     * AJAX Search for active customers (for the dropdown)
     */
    public function search_clients()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }

        $q = trim((string) $this->input->get('q'));
        $this->db->select('userid, company, phonenumber, city');
        $this->db->from(db_prefix() . 'clients');
        $this->db->where('active', 1);

        if ($q !== '') {
            $this->db->group_start();
            $this->db->like('company', $q);
            $this->db->or_like('phonenumber', $q);
            $this->db->or_like('city', $q);
            $this->db->group_end();
        }

        $this->db->limit(50);
        $clients = $this->db->get()->result_array();

        $data = [];
        foreach ($clients as $c) {
            $text = $c['company'];
            if (!empty($c['city'])) {
                $text .= ' (' . $c['city'] . ')';
            }
            if (!empty($c['phonenumber'])) {
                $text .= ' - ' . $c['phonenumber'];
            }
            $data[] = [
                'id'   => (int) $c['userid'],
                'text' => $text,
            ];
        }

        echo json_encode($data);
        die();
    }
}
