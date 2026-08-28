<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dashboard widget data: today's check-in, org attendance stats, staff list popups.
 * Reads external tables only (staff, check_in_out, timesheets_requisition_leave, shifts).
 */
class Kpi_widget_model extends App_Model
{
    public function get_today_checkin_status($staff_id = null)
    {
        if (!$staff_id) {
            $staff_id = get_staff_user_id();
        }

        $today = $this->widget_today();

        $this->db->select('MIN(date) AS first_checkin', false);
        $this->db->from(db_prefix() . 'check_in_out');
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('type_check', 1);
        $this->where_date_equals('DATE(date)', $today);
        $row = $this->db->get()->row_array();

        if (!$row || empty($row['first_checkin'])) {
            return [
                'checked_in' => false,
                'time'       => '--',
                'msg'        => 'Not checked in today',
            ];
        }

        return [
            'checked_in' => true,
            'time'       => date('H:i', strtotime($row['first_checkin'])),
            'msg'        => 'Checked in at ' . date('H:i', strtotime($row['first_checkin'])),
        ];
    }

    public function get_staff_statistics()
    {
        $today = $this->widget_today();

        $this->db->from(db_prefix() . 'staff');
        $this->db->where('active', 1);
        $total_active = (int) $this->db->count_all_results();

        $this->db->select('COUNT(DISTINCT cio.staff_id) as present', false);
        $this->db->from(db_prefix() . 'check_in_out cio');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = cio.staff_id', 'inner');
        $this->where_date_equals('DATE(cio.date)', $today);
        $this->db->where('cio.type_check', 1);
        $this->db->where('s.active', 1);
        $result_present = $this->db->get()->row_array();
        $present = (int) ($result_present['present'] ?? 0);

        $on_leave = 0;
        if ($this->db->table_exists(db_prefix() . 'timesheets_requisition_leave')) {
            $this->db->select('COUNT(DISTINCT trl.staff_id) as on_leave', false);
            $this->db->from(db_prefix() . 'timesheets_requisition_leave trl');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = trl.staff_id', 'inner');
            $this->db->group_start();
            $this->db->where('trl.status', 0);
            $this->db->or_where('trl.status', 1);
            $this->db->group_end();
            $this->db->where('s.active', 1);
            $this->db->where($this->db->escape($today) . ' BETWEEN DATE(trl.start_time) AND DATE(trl.end_time)', null, false);
            $this->db->where('trl.staff_id NOT IN (
                SELECT DISTINCT cio.staff_id
                FROM ' . db_prefix() . 'check_in_out cio
                WHERE DATE(cio.date) = ' . $this->db->escape($today) . '
                AND cio.type_check = 1
            )', null, false);
            $result_on_leave = $this->db->get()->row_array();
            $on_leave = (int) ($result_on_leave['on_leave'] ?? 0);
        }

        $absent = $total_active - $present - $on_leave;

        return [
            'total_active' => $total_active,
            'present'      => $present,
            'on_leave'     => $on_leave,
            'absent'       => $absent,
        ];
    }

    public function get_active_staff_list()
    {
        $this->db->select('staffid, firstname, lastname, email, CONCAT(firstname, " ", lastname) as full_name', false);
        $this->db->from(db_prefix() . 'staff');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'ASC');

