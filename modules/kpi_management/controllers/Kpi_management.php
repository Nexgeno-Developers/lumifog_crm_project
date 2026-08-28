<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_management extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('kpi_management/kpi_transactions_model');
    }

    public function index()
    {
        if (!has_permission('kpi_management', '', 'view')) {
            access_denied('kpi_management');
        }

        $data['title'] = 'KPI Management';

        $data['total_rules'] = (int) total_rows(db_prefix() . 'kpi_rules');
        $data['total_transactions'] = (int) total_rows(db_prefix() . 'kpi_transactions');

        $data['top_performers_current_month'] = $this->kpi_transactions_model->get_top_performers_current_month(4);
        $data['staff_leaderboard_current_month'] = $this->kpi_transactions_model->get_staff_leaderboard_current_month();

        $this->load->view('dashboard', $data);
    }

    public function rules()
    {
        if (!has_permission('kpi_management', '', 'view')) {
            access_denied('kpi_management');
        }

        redirect(admin_url('kpi_management/rules'));
    }

    public function transactions()
    {
        if (!has_permission('kpi_management', '', 'view')) {
            access_denied('kpi_management');
        }

        redirect(admin_url('kpi_management/transactions'));
    }

}

