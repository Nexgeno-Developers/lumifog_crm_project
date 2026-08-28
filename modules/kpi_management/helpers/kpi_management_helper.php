<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Whether the current staff member may see the KPI dashboard widget (own or global scope).
 */
function kpi_widget_user_can_view()
{
    if (is_admin()) {
        return true;
    }

    if (!function_exists('has_permission')) {
        return false;
    }

    return has_permission('kpi_widget_overview', '', 'view_own')
        || has_permission('kpi_widget_overview', '', 'view')
        || has_permission('kpi', '', 'view_own')
        || has_permission('kpi', '', 'view');
}

/**
 * Whether the current staff member may see all-staff KPI widget data (same scope as admin).
 */
function kpi_widget_user_can_view_global()
{
    if (is_admin()) {
        return true;
    }

    if (!function_exists('has_permission')) {
        return false;
    }

    return has_permission('kpi_widget_overview', '', 'view')
        || has_permission('kpi', '', 'view');
}

/**
 * Resolve the configured badge color for an attendance points value.
 */
function get_attendance_badge_color($points)
{
    if ($points === null) {
        return 'gray';
    }

    static $attendanceRules = null;

    if ($attendanceRules === null) {
        $CI = &get_instance();
        $rows = $CI->db
            ->select('rules_json')
            ->from(db_prefix() . 'kpi_rules')
            ->where('module', 'attendance')
            ->where('is_active', 1)
            ->get()
            ->result_array();

        $attendanceRules = [];
        foreach ($rows as $row) {
            $decoded = json_decode((string) ($row['rules_json'] ?? ''), true);
            if (is_array($decoded)) {
                $attendanceRules[] = $decoded;
            }
        }
    }

    $allowedColors = [
        'gold',
        'green',
        'lime',
        'gray-light',
        'gray-medium',
        'gray-dark',
        'gray',
    ];

    foreach ($attendanceRules as $rule) {
        $conditions = isset($rule['conditions']) && is_array($rule['conditions'])
            ? $rule['conditions']
            : [];

        foreach ($conditions as $condition) {
            if (!is_array($condition)
                || !array_key_exists('points', $condition)
                || !is_numeric($condition['points'])
                || (float) $condition['points'] !== (float) $points) {
                continue;
            }

            $color = isset($condition['color']) ? trim((string) $condition['color']) : '';

            return in_array($color, $allowedColors, true) ? $color : 'gray';
        }
    }

    return 'gray';
}

/**
 * Build a full shift-start datetime for KPI rule evaluation.
 * Mirrors Kpi_personal_dashboard_model::resolve_shift_start_time_from_ids().
 *
 * @param object|array<string,mixed>|null $shiftType
 */
function kpi_resolve_shift_start_time($shiftType, $date)
{
    if ($shiftType === null) {
        return '';
    }

    $date = date('Y-m-d', strtotime((string) $date));
    if (!$date || $date === '1970-01-01') {
        return '';
    }

    $startWorkHour = is_object($shiftType)
        ? ($shiftType->start_work_hour ?? '')
        : ($shiftType['start_work_hour'] ?? '');

    if ($startWorkHour !== null && trim((string) $startWorkHour) !== '') {
        return $date . ' ' . trim((string) $startWorkHour);
    }

    $stored = trim((string) (is_object($shiftType)
        ? ($shiftType->time_start_work ?? '')
        : ($shiftType['time_start_work'] ?? '')));

    if ($stored === '') {
        return '';
    }

    $parts = explode(' ', $stored);
    $time  = isset($parts[1]) ? $parts[1] : $parts[0];

    if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
        $time .= ':00';
    }

    return $date . ' ' . $time;
}

/**
 * Sum KPI transaction points once per transaction id.
 *
 * @param list<array<string,mixed>> ...$transactionGroups
 */
