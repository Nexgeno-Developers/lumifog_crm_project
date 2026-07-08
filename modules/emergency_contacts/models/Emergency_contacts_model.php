<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Emergency_contacts_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_by_staff($staffid)
    {
        $this->db->where('staffid', (int) $staffid);
        $this->db->order_by('is_primary', 'DESC');
        $this->db->order_by('id', 'DESC');

        return $this->db->get(db_prefix() . 'hr_staff_emergency_contacts')->result_array();
    }

    public function get($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get(db_prefix() . 'hr_staff_emergency_contacts')->row_array();
    }

    public function add($data)
    {
        $data['staffid'] = isset($data['staffid']) ? (int) $data['staffid'] : 0;
        if ($data['staffid'] <= 0) {
            return false;
        }

        $data['is_primary']   = !empty($data['is_primary']) ? 1 : 0;
        $data['date_created'] = date('Y-m-d H:i:s');

        if ($data['is_primary']) {
            $this->db->where('staffid', $data['staffid']);
            $this->db->update(db_prefix() . 'hr_staff_emergency_contacts', ['is_primary' => 0]);
        }

        $this->db->insert(db_prefix() . 'hr_staff_emergency_contacts', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function update($data, $id)
    {
        $id       = (int) $id;
        $existing = $this->get($id);

        if (!$existing) {
            return false;
        }

        $staffid            = isset($data['staffid']) ? (int) $data['staffid'] : (int) $existing['staffid'];
        $data['staffid']    = $staffid;
        $data['is_primary'] = !empty($data['is_primary']) ? 1 : 0;

        if ($data['is_primary']) {
            $this->db->where('staffid', $staffid);
            $this->db->where('id !=', $id);
            $this->db->update(db_prefix() . 'hr_staff_emergency_contacts', ['is_primary' => 0]);
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'hr_staff_emergency_contacts', $data);

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return $this->db->error()['code'] === 0;
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->delete(db_prefix() . 'hr_staff_emergency_contacts');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }
}