        return $this->db->get()->result_array();
    }

    public function get_present_staff_list()
    {
        $today = $this->widget_today();

        $checkinsTable    = db_prefix() . 'check_in_out';
        $staffTable       = db_prefix() . 'staff';
        $transactionsTable = db_prefix() . 'kpi_transactions';

        $sql = '
            SELECT
                s.staffid,
                s.firstname,
                s.lastname,
                s.email,
                CONCAT(s.firstname, " ", s.lastname) AS full_name,
                cio.checkin_time,
                kt.points AS kpi_points,
                kt.remarks AS kpi_remarks
            FROM (
                SELECT
                    staff_id,
                    MIN(id) AS rel_id,
                    MIN(date) AS checkin_time
                FROM ' . $checkinsTable . '
                WHERE DATE(date) = ?
                  AND type_check = 1
                GROUP BY staff_id
            ) cio
            INNER JOIN ' . $staffTable . ' s
                ON s.staffid = cio.staff_id
            LEFT JOIN ' . $transactionsTable . ' kt
                ON kt.rel_type = ?
               AND kt.rel_id = cio.rel_id
               AND kt.transaction_date = ?
            WHERE s.active = 1
            GROUP BY s.staffid, s.firstname, s.lastname, s.email
            ORDER BY s.firstname ASC';

        $rows = $this->db->query($sql, [$today, 'check_in_out', $today])->result_array();

        foreach ($rows as &$row) {
            $row['checkin_time_display'] = !empty($row['checkin_time'])
                ? date('H:i', strtotime($row['checkin_time']))
                : '-';
        }
        unset($row);

        return $this->attach_shift_start_times($rows, $today);
    }

    public function get_absent_staff_list()
    {
        $today = $this->widget_today();

        $this->db->distinct();
        $this->db->select('cio.staff_id');
        $this->db->from(db_prefix() . 'check_in_out cio');
        $this->where_date_equals('DATE(cio.date)', $today);
        $this->db->where('cio.type_check', 1);
        $checked_in_ids = array_column($this->db->get()->result_array(), 'staff_id');

        $on_leave_ids = [];
        if ($this->db->table_exists(db_prefix() . 'timesheets_requisition_leave')) {
            $this->db->distinct();
            $this->db->select('trl.staff_id');
            $this->db->from(db_prefix() . 'timesheets_requisition_leave trl');
            $this->db->join(db_prefix() . 'staff s', 's.staffid = trl.staff_id', 'inner');
            $this->db->group_start();
            $this->db->where('trl.status', 0);
            $this->db->or_where('trl.status', 1);
            $this->db->group_end();
            $this->db->where('s.active', 1);
            $this->db->where($this->db->escape($today) . ' BETWEEN DATE(trl.start_time) AND DATE(trl.end_time)', null, false);
            $on_leave_ids = array_column($this->db->get()->result_array(), 'staff_id');
        }

        $this->db->select('staffid, firstname, lastname, email, CONCAT(firstname, " ", lastname) as full_name', false);
        $this->db->from(db_prefix() . 'staff');
        $this->db->where('active', 1);

        if (!empty($checked_in_ids)) {
            $this->db->where_not_in('staffid', $checked_in_ids);
        }

        if (!empty($on_leave_ids)) {
            $this->db->where_not_in('staffid', $on_leave_ids);
        }

        $this->db->order_by('firstname', 'ASC');
        $rows = $this->db->get()->result_array();

        return $this->attach_shift_start_times($rows, $today);
    }

    public function get_on_leave_staff_list()
    {
        $today = $this->widget_today();

        if (!$this->db->table_exists(db_prefix() . 'timesheets_requisition_leave')) {
            return [];
        }

        $hasTypeOfLeaveText = $this->db->field_exists('type_of_leave_text', db_prefix() . 'timesheets_requisition_leave');
        $hasTypeOfLeave     = $this->db->field_exists('type_of_leave', db_prefix() . 'timesheets_requisition_leave');

        $select = 'trl.id as leave_id, s.staffid, s.firstname, s.lastname, s.email, CONCAT(s.firstname, " ", s.lastname) as full_name, trl.start_time, trl.end_time, trl.subject, trl.rel_type, trl.status';
        if ($hasTypeOfLeave) {
            $select .= ', trl.type_of_leave';
        }
        if ($hasTypeOfLeaveText) {
            $select .= ', trl.type_of_leave_text';
        }

        $this->db->select($select, false);
        $this->db->from(db_prefix() . 'timesheets_requisition_leave trl');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = trl.staff_id', 'inner');
        $this->db->group_start();
        $this->db->where('trl.status', 0);
        $this->db->or_where('trl.status', 1);
        $this->db->group_end();
        $this->db->where('s.active', 1);
        $this->db->where($this->db->escape($today) . ' BETWEEN DATE(trl.start_time) AND DATE(trl.end_time)', null, false);
        $this->db->where('trl.staff_id NOT IN (
            SELECT DISTINCT cio.staff_id
            FROM ' . db_prefix() . 'check_in_out cio
            WHERE DATE(cio.date) = ' . $this->db->escape($today) . '
            AND cio.type_check = 1
        )', null, false);
        $this->db->order_by('s.firstname', 'ASC');

        $rows = $this->db->get()->result_array();

        foreach ($rows as &$row) {
            $leaveId      = (int) ($row['leave_id'] ?? 0);
            $leaveStaffId = (int) ($row['staffid'] ?? 0);
            $row['leave_type_name'] = $this->resolve_leave_type_name($row);
            // Only expose detail URL when this user can open the leave page (avoids access_denied).
            $row['leave_detail_url'] = ($leaveId > 0 && $this->can_view_leave_detail($leaveId, $leaveStaffId))
                ? admin_url('timesheets/requisition_detail/' . $leaveId)
                : '';
        }
        unset($row);

        return $rows;
    }

    /**
     * Whether the logged-in staff may open timesheets/requisition_detail/{id}.
     * Mirrors leave list visibility: admin/HR, full view, owner, approver, or staff manager.
     *
     * @param int $leaveId
     * @param int $leaveStaffId Applicant staffid
     * @return bool
     */
    private function can_view_leave_detail($leaveId, $leaveStaffId)
    {
        $leaveId      = (int) $leaveId;
        $leaveStaffId = (int) $leaveStaffId;
        if ($leaveId <= 0) {
            return false;
        }

        if (function_exists('timesheets_is_admin_or_hr') && timesheets_is_admin_or_hr()) {
            return true;
        }

        if (!function_exists('has_permission')) {
            return false;
        }

        // No leave_management capability at all → detail page will access_denied.
        $canViewOwn = has_permission('leave_management', '', 'view_own');
        $canViewAll = has_permission('leave_management', '', 'view');
        if (!$canViewOwn && !$canViewAll) {
            return false;
        }

        if ($canViewAll) {
            return true;
        }

        $currentStaffId = (int) get_staff_user_id();
        if ($currentStaffId <= 0) {
            return false;
        }

        // Own leave
        if ($leaveStaffId > 0 && $currentStaffId === $leaveStaffId) {
            return true;
        }

        // Approver on this request
        if ($this->db->table_exists(db_prefix() . 'timesheets_approval_details')) {
            $this->db->from(db_prefix() . 'timesheets_approval_details');
            $this->db->where('rel_id', $leaveId);
            $this->db->where('staffid', $currentStaffId);
            $this->db->where('rel_type <>', 'additional_timesheets');
            if ($this->db->count_all_results() > 0) {
                return true;
            }
        }

        // Department / staff manager scope (same idea as leave list filter)
        if (function_exists('timesheet_staff_manager_query') && $leaveStaffId > 0) {
            $managerSql = timesheet_staff_manager_query('leave_management', 'staff_id', 'AND');
            if (is_string($managerSql) && trim($managerSql) !== '' && strpos($managerSql, 'IN ()') === false) {
                $sql = 'SELECT 1 FROM ' . db_prefix() . 'timesheets_requisition_leave
                    WHERE id = ?
                    AND staff_id = ?
                    ' . $managerSql . '
                    LIMIT 1';
                $hit = $this->db->query($sql, [$leaveId, $leaveStaffId])->row();
                if ($hit) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Resolve display name for "Type of leave" (e.g. Annual leave), matching timesheets detail page.
     *
     * @param array<string,mixed> $row
     * @return string
     */
    private function resolve_leave_type_name(array $row)
    {
        $relType = (int) ($row['rel_type'] ?? 0);

        if ($relType === 1) {
            $typeOfLeave = $row['type_of_leave'] ?? '';
            $typeName    = '';

            if (is_numeric($typeOfLeave)) {
                switch ((int) $typeOfLeave) {
                    case 8:
                        $typeName = _l('annual_leave');
                        break;
                    case 2:
                        $typeName = _l('maternity_leave');
                        break;
                    case 4:
                        $typeName = _l('private_work_without_pay');
                        break;
                    case 1:
                        $typeName = _l('sick_leave');
                        break;
                }
            }

            if ($typeName === '') {
                $slug = (string) ($row['type_of_leave_text'] ?? '');
                if ($slug !== '') {
                    $CI = &get_instance();
                    if (isset($CI->app_modules) && $CI->app_modules->is_active('timesheets')) {
                        $CI->load->model('timesheets/timesheets_model');
                        $customName = $CI->timesheets_model->get_custom_leave_name_by_slug($slug);
                        if ($customName !== '') {
                            $typeName = $customName;
                        } else {
                            $typeName = $slug;
                        }
                    } else {
                        $typeName = $slug;
                    }
                }
            }

            return $typeName !== '' ? $typeName : '-';
        }

        $map = [
            2 => 'late',
            3 => 'Go_out',
            4 => 'Go_on_bussiness',
            5 => 'quit_job',
            6 => 'early',
        ];

        if (isset($map[$relType])) {
            return _l($map[$relType]);
        }

        return '-';
    }

    /**
     * Today's date for widget filters (Y-m-d), same source as personal check-in status.
     */
    private function widget_today()
    {
        return date('Y-m-d');
    }

    /**
     * Active Record-safe DATE(column) = 'Y-m-d' filter.
     *
     * IMPORTANT: Do NOT use where('DATE(col) =', $date, false) — with escape=false
     * CI emits DATE(col) = 2026-07-22 (unquoted), which MySQL evaluates as arithmetic
     * (2026-07-22 => 1997) and matches zero rows.
     */
    private function where_date_equals($dateExpression, $date)
    {
        $this->db->where($dateExpression . ' = ' . $this->db->escape($date), null, false);
    }

    /**
     * Attach today's shift start time (H:i display) to each staff row.
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function attach_shift_start_times(array $rows, $date)
    {
        if (empty($rows)) {
            return $rows;
        }

        $CI = &get_instance();
        $timesheetsActive = isset($CI->app_modules) && $CI->app_modules->is_active('timesheets');

        if ($timesheetsActive) {
            $CI->load->model('timesheets/timesheets_model');
        }

        foreach ($rows as &$row) {
            $display = '-';

            if ($timesheetsActive) {
                $staffId  = (int) ($row['staffid'] ?? 0);
                $shiftIds = $staffId > 0
                    ? $CI->timesheets_model->get_shift_work_staff_by_date($staffId, $date)
                    : [];

                if (!empty($shiftIds)) {
                    $shiftType = $CI->timesheets_model->get_shift_type((int) $shiftIds[0]);
                    $resolved  = function_exists('kpi_resolve_shift_start_time')
                        ? kpi_resolve_shift_start_time($shiftType, $date)
                        : '';

                    if ($resolved !== '') {
                        $display = date('H:i', strtotime($resolved));
                    }
                }
            }

            $row['shift_start_time'] = $display;
        }
        unset($row);

        return $rows;
    }
}