function kpi_sum_unique_transaction_points(array ...$transactionGroups)
{
    $points = 0;
    $seen   = [];

    foreach ($transactionGroups as $transactions) {
        foreach ($transactions as $transaction) {
            $txId = (int) ($transaction['id'] ?? 0);
            if ($txId > 0) {
                if (isset($seen[$txId])) {
                    continue;
                }
                $seen[$txId] = true;
            }

            $points += (int) ($transaction['points'] ?? 0);
        }
    }

    return $points;
}

/**
 * Load KPI rule, run the rule engine, and persist a KPI transaction when the rule matches.
 *
 * @param string               $module_name KPI rule module (must match the rule row)
 * @param int                    $kpi_rule_id Rule primary key in tblkpi_rules
 * @param array<string, mixed>   $context     Must include staff_id, rel_id; typically checkin_time, shift_start_time; module is merged in by this function
 * @return array{success:bool,inserted:bool,calculation:array,message:string}
 */
function init_kpi_transaction($module_name, $kpi_rule_id, array $context = [])
{
    $CI = &get_instance();

    if (!$CI->app_modules->is_active('kpi_management')) {
        return [
            'success'     => false,
            'inserted'    => false,
            'calculation' => [],
            'message'     => 'KPI Management module is not active',
        ];
    }

    $module_name = trim((string) $module_name);
    $kpi_rule_id = (int) $kpi_rule_id;

    if ($module_name === '' || $kpi_rule_id <= 0) {
        return [
            'success'     => false,
            'inserted'    => false,
            'calculation' => [],
            'message'     => 'Invalid module name or KPI rule id',
        ];
    }

    $staffId = (int) ($context['staff_id'] ?? 0);
    $relId   = (int) ($context['rel_id'] ?? 0);

    if ($staffId <= 0 || $relId <= 0) {
        return [
            'success'     => false,
            'inserted'    => false,
            'calculation' => [],
            'message'     => 'Context must include staff_id and rel_id',
        ];
    }

    $CI->load->model('kpi_management/kpi_rules_model');
    $CI->load->model('kpi_management/kpi_transactions_model');
    $CI->load->library('kpi_management/Kpi_rule_engine');

    $ruleRow = $CI->kpi_rules_model->get($kpi_rule_id);
    if (!$ruleRow) {
        return [
            'success'     => false,
            'inserted'    => false,
            'calculation' => [],
            'message'     => 'KPI rule not found',
        ];
    }

    if ((int) ($ruleRow['is_active'] ?? 0) !== 1) {
        return [
            'success'     => false,
            'inserted'    => false,
            'calculation' => [],
            'message'     => 'KPI rule is inactive',
        ];
    }

    if (($ruleRow['module'] ?? '') !== $module_name) {
        return [
            'success'     => false,
            'inserted'    => false,
            'calculation' => [],
            'message'     => 'KPI rule module does not match',
        ];
    }

    $rulesJson = $ruleRow['rules_json'] ?? null;
    if (is_array($rulesJson)) {
        $ruleJsonForEngine = $rulesJson;
    } else {
        $ruleJsonForEngine = is_string($rulesJson) ? $rulesJson : '';
    }

    $context['module'] = $module_name;

    $calculation = Kpi_rule_engine::calculatePoints($ruleJsonForEngine, $context);

    if (($calculation['status'] ?? '') !== 'success') {
        return [
            'success'     => true,
            'inserted'    => false,
            'calculation' => $calculation,
            'message'     => 'Rule did not match',
        ];
    }

    $checkinTime = (string) ($context['checkin_time'] ?? '');
    $txDate      = $checkinTime !== '' ? date('Y-m-d', strtotime($checkinTime)) : date('Y-m-d');
    if (!$txDate || $txDate === '1970-01-01') {
        $txDate = date('Y-m-d');
    }

    $relType = isset($context['rel_type']) ? (string) $context['rel_type'] : 'check_in_out';
    if ($relType === '') {
        $relType = 'check_in_out';
    }

    $matched = $calculation['matched_condition'] ?? null;
    $remarks = '';
    if ($matched !== null && $matched !== '') {
        //$remarks = is_string($matched) ? $matched : json_encode($matched);
        $remarks = is_string($matched) ? $matched : $matched['remarks'] ?? '';
        if (strlen($remarks) > 255) {
            $remarks = substr($remarks, 0, 252) . '...';
        }
    }

    $uid = function_exists('get_staff_user_id') ? (int) get_staff_user_id() : $staffId;
    $createdBy = $uid > 0 ? $uid : null;

    $insertData = [
        'staff_id'          => $staffId,
        'kpi_rule_id'       => $kpi_rule_id,
        'rel_type'          => $relType,
        'rel_id'            => $relId,
        'points'            => (int) ($calculation['points'] ?? 0),
        'transaction_date'  => $txDate,
        'status'            => $context['status'] ?? 'pending',
        'remarks'           => $remarks,
        'created_by'        => $createdBy,
    ];

    $inserted = $CI->kpi_transactions_model->insertTransactionIfNotExists($insertData);

    return [
        'success'     => true,
        'inserted'    => (bool) $inserted,
        'calculation' => $calculation,
        'message'     => $inserted ? 'KPI transaction recorded' : 'Duplicate or insert skipped',
    ];
}

