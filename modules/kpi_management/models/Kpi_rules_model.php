<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_rules_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'kpi_rules')->row_array();
        }

        return $this->db->get(db_prefix() . 'kpi_rules')->result_array();
    }

    public function get_filtered($filters = [])
    {
        if (isset($filters['module']) && $filters['module'] !== '') {
            $this->db->where('module', $filters['module']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $this->db->where('is_active', (int) $filters['is_active']);
        }

        $this->db->order_by('id', 'DESC');
        return $this->db->get(db_prefix() . 'kpi_rules')->result_array();
    }

    public function add($data)
    {
        $this->db->insert(db_prefix() . 'kpi_rules', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('KPI Rule Added [ID:' . $insert_id . ']');
            return $insert_id;
        }

        return false;
    }

    public function update($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'kpi_rules', $data);

        if ($this->db->affected_rows() > 0) {
            log_activity('KPI Rule Updated [ID:' . $id . ']');
            return true;
        }

        return false;
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'kpi_rules');

        if ($this->db->affected_rows() > 0) {
            log_activity('KPI Rule Deleted [ID:' . $id . ']');
            return true;
        }

        return false;
    }
}

