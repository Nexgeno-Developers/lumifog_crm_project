<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Emergency_contacts extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('emergency_contacts/emergency_contacts_model');
        $this->load->model('hr_profile/hr_profile_model');
    }

    public function table($staffid = '')
    {
        $this->app->get_table_data(module_views_path('emergency_contacts', 'table'), [
            'staffid' => $staffid,
        ]);
    }

    public function save()
    {
        if (!$this->input->post()) {
            redirect(admin_url('hr_profile/staff_infor'));
        }

        if (!is_admin() && !has_permission('hrm_hr_records', '', 'edit')) {
            access_denied('hr_profile');
        }

        $data    = $this->input->post();
        $id      = isset($data['id']) ? (int) $data['id'] : 0;
        $staffid = isset($data['staffid']) ? (int) $data['staffid'] : 0;

        $redirect = admin_url('hr_profile/member/' . $staffid . '/emergency_contact');

        if ($staffid <= 0) {
            set_alert('warning', _l('ec_invalid_staff'));
            redirect(admin_url('hr_profile/staff_infor'));
        }

        if (!isset($data['contact_name']) || trim($data['contact_name']) === '' || !isset($data['phone']) || trim($data['phone']) === '') {
            set_alert('warning', _l('ec_contact_name_phone_required'));
            redirect($redirect);
        }

        if (isset($data['email']) && $data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            set_alert('warning', _l('ec_invalid_email'));
            redirect($redirect);
        }

        $save_data = [
            'staffid'      => $staffid,
            'contact_name' => trim($data['contact_name']),
            'relationship' => isset($data['relationship']) ? trim($data['relationship']) : null,
            'phone'        => trim($data['phone']),
            'alt_phone'    => isset($data['alt_phone']) ? trim($data['alt_phone']) : null,
            'email'        => isset($data['email']) ? trim($data['email']) : null,
            'address'      => isset($data['address']) ? trim($data['address']) : null,
            'is_primary'   => isset($data['is_primary']) ? 1 : 0,
            'notes'        => isset($data['notes']) ? trim($data['notes']) : null,
        ];

        if ($id > 0) {
            $success = $this->emergency_contacts_model->update($save_data, $id);
            if ($success) {
                set_alert('success', _l('updated_successfully', _l('ec_emergency_contact')));
            } else {
                set_alert('warning', _l('updated_failed', _l('ec_emergency_contact')));
            }
        } else {
            $insert_id = $this->emergency_contacts_model->add($save_data);
            if ($insert_id) {
                set_alert('success', _l('added_successfully', _l('ec_emergency_contact')));
            } else {
                set_alert('warning', _l('added_failed', _l('ec_emergency_contact')));
            }
        }

        redirect($redirect);
    }

    public function view($id = 0)
    {
        $id = (int) $id;
        if ($id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => _l('ec_contact_not_found'),
            ]);
            exit;
        }

        $contact = $this->emergency_contacts_model->get($id);
        if (!$contact) {
            echo json_encode([
                'success' => false,
                'message' => _l('ec_contact_not_found'),
            ]);
            exit;
        }

        $staff_ids = $this->hr_profile_model->get_staff_by_manager();
        if (
            !is_admin()
            && !has_permission('hrm_hr_records', '', 'view')
            && !has_permission('hrm_hr_records', '', 'edit')
            && !has_permission('hrm_hr_records', '', 'create')
            && get_staff_user_id() != (int) $contact['staffid']
            && !in_array((int) $contact['staffid'], $staff_ids)
        ) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied'),
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data'    => $contact,
        ]);
        exit;
    }

    public function delete($id = 0)
    {
        if (!is_admin() && !has_permission('hrm_hr_records', '', 'delete')) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied'),
            ]);
            exit;
        }

        $id = (int) $id;
        if ($id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => _l('problem_deleting'),
            ]);
            exit;
        }

        $success = $this->emergency_contacts_model->delete($id);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => _l('deleted', _l('ec_emergency_contact')),
            ]);
            exit;
        }

        echo json_encode([
            'success' => false,
            'message' => _l('problem_deleting'),
        ]);
        exit;
    }
}