/**
 * After a staff check-in row is stored: run all active attendance KPI rules.
 *
 * @param int    $staff_id
 * @param string $checkin_time Full datetime string (same as check_in_out.date)
 * @param int    $rel_id       check_in_out.id
 */
function kpi_attendance_after_checkin($staff_id, $checkin_time, $rel_id)
{
    $CI = &get_instance();

    if (!$CI->app_modules->is_active('kpi_management')) {
        return;
    }

    $staff_id     = (int) $staff_id;
    $rel_id       = (int) $rel_id;
    $checkin_time = (string) $checkin_time;

    if ($staff_id <= 0 || $rel_id <= 0 || $checkin_time === '') {
        return;
    }

    $date = date('Y-m-d', strtotime($checkin_time));
    if (!$date || $date === '1970-01-01') {
        $date = date('Y-m-d');
    }

    $CI->load->model('timesheets/timesheets_model');

    //Holiday validation: if it's a holiday, do not insert attendance KPI transaction
    if ($CI->timesheets_model->check_holiday($staff_id, $date)) {
        return;
    }

    //Leave validation: if there's an approved leave for the staff on the date, do not insert attendance KPI transaction
    $CI->db->where('staff_id', $staff_id);
    $CI->db->where('date_work', $date);
    $CI->db->where('relate_type', 'leave');
    $CI->db->limit(1);
    if ($CI->db->get(db_prefix() . 'timesheets_timesheet')->num_rows() > 0) {
        return;
    }

    $leaveReq = $CI->db->query(
        'SELECT id FROM ' . db_prefix() . 'timesheets_requisition_leave WHERE staff_id = ? AND rel_type = 1 AND status = 1 AND DATE(?) BETWEEN DATE(start_time) AND DATE(end_time) LIMIT 1',
        [$staff_id, $date]
    )->row();
    if ($leaveReq) {
        return;
    }

    // No scheduled shift on the check-in date → do not record attendance KPI transactions
    $shiftIds = $CI->timesheets_model->get_shift_work_staff_by_date($staff_id, $date);
    if (empty($shiftIds)) {
        return;
    }

    $CI->load->model('kpi_management/kpi_rules_model');
    $rules = $CI->kpi_rules_model->get_filtered([
        'module'     => 'attendance',
        'is_active'  => 1,
    ]);

    if (empty($rules)) {
        return;
    }

    $st = $CI->timesheets_model->get_shift_type($shiftIds[0]);
    $shift_start_time = kpi_resolve_shift_start_time($st, $date);

    $baseContext = [
        'staff_id'          => $staff_id,
        'checkin_time'      => $checkin_time,
        'shift_start_time'  => $shift_start_time,
        'rel_id'            => $rel_id,
        'rel_type'          => 'check_in_out',
        'status'            => 'approved',
    ];

    $relType = (string) ($baseContext['rel_type'] ?? 'check_in_out');
    $ruleIds = [];
    foreach ($rules as $rule) {
        $rid = (int) ($rule['id'] ?? 0);
        if ($rid > 0) {
            $ruleIds[] = $rid;
        }
    }
    $ruleIds = array_values(array_unique($ruleIds));

    $CI->load->library('kpi_management/Kpi_rule_engine');

    $matchedByRuleId = [];
    foreach ($rules as $rule) {
        $rid = (int) ($rule['id'] ?? 0);
        if ($rid <= 0) {
            continue;
        }

        $rulesJson = $rule['rules_json'] ?? null;
        if (is_array($rulesJson)) {
            $ruleJsonForEngine = $rulesJson;
        } else {
            $ruleJsonForEngine = is_string($rulesJson) ? $rulesJson : '';
        }

        $context = $baseContext;
        $context['module'] = 'attendance';
        $calculation = Kpi_rule_engine::calculatePoints($ruleJsonForEngine, $context);

        if (($calculation['status'] ?? '') === 'success') {
            $matchedByRuleId[$rid] = $calculation;
        }
    }

    $existingRows = [];
    if (!empty($ruleIds)) {
        $existingRows = $CI->db
            ->select('id, kpi_rule_id, points')
            ->from(db_prefix() . 'kpi_transactions')
            ->where('staff_id', $staff_id)
            ->where('rel_type', $relType)
            ->where('transaction_date', $date)
            ->where_in('kpi_rule_id', $ruleIds)
            ->get()
            ->result_array();
    }

    foreach ($existingRows as $row) {
        $existingRuleId = (int) ($row['kpi_rule_id'] ?? 0);
        if ($existingRuleId <= 0 || isset($matchedByRuleId[$existingRuleId])) {
            continue;
        }

        $CI->db->where('id', (int) ($row['id'] ?? 0));
        $CI->db->delete(db_prefix() . 'kpi_transactions');
    }

    foreach ($rules as $rule) {
        $rid = (int) ($rule['id'] ?? 0);
        if ($rid <= 0 || !isset($matchedByRuleId[$rid])) {
            continue;
        }

        $existingTx = null;
        foreach ($existingRows as $row) {
            if ((int) ($row['kpi_rule_id'] ?? 0) === $rid) {
                $existingTx = $row;
                break;
            }
        }

        if ($existingTx) {
            $expectedPoints = (int) ($matchedByRuleId[$rid]['points'] ?? 0);
            if ((int) ($existingTx['points'] ?? 0) === $expectedPoints) {
                continue;
            }

            $matched = $matchedByRuleId[$rid]['matched_condition'] ?? null;
            $remarks = '';
            if ($matched !== null && $matched !== '') {
                $remarks = is_string($matched) ? $matched : ($matched['remarks'] ?? '');
                if (strlen($remarks) > 255) {
                    $remarks = substr($remarks, 0, 252) . '...';
                }
            }

            $CI->db->where('id', (int) ($existingTx['id'] ?? 0));
            $CI->db->update(db_prefix() . 'kpi_transactions', [
                'points'  => $expectedPoints,
                'rel_id'  => $rel_id,
                'remarks' => $remarks !== '' ? $remarks : null,
            ]);
            continue;
        }

        init_kpi_transaction('attendance', $rid, $baseContext);
    }
}

