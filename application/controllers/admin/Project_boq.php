<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Project_boq extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
        $this->load->model('project_boq_model');
    }

    /**
     * AJAX: list BOQ rows for a project (JSON).
     *
     * @param int $project_id
     */
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

    /**
     * AJAX: list BOQ items with remaining qty for purchase forms.
     *
     * Returns only rows where remaining_qty > 0, grouped by item_id.
     *
     * @param int $project_id
     */
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
            ? $this->project_boq_model->is_po_restriction_enabled()
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

    /**
     * AJAX: add BOQ line.
     */
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

    /**
     * AJAX: update BOQ line (revised_qty and/or item only; planned_qty is immutable).
     */
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

    /**
     * AJAX: delete BOQ line.
     */
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
        $boq_sheet->getStyle('A1:U1')->applyFromArray($boq_header_style);

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
        foreach (range('A', 'U') as $col) {
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

            // Protect the CRM ITEM ID column cells (formula-only, no manual edit)
            $boq_sheet->getProtection()->setSheet(true);
            $boq_sheet->getProtection()->setPassword('boqtemplate');

            // Unlock all editable cells (everything except column C)
            $unlock_style = ['protection' => ['locked' => \PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED]];
            $boq_sheet->getStyle('A2:B' . ($template_rows + 1))->applyFromArray($unlock_style);
            $boq_sheet->getStyle('D2:U' . ($template_rows + 1))->applyFromArray($unlock_style);
            // Also unlock headers for column A,B,D-U (keep them unlocked for usability)
            $boq_sheet->getStyle('A1:B1')->applyFromArray($unlock_style);
            $boq_sheet->getStyle('D1:U1')->applyFromArray($unlock_style);
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

    public function import_csv($project_id)
    {
        if (empty($_FILES['boq_csv']) && ! $this->input->is_ajax_request()) {
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

        $imported = 0;
        $merged   = 0;
        $errors   = [];

        foreach ($rows as $row) {
            if (count($row) < count($header)) {
                continue;
            }

            $data = [];
            foreach ($header as $idx => $col) {
                $data[$col] = isset($row[$idx]) ? $row[$idx] : null;
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
            if (isset($data['CRM ITEM ID']) && is_numeric(trim((string)$data['CRM ITEM ID'])) && (int)$data['CRM ITEM ID'] > 0) {
                $item_id = (int) $data['CRM ITEM ID'];
            } elseif (isset($data['CRM Item']) && is_numeric(trim((string)$data['CRM Item'])) && (int)$data['CRM Item'] > 0) {
                $item_id = (int) $data['CRM Item'];
            } elseif (!empty($data['CRM Item'])) {
                // If CRM Item contains description from dropdown lookup
                $desc_clean = trim((string)$data['CRM Item']);
                $lookup_item = $this->db->select('id')
                    ->from(db_prefix() . 'items')
                    ->where('description', $desc_clean)
                    ->get()->row();
                if ($lookup_item) {
                    $item_id = (int) $lookup_item->id;
                }
            }

            $order_qty = isset($data['Order Quantity']) && $data['Order Quantity'] !== '' ? (float) $data['Order Quantity'] : 0;

            // Silently skip pre-filled template rows that user didn't populate
            if ($item_id < 1 && $order_qty <= 0) {
                continue;
            }

            if ($item_id < 1 || $order_qty <= 0) {
                $errors[] = _l('project_boq_import_invalid_row', $item_id > 0 ? $item_id : (string)($data['CRM Item'] ?? ''));
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

        $project    = $this->projects_model->get($request->project_id);
        $type_label = _l('project_boq_request_type_' . $request->request_type);
        $message    = _l(
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

    /**
     * @param mixed $value
     * @return string decimal string for DB
     */
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

    /**
     * Baseline planned qty on create only (purchased is always 0).
     *
     * @param string $planned normalized decimal string
     * @return true|string
     */
    protected function validate_planned_qty_for_add($planned)
    {
        $planned = (float) $planned;

        if ($planned < 0) {
            return _l('project_boq_planned_qty_non_negative');
        }

        return true;
    }

    /**
     * Revised qty must be >= purchased_qty.
     *
     * @param string $revised normalized decimal string
     * @param float  $purchased
     * @return true|string
     */
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
