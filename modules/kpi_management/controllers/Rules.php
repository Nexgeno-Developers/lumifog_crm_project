<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Rules extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->ensure_kpi_tables();
        $this->load->model('kpi_management/kpi_rules_model');
    }

    public function index()
    {
        if (!has_permission('kpi_management', '', 'view')) {
            access_denied('kpi_management');
        }

        $data['title'] = 'KPI Rules';
        $data['modules'] = $this->get_rule_modules();
        $this->load->view('rules/manage', $data);
    }

    public function table()
    {
        if (!has_permission('kpi_management', '', 'view')) {
            access_denied('kpi_management');
        }

        // DataTables requests are AJAX
        $this->app->get_table_data(module_views_path('kpi_management', 'tables/kpi_rules'));
    }

    public function get($id)
    {
        if (!has_permission('kpi_management', '', 'view')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            die();
        }

        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $rule = $this->kpi_rules_model->get($id);
        if (!$rule) {
            echo json_encode(['success' => false, 'message' => 'Rule not found']);
            die();
        }

        // JSON columns are normalized by MySQL (no whitespace preserved).
        // Always return pretty JSON for UI/editing.
        if (isset($rule['rules_json']) && is_string($rule['rules_json']) && $rule['rules_json'] !== '') {
            $decoded = json_decode($rule['rules_json'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $rule['rules_json'] = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        echo json_encode(['success' => true, 'rule' => $rule]);
        die();
    }

    public function save()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $id = $this->input->post('id');
        $isEdit = is_numeric($id) && (int) $id > 0;

        if ($isEdit) {
            if (!has_permission('kpi_management', '', 'edit')) {
                echo json_encode(['success' => false, 'message' => _l('access_denied')]);
                die();
            }
        } else {
            if (!has_permission('kpi_management', '', 'create')) {
                echo json_encode(['success' => false, 'message' => _l('access_denied')]);
                die();
            }
        }

        $payload = [
            'title'       => trim((string) $this->input->post('title')),
            'module'      => trim((string) $this->input->post('module')),
            'rules_json'  => (string) $this->input->post('rules_json'),
            'description' => $this->input->post('description', false),
            'is_active'   => $this->input->post('is_active') == '1' ? 1 : 0,
        ];

        $errors = $this->validate_rule_payload($payload);
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $errors,
            ]);
            die();
        }

        // Normalize JSON
        $decoded = json_decode($payload['rules_json'], true);
        $payload['rules_json'] = json_encode($decoded);

        if ($isEdit) {
            $success = $this->kpi_rules_model->update($payload, (int) $id);
            echo json_encode([
                'success' => (bool) $success,
                'message' => $success ? 'Rule updated' : 'No changes were made',
            ]);
            die();
        }

        $newId = $this->kpi_rules_model->add($payload);
        echo json_encode([
            'success' => (bool) $newId,
            'message' => $newId ? 'Rule added' : 'Failed to add rule',
            'id'      => $newId,
        ]);
        die();
    }

    public function delete($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('kpi_management', '', 'delete')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            die();
        }

        if (!is_numeric($id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid rule id']);
            die();
        }

        $success = $this->kpi_rules_model->delete((int) $id);
        echo json_encode([
            'success' => (bool) $success,
            'message' => $success ? 'Rule deleted' : 'Failed to delete rule',
        ]);
        die();
    }

    private function get_rule_modules()
    {
        return [
            'attendance' => 'Attendance',
            // 'sales'      => 'Sales',
            // 'support'    => 'Support',
            //'manual'     => 'Manual',
        ];
    }

    private function validate_rule_payload($payload)
    {
        $errors = [];

        if ($payload['title'] === '' || mb_strlen($payload['title']) > 150) {
            $errors['title'] = 'Title is required (max 150 chars).';
        }

        $allowedModules = array_keys($this->get_rule_modules());
        if ($payload['module'] === '' || !in_array($payload['module'], $allowedModules, true)) {
            $errors['module'] = 'Invalid module selected.';
        }

        if (!in_array((int) $payload['is_active'], [0, 1], true)) {
            $errors['is_active'] = 'Invalid active value.';
        }

        $jsonText = trim((string) $payload['rules_json']);
        if ($jsonText === '') {
            $errors['rules_json'] = 'rules_json is required.';
            return $errors;
        }

        // Allow ANY JSON (object/array/string/number/bool/null) as long as it's valid JSON
        json_decode($jsonText, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors['rules_json'] = 'Invalid JSON.';
            return $errors;
        }

        return $errors;
    }

    private function ensure_kpi_tables()
    {
        if ($this->db->table_exists(db_prefix() . 'kpi_rules') && $this->db->table_exists(db_prefix() . 'kpi_transactions')) {
            return;
        }

        $CI = &get_instance();
        require_once(module_dir_path('kpi_management', 'install.php'));
    }
}

