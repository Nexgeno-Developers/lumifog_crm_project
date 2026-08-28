<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_reconcile_attendance_transactions_for_staff extends AdminController
{
    public function index()
    {
        if (!is_admin() && !has_permission('kpi_management', '', 'edit')) {
            access_denied('kpi_management');
        }

        $this->load->helper('kpi_management/kpi_management');

        $fromDate = trim((string) $this->input->get('from'));
        $toDate   = trim((string) $this->input->get('to'));

        $result = kpi_reconcile_attendance_transactions_for_all_staff(
            $fromDate !== '' ? $fromDate : null,
            $toDate !== '' ? $toDate : null
        );

        $data['title']  = 'KPI Attendance Reconcile';
        $data['result'] = $result;

        $this->load->view('reconcile_attendance', $data);
    }
}