/**
 * Re-evaluate attendance KPI transactions for existing check-ins in a date range.
 * Use after fixing shift-start resolution so stale late/early rows are corrected.
 *
 * @return int Number of check-in days processed
 */
function kpi_reconcile_attendance_transactions_for_staff($staff_id, $fromDate, $toDate)
{
    $CI = &get_instance();

    if (!$CI->app_modules->is_active('kpi_management')) {
        return 0;
    }

    $staff_id  = (int) $staff_id;
    $fromDate  = date('Y-m-d', strtotime((string) $fromDate));
    $toDate    = date('Y-m-d', strtotime((string) $toDate));

    if ($staff_id <= 0 || !$fromDate || !$toDate || $fromDate === '1970-01-01' || $toDate === '1970-01-01') {
        return 0;
    }

    if ($fromDate > $toDate) {
        $tmp      = $fromDate;
        $fromDate = $toDate;
        $toDate   = $tmp;
    }

    $rows = $CI->db
        ->select('MIN(id) as id, MIN(date) as date')
        ->from(db_prefix() . 'check_in_out')
        ->where('staff_id', $staff_id)
        ->where('type_check', 1)
        ->where('DATE(date) >=', $fromDate)
        ->where('DATE(date) <=', $toDate)
        ->group_by('DATE(date)')
        ->order_by('DATE(date)', 'ASC')
        ->get()
        ->result_array();

    $processed = 0;
    foreach ($rows as $row) {
        $relId = (int) ($row['id'] ?? 0);
        $date  = (string) ($row['date'] ?? '');
        if ($relId <= 0 || $date === '') {
            continue;
        }

        kpi_attendance_after_checkin($staff_id, $date, $relId);
        $processed++;
    }

    return $processed;
}

