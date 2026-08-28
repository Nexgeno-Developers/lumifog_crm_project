<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Reports extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->ensure_kpi_tables();
    }

    public function index()
    {
        if (!has_permission('kpi_management', '', 'view')) {
            access_denied('kpi_management');
        }

        $data['title'] = 'KPI Reports';
        $this->load->view('reports/index', $data);
    }

    public function top_performers()
    {
        if (!has_permission('kpi_management', '', 'view')) {
            access_denied('kpi_management');
        }

        $data['title'] = 'Top Performer of the Month';
        $this->load->view('reports/top_performers', $data);
    }

    public function top_performers_table()
    {
        if (!has_permission('kpi_management', '', 'view')) {
            access_denied('kpi_management');
        }

        $this->app->get_table_data(module_views_path('kpi_management', 'tables/kpi_top_performers'));
    }

    private function ensure_kpi_tables()
    {
        if ($this->db->table_exists(db_prefix() . 'kpi_rules') && $this->db->table_exists(db_prefix() . 'kpi_transactions')) {
            return;
        }

        require_once(module_dir_path('kpi_management', 'install.php'));
    }
}

