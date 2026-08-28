<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$aColumns = [
    db_prefix() . 'kpi_transactions.id as id',
    'CONCAT(' . db_prefix() . "staff.firstname,' '," . db_prefix() . 'staff.lastname) as staff_full_name',
    db_prefix() . 'kpi_rules.title as rule_title',
    db_prefix() . 'kpi_rules.module as module',
    db_prefix() . 'kpi_transactions.points as points',
    db_prefix() . 'kpi_transactions.transaction_date as transaction_date',
    db_prefix() . 'kpi_transactions.status as status',
    db_prefix() . 'kpi_transactions.remarks as remarks',
    db_prefix() . 'kpi_transactions.created_at as created_at',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'kpi_transactions';

$join = [
    'LEFT JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'kpi_transactions.staff_id',
    'LEFT JOIN ' . db_prefix() . 'kpi_rules ON ' . db_prefix() . 'kpi_rules.id = ' . db_prefix() . 'kpi_transactions.kpi_rule_id',
];

$where = [];

$staffId = $CI->input->post('staff_id');
if ($staffId !== null && $staffId !== '' && is_numeric($staffId)) {
    $where[] = 'AND ' . db_prefix() . 'kpi_transactions.staff_id=' . (int) $staffId;
}

$module = $CI->input->post('module');
if ($module !== null && $module !== '') {
    $where[] = 'AND ' . db_prefix() . 'kpi_rules.module=' . $CI->db->escape($module);
}

$date = $CI->input->post('date');
if ($date !== null && $date !== '') {
    $sqlDate = to_sql_date($date);
    if ($sqlDate) {
        $where[] = 'AND ' . db_prefix() . 'kpi_transactions.transaction_date=' . $CI->db->escape($sqlDate);
    }
}

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, []);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $id = (int) ($aRow['id'] ?? 0);

    $row[] = $id;
    $row[] = e($aRow['staff_full_name'] ?? '');
    $row[] = e($aRow['rule_title'] ?? '');
    $row[] = e($aRow['module'] ?? '');
    $row[] = (int) ($aRow['points'] ?? 0);

    $txDate = $aRow['transaction_date'] ?? '';
    if ($txDate === '' || $txDate === null || $txDate === '0000-00-00' || $txDate === '0000-00-00 00:00:00' || ! is_date($txDate)) {
        $row[] = '';
    } else {
        $row[] = e(_d(date('Y-m-d', strtotime($txDate))));
    }

    $status = (string) ($aRow['status'] ?? '');
    if ($status === 'approved') {
        $row[] = '<span class="label label-success">Approved</span>';
    } elseif ($status === 'rejected') {
        $row[] = '<span class="label label-danger">Rejected</span>';
    } else {
        $row[] = '<span class="label label-warning">Pending</span>';
    }

    $row[] = e($aRow['remarks'] ?? '');

    $createdAt = $aRow['created_at'] ?? '';
    if ($createdAt === '' || $createdAt === null || $createdAt === '0000-00-00 00:00:00' || ! is_date($createdAt)) {
        $row[] = '';
    } else {
        $row[] = '<span data-toggle="tooltip" data-title="' . e(_dt($createdAt)) . '" class="text-has-action is-date">' . e(time_ago($createdAt)) . '</span>';
    }

    $actions = '';
    if (has_permission('kpi_management', '', 'edit') && $status === 'pending') {
        $actions .= '<button type="button" class="btn btn-success btn-sm" onclick="kpi_update_transaction_status(' . $id . ', \'approved\');"><i class="fa-solid fa-check"></i></button> ';
        $actions .= '<button type="button" class="btn btn-warning btn-sm" onclick="kpi_update_transaction_status(' . $id . ', \'rejected\');"><i class="fa-solid fa-xmark"></i></button> ';
    }
    if (has_permission('kpi_management', '', 'delete')) {
        $actions .= '<button type="button" class="btn btn-danger btn-sm" onclick="kpi_delete_transaction(' . $id . ');"><i class="fa-solid fa-trash"></i></button>';
    }

    $row[] = $actions;

    $output['aaData'][] = $row;
}