/**
 * Re-evaluate attendance KPI transactions for all active staff in a date range.
 *
 * @param string|null $fromDate Defaults to first day of current month
 * @param string|null $toDate   Defaults to today
 * @return array{staff_total:int,staff_with_checkins:int,days_processed:int,from:string,to:string,staff:list<array{staff_id:int,staff_name:string,days:int}>}
 */
function kpi_reconcile_attendance_transactions_for_all_staff($fromDate = null, $toDate = null)
{
    $CI = &get_instance();

    if (!$CI->app_modules->is_active('kpi_management')) {
        return [
            'staff_total'         => 0,
            'staff_with_checkins' => 0,
            'days_processed'      => 0,
            'from'                => '',
            'to'                  => '',
            'staff'               => [],
        ];
    }

    $fromDate = $fromDate !== null && $fromDate !== ''
        ? date('Y-m-d', strtotime((string) $fromDate))
        : date('Y-m-01');
    $toDate = $toDate !== null && $toDate !== ''
        ? date('Y-m-d', strtotime((string) $toDate))
        : date('Y-m-d');

    if (!$fromDate || !$toDate || $fromDate === '1970-01-01' || $toDate === '1970-01-01') {
        return [
            'staff_total'         => 0,
            'staff_with_checkins' => 0,
            'days_processed'      => 0,
            'from'                => '',
            'to'                  => '',
            'staff'               => [],
        ];
    }

    if ($fromDate > $toDate) {
        $tmp      = $fromDate;
        $fromDate = $toDate;
        $toDate   = $tmp;
    }

    $staffRows = $CI->db
        ->select('staffid, firstname, lastname')
        ->from(db_prefix() . 'staff')
        ->where('active', 1)
        ->order_by('firstname', 'ASC')
        ->order_by('lastname', 'ASC')
        ->get()
        ->result_array();

    $staffSummary = [];
    $daysProcessed = 0;

    foreach ($staffRows as $row) {
        $staffId = (int) ($row['staffid'] ?? 0);
        if ($staffId <= 0) {
            continue;
        }

        $days = kpi_reconcile_attendance_transactions_for_staff($staffId, $fromDate, $toDate);
        if ($days <= 0) {
            continue;
        }

        $name = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        $staffSummary[] = [
            'staff_id'   => $staffId,
            'staff_name' => $name !== '' ? $name : ('#' . $staffId),
            'days'       => $days,
        ];
        $daysProcessed += $days;
    }

    return [
        'staff_total'         => count($staffRows),
        'staff_with_checkins' => count($staffSummary),
        'days_processed'      => $daysProcessed,
        'from'                => $fromDate,
        'to'                  => $toDate,
        'staff'               => $staffSummary,
    ];
}
