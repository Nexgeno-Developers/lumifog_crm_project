<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Admin extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
        $this->load->model('boq/Boq_model', 'project_boq_model');
    }

    public function index($project_id)
    {
        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        $project_id = (int) $project_id;

        if (! $this->can_access_project($project_id)) {
            ajax_access_denied();
        }

        $items = $this->project_boq_model->get_by_project_id($project_id);

        $this->json_response([
            'success' => true,
            'data'    => $items,
        ]);
    }

    public function available_items($project_id)
    {
        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        $project_id = (int) $project_id;
        if ($project_id < 1 || ! $this->can_access_project($project_id)) {
            ajax_access_denied();
        }

        $boq_rows = $this->project_boq_model->get_by_project_id($project_id);
        $grouped  = [];

        foreach ($boq_rows as $row) {
            $item_id = isset($row['item_id']) ? (int) $row['item_id'] : 0;
            if ($item_id < 1) {
                continue;
            }

            $revised = isset($row['_revised_for_display']) ? (float) $row['_revised_for_display']
                : (isset($row['revised_qty']) ? (float) $row['revised_qty'] : 0);
            if ($revised === 0.0 && isset($row['order_quantity'])) {
                $revised = (float) $row['order_quantity'];
            }
            $purchased = isset($row['purchased_qty']) ? (float) $row['purchased_qty'] : 0;
            $remaining = $revised - $purchased;
            if ($remaining <= 0) {
                continue;
            }

            if (! isset($grouped[$item_id])) {
                $grouped[$item_id] = [
                    'item_id'      => $item_id,
                    'item_name'    => (string) ($row['item_description'] ?? ''),
                    'remaining_qty'=> 0,
                ];
            }

            $grouped[$item_id]['remaining_qty'] += $remaining;
        }

        foreach ($grouped as &$item) {
            $item['remaining_qty'] = (float) number_format((float) $item['remaining_qty'], get_decimal_places(), '.', '');
        }
        unset($item);

        $this->json_response([
            'success' => true,
            'data'    => array_values($grouped),
        ]);
    }

    public function add()
    {
        if (! $this->input->is_ajax_request() || ! $this->input->post()) {
            show_404();
        }

        $project_id = (int) $this->input->post('project_id');
        $item_id    = (int) $this->input->post('item_id');
        $planned    = $this->normalize_qty($this->input->post('planned_qty'));

        if (! $this->can_edit_project($project_id)) {
            ajax_access_denied();
        }

        $validation = $this->validate_planned_qty_for_add($planned);
        if ($validation !== true) {
            $this->json_response(['success' => false, 'message' => $validation]);

            exit;
        }

        if ($item_id < 1 || ! $this->item_exists($item_id)) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_invalid_item')]);

            exit;
        }

        $id = $this->project_boq_model->add([
            'project_id'  => $project_id,
            'item_id'     => $item_id,
            'planned_qty' => $planned,
            'revised_qty' => $planned,
        ]);

        if (! $id) {
            $this->json_response(['success' => false, 'message' => _l('something_went_wrong')]);

            exit;
        }

        $row = $this->project_boq_model->get($id);

        $this->json_response([
            'success' => true,
            'message' => _l('added_successfully', _l('project_boq_line')),
            'row'     => $row,
        ]);
    }

    public function update()
    {
        if (! $this->input->is_ajax_request() || ! $this->input->post()) {
            show_404();
        }

        $id         = (int) $this->input->post('id');
        $project_id = (int) $this->input->post('project_id');
        $revised    = $this->normalize_qty($this->input->post('revised_qty'));

        $posted_item = $this->input->post('item_id');
        $item_id     = ($posted_item === null || $posted_item === '' || $posted_item === false)
            ? null
            : (int) $posted_item;

        $existing = $this->project_boq_model->get($id);

        if (! $existing || (int) $existing->project_id !== $project_id) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_line_not_found')]);

            exit;
        }

        if (! $this->can_edit_project($project_id)) {
            ajax_access_denied();
        }

        $purchased = (float) $existing->purchased_qty;

        $validation = $this->validate_revised_qty($revised, $purchased);
        if ($validation !== true) {
            $this->json_response(['success' => false, 'message' => $validation]);

            exit;
        }

        if ($item_id !== null && $item_id > 0 && ! $this->item_exists($item_id)) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_invalid_item')]);

            exit;
        }

        $data = ['revised_qty' => $revised];
        if ($item_id !== null && $item_id > 0) {
            $data['item_id'] = $item_id;
        }

        $this->project_boq_model->update_row($id, $data);

        $row = $this->project_boq_model->get($id);

        $this->json_response([
            'success' => true,
            'message' => _l('updated_successfully', _l('project_boq_line')),
            'row'     => $row,
        ]);
    }

    public function delete()
    {
        if (! $this->input->is_ajax_request() || ! $this->input->post()) {
            show_404();
        }

        $id         = (int) $this->input->post('id');
        $project_id = (int) $this->input->post('project_id');

        $existing = $this->project_boq_model->get($id);

        if (! $existing || (int) $existing->project_id !== $project_id) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_line_not_found')]);

            exit;
        }

        if (! $this->can_edit_project($project_id)) {
            ajax_access_denied();
        }

        $ok = $this->project_boq_model->delete($id);

        $this->json_response([
            'success' => $ok,
            'message' => $ok ? _l('deleted', _l('project_boq_line')) : _l('problem_deleting', _l('project_boq_line_lowercase')),
        ]);
    }

    public function import_csv($project_id)
    {
        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        $project_id = (int) $project_id;
        if (! $this->can_access_project($project_id)) {
            ajax_access_denied();
        }

        if (empty($_FILES['boq_csv']) || $_FILES['boq_csv']['error'] !== UPLOAD_ERR_OK) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_import_no_file')]);
            exit;
        }

        $file = $_FILES['boq_csv']['tmp_name'];
        $fd   = fopen($file, 'r');
        if ($fd === false) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_import_read_error')]);
            exit;
        }

        $header = fgetcsv($fd);
        fclose($fd);

        if ($header === false || empty($header)) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_import_invalid_headers')]);
            exit;
        }

        $normalized = array_map(function ($h) {
            return strtolower(trim($h));
        }, $header);

        $required_keys = [
            'internal ref no', 'client ref no', 'crm item',
            'boq description', 'boq unit', 'order quantity',
            'supply rate', 'itc rate', 'supply amount', 'itc amount',
            'tax', 'summary item',
            '(internal)client ref no', '(internal)description',
            '(internal)unit', '(internal)quantity',
            '(internal)material rate', '(internal)labour rate',
            '(internal)supply amount', '(internal)itc amount',
        ];

        foreach ($required_keys as $req) {
            if (!in_array($req, $normalized, true)) {
                $this->json_response(['success' => false, 'message' => _l('project_boq_import_invalid_headers')]);
                exit;
            }
        }

        $fd = fopen($file, 'r');
        fgetcsv($fd);

        $imported = 0;
        $merged   = 0;
        $errors   = [];

        while (($row = fgetcsv($fd)) !== false) {
            if (count($row) < count($header)) {
                continue;
            }

            $data = [];
            foreach ($header as $idx => $col) {
                $data[$col] = isset($row[$idx]) ? $row[$idx] : null;
            }

            $item_id = 0;
            if (isset($data['CRM ITEM ID'])) {
                $item_id = (int) $data['CRM ITEM ID'];
            } elseif (isset($data['CRM Item'])) {
                $item_id = (int) $data['CRM Item'];
            }
            $order_qty = isset($data['Order Quantity']) ? (float) $data['Order Quantity'] : 0;

            if ($item_id < 1 || $order_qty <= 0) {
                $errors[] = _l('project_boq_import_invalid_row', $item_id > 0 ? $item_id : '');
                continue;
            }

            if (! $this->item_exists($item_id)) {
                $errors[] = _l('project_boq_import_item_not_found', $item_id);
                continue;
            }

            $boq_data = [
                'internal_ref_no'          => $data['Internal Ref No'] ?? null,
                'client_ref_no'            => $data['Client Ref No'] ?? null,
                'boq_description'          => $data['BOQ Description'] ?? null,
                'boq_unit'                 => $data['BOQ Unit'] ?? null,
                'planned_qty'              => $order_qty,
                'revised_qty'              => $order_qty,
                'order_quantity'           => $order_qty,
                'supply_rate'              => isset($data['Supply Rate']) && $data['Supply Rate'] !== '' ? (float) $data['Supply Rate'] : null,
                'itc_rate'                 => isset($data['ITC Rate']) && $data['ITC Rate'] !== '' ? (float) $data['ITC Rate'] : null,
                'supply_amount'            => isset($data['Supply Amount']) && $data['Supply Amount'] !== '' ? (float) $data['Supply Amount'] : null,
                'itc_amount'               => isset($data['ITC Amount']) && $data['ITC Amount'] !== '' ? (float) $data['ITC Amount'] : null,
                'tax_rate'                 => isset($data['TAX']) && $data['TAX'] !== '' ? (float) $data['TAX'] : null,
                'is_summary_item'          => isset($data['Summary Item']) && $data['Summary Item'] !== '' ? (int) $data['Summary Item'] : 0,
                'internal_client_ref_no'   => $data['(internal)Client Ref No'] ?? null,
                'internal_description'     => $data['(internal)Description'] ?? null,
                'internal_unit'            => $data['(internal)Unit'] ?? null,
                'internal_quantity'        => isset($data['(internal)Quantity']) && $data['(internal)Quantity'] !== '' ? (float) $data['(internal)Quantity'] : null,
                'internal_material_rate'   => isset($data['(internal)Material Rate']) && $data['(internal)Material Rate'] !== '' ? (float) $data['(internal)Material Rate'] : null,
                'internal_labour_rate'     => isset($data['(internal)Labour Rate']) && $data['(internal)Labour Rate'] !== '' ? (float) $data['(internal)Labour Rate'] : null,
                'internal_supply_amount'   => isset($data['(internal)Supply Amount']) && $data['(internal)Supply Amount'] !== '' ? (float) $data['(internal)Supply Amount'] : null,
                'internal_itc_amount'      => isset($data['(internal)ITC Amount']) && $data['(internal)ITC Amount'] !== '' ? (float) $data['(internal)ITC Amount'] : null,
            ];

            $existing = $this->project_boq_model->get_by_project_and_item($project_id, $item_id);
            $this->project_boq_model->merge_or_add_row($project_id, $item_id, $boq_data);

            if ($existing) {
                $merged++;
            } else {
                $imported++;
            }
        }

        fclose($fd);

        $this->json_response([
            'success'  => true,
            'message'  => _l('project_boq_import_success', $imported, $merged),
            'errors'   => $errors,
            'imported' => $imported,
            'merged'   => $merged,
        ]);
        exit;
    }

    public function request_edit()
    {
        if (! $this->input->is_ajax_request() || ! $this->input->post()) {
            show_404();
        }

        $project_id   = (int) $this->input->post('project_id');
        $request_type = $this->input->post('request_type');

        if (! $this->can_access_project($project_id)) {
            ajax_access_denied();
        }

        if (!in_array($request_type, ['add_item', 'change_qty'])) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_invalid_request_type')]);
            exit;
        }

        $data = [
            'project_id'   => $project_id,
            'request_type' => $request_type,
        ];

        if ($request_type === 'change_qty') {
            $boq_item_id = (int) $this->input->post('boq_item_id');
            $existing = $this->project_boq_model->get($boq_item_id);
            if (! $existing || (int) $existing->project_id !== $project_id) {
                $this->json_response(['success' => false, 'message' => _l('project_boq_line_not_found')]);
                exit;
            }
            $data['boq_item_id'] = $boq_item_id;
            $new_qty = $this->normalize_qty($this->input->post('order_quantity'));
            $validation = $this->validate_planned_qty_for_add($new_qty);
            if ($validation !== true) {
                $this->json_response(['success' => false, 'message' => $validation]);
                exit;
            }
            $data['new_data'] = ['order_quantity' => $new_qty];
        } elseif ($request_type === 'add_item') {
            $item_id   = (int) $this->input->post('item_id');
            $order_qty = $this->normalize_qty($this->input->post('order_quantity'));

            if ($item_id < 1 || ! $this->item_exists($item_id)) {
                $this->json_response(['success' => false, 'message' => _l('project_boq_invalid_item')]);
                exit;
            }
            $validation = $this->validate_planned_qty_for_add($order_qty);
            if ($validation !== true) {
                $this->json_response(['success' => false, 'message' => $validation]);
                exit;
            }
            $data['new_data'] = [
                'item_id'                  => $item_id,
                'order_quantity'           => $order_qty,
                'boq_description'          => $this->input->post('boq_description'),
                'boq_unit'                 => $this->input->post('boq_unit'),
                'internal_ref_no'          => $this->input->post('internal_ref_no'),
                'client_ref_no'            => $this->input->post('client_ref_no'),
                'supply_rate'              => $this->normalize_qty_or_null($this->input->post('supply_rate')),
                'itc_rate'                 => $this->normalize_qty_or_null($this->input->post('itc_rate')),
                'supply_amount'            => $this->normalize_qty_or_null($this->input->post('supply_amount')),
                'itc_amount'               => $this->normalize_qty_or_null($this->input->post('itc_amount')),
                'tax_rate'                 => $this->normalize_qty_or_null($this->input->post('tax_rate')),
                'is_summary_item'          => (int) $this->input->post('is_summary_item'),
                'internal_client_ref_no'   => $this->input->post('internal_client_ref_no'),
                'internal_description'     => $this->input->post('internal_description'),
                'internal_unit'            => $this->input->post('internal_unit'),
                'internal_quantity'        => $this->normalize_qty_or_null($this->input->post('internal_quantity')),
                'internal_material_rate'   => $this->normalize_qty_or_null($this->input->post('internal_material_rate')),
                'internal_labour_rate'     => $this->normalize_qty_or_null($this->input->post('internal_labour_rate')),
                'internal_supply_amount'   => $this->normalize_qty_or_null($this->input->post('internal_supply_amount')),
                'internal_itc_amount'      => $this->normalize_qty_or_null($this->input->post('internal_itc_amount')),
            ];
        }

        $request_id = $this->project_boq_model->add_edit_request($data);
        if ($request_id) {
            $this->notify_admins_boq_edit_request($request_id, $project_id, $request_type);
            $this->json_response(['success' => true, 'message' => _l('project_boq_edit_request_submitted')]);
        } else {
            $this->json_response(['success' => false, 'message' => _l('something_went_wrong')]);
        }
        exit;
    }

    public function process_edit_request()
    {
        if (! $this->input->is_ajax_request() || ! $this->input->post()) {
            show_404();
        }

        if (! is_admin() && ! staff_can('purchase_order_change_approve_status', '', 'edit')) {
            ajax_access_denied();
        }

        $request_id   = (int) $this->input->post('request_id');
        $status       = $this->input->post('status');
        $review_notes = $this->input->post('review_notes') ?? '';

        if (!in_array($status, ['approved', 'rejected'])) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_invalid_status')]);
            exit;
        }

        $success = $this->project_boq_model->process_edit_request($request_id, $status, $review_notes);
        if ($success) {
            $this->notify_requester_boq_edit_result($request_id, $status);
            $this->json_response(['success' => true, 'message' => _l('project_boq_edit_' . $status)]);
        } else {
            $this->json_response(['success' => false, 'message' => _l('something_went_wrong')]);
        }
        exit;
    }

    protected function notify_admins_boq_edit_request($request_id, $project_id, $request_type)
    {
        $request = $this->db->select('*')
            ->from(db_prefix() . 'project_boq_edit_requests')
            ->where('id', $request_id)
            ->get()->row();

        if (! $request) {
            return;
        }

        $requester = $this->staff_model->get($request->requested_by);
        $project   = $this->projects_model->get($project_id);
        $item_label = '';
        if ($request->boq_item_id) {
            $boq = $this->project_boq_model->get((int) $request->boq_item_id);
            $item_label = $boq->boq_description ?? ('BOQ #' . $request->boq_item_id);
        } else {
            $new_data = json_decode($request->new_data, true);
            $item_label = _l('project_boq_new_item') . ' (Item #' . ($new_data['item_id'] ?? '') . ')';
        }

        $message = _l(
            'pur_boq_po_pending_approval_message',
            $requester->firstname . ' ' . $requester->lastname,
            $request_id,
            $item_label
        );

        $admins = $this->db->select('staffid, firstname, lastname, email')
            ->from(db_prefix() . 'staff')
            ->where('admin', 1)
            ->where('active', 1)
            ->get()->result_array();

        foreach ($admins as $admin) {
            add_notification([
                'description' => $message,
                'touserid'    => $admin['staffid'],
                'fromuserid'  => $requester->staffid,
                'link'        => 'projects/view/' . $project_id . '?group=project_boq',
            ]);
            send_mail_template('boq_po_pending_approval', $admin['email'], $requester, [
                'message'      => $message,
                'pur_order_id' => $request_id,
                'item_names'   => $item_label,
            ]);
        }
    }

    protected function notify_requester_boq_edit_result($request_id, $status)
    {
        $request = $this->db->select('*')
            ->from(db_prefix() . 'project_boq_edit_requests')
            ->where('id', (int) $request_id)
            ->get()->row();

        if (! $request) {
            return;
        }

        $requester = $this->staff_model->get($request->requested_by);
        $project   = $this->projects_model->get($request->project_id);
        $type_label = _l('project_boq_request_type_' . $request->request_type);

        $message = _l(
            'project_boq_edit_request_notification',
            $project->name,
            $type_label,
            _l('project_boq_edit_' . $status)
        );

        add_notification([
            'description' => $message,
            'touserid'    => $request->requested_by,
            'fromuserid'  => get_staff_user_id(),
            'link'        => 'projects/view/' . $request->project_id . '?group=project_boq',
        ]);

        $template_slug = $status === 'approved' ? 'boq_edit_approved' : 'boq_edit_rejected';
        send_mail_template($template_slug, $requester->email, $requester, [
            'project_name'  => $project->name,
            'request_type'  => $type_label,
            'review_notes'  => $request->review_notes,
        ]);
    }

    protected function json_response($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }

    protected function can_access_project($project_id)
    {
        return staff_can('view', 'projects') || $this->projects_model->is_member($project_id);
    }

    protected function can_edit_project($project_id)
    {
        return staff_can('edit', 'projects');
    }

    protected function item_exists($item_id)
    {
        return total_rows(db_prefix() . 'items', ['id' => $item_id]) > 0;
    }

    protected function normalize_qty($value)
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return number_format((float) $value, get_decimal_places(), '.', '');
    }

    protected function normalize_qty_or_null($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, get_decimal_places(), '.', '');
    }

    protected function validate_planned_qty_for_add($planned)
    {
        $planned = (float) ($planned ?? 0);

        if ($planned < 0) {
            return _l('project_boq_planned_qty_non_negative');
        }

        return true;
    }

    protected function validate_revised_qty($revised, $purchased)
    {
        $revised   = (float) $revised;
        $purchased = (float) $purchased;

        if ($revised < 0) {
            return _l('project_boq_revised_qty_non_negative');
        }

        if ($revised < $purchased) {
            return _l('project_boq_revised_not_below_purchased');
        }

        return true;
    }
}
