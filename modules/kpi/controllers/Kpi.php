<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kpi extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (!has_permission('kpi', '', 'view_own') && 
            !has_permission('kpi', '', 'view') && 
            !is_admin()) {
            access_denied('KPI');
        }
        
        $this->load->model('kpi_model');
    }

    public function index()
    {
        $data['is_admin'] = is_admin();
        $data['can_view_global'] = has_permission('kpi', '', 'view') || is_admin();
        
        if ($data['is_admin'] || $data['can_view_global']) {
            $data['title'] = 'Attendance KPI - All Staff';
            $data['staff_members'] = $this->staff_model->get();
            $data['show_full_filters'] = true;
        } else {
            $staff_name = get_staff_full_name(get_staff_user_id());
            $data['title'] = 'My Attendance - ' . $staff_name;
            $data['show_full_filters'] = false;
        }
        
        $data['months'] = [];
        for ($i = 0; $i < 12; $i++) {
            $timestamp = strtotime("-$i months");
            $data['months'][] = [
                'id' => date('Y-m', $timestamp),
                'name' => date('F Y', $timestamp)
            ];
        }

        $this->load->view('kpi/attendance/manage', $data);
    }

 public function table()
{
    if (!is_staff_logged_in()) {
        ajax_access_denied();
    }

    if (!has_permission('kpi', '', 'view_own') && 
        !has_permission('kpi', '', 'view') && 
        !is_admin()) {
        ajax_access_denied();
    }

    $view = $this->input->get('view') ?: 'daily';
    $month = $this->input->get('month') ?: '';
    $staff_id = $this->input->get('staff_id') ?: '';
    $from_date = $this->input->get('from_date') ?: '';
    $to_date = $this->input->get('to_date') ?: '';
    $status_filter = $this->input->get('status_filter') ?: '';
    $start = $this->input->get('start');
    $length = $this->input->get('length');
    $search_value = $this->input->get('search')['value'] ?? '';

    $can_view_global = has_permission('kpi', '', 'view') || is_admin();
    
    if (!$can_view_global) {
        $staff_id = get_staff_user_id();
    }

    // Auto-detect view type based on filters
    if (empty($staff_id) && $view === 'daily') {
        // If all staff selected, force monthly view
        $view = 'monthly';
    }

    // Get data from model
    $data = $this->kpi_model->get_datatable_data([
        'view' => $view,
        'month' => $month,
        'staff_id' => $staff_id,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'status_filter' => $status_filter,
        'start' => $start,
        'length' => $length,
        'search' => $search_value,
        'can_view_global' => $can_view_global
    ]);

    // Get counts
    $total_records = $this->kpi_model->count_all_records([
        'view' => $view,
        'month' => $month,
        'staff_id' => $staff_id,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'status_filter' => $status_filter,
        'can_view_global' => $can_view_global
    ]);

    $filtered_records = $this->kpi_model->count_filtered_records([
        'view' => $view,
        'month' => $month,
        'staff_id' => $staff_id,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'status_filter' => $status_filter,
        'search' => $search_value,
        'can_view_global' => $can_view_global
    ]);

    // Handle empty data
    if (empty($data)) {
        $output = [
            'draw' => intval($this->input->get('draw')) ?: 1,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ];
        echo json_encode($output);
        return;
    }

    $output = [
        'draw' => intval($this->input->get('draw')) ?: 1,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $filtered_records,
        'data' => [],
    ];

    foreach ($data as $row) {
        $table_row = [];
        
        $table_row[] = $row['staff_name'];
        
        // Date column - show range for monthly/range views, single date for daily
        if ($view === 'monthly' || $view === 'range_total') {
            $table_row[] = $row['date'];
        } else {
            $table_row[] = '<span class="text-has-action" data-toggle="tooltip" data-title="' . _dt($row['date']) . '">' . _d($row['date']) . '</span>';
        }
        
        // Check-in column logic
        if ($view === 'daily') {
            // Daily view - always show check-in time
            $check_in_color = 'text-' . ($row['color'] === 'gray' ? 'muted' : ($row['color'] === 'green' ? 'success' : ($row['color'] === 'yellow' ? 'warning' : 'danger')));
            $table_row[] = '<span class="' . $check_in_color . '">' . ($row['check_in'] ?: '--') . '</span>';
        } elseif (empty($staff_id) && !empty($from_date) && !empty($to_date) && $from_date === $to_date) {
            // All Staff + Single Day - show actual check-in time
            $check_in_color = 'text-' . ($row['color'] === 'gray' ? 'muted' : ($row['color'] === 'green' ? 'success' : ($row['color'] === 'yellow' ? 'warning' : 'danger')));
            $table_row[] = '<span class="' . $check_in_color . '">' . ($row['check_in'] ?: '--') . '</span>';
        } else {
            // Monthly view or date range - show --
            $table_row[] = '--';
        }
        
        // Check-out column logic  
        if ($view === 'daily') {
            // Daily view - always show check-out time
            $table_row[] = $row['check_out'] ?: '--';
        } elseif (empty($staff_id) && !empty($from_date) && !empty($to_date) && $from_date === $to_date) {
            // All Staff + Single Day - show actual check-out time
            $table_row[] = $row['check_out'] ?: '--';
        } else {
            // Monthly view or date range - show --
            $table_row[] = '--';
        }
        
        // Always show total hours
        $table_row[] = '<span class="bold">' . $row['total_hours'] . '</span>';
        
        $output['data'][] = $table_row;
    }

    echo json_encode($output);
}
    public function get_monthly_total_hours()
    {
        if (!is_staff_logged_in()) {
            echo json_encode(['success' => false, 'total_hours' => 0]);
            return;
        }

        $month = $this->input->get('month') ?: date('Y-m');
        $staff_id = get_staff_user_id();

        $monthly_data = $this->kpi_model->get_datatable_data([
            'view' => 'monthly',
            'month' => $month,
            'staff_id' => $staff_id
        ]);

        $total_hours = 0;
        
        if (!empty($monthly_data)) {
            $total_hours = $monthly_data[0]['total_hours'];
        }

        echo json_encode([
            'success' => true,
            'total_hours' => $total_hours,
            'month' => $month
        ]);
    }

    public function staff_attendance()
    {
        redirect(admin_url('kpi'));
    }
}