<?php

$CI = &get_instance();

$kpiTxTable = db_prefix() . 'kpi_transactions';
$staffTable = db_prefix() . 'staff';

$dateRange = (string) ($CI->input->post('date_range') ?? '');
if ($dateRange === '') {
    $dateRange = 'this_month';
}

$fromDate = '';
$toDate   = '';

if ($dateRange === 'today') {
    $fromDate = date('Y-m-d');
    $toDate   = $fromDate;
} elseif ($dateRange === 'yesterday') {
    $fromDate = date('Y-m-d', strtotime('-1 day'));
    $toDate   = $fromDate;
} elseif ($dateRange === 'this_month') {
    $fromDate = date('Y-m-01');
    $toDate   = date('Y-m-t');
} elseif ($dateRange === 'last_month') {
    $fromDate = date('Y-m-01', strtotime('first day of last month'));
    $toDate   = date('Y-m-t', strtotime('last day of last month'));
} elseif ($dateRange === 'this_year') {
    $fromDate = date('Y-01-01');
    $toDate   = date('Y-12-31');
} elseif ($dateRange === 'last_year') {
    $y = (int) date('Y') - 1;
    $fromDate = $y . '-01-01';
    $toDate   = $y . '-12-31';
} elseif ($dateRange === 'custom') {
    $fromDate = to_sql_date($CI->input->post('report_from'));
    $toDate   = to_sql_date($CI->input->post('report_to'));

    if (empty($fromDate)) {
        $fromDate = date('Y-m-d');
    }
    if (empty($toDate)) {
        $toDate = $fromDate;
    }

    // Ensure proper ordering
    if ($fromDate > $toDate) {
        $tmp       = $fromDate;
        $fromDate = $toDate;
        $toDate   = $tmp;
    }
} else {
    $fromDate = date('Y-m-01');
    $toDate   = date('Y-m-t');
}

$fromEsc = $CI->db->escape_str($fromDate);
$toEsc   = $CI->db->escape_str($toDate);

/*
 * Pre-aggregate totals per staff inside a derived table to keep DataTables
 * pagination/counting correct.
 */
$derivedTotals = '(SELECT staff_id, SUM(points) as total_points
FROM `' . $kpiTxTable . '`
WHERE status = "approved"
  AND transaction_date BETWEEN "' . $fromEsc . '" AND "' . $toEsc . '"
GROUP BY staff_id) as totals';

$aColumns = [
    'CONCAT(' . $staffTable . '.firstname,\' \',' . $staffTable . '.lastname) as staff_full_name',
    'totals.total_points as total_points',
];

$sIndexColumn = 'staff_full_name';
$sTable       = $derivedTotals;
$join          = [
    'LEFT JOIN `' . $staffTable . '` ON `' . $staffTable . '`.`staffid` = totals.staff_id',
];

$where = [];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, []);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $row[] = e($aRow['staff_full_name'] ?? '');
    $row[] = (int) ($aRow['total_points'] ?? 0);

    $output['aaData'][] = $row;
}

