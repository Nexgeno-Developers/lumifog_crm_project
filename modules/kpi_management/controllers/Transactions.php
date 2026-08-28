<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Transactions extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->ensure_kpi_tables();

        $this->load->model('kpi_management/kpi_transactions_model');
        $this->load->model('staff_model');
    }

    public function index()
    {
        if (!has_permission('kpi_management', '', 'view')) {
            access_denied('kpi_management');
        }

        $data['title']   = 'KPI Transactions';
        $data['staff']   = $this->staff_model->get('', ['active' => 1]);
        $data['modules'] = $this->kpi_transactions_model->get_modules();

        $this->load->view('transactions/manage', $data);
    }

    public function table()
    {
        if (!has_permission('kpi_management', '', 'view')) {
            access_denied('kpi_management');
        }

        $this->app->get_table_data(module_views_path('kpi_management', 'tables/kpi_transactions'));
    }

    public function update_status($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        if (!has_permission('kpi_management', '', 'edit')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            die();
        }

        if (!is_numeric($id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid transaction id']);
            die();
        }

        $status = trim((string) $this->input->post('status'));
        $remarks = trim((string) $this->input->post('remarks'));

        $allowed = ['approved', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            die();
        }

        $tx = $this->kpi_transactions_model->get((int) $id);
        if (!$tx) {
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            die();
        }

        // Only allow actioning pending transactions (avoid accidental re-approval)
        if (isset($tx['status']) && $tx['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Only pending transactions can be updated']);
            die();
        }

        $success = $this->kpi_transactions_model->update_status((int) $id, $status, $remarks);

        echo json_encode([
            'success' => (bool) $success,
            'message' => $success ? 'Status updated' : 'No changes were made',
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
            echo json_encode(['success' => false, 'message' => 'Invalid transaction id']);
            die();
        }

        $tx = $this->kpi_transactions_model->get((int) $id);
        if (!$tx) {
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            die();
        }

        $success = $this->kpi_transactions_model->delete((int) $id);

        echo json_encode([
            'success' => (bool) $success,
            'message' => $success ? 'Transaction deleted' : 'Failed to delete transaction',
        ]);
        die();
    }

    private function ensure_kpi_tables()
    {
        if ($this->db->table_exists(db_prefix() . 'kpi_rules') && $this->db->table_exists(db_prefix() . 'kpi_transactions')) {
            return;
        }

        $CI = &get_instance();
        require_once(module_dir_path('kpi_management', 'install.php'));
    }

    function test_kpi_transaction() {

    // ===== TODAY =====
    // $start = date('Y-m-d 00:00:00');
    // $end   = date('Y-m-d 00:00:00', strtotime('+1 day'));

    // ===== YESTERDAY =====
    // $start = date('Y-m-d 00:00:00', strtotime('-1 day'));
    // $end   = date('Y-m-d 00:00:00');

    // ===== CURRENT MONTH =====
    $start = date('Y-m-01 00:00:00');
    $end   = date('Y-m-01 00:00:00', strtotime('+1 month'));

    // ===== LAST MONTH =====
    // $start = date('Y-m-01 00:00:00', strtotime('-1 month'));
    // $end   = date('Y-m-01 00:00:00');    

       $this->db->select('id, staff_id, date, type_check');
        
        // Efficient date filtering (NO DATE() function)
        $this->db->where('date >=', $start);
        $this->db->where('date <',  $end);
        $this->db->where('type_check', 1);

        $records = $this->db->get(db_prefix() . 'check_in_out')->result_array();

        foreach ($records as $data) {
            if (isset($data['type_check']) && (int) $data['type_check'] === 1 && function_exists('kpi_attendance_after_checkin')) {
                kpi_attendance_after_checkin((int) $data['staff_id'], (string) $data['date'], (int)  $data['id']);
            }
        }    


    }
}

