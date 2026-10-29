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
            'is_nt_item'    => isset($data['is_nt_item']) ? (int) $data['is_nt_item'] : 0,
            'is_billable'   => isset($data['is_billable']) ? (int) $data['is_billable'] : 1,
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
                $update['order_quantity'] = (float) $data['order_quantity'] + (float) ($existing['order_quantity'] ?? 0);
            }
            $fields = [
                'boq_description', 'boq_unit', 'supply_rate', 'itc_rate',
                'supply_amount', 'itc_amount', 'tax_rate', 'is_summary_item', 'is_manual_added',
                'is_nt_item', 'is_billable',
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
            'project_id' => (int) $project_id,
            'item_id'    => (int) $item_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
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
        if (!$this->db->table_exists(db_prefix() . 'project_boq_edit_requests')) {
            return [];
        }
        $this->db->select('*');
        $this->db->from(db_prefix() . 'project_boq_edit_requests');
        $this->db->where('project_id', (int) $project_id);
        $this->db->where('status', 'pending');
        $this->db->order_by('created_at', 'asc');
        return $this->db->get()->result_array();
    }

    public function get_pending_requests_by_staff($project_id, $staff_id)
    {
        if (!$this->db->table_exists(db_prefix() . 'project_boq_edit_requests')) {
            return [];
        }
        $this->db->select('*');
        $this->db->from(db_prefix() . 'project_boq_edit_requests');
        $this->db->where('project_id', (int) $project_id);
        $this->db->where('requested_by', (int) $staff_id);
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
                $revised_qty = isset($new_data['revised_qty']) ? (float)$new_data['revised_qty'] : (float)($new_data['order_quantity'] ?? 0);
                $update_fields = [
                    'revised_qty' => $revised_qty,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ];
                $this->db->where('id', (int) $request->boq_item_id);
                $this->db->update(db_prefix() . 'project_boq_items', $update_fields);
            }
        }

        $this->db->trans_complete();
        return true;
    }

    public function get_project_po_restriction_setting($project_id)
    {
        $project_id = (int) $project_id;
        if ($project_id < 1) {
            return 'inherit';
        }

        if (!$this->db->table_exists(db_prefix() . 'project_settings')) {
            return 'inherit';
        }

        $this->db->where('project_id', $project_id);
        $this->db->where('name', 'boq_enforce_restriction');
        $row = $this->db->get(db_prefix() . 'project_settings')->row();

        if ($row && in_array((string) $row->value, ['inherit', '1', '0'], true)) {
            return (string) $row->value;
        }

        return 'inherit';
    }

    public function set_project_po_restriction_setting($project_id, $value)
    {
        $project_id = (int) $project_id;
        if ($project_id < 1) {
            return false;
        }

        $valid_values = ['inherit', '1', '0'];
        if (!in_array((string) $value, $valid_values, true)) {
            $value = 'inherit';
        }

        if (!$this->db->table_exists(db_prefix() . 'project_settings')) {
            return false;
        }

        $this->db->where('project_id', $project_id);
        $this->db->where('name', 'boq_enforce_restriction');
        $existing = $this->db->get(db_prefix() . 'project_settings')->row();

        if ($existing) {
            $this->db->where('id', $existing->id);
            $this->db->update(db_prefix() . 'project_settings', ['value' => (string) $value]);
        } else {
            $this->db->insert(db_prefix() . 'project_settings', [
                'project_id' => $project_id,
                'name'       => 'boq_enforce_restriction',
                'value'      => (string) $value,
            ]);
        }

        return true;
    }

    public function is_po_restriction_enabled($project_id = null)
    {
        if ($project_id && (int) $project_id > 0) {
            $project_setting = $this->get_project_po_restriction_setting($project_id);
            if ($project_setting === '1') {
                return true;
            }
            if ($project_setting === '0') {
                return false;
            }
            // 'inherit' falls back to global setting below
        }

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

    /**
     * Scan approved POs for this project and reconcile purchased_qty with BOQ lines.
     *
     * @param int $project_id
     * @return array
     */
    public function sync_project_past_pos($project_id)
    {
        $project_id = (int) $project_id;
        if ($project_id < 1) {
            return [
                'success' => false,
                'message' => 'Invalid project ID',
            ];
        }

        if (!$this->db->table_exists(db_prefix() . 'project_boq_items') || !$this->db->table_exists(db_prefix() . 'boq_purchase_links')) {
            return [
                'success' => false,
                'message' => 'BOQ database tables do not exist',
            ];
        }

        // 1. Get all approved Purchase Orders for this project
        $this->db->select('po.id as pur_order_id, po.pur_order_number, pod.id as purchase_item_id, pod.item_code, pod.quantity, pod.description');
        $this->db->from(db_prefix() . 'pur_orders po');
        $this->db->join(db_prefix() . 'pur_order_detail pod', 'pod.pur_order = po.id', 'inner');
        $this->db->where('po.project', $project_id);
        $this->db->where('po.approve_status', 2);
        $po_items = $this->db->get()->result_array();

        if (empty($po_items)) {
            return [
                'success'              => true,
                'synced_count'         => 0,
                'already_synced_count' => 0,
                'unmatched_count'      => 0,
                'unmatched_items'      => [],
                'message'              => _l('project_boq_sync_no_approved_pos'),
            ];
        }

        // 2. Get existing links
        $this->db->select('purchase_item_id');
        $this->db->from(db_prefix() . 'boq_purchase_links');
        $existing_links = $this->db->get()->result_array();
        $linked_map     = array_flip(array_column($existing_links, 'purchase_item_id'));

        // 3. Get all BOQ items for this project
        $this->db->select('id, item_id, planned_qty, revised_qty, purchased_qty, boq_description');
        $this->db->from(db_prefix() . 'project_boq_items');
        $this->db->where('project_id', $project_id);
        $boq_items = $this->db->get()->result_array();

        $boq_by_item = [];
        foreach ($boq_items as $bItem) {
            $iId = (int) $bItem['item_id'];
            if ($iId > 0 && !isset($boq_by_item[$iId])) {
                $boq_by_item[$iId] = $bItem;
            }
        }

        $synced_count         = 0;
        $already_synced_count = 0;
        $unmatched_items      = [];
        $qty_to_add_by_boq_id = [];

        $this->db->trans_start();

        foreach ($po_items as $line) {
            $purchase_item_id = (int) ($line['purchase_item_id'] ?? 0);
            $item_id          = (int) ($line['item_code'] ?? 0);
            $qty              = (float) ($line['quantity'] ?? 0);

            if ($purchase_item_id < 1 || $qty <= 0) {
                continue;
            }

            // Idempotency: skip if already linked
            if (isset($linked_map[$purchase_item_id])) {
                $already_synced_count++;
                continue;
            }

            if ($item_id > 0 && isset($boq_by_item[$item_id])) {
                $boq_row     = $boq_by_item[$item_id];
                $boq_item_id = (int) $boq_row['id'];

                $this->db->insert(db_prefix() . 'boq_purchase_links', [
                    'purchase_item_id' => $purchase_item_id,
                    'boq_item_id'      => $boq_item_id,
                    'purchase_id'      => (int) $line['pur_order_id'],
                    'qty'              => number_format($qty, 4, '.', ''),
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);

                if (!isset($qty_to_add_by_boq_id[$boq_item_id])) {
                    $qty_to_add_by_boq_id[$boq_item_id] = 0;
                }
                $qty_to_add_by_boq_id[$boq_item_id] += $qty;
                $synced_count++;
            } else {
                $unmatched_items[] = [
                    'pur_order_number' => $line['pur_order_number'],
                    'item_code'        => $item_id,
                    'description'      => !empty($line['description']) ? $line['description'] : ('Item #' . $item_id),
                    'quantity'         => $qty,
                ];
            }
        }

        // Apply accumulated purchased_qty updates to project_boq_items
        foreach ($qty_to_add_by_boq_id as $boq_id => $add_qty) {
            $this->db->set('purchased_qty', 'purchased_qty + ' . (float) $add_qty, false);
            $this->db->where('id', $boq_id);
            $this->db->update(db_prefix() . 'project_boq_items');
        }

        $this->db->trans_complete();

        return [
            'success'              => true,
            'synced_count'         => $synced_count,
            'already_synced_count' => $already_synced_count,
            'unmatched_count'      => count($unmatched_items),
            'unmatched_items'      => $unmatched_items,
            'message'              => sprintf(_l('project_boq_sync_success_message'), $synced_count, $already_synced_count),
        ];
    }

    /**
     * Get list of approved PO items that are not in the project BOQ and not linked.
     *
     * @param int $project_id
     * @return array
     */
    public function get_project_unmatched_po_items($project_id)
    {
        $project_id = (int) $project_id;
        if ($project_id < 1 || !$this->db->table_exists(db_prefix() . 'pur_orders')) {
            return [];
        }

        $this->db->select('po.id as pur_order_id, po.pur_order_number, pod.id as purchase_item_id, pod.item_code, pod.quantity, pod.description');
        $this->db->from(db_prefix() . 'pur_orders po');
        $this->db->join(db_prefix() . 'pur_order_detail pod', 'pod.pur_order = po.id', 'inner');
        $this->db->where('po.project', $project_id);
        $this->db->where('po.approve_status', 2);
        $po_items = $this->db->get()->result_array();

        if (empty($po_items)) {
            return [];
        }

        $this->db->select('purchase_item_id');
        $this->db->from(db_prefix() . 'boq_purchase_links');
        $existing_links = $this->db->get()->result_array();
        $linked_map     = array_flip(array_column($existing_links, 'purchase_item_id'));

        $this->db->select('id, item_id');
        $this->db->from(db_prefix() . 'project_boq_items');
        $this->db->where('project_id', $project_id);
        $boq_items = $this->db->get()->result_array();

        $boq_by_item = [];
        foreach ($boq_items as $bItem) {
            $iId = (int) $bItem['item_id'];
            if ($iId > 0 && !isset($boq_by_item[$iId])) {
                $boq_by_item[$iId] = true;
            }
        }

        $unmatched = [];
        foreach ($po_items as $line) {
            $purchase_item_id = (int) ($line['purchase_item_id'] ?? 0);
            $item_id          = (int) ($line['item_code'] ?? 0);
            $qty              = (float) ($line['quantity'] ?? 0);

            if ($purchase_item_id < 1 || $qty <= 0) {
                continue;
            }

            if (isset($linked_map[$purchase_item_id])) {
                continue;
            }

            if ($item_id <= 0 || !isset($boq_by_item[$item_id])) {
                $unmatched[] = [
                    'pur_order_number' => $line['pur_order_number'],
                    'item_code'        => $item_id,
                    'description'      => !empty($line['description']) ? $line['description'] : ('Item #' . $item_id),
                    'quantity'         => $qty,
                ];
            }
        }

        return $unmatched;
    }
}
