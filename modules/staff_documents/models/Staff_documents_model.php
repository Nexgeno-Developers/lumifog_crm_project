<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Staff_documents_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get document by ID.
     *
     * @param  int $id
     * @return array|null
     */
    public function get($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get(db_prefix() . 'staff_documents')->row_array();
    }

    /**
     * Get all documents for a staff member.
     *
     * @param  int $staff_id
     * @return array
     */
    public function get_by_staff($staff_id)
    {
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->order_by('dateadded', 'DESC');

        return $this->db->get(db_prefix() . 'staff_documents')->result_array();
    }

    /**
     * Get all documents (global view) with optional filters.
     *
     * @param  array $filters staff_id, date_from, date_to (Y-m-d)
     * @return array
     */
    public function get_all($filters = [])
    {
        $this->db->select(
            db_prefix() . 'staff_documents.*, '
            . db_prefix() . 'staff.firstname, '
            . db_prefix() . 'staff.lastname'
        );
        $this->db->join(
            db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . db_prefix() . 'staff_documents.staff_id'
        );

        if (!empty($filters['staff_id'])) {
            $this->db->where(db_prefix() . 'staff_documents.staff_id', (int) $filters['staff_id']);
        }

        if (!empty($filters['date_from'])) {
            $this->db->where(
                db_prefix() . 'staff_documents.dateadded >=',
                $filters['date_from'] . ' 00:00:00'
            );
        }

        if (!empty($filters['date_to'])) {
            $this->db->where(
                db_prefix() . 'staff_documents.dateadded <=',
                $filters['date_to'] . ' 23:59:59'
            );
        }

        $this->db->order_by(db_prefix() . 'staff_documents.dateadded', 'DESC');

        return $this->db->get(db_prefix() . 'staff_documents')->result_array();
    }

    /**
     * Add a new document record.
     *
     * @param  array $data
     * @return int|false
     */
    public function add($data)
    {
        $insert = [
            'staff_id'      => (int) $data['staff_id'],
            'file_name'     => $data['file_name'],
            'original_name' => $data['original_name'],
            'file_type'     => $data['file_type'],
            'file_size'     => (int) $data['file_size'],
            'description'   => isset($data['description']) ? $data['description'] : '',
            'dateadded'     => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix() . 'staff_documents', $insert);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('Staff Document Uploaded [ID: ' . $insert_id . ', Staff: ' . $insert['staff_id'] . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Delete a document record.
     *
     * @param  int $id
     * @return bool
     */
    public function delete($id)
    {
        $document = $this->get($id);

        if (!$document) {
            return false;
        }

        $this->db->where('id', (int) $id);
        $this->db->delete(db_prefix() . 'staff_documents');

        if ($this->db->affected_rows() > 0) {
            log_activity('Staff Document Deleted [ID: ' . $id . ', Staff: ' . $document['staff_id'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Get full filesystem path for a document.
     *
     * @param  array $document
     * @return string
     */
    public function get_file_path($document)
    {
        return get_staff_documents_upload_path($document['staff_id']) . $document['file_name'];
    }
}
