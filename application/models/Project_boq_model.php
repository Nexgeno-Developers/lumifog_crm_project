<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Project_boq_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * BOQ rows for a project with item description.
     *
     * @param int $project_id
     * @return array
     */
    public function get_by_project_id($project_id)
    {
        $this->db->select(
            db_prefix() . 'project_boq_items.*, ' . db_prefix() . 'items.description as item_description, ' . db_prefix() . 'items.unit as item_unit'
        );
        $this->db->from(db_prefix() . 'project_boq_items');
        $this->db->join(db_prefix() . 'items', db_prefix() . 'items.id = ' . db_prefix() . 'project_boq_items.item_id', 'inner');
        $this->db->where(db_prefix() . 'project_boq_items.project_id', $project_id);
        $this->db->order_by(db_prefix() . 'project_boq_items.id', 'asc');

        $rows = $this->db->get()->result_array();

        foreach ($rows as $key => $row) {
            $rows[$key] = $this->append_computed_fields($row);
        }

        return $rows;
    }

    /**
     * @param int $id
     * @return object|null
     */
    public function get($id)
    {
        $this->db->select(
            db_prefix() . 'project_boq_items.*, ' . db_prefix() . 'items.description as item_description, ' . db_prefix() . 'items.unit as item_unit'
        );
        $this->db->from(db_prefix() . 'project_boq_items');
        $this->db->join(db_prefix() . 'items', db_prefix() . 'items.id = ' . db_prefix() . 'project_boq_items.item_id', 'inner');
        $this->db->where(db_prefix() . 'project_boq_items.id', $id);

        $row = $this->db->get()->row();

        if ($row) {
            return (object) $this->append_computed_fields((array) $row);
        }

        return null;
    }

    /**
     * @param array $data project_id, item_id, planned_qty, revised_qty (optional; defaults to planned_qty)
     * @return false|int insert id
     */
    public function add($data)
    {
        $planned = $data['planned_qty'];
        $revised = isset($data['revised_qty']) ? $data['revised_qty'] : $planned;

        $insert = [
            'project_id'    => (int) $data['project_id'],
            'item_id'       => (int) $data['item_id'],
            'planned_qty'   => $planned,
            'revised_qty'   => $revised,
            'purchased_qty' => isset($data['purchased_qty']) ? $data['purchased_qty'] : 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix() . 'project_boq_items', $insert);

        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }

        return false;
    }

    /**
     * @param int   $id
     * @param array $data item_id, revised_qty (planned_qty is not updated after creation)
     * @return bool
     */
    public function update_row($id, $data)
    {
        $update = [];

        if (isset($data['item_id'])) {
            $update['item_id'] = (int) $data['item_id'];
        }

        if (isset($data['revised_qty'])) {
            $update['revised_qty'] = $data['revised_qty'];
        }

        if ($update === []) {
            return false;
        }

        $this->db->where('id', (int) $id);
        $this->db->update(db_prefix() . 'project_boq_items', $update);

        return true;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->delete(db_prefix() . 'project_boq_items');

        return $this->db->affected_rows() > 0;
    }

    /**
     * @param array $row
     * @return array
     */
    protected function append_computed_fields($row)
    {
        $planned   = (float) $row['planned_qty'];
        $revised   = isset($row['revised_qty']) ? (float) $row['revised_qty'] : $planned;
        $purchased = (float) $row['purchased_qty'];
        $remaining = $revised - $purchased;

        $row['remaining_qty']         = $remaining;
        $row['remaining_qty_display']   = app_format_number($remaining);
        $row['planned_qty_display']   = app_format_number($planned);
        $row['revised_qty_display']     = app_format_number($revised);
        $row['purchased_qty_display']   = app_format_number($purchased);

        return $row;
    }
}
