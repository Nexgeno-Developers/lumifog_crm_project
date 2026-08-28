<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Personal_dashboard extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('kpi_management/kpi_personal_dashboard_model');
    }

    public function index()
    {
        if (!is_staff_logged_in()) {
            access_denied('kpi_management');
        }

        $staffId = (int) get_staff_user_id();
        if ($staffId <= 0) {
            access_denied('kpi_management');
        }

        $year  = (int) $this->input->get('year');
        $month = (int) $this->input->get('month');

        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $dashboard = $this->kpi_personal_dashboard_model->get_dashboard_data($staffId, $year, $month);

        $periodTs = strtotime(sprintf('%04d-%02d-01', $year, $month));
        $prevTs   = strtotime('-1 month', $periodTs);
        $nextTs   = strtotime('+1 month', $periodTs);

        $data['title']       = 'Personal Dashboard';
        $data['year']        = $year;
        $data['month']       = $month;
        $data['summary']     = $dashboard['summary'];
        $data['dates']       = $dashboard['dates'];
        $data['chart']       = $dashboard['chart'];
        $data['period']      = $dashboard['period'];
        $data['prev_url']    = admin_url('kpi_management/personal_dashboard?year=' . (int) date('Y', $prevTs) . '&month=' . (int) date('n', $prevTs));
        $data['next_url']    = admin_url('kpi_management/personal_dashboard?year=' . (int) date('Y', $nextTs) . '&month=' . (int) date('n', $nextTs));
        $data['current_url'] = admin_url('kpi_management/personal_dashboard?year=' . (int) date('Y') . '&month=' . (int) date('n'));

        $this->app_scripts->add('chart-js', 'assets/plugins/Chart.js/Chart.bundle.js');

        $this->load->view('personal_dashboard/index', $data);
    }
}
