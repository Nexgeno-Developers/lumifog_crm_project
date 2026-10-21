<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Boq_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

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
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix() . 'project_boq_items', $insert);

        if ($this->db->affected_rows() > 0) {
            return $this->db->insert_id();
        }

        return false;
    }

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

    public function delete($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->delete(db_prefix() . 'project_boq_items');

        return $this->db->affected_rows() > 0;
    }

    protected function append_computed_fields($row)
    {
        $planned   = (float) $row['planned_qty'];
        $order_qty = isset($row['order_quantity']) ? (float) $row['order_quantity'] : null;
        $revised   = isset($row['revised_qty']) ? (float) $row['revised_qty'] : null;
        if ($revised === null || $revised === 0.0) {
            $revised = $order_qty !== null ? $order_qty : $planned;
        }
        $purchased = (float) $row['purchased_qty'];
        $remaining = $revised - $purchased;

        $row['remaining_qty']         = $remaining;
        $row['remaining_qty_display']   = app_format_number($remaining);
        $row['planned_qty_display']   = app_format_number($planned);
        $row['revised_qty_display']     = app_format_number($revised);
        $row['purchased_qty_display']   = app_format_number($purchased);
        $row['_revised_for_display']    = $revised;

        return $row;
    }

    public function merge_or_add_row($project_id, $item_id, array $data)
    {
        $existing = $this->get_by_project_and_item($project_id, $item_id);
        if ($existing) {
            $update = [];
            if (isset($data['planned_qty'])) {
                $update['planned_qty'] = (float) $data['planned_qty'] + (float) $existing['planned_qty'];
            }
            if (isset($data['revised_qty'])) {
                $update['revised_qty'] = (float) $data['revised_qty'] + (float) $existing['revised_qty'];
            }
            if (isset($data['order_quantity'])) {
                $update['order_quantity'] = (float) $data['order_quantity'] + (float) $existing['order_quantity'];
            }
            $fields = [
                'boq_description', 'boq_unit', 'supply_rate', 'itc_rate',
                'supply_amount', 'itc_amount', 'tax_rate', 'is_summary_item', 'is_manual_added',
                'internal_ref_no', 'client_ref_no', 'internal_client_ref_no',
                'internal_description', 'internal_unit', 'internal_quantity',
                'internal_material_rate', 'internal_labour_rate',
                'internal_supply_amount', 'internal_itc_amount',
            ];
            foreach ($fields as $field) {
                if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '') {
                    $update[$field] = $data[$field];
                }
            }
            $update['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $existing['id']);
            $this->db->update(db_prefix() . 'project_boq_items', $update);
            return (int) $existing['id'];
        }

        $insert = [
            'project_id'    => (int) $project_id,
            'item_id'       => (int) $item_id,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        $fields = [
            'boq_description', 'boq_unit', 'order_quantity', 'planned_qty', 'revised_qty',
            'supply_rate', 'itc_rate', 'supply_amount', 'itc_amount', 'tax_rate', 'is_summary_item', 'is_manual_added',
            'internal_ref_no', 'client_ref_no', 'internal_client_ref_no',
            'internal_description', 'internal_unit', 'internal_quantity',
            'internal_material_rate', 'internal_labour_rate',
            'internal_supply_amount', 'internal_itc_amount',
        ];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $insert[$field] = $data[$field];
            }
        }
        $this->db->insert(db_prefix() . 'project_boq_items', $insert);
        return $this->db->insert_id();
    }

    public function get_by_project_and_item($project_id, $item_id)
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . 'project_boq_items');
        $this->db->where('project_id', (int) $project_id);
        $this->db->where('item_id', (int) $item_id);
        $this->db->limit(1);
        $row = $this->db->get()->row_array();
        return $row ?: null;
    }

    public function get_remaining_qty($boq_item_id)
    {
        $row = $this->get($boq_item_id);
        if (!$row) {
            return 0;
        }

        return max(0, (float) ($row->remaining_qty ?? 0));
    }

    public function add_edit_request(array $data)
    {
        $insert = [
            'project_id'   => (int) $data['project_id'],
            'boq_item_id'  => isset($data['boq_item_id']) && $data['boq_item_id'] !== null ? (int) $data['boq_item_id'] : null,
            'requested_by' => get_staff_user_id(),
            'request_type' => $data['request_type'],
            'new_data'     => json_encode($data['new_data']),
            'status'       => 'pending',
            'created_at'   => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . 'project_boq_edit_requests', $insert);
        return $this->db->insert_id();
    }

    public function get_pending_requests($project_id)
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . 'project_boq_edit_requests');
        $this->db->where('project_id', (int) $project_id);
        $this->db->where('status', 'pending');
        $this->db->order_by('created_at', 'asc');
        return $this->db->get()->result_array();
    }

    public function process_edit_request($request_id, $status, $review_notes = '')
    {
        $request = $this->db->select('*')
            ->from(db_prefix() . 'project_boq_edit_requests')
            ->where('id', (int) $request_id)
            ->get()->row();

        if (!$request || $request->status !== 'pending') {
            return false;
        }

        $this->db->trans_start();

        $this->db->where('id', (int) $request_id);
        $this->db->update(db_prefix() . 'project_boq_edit_requests', [
            'status'       => $status,
            'reviewed_by'  => get_staff_user_id(),
            'reviewed_at'  => date('Y-m-d H:i:s'),
            'review_notes' => $review_notes,
        ]);

        if ($status === 'approved') {
            $new_data = json_decode($request->new_data, true);
            if ($request->request_type === 'add_item') {
                $new_data['is_manual_added'] = 1;
                $this->merge_or_add_row($request->project_id, $new_data['item_id'], $new_data);
            } elseif ($request->request_type === 'change_qty') {
                $this->db->where('id', (int) $request->boq_item_id);
                $this->db->update(db_prefix() . 'project_boq_items', [
                    'order_quantity' => (float) $new_data['order_quantity'],
                    'updated_at'     => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->trans_complete();
        return true;
    }

    public function is_po_restriction_enabled($project_id = null)
    {
        $val = get_option('boq_po_restriction');
        if ($val === '' || $val === null) {
            return true;
        }

        return (int) $val === 1;
    }

    public function set_po_restriction($enabled)
    {
        $val = ((int) $enabled === 1 || $enabled === true || $enabled === '1') ? 1 : 0;
        update_option('boq_po_restriction', $val);

        return true;
    }
}
