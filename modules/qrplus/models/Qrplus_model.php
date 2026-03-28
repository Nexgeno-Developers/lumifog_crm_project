<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Qrplus_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function add($data)
    {
        $this->db->insert(db_prefix() . 'qrplus_list', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function get($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'qrplus_list')->row();
    }

    public function getAll()
    {
        return $this->db->get(db_prefix() . 'qrplus_list')->result_array();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'qrplus_list', $data);

        return $this->db->affected_rows() > 0;
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'qrplus_list');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }
}
