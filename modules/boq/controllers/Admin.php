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

        $po_restriction_enabled = method_exists($this->project_boq_model, 'is_po_restriction_enabled')
            ? $this->project_boq_model->is_po_restriction_enabled($project_id)
            : true;

        $for_po = ($this->input->get('for') === 'po');
        if ($for_po && !$po_restriction_enabled) {
            $this->json_response([
                'success'                => true,
                'po_restriction_enabled' => false,
                'data'                   => [],
            ]);
            return;
        }

        $this->json_response([
            'success'                => true,
            'po_restriction_enabled' => $po_restriction_enabled,
            'data'                   => array_values($grouped),
        ]);
    }

    public function change_po_restriction_status($arg1 = '', $arg2 = '')
    {
        if (! is_admin()) {
            ajax_access_denied();
        }

        if ($arg2 !== '') {
            $status = (int) $arg2;
        } elseif ($arg1 !== '') {
            $status = (int) $arg1;
        } else {
            $status = (int) $this->input->post('status');
        }

        $success = $this->project_boq_model->set_po_restriction($status);

        $this->json_response([
            'success' => (bool) $success,
            'message' => _l('project_boq_po_restriction_updated'),
        ]);
    }

    public function change_project_po_restriction($project_id = '')
    {
        if (! $this->input->is_ajax_request() || ! $this->input->post()) {
            show_404();
        }

        if (! is_admin()) {
            ajax_access_denied();
        }

        $project_id = (int) $project_id;
        if ($project_id < 1 || ! $this->can_access_project($project_id)) {
            ajax_access_denied();
        }

        $status  = $this->input->post('status');
        $success = $this->project_boq_model->set_project_po_restriction_setting($project_id, $status);

        $this->json_response([
            'success' => (bool) $success,
            'message' => _l('project_boq_project_restriction_updated'),
        ]);
    }

    public function sync_past_pos($project_id = '')
    {
        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        if (! is_admin()) {
            ajax_access_denied();
        }

        $project_id = (int) $project_id;
        if ($project_id < 1 || ! $this->can_access_project($project_id)) {
            ajax_access_denied();
        }

        $result = $this->project_boq_model->sync_project_past_pos($project_id);

        $this->json_response($result);
    }

    public function get_unmatched_pos($project_id = '')
    {
        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        if (! is_admin()) {
            ajax_access_denied();
        }

        $project_id = (int) $project_id;
        if ($project_id < 1 || ! $this->can_access_project($project_id)) {
            ajax_access_denied();
        }

        $unmatched = $this->project_boq_model->get_project_unmatched_po_items($project_id);

        $this->json_response([
            'success' => true,
            'count'   => count($unmatched),
            'items'   => $unmatched,
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

        $is_nt_item  = $this->input->post('is_nt_item') !== null ? (int) $this->input->post('is_nt_item') : 0;
        $is_billable = $this->input->post('is_billable') !== null ? (int) $this->input->post('is_billable') : 1;

        $id = $this->project_boq_model->add([
            'project_id'  => $project_id,
            'item_id'     => $item_id,
            'planned_qty' => $planned,
            'revised_qty' => $planned,
            'is_nt_item'  => $is_nt_item,
            'is_billable' => $is_billable,
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

        if (! $this->can_access_project($project_id)) {
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

        $is_approver = is_boq_approver();

        if (! $is_approver) {
            $request_data = [
                'project_id'   => $project_id,
                'boq_item_id'  => $id,
                'requested_by' => get_staff_user_id(),
                'request_type' => 'change_qty',
                'new_data'     => [
                    'item_id'     => $item_id,
                    'revised_qty' => $revised,
                ],
            ];

            $request_id = $this->project_boq_model->add_edit_request($request_data);

            if ($request_id) {
                $this->notify_admins_boq_edit_request($request_id, $project_id, 'change_qty');

                $this->json_response([
                    'success'           => true,
                    'approval_required' => true,
                    'message'           => _l('project_boq_revision_submitted_for_approval'),
                    'boq_id'            => $id,
                    'revised_qty'       => $revised,
                ]);
                exit;
            } else {
                $this->json_response(['success' => false, 'message' => _l('something_went_wrong')]);
                exit;
            }
        }

        $data = ['revised_qty' => $revised];
        if ($item_id !== null && $item_id > 0) {
            $data['item_id'] = $item_id;
        }
        if ($this->input->post('is_nt_item') !== null) {
            $data['is_nt_item'] = (int) $this->input->post('is_nt_item');
        }
        if ($this->input->post('is_billable') !== null) {
            $data['is_billable'] = (int) $this->input->post('is_billable');
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

        $file_name = $_FILES['boq_csv']['name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_tmp  = $_FILES['boq_csv']['tmp_name'];

        $rows = [];
        if ($ext === 'csv') {
            $fd = fopen($file_tmp, 'r');
            if ($fd === false) {
                $this->json_response(['success' => false, 'message' => _l('project_boq_import_read_error')]);
                exit;
            }
            while (($line = fgetcsv($fd)) !== false) {
                $rows[] = $line;
            }
            fclose($fd);
        } elseif (in_array($ext, ['xlsx', 'xls', 'ods'])) {
            require_once(APPPATH . 'vendor/autoload.php');
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_tmp);
                $reader->setReadDataOnly(false);
                $spreadsheet = $reader->load($file_tmp);
                $sheet = $spreadsheet->getSheet(0);
                $rows  = $sheet->toArray(null, true, false, false);
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            } catch (\Exception $e) {
                $this->json_response(['success' => false, 'message' => _l('project_boq_import_read_error') . ': ' . $e->getMessage()]);
                exit;
            }
        } else {
            $this->json_response(['success' => false, 'message' => _l('project_boq_import_invalid_file_type')]);
            exit;
        }

        if (empty($rows) || count($rows) < 2) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_import_invalid_headers')]);
            exit;
        }

        $header = array_shift($rows);
        if ($header === false || empty($header)) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_import_invalid_headers')]);
            exit;
        }

        $normalized = array_map(function ($h) {
            return strtolower(trim((string)$h));
        }, $header);

        // Check if header matches official template structure (21 columns)
        // In the template, column index 4 is 'BOQ Description' and index 14 is '(internal)Description'.
        // If users paste or type text into row 1 for these description columns, auto-detect them.
        if (count($header) >= 20) {
            $is_template_layout = (
                isset($normalized[0]) && strpos($normalized[0], 'internal ref') !== false &&
                isset($normalized[1]) && strpos($normalized[1], 'client ref') !== false &&
                isset($normalized[3]) && strpos($normalized[3], 'crm item') !== false &&
                isset($normalized[5]) && strpos($normalized[5], 'unit') !== false &&
                isset($normalized[6]) && strpos($normalized[6], 'quantity') !== false
            );
            if ($is_template_layout) {
                if (!isset($normalized[4]) || $normalized[4] !== 'boq description') {
                    $header[4] = 'BOQ Description';
                    $normalized[4] = 'boq description';
                }
                if (!isset($normalized[14]) || $normalized[14] !== '(internal)description') {
                    $header[14] = '(internal)Description';
                    $normalized[14] = '(internal)description';
                }
            }
        }

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

        $missing_keys = [];
        foreach ($required_keys as $req) {
            if (!in_array($req, $normalized, true)) {
                $missing_keys[] = $req;
            }
        }

        if (!empty($missing_keys)) {
            $this->json_response([
                'success' => false,
                'message' => _l('project_boq_import_invalid_headers') . ' Missing column(s): ' . implode(', ', $missing_keys) . '.'
            ]);
            exit;
        }

        $imported = 0;
        $merged   = 0;
        $errors   = [];

        foreach ($rows as $row) {
            if (count($row) < count($header)) {
                continue;
            }

            $data = [];
            foreach ($header as $idx => $col) {
                $col_key = strtolower(trim((string)$col));
                $data[$col_key] = isset($row[$idx]) ? $row[$idx] : null;
                $data[$col]     = isset($row[$idx]) ? $row[$idx] : null;
            }

            // Positional fallback for descriptions if needed
            if (!isset($data['boq description']) || $data['boq description'] === null) {
                $data['boq description'] = $row[4] ?? null;
            }
            if (!isset($data['(internal)description']) || $data['(internal)description'] === null) {
                $data['(internal)description'] = $row[14] ?? null;
            }

            // Check if entire row is blank
            $is_blank = true;
            foreach ($data as $k => $v) {
                if ($v !== null && trim((string)$v) !== '') {
                    $is_blank = false;
                    break;
                }
            }
            if ($is_blank) {
                continue;
            }

            $item_id = 0;
            $crm_item_id_val = $data['crm item id'] ?? $data['CRM ITEM ID'] ?? ($row[2] ?? null);
            $crm_item_val    = $data['crm item'] ?? $data['CRM Item'] ?? ($row[3] ?? null);

            if ($crm_item_id_val !== null && is_numeric(trim((string)$crm_item_id_val)) && (int)$crm_item_id_val > 0) {
                $item_id = (int) $crm_item_id_val;
            } elseif ($crm_item_val !== null && is_numeric(trim((string)$crm_item_val)) && (int)$crm_item_val > 0) {
                $item_id = (int) $crm_item_val;
            } elseif (!empty($crm_item_val)) {
                // If CRM Item contains description from dropdown lookup
                $desc_clean = trim((string)$crm_item_val);
                $lookup_item = $this->db->select('id')
                    ->from(db_prefix() . 'items')
                    ->where('description', $desc_clean)
                    ->get()->row();
                if ($lookup_item) {
                    $item_id = (int) $lookup_item->id;
                }
            }

            $order_qty_val = $data['order quantity'] ?? $data['Order Quantity'] ?? ($row[6] ?? 0);
            $order_qty = ($order_qty_val !== '' && $order_qty_val !== null) ? (float) $order_qty_val : 0;

            // Silently skip pre-filled template rows that user didn't populate
            if ($item_id < 1 && $order_qty <= 0) {
                continue;
            }

            if ($item_id < 1 || $order_qty <= 0) {
                $errors[] = _l('project_boq_import_invalid_row', $item_id > 0 ? $item_id : (string)($crm_item_val ?? ''));
                continue;
            }

            if (! $this->item_exists($item_id)) {
                $errors[] = _l('project_boq_import_item_not_found', $item_id);
                continue;
            }

            // Item Type (Tender / Non-Tender) - default 0 (Tender)
            $item_type_val = $data['item type'] ?? $data['Item Type'] ?? ($row[21] ?? null);
            $is_nt = 0;
            if ($item_type_val !== null) {
                $cleaned_type = strtolower(trim((string)$item_type_val));
                if (in_array($cleaned_type, ['nt', 'non tender', 'non-tender', '1', 'yes', 'true'], true)) {
                    $is_nt = 1;
                }
            }

            // Billable (Billable / Non-Billable) - default 1 (Billable)
            $billable_val = $data['billable'] ?? $data['Billable'] ?? ($row[22] ?? null);
            $is_billable = 1;
            if ($billable_val !== null) {
                $cleaned_bill = strtolower(trim((string)$billable_val));
                if (in_array($cleaned_bill, ['0', 'no', 'false', 'non billable', 'non-billable', 'nonbillable'], true)) {
                    $is_billable = 0;
                }
            }

            $boq_data = [
                'internal_ref_no'          => $data['internal ref no'] ?? $data['Internal Ref No'] ?? ($row[0] ?? null),
                'client_ref_no'            => $data['client ref no'] ?? $data['Client Ref No'] ?? ($row[1] ?? null),
                'boq_description'          => $data['boq description'] ?? $data['BOQ Description'] ?? ($row[4] ?? null),
                'boq_unit'                 => $data['boq unit'] ?? $data['BOQ Unit'] ?? ($row[5] ?? null),
                'planned_qty'              => $order_qty,
                'revised_qty'              => $order_qty,
                'order_quantity'           => $order_qty,
                'is_nt_item'               => $is_nt,
                'is_billable'              => $is_billable,
                'supply_rate'              => (isset($data['supply rate']) && $data['supply rate'] !== '') ? (float) $data['supply rate'] : ((isset($data['Supply Rate']) && $data['Supply Rate'] !== '') ? (float) $data['Supply Rate'] : null),
                'itc_rate'                 => (isset($data['itc rate']) && $data['itc rate'] !== '') ? (float) $data['itc rate'] : ((isset($data['ITC Rate']) && $data['ITC Rate'] !== '') ? (float) $data['ITC Rate'] : null),
                'supply_amount'            => (isset($data['supply amount']) && $data['supply amount'] !== '') ? (float) $data['supply amount'] : ((isset($data['Supply Amount']) && $data['Supply Amount'] !== '') ? (float) $data['Supply Amount'] : null),
                'itc_amount'               => (isset($data['itc amount']) && $data['itc amount'] !== '') ? (float) $data['itc amount'] : ((isset($data['ITC Amount']) && $data['ITC Amount'] !== '') ? (float) $data['ITC Amount'] : null),
                'tax_rate'                 => (isset($data['tax']) && $data['tax'] !== '') ? (float) $data['tax'] : ((isset($data['TAX']) && $data['TAX'] !== '') ? (float) $data['TAX'] : null),
                'is_summary_item'          => (isset($data['summary item']) && $data['summary item'] !== '') ? (int) $data['summary item'] : ((isset($data['Summary Item']) && $data['Summary Item'] !== '') ? (int) $data['Summary Item'] : 0),
                'internal_client_ref_no'   => $data['(internal)client ref no'] ?? $data['(internal)Client Ref No'] ?? ($row[13] ?? null),
                'internal_description'     => $data['(internal)description'] ?? $data['(internal)Description'] ?? ($row[14] ?? null),
                'internal_unit'            => $data['(internal)unit'] ?? $data['(internal)Unit'] ?? ($row[15] ?? null),
                'internal_quantity'        => (isset($data['(internal)quantity']) && $data['(internal)quantity'] !== '') ? (float) $data['(internal)quantity'] : ((isset($data['(internal)Quantity']) && $data['(internal)Quantity'] !== '') ? (float) $data['(internal)Quantity'] : null),
                'internal_material_rate'   => (isset($data['(internal)material rate']) && $data['(internal)material rate'] !== '') ? (float) $data['(internal)material rate'] : ((isset($data['(internal)Material Rate']) && $data['(internal)Material Rate'] !== '') ? (float) $data['(internal)Material Rate'] : null),
                'internal_labour_rate'     => (isset($data['(internal)labour rate']) && $data['(internal)labour rate'] !== '') ? (float) $data['(internal)labour rate'] : ((isset($data['(internal)Labour Rate']) && $data['(internal)Labour Rate'] !== '') ? (float) $data['(internal)Labour Rate'] : null),
                'internal_supply_amount'   => (isset($data['(internal)supply amount']) && $data['(internal)supply amount'] !== '') ? (float) $data['(internal)supply amount'] : ((isset($data['(internal)Supply Amount']) && $data['(internal)Supply Amount'] !== '') ? (float) $data['(internal)Supply Amount'] : null),
                'internal_itc_amount'      => (isset($data['(internal)itc amount']) && $data['(internal)itc amount'] !== '') ? (float) $data['(internal)itc amount'] : ((isset($data['(internal)ITC Amount']) && $data['(internal)ITC Amount'] !== '') ? (float) $data['(internal)ITC Amount'] : null),
            ];

            $existing = $this->project_boq_model->get_by_project_and_item($project_id, $item_id);
            $this->project_boq_model->merge_or_add_row($project_id, $item_id, $boq_data);

            if ($existing) {
                $merged++;
            } else {
                $imported++;
            }
        }

        $this->json_response([
            'success'  => true,
            'message'  => _l('project_boq_import_success', $imported, $merged),
            'errors'   => $errors,
            'imported' => $imported,
            'merged'   => $merged,
        ]);
        exit;
    }

    /**
     * Download an XLSX template for BOQ import.
     *
     * Sheet 1 "BOQ format" — data-entry sheet with:
     *   • CRM Item column: data-validation dropdown of item descriptions from Sheet 2
     *   • CRM ITEM ID column: INDEX-MATCH formula that auto-fills the ID
     *
     * Sheet 2 "Items" — read-only reference of all inventory items.
     */
    public function download_template($project_id)
    {
        $project_id = (int) $project_id;
        if (! $this->can_access_project($project_id)) {
            show_404();
        }

        require_once(APPPATH . 'vendor/autoload.php');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Lumifog CRM')
            ->setTitle('BOQ Template');

        // ── Fetch all inventory items ──
        $items = $this->db->select('id, description, unit, commodity_code, rate, sku_code, group_id')
            ->from(db_prefix() . 'items')
            ->order_by('description', 'asc')
            ->get()->result_array();

        $item_count = count($items);

        // ── Sheet 2: Items (reference data) ──
        $items_sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Items');
        $spreadsheet->addSheet($items_sheet, 1);

        $items_headers = ['id', 'description', 'unit', 'commodity_code', 'rate', 'sku_code', 'group_id'];
        $items_sheet->fromArray($items_headers, null, 'A1');

        // Style Items header row
        $items_header_style = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '1A252F'],
                ],
            ],
        ];
        $items_sheet->getStyle('A1:G1')->applyFromArray($items_header_style);

        // Write item rows
        if ($item_count > 0) {
            $row_idx = 2;
            foreach ($items as $item) {
                $items_sheet->setCellValue('A' . $row_idx, (int) $item['id']);
                $items_sheet->setCellValue('B' . $row_idx, $item['description'] ?? '');
                $items_sheet->setCellValue('C' . $row_idx, $item['unit'] ?? '');
                $items_sheet->setCellValue('D' . $row_idx, $item['commodity_code'] ?? '');
                $items_sheet->setCellValue('E' . $row_idx, $item['rate'] !== null ? (float) $item['rate'] : '');
                $items_sheet->setCellValue('F' . $row_idx, $item['sku_code'] ?? '');
                $items_sheet->setCellValue('G' . $row_idx, $item['group_id'] !== null ? (int) $item['group_id'] : '');
                $row_idx++;
            }
        }

        // Auto-size Items columns
        foreach (range('A', 'G') as $col) {
            $items_sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Protect Items sheet (read-only)
        $items_sheet->getProtection()->setSheet(true);
        $items_sheet->getProtection()->setPassword('boqref');

        // ── Sheet 1: BOQ format (data entry) ──
        $boq_sheet = $spreadsheet->getSheet(0);
        $boq_sheet->setTitle('BOQ format');

        $boq_headers = [
            'Internal Ref No',       // A
            'Client Ref No',         // B
            'CRM ITEM ID',           // C  (auto-filled by formula)
            'CRM Item',              // D  (dropdown from Items sheet)
            'BOQ Description',       // E
            'BOQ Unit',              // F
            'Order Quantity',        // G
            'Supply Rate',           // H
            'ITC Rate',              // I
            'Supply Amount',         // J
            'ITC Amount',            // K
            'TAX',                   // L
            'Summary Item',          // M
            '(internal)Client Ref No',    // N
            '(internal)Description',      // O
            '(internal)Unit',             // P
            '(internal)Quantity',         // Q
            '(internal)Material Rate',    // R
            '(internal)Labour Rate',      // S
            '(internal)Supply Amount',    // T
            '(internal)ITC Amount',       // U
            'Item Type',                  // V  (Tender / Non-Tender)
            'Billable',                   // W  (Billable / Non-Billable)
        ];

        $boq_sheet->fromArray($boq_headers, null, 'A1');

        // Style BOQ header row — dark navy header with white text
        $boq_header_style = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '1A252F'],
                ],
            ],
        ];
        $boq_sheet->getStyle('A1:W1')->applyFromArray($boq_header_style);

        // Style internal columns (N-U) header with a different accent
        $internal_header_style = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D4AC0D'],  // gold for internal columns
            ],
        ];
        $boq_sheet->getStyle('N1:U1')->applyFromArray($internal_header_style);

        // Style CRM ITEM ID header to indicate it's auto-filled
        $autofill_header_style = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '27AE60'],  // green for auto-fill
            ],
        ];
        $boq_sheet->getStyle('C1')->applyFromArray($autofill_header_style);

        // Auto-size BOQ columns
        foreach (range('A', 'W') as $col) {
            $boq_sheet->getColumnDimension($col)->setWidth(18);
        }

        // Determine data rows range (pre-fill formulas/validations for 500 rows)
        $template_rows = 500;
        $items_last_row = $item_count + 1; // header is row 1

        if ($item_count > 0) {
            // Add data validation (dropdown) for CRM Item column (D2:D501)
            for ($r = 2; $r <= $template_rows + 1; $r++) {
                $cell_coord = 'D' . $r;
                $validation = $boq_sheet->getCell($cell_coord)->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setPromptTitle('Select Item');
                $validation->setPrompt('Choose an item from the inventory list. Start typing to search.');
                $validation->setErrorTitle('Invalid Item');
                $validation->setError('Please select a valid item from the dropdown list.');
                $validation->setFormula1('Items!$B$2:$B$' . $items_last_row);
            }

            // Add INDEX-MATCH formula for CRM ITEM ID column (C2:C501)
            // =IFERROR(INDEX(Items!$A$2:$A$N, MATCH(D2, Items!$B$2:$B$N, 0)), "")
            for ($r = 2; $r <= $template_rows + 1; $r++) {
                $formula = '=IFERROR(INDEX(Items!$A$2:$A$' . $items_last_row
                    . ',MATCH(D' . $r . ',Items!$B$2:$B$' . $items_last_row . ',0)),"")';
                $boq_sheet->setCellValue('C' . $r, $formula);

                // Auto-calculation formulas (blank-aware, user-editable)
                // Supply Amount = Order Quantity * Supply Rate
                $boq_sheet->setCellValue('J' . $r, '=IF(OR(G' . $r . '="",H' . $r . '=""),"",G' . $r . '*H' . $r . ')');
                // ITC Amount = Order Quantity * ITC Rate
                $boq_sheet->setCellValue('K' . $r, '=IF(OR(G' . $r . '="",I' . $r . '=""),"",G' . $r . '*I' . $r . ')');
                // TAX = (Supply Amount + ITC Amount) * 18%
                $boq_sheet->setCellValue('L' . $r, '=IF(AND(J' . $r . '="",K' . $r . '=""),"",SUM(J' . $r . ',K' . $r . ')*18%)');
                // (internal)Supply Amount = (internal)Quantity * (internal)Material Rate
                $boq_sheet->setCellValue('T' . $r, '=IF(OR(Q' . $r . '="",R' . $r . '=""),"",Q' . $r . '*R' . $r . ')');
                // (internal)ITC Amount = (internal)Quantity * (internal)Labour Rate
                $boq_sheet->setCellValue('U' . $r, '=IF(OR(Q' . $r . '="",S' . $r . '=""),"",Q' . $r . '*S' . $r . ')');

                // Item Type (V)
                $type_val = $boq_sheet->getCell('V' . $r)->getDataValidation();
                $type_val->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $type_val->setAllowBlank(true);
                $type_val->setShowDropDown(true);
                $type_val->setFormula1('"Tender,Non-Tender"');
                $boq_sheet->setCellValue('V' . $r, 'Tender');

                // Billable (W)
                $bill_val = $boq_sheet->getCell('W' . $r)->getDataValidation();
                $bill_val->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $bill_val->setAllowBlank(true);
                $bill_val->setShowDropDown(true);
                $bill_val->setFormula1('"Billable,Non-Billable"');
                $boq_sheet->setCellValue('W' . $r, 'Billable');
            }

            // Light green background for auto-filled CRM ITEM ID cells
            $autofill_cell_style = [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F8F5'],
                ],
                'font' => ['color' => ['rgb' => '7F8C8D']],
            ];
            $boq_sheet->getStyle('C2:C' . ($template_rows + 1))->applyFromArray($autofill_cell_style);

        }

        // Freeze header row
        $boq_sheet->freezePane('A2');

        // Set active sheet to BOQ format
        $spreadsheet->setActiveSheetIndex(0);

        // ── Output ──
        $filename = 'BOQ_Template_P' . $project_id . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
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
            $data['new_data'] = ['revised_qty' => $new_qty];
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
                'planned_qty'              => $order_qty,
                'revised_qty'              => $order_qty,
                'is_manual_added'          => 1,
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

            if ($this->can_edit_project($project_id)) {
                $boq_row_id = $this->project_boq_model->merge_or_add_row($project_id, $item_id, $data['new_data']);
                if ($boq_row_id) {
                    $data['status']       = 'approved';
                    $data['reviewed_by']  = get_staff_user_id();
                    $data['reviewed_at']  = date('Y-m-d H:i:s');
                    $data['review_notes'] = 'Added manually by ' . get_staff_full_name(get_staff_user_id());
                    $this->project_boq_model->add_edit_request($data);

                    $row = $this->project_boq_model->get($boq_row_id);
                    $this->json_response([
                        'success' => true,
                        'message' => _l('added_successfully', _l('project_boq_line')),
                        'row'     => $row,
                        'direct'  => true,
                    ]);
                    exit;
                }
            }
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

        if (! is_boq_approver() && ! is_admin() && ! staff_can('purchase_order_change_approve_status', '', 'edit')) {
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
        $raw_item_label = '';
        if ($request->boq_item_id) {
            $boq = $this->project_boq_model->get((int) $request->boq_item_id);
            $raw_item_label = $boq ? ($boq->boq_description ?: 'BOQ #' . $request->boq_item_id) : ('BOQ #' . $request->boq_item_id);
        } else {
            $new_data = json_decode($request->new_data, true);
            $raw_item_label = _l('project_boq_new_item') . ' (Item #' . ($new_data['item_id'] ?? '') . ')';
        }

        $clean_label = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />', '&nbsp;'], ' ', $raw_item_label)));
        $clean_label = preg_replace('/\s+/', ' ', $clean_label);
        $item_label  = mb_strlen($clean_label) > 85 ? (mb_substr($clean_label, 0, 82) . '...') : $clean_label;

        $requester_name = $requester ? ($requester->firstname . ' ' . $requester->lastname) : 'Staff';
        $project_name   = $project ? $project->name : ('Project #' . $project_id);

        if ($request_type === 'change_qty') {
            $message = sprintf(_l('project_boq_revision_notification_admin'), $requester_name, $item_label, $project_name);
        } else {
            $message = _l(
                'pur_boq_po_pending_approval_message',
                $requester_name,
                $request_id,
                $item_label
            );
        }

        $approver_ids = get_boq_approver_ids();
        $this->db->select('staffid, firstname, lastname, email')
            ->from(db_prefix() . 'staff')
            ->where('active', 1)
            ->group_start()
                ->where('admin', 1);
        if (!empty($approver_ids)) {
            $this->db->or_where_in('staffid', $approver_ids);
        }
        $this->db->group_end();
        $notified_staff = $this->db->get()->result_array();

        foreach ($notified_staff as $staff_member) {
            add_notification([
                'description' => $message,
                'touserid'    => $staff_member['staffid'],
                'fromuserid'  => $requester ? $requester->staffid : get_staff_user_id(),
                'link'        => 'projects/view/' . $project_id . '?group=project_boq',
            ]);
            try {
                if (!empty($staff_member['email'])) {
                    send_mail_template('boq_po_pending_approval', $staff_member['email'], $requester, [
                        'message'      => $message,
                        'pur_order_id' => $request_id,
                        'item_names'   => $item_label,
                    ]);
                }
            } catch (Throwable $e) {
                log_message('error', 'BOQ revision email notification failed: ' . $e->getMessage());
            }
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

        $raw_item_label = '';
        if ($request->boq_item_id) {
            $boq = $this->project_boq_model->get((int) $request->boq_item_id);
            $raw_item_label = $boq ? ($boq->boq_description ?: 'BOQ #' . $request->boq_item_id) : ('BOQ #' . $request->boq_item_id);
        } else {
            $new_data = json_decode($request->new_data, true);
            $raw_item_label = _l('project_boq_new_item') . ' (Item #' . ($new_data['item_id'] ?? '') . ')';
        }
        $clean_label = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />', '&nbsp;'], ' ', $raw_item_label)));
        $clean_label = preg_replace('/\s+/', ' ', $clean_label);
        $item_label  = mb_strlen($clean_label) > 85 ? (mb_substr($clean_label, 0, 82) . '...') : $clean_label;

        $type_label   = _l('project_boq_request_type_' . $request->request_type);
        $status_text  = _l('project_boq_edit_' . $status);
        $project_name = $project ? $project->name : ('Project #' . $request->project_id);
        $message      = sprintf('Your BOQ revision for "%s" in project "%s" has been %s.', $item_label, $project_name, $status_text);

        add_notification([
            'description' => $message,
            'touserid'    => $request->requested_by,
            'fromuserid'  => get_staff_user_id(),
            'link'        => 'projects/view/' . $request->project_id . '?group=project_boq',
        ]);

        $template_slug = $status === 'approved' ? 'boq_edit_approved' : 'boq_edit_rejected';
        try {
            if ($requester && !empty($requester->email)) {
                send_mail_template($template_slug, $requester->email, $requester, [
                    'project_name'  => $project ? $project->name : '',
                    'request_type'  => $type_label,
                    'review_notes'  => $request->review_notes,
                ]);
            }
        } catch (Throwable $e) {
            log_message('error', 'BOQ review result email failed: ' . $e->getMessage());
        }
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
