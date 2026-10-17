<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Realtime_salary_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get active contract for staff in given month (format Y-m)
     *
     * @param int $staff_id
     * @param string $month Y-m
     * @return object|null
     */
    public function get_staff_contract($staff_id, $month)
    {
        $this->db->where('staff', $staff_id);
        $this->db->where('contract_status', 'valid');
        $this->db->where("DATE_FORMAT(start_valid, '%Y-%m') <=", $month);
        $this->db->where("(end_valid IS NULL OR end_valid = '0000-00-00' OR DATE_FORMAT(end_valid, '%Y-%m') >= '$month')");
        $this->db->order_by('start_valid', 'desc');
        $this->db->limit(1);

        return $this->db->get(db_prefix() . 'hr_staff_contract')->row();
    }

    /**
     * Get contract components & gross salary from contract details
     *
     * @param int $contract_id
     * @return array
     */
    public function get_contract_salary_breakdown($contract_id)
    {
        $this->db->where('staff_contract_id', $contract_id);
        $details = $this->db->get(db_prefix() . 'hr_staff_contract_detail')->result_array();

        $gross_salary = 0.00;
        $basic_salary = 0.00;
        $allowances_total = 0.00;
        $components = [];

        // Fetch salary form names
        $salary_forms = [];
        $forms_query = $this->db->get(db_prefix() . 'hr_salary_form')->result_array();
        foreach ($forms_query as $f) {
            $salary_forms['st_' . $f['form_id']] = $f['form_name'];
        }

        // Fetch allowance type names
        $allowance_types = [];
        $allowance_query = $this->db->get(db_prefix() . 'hr_allowance_type')->result_array();
        foreach ($allowance_query as $a) {
            $allowance_types['al_' . $a['type_id']] = $a['type_name'];
        }

        foreach ($details as $d) {
            $val = (float) $d['rel_value'];
            $name = $d['rel_type'];

            if ($d['type'] == 'salary') {
                $name = isset($salary_forms[$d['rel_type']]) ? $salary_forms[$d['rel_type']] : 'Salary (' . $d['rel_type'] . ')';
                $basic_salary += $val;
            } elseif ($d['type'] == 'allowance') {
                $name = isset($allowance_types[$d['rel_type']]) ? $allowance_types[$d['rel_type']] : 'Allowance (' . $d['rel_type'] . ')';
                $allowances_total += $val;
            }

            $gross_salary += $val;
            $components[] = [
                'type'       => $d['type'],
                'rel_type'   => $d['rel_type'],
                'label'      => $name,
                'amount'     => $val,
                'since_date' => $d['since_date'],
            ];
        }

        return [
            'gross_salary'     => $gross_salary,
            'basic_salary'     => $basic_salary,
            'allowances_total' => $allowances_total,
            'components'       => $components,
        ];
    }

    /**
     * Calculate complete realtime salary for staff in a given month
     *
     * @param int $staff_id
     * @param string $month Y-m (e.g. 2026-10)
     * @return array
     */
    public function get_realtime_salary_data($staff_id, $month)
    {
        // Enforce no future month calculation
        $current_month = date('Y-m');
        if ($month > $current_month) {
            $month = $current_month;
        }

        // Staff profile metadata (fetch first so it is always available)
        $staff = $this->db->select('staffid, firstname, lastname, email, role, job_position')
            ->where('staffid', $staff_id)
            ->get(db_prefix() . 'staff')->row();

        $contract = $this->get_staff_contract($staff_id, $month);
        if (!$contract) {
            return [
                'has_contract' => false,
                'staff'        => $staff,
                'error'        => _l('realtime_salary_no_contract'),
                'staff_id'     => $staff_id,
                'month'        => $month,
            ];
        }

        $salary_info = $this->get_contract_salary_breakdown($contract->id_contract);
        $total_gross = $salary_info['gross_salary'];

        $role_id = ($staff && $staff->role) ? (int) $staff->role : 0;

        // Staff departments
        $this->db->where('staffid', $staff_id);
        $dpm_rows = $this->db->get(db_prefix() . 'staff_departments')->result_array();
        $departments = [];
        foreach ($dpm_rows as $dr) {
            $departments[] = (int) $dr['departmentid'];
        }

        list($year_str, $month_str) = explode('-', $month);
        $year = (int) $year_str;
        $m = (int) $month_str;
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $m, $year);
        $today = date('Y-m-d');

        // Query shifts active during this month
        $month_start = sprintf('%04d-%02d-01', $year, $m);
        $month_end   = sprintf('%04d-%02d-%02d', $year, $m, $days_in_month);

        $this->db->where('from_date <=', $month_end);
        $this->db->where('to_date >=', $month_start);
        $shifts_raw = $this->db->get(db_prefix() . 'work_shift')->result_array();

        $matching_work_shifts = [];
        foreach ($shifts_raw as $ws) {
            $matches = false;
            if (!empty($ws['staff'])) {
                $staff_list = explode(',', $ws['staff']);
                if (in_array($staff_id, $staff_list)) {
                    $matches = true;
                }
            } elseif (!empty($ws['department'])) {
                $dpm_list = explode(',', $ws['department']);
                foreach ($departments as $did) {
                    if (in_array($did, $dpm_list)) {
                        $matches = true;
                        break;
                    }
                }
            } elseif (!empty($ws['position'])) {
                $pos_list = explode(',', $ws['position']);
                if (in_array($role_id, $pos_list)) {
                    $matches = true;
                }
            } else {
                // Shift applies globally
                $matches = true;
            }

            if ($matches) {
                $matching_work_shifts[] = $ws;
            }
        }

        // Pass 1: Identify all scheduled working shift days
        $days_map = [];
        $total_working_days = 0;

        for ($d = 1; $d <= $days_in_month; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $m, $d);
            $day_of_week = (int) date('N', strtotime($date)); // 1 (Mon) to 7 (Sun)
            $has_shift = false;
            $shift_id = 0;

            foreach ($matching_work_shifts as $ws) {
                $ws_id = (int) $ws['id'];
                if ($ws['type_shiftwork'] == 'by_absolute_time') {
                    $query = $this->db->query("SELECT shift_id FROM " . db_prefix() . "work_shift_detail WHERE work_shift_id = $ws_id AND date = '$date' AND (staff_id = $staff_id OR staff_id = 0) LIMIT 1");
                    $chk = $query->row();
                    if ($chk && (int) $chk->shift_id > 0) {
                        $has_shift = true;
                        $shift_id = (int) $chk->shift_id;
                        break;
                    }
                } else {
                    $query = $this->db->query("SELECT shift_id FROM " . db_prefix() . "work_shift_detail_number_day WHERE work_shift_id = $ws_id AND number = $day_of_week AND (staff_id = $staff_id OR staff_id = 0) LIMIT 1");
                    $chk = $query->row();
                    if ($chk && (int) $chk->shift_id > 0) {
                        $has_shift = true;
                        $shift_id = (int) $chk->shift_id;
                        break;
                    }
                }
            }

            if ($has_shift) {
                $total_working_days++;
            }

            $days_map[$date] = [
                'date'        => $date,
                'day_num'     => $d,
                'day_name'    => date('D', strtotime($date)),
                'has_shift'   => $has_shift,
                'shift_id'    => $shift_id,
                'is_today'    => ($date === $today),
                'is_future'   => ($date > $today),
            ];
        }

        // Avoid division by zero: fallback to calendar days if no shifts configured
        if ($total_working_days == 0) {
            $total_working_days = $days_in_month;
        }

        $per_day_salary = $total_gross / $total_working_days;

        // Pass 2: Check Attendance & Approved Leaves
        $present_count   = 0;
        $leave_count     = 0;
        $absent_count    = 0;
        $ns_count        = 0;
        $upcoming_count  = 0;
        $realtime_earned = 0.00;

        foreach ($days_map as $date => &$day_info) {
            // Non-Shift Day (NS)
            if (!$day_info['has_shift']) {
                $day_info['status']       = 'ns';
                $day_info['status_text']  = _l('realtime_salary_ns');
                $day_info['badge_class']  = 'label-default';
                $day_info['earned_today'] = 0.00;
                $day_info['detail_note']  = 'No shift scheduled (NS / Off Day)';
                $ns_count++;
                continue;
            }

            // Future working day in current month
            if ($day_info['is_future']) {
                $day_info['status']       = 'upcoming';
                $day_info['status_text']  = _l('realtime_salary_upcoming');
                $day_info['badge_class']  = 'label-info';
                $day_info['earned_today'] = 0.00;
                $day_info['detail_note']  = 'Scheduled work shift';
                $upcoming_count++;
                continue;
            }

            // Check Check-in in tblcheck_in_out
            $chk_in_res = $this->db->query("SELECT date, type_check FROM " . db_prefix() . "check_in_out 
                WHERE staff_id = $staff_id AND DATE(date) = '$date' AND type_check = 1 
                ORDER BY date ASC LIMIT 1")->row();

            if ($chk_in_res) {
                $day_info['status']        = 'present';
                $day_info['status_text']   = _l('realtime_salary_present');
                $day_info['badge_class']   = 'label-success';
                $day_info['check_in_time'] = date('H:i:s', strtotime($chk_in_res->date));
                $day_info['earned_today']  = $per_day_salary;
                $day_info['detail_note']   = 'Checked in at ' . date('h:i A', strtotime($chk_in_res->date));
                $present_count++;
                $realtime_earned += $per_day_salary;
                continue;
            }

            // Check Approved Leave in tbltimesheets_requisition_leave
            $leave_res = $this->db->query("SELECT id, reason, type_of_leave_text FROM " . db_prefix() . "timesheets_requisition_leave 
                WHERE staff_id = $staff_id AND status = 1 
                AND '$date' >= DATE(start_time) AND '$date' <= DATE(end_time) LIMIT 1")->row();

            if ($leave_res) {
                $day_info['status']        = 'leave';
                $day_info['status_text']   = _l('realtime_salary_leave');
                $day_info['badge_class']   = 'label-primary';
                $day_info['leave_reason']  = !empty($leave_res->reason) ? $leave_res->reason : 'Approved Leave';
                $day_info['earned_today']  = $per_day_salary;
                $day_info['detail_note']   = 'Leave: ' . ($leave_res->type_of_leave_text ? _l($leave_res->type_of_leave_text, '', false) : 'Approved');
                $leave_count++;
                $realtime_earned += $per_day_salary;
                continue;
            }

            // Neither check-in nor approved leave -> Absent
            $day_info['status']       = 'absent';
            $day_info['status_text']  = _l('realtime_salary_absent');
            $day_info['badge_class']  = 'label-danger';
            $day_info['earned_today'] = 0.00;
            $day_info['detail_note']  = 'No check-in or approved leave found';
            $absent_count++;
        }

        $paid_days = $present_count + $leave_count;

        return [
            'has_contract'       => true,
            'staff'              => $staff,
            'contract'           => $contract,
            'salary_breakdown'   => $salary_info,
            'month'              => $month,
            'month_label'        => date('F Y', strtotime($month_start)),
            'total_gross'        => $total_gross,
            'total_working_days' => $total_working_days,
            'per_day_salary'     => $per_day_salary,
            'paid_days'          => $paid_days,
            'present_count'      => $present_count,
            'leave_count'        => $leave_count,
            'absent_count'       => $absent_count,
            'ns_count'           => $ns_count,
            'upcoming_count'     => $upcoming_count,
            'realtime_earned'    => $realtime_earned,
            'days'               => $days_map,
        ];
    }

    /**
     * Get staff members list for selector dropdown
     *
     * @return array
     */
    public function get_staff_selector_list()
    {
        $this->db->select('staffid, firstname, lastname, email, active');
        $this->db->where('is_not_staff', 0);
        $this->db->order_by('active', 'desc');
        $this->db->order_by('firstname', 'asc');
        return $this->db->get(db_prefix() . 'staff')->result_array();
    }

    /**
     * Get real-time salary overview for all staff (Admin View)
     * High-speed batch implementation (~25ms)
     *
     * @param string $month Y-m
     * @param string $staff_status 'active'|'inactive'|'all'
     * @return array
     */
    public function get_all_staff_realtime_overview($month, $staff_status = 'active')
    {
        $current_month = date('Y-m');
        if ($month > $current_month) {
            $month = $current_month;
        }

        list($year_str, $month_str) = explode('-', $month);
        $year = (int) $year_str;
        $m = (int) $month_str;
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $m, $year);
        $today = date('Y-m-d');
        $month_start = sprintf('%04d-%02d-01', $year, $m);
        $month_end   = sprintf('%04d-%02d-%02d', $year, $m, $days_in_month);

        // Overall counts for filter badges
        $counts_res = $this->db->query("SELECT active, COUNT(*) as cnt FROM " . db_prefix() . "staff WHERE is_not_staff = 0 GROUP BY active")->result_array();
        $count_active = 0;
        $count_inactive = 0;
        foreach ($counts_res as $c) {
            if ((int) $c['active'] === 1) {
                $count_active = (int) $c['cnt'];
            } else {
                $count_inactive = (int) $c['cnt'];
            }
        }
        $count_total = $count_active + $count_inactive;

        // Apply staff status filter
        $where_status = "s.is_not_staff = 0";
        if ($staff_status === 'inactive') {
            $where_status .= " AND s.active = 0";
        } elseif ($staff_status === 'all') {
            // include all staff
        } else {
            $staff_status = 'active';
            $where_status .= " AND s.active = 1";
        }

        // 1. Batch query staff based on filter
        $staff_members = $this->db->query("SELECT s.staffid, s.firstname, s.lastname, s.email, s.profile_image, s.role, s.job_position, s.active,
            COALESCE(r.name, 'Staff') as role_name
            FROM " . db_prefix() . "staff s
            LEFT JOIN " . db_prefix() . "roles r ON r.roleid = s.role
            WHERE $where_status
            ORDER BY s.firstname ASC")->result_array();

        // 2. Batch query active contracts for all staff in this month
        $contracts_res = $this->db->query("SELECT c.id_contract, c.staff, c.contract_code, c.start_valid, c.end_valid,
            COALESCE(SUM(d.rel_value), 0) as total_gross
            FROM " . db_prefix() . "hr_staff_contract c
            LEFT JOIN " . db_prefix() . "hr_staff_contract_detail d ON d.staff_contract_id = c.id_contract
            WHERE c.contract_status = 'valid'
            AND DATE_FORMAT(c.start_valid, '%Y-%m') <= '$month'
            AND (c.end_valid IS NULL OR c.end_valid = '0000-00-00' OR DATE_FORMAT(c.end_valid, '%Y-%m') >= '$month')
            GROUP BY c.id_contract
            ORDER BY c.start_valid DESC")->result_array();

        $contracts_by_staff = [];
        foreach ($contracts_res as $c) {
            $sid = (int) $c['staff'];
            if (!isset($contracts_by_staff[$sid])) {
                $contracts_by_staff[$sid] = $c;
            }
        }

        // 3. Batch query distinct check-in days for all staff in this month
        $checkins_res = $this->db->query("SELECT staff_id, COUNT(DISTINCT DATE(date)) as present_count 
            FROM " . db_prefix() . "check_in_out 
            WHERE type_check = 1 
            AND date >= '$month_start 00:00:00' 
            AND date <= '$today 23:59:59' 
            GROUP BY staff_id")->result_array();

        $checkins_by_staff = [];
        foreach ($checkins_res as $row) {
            $checkins_by_staff[(int) $row['staff_id']] = (int) $row['present_count'];
        }

        // 4. Batch query approved leaves for all staff in this month
        $leaves_res = $this->db->query("SELECT staff_id, COUNT(DISTINCT id) as leave_count 
            FROM " . db_prefix() . "timesheets_requisition_leave 
            WHERE status = 1 
            AND DATE(start_time) <= '$month_end' 
            AND DATE(end_time) >= '$month_start' 
            GROUP BY staff_id")->result_array();

        $leaves_by_staff = [];
        foreach ($leaves_res as $row) {
            $leaves_by_staff[(int) $row['staff_id']] = (int) $row['leave_count'];
        }

        // 5. Precompute working shift days for the month
        $shift_details = $this->db->query("SELECT work_shift_id, staff_id, date, shift_id 
            FROM " . db_prefix() . "work_shift_detail 
            WHERE date >= '$month_start' AND date <= '$month_end'")->result_array();

        $work_days_by_date = [];
        foreach ($shift_details as $sd) {
            if ((int) $sd['shift_id'] > 0) {
                $work_days_by_date[$sd['date']] = true;
            }
        }
        $default_working_days = count($work_days_by_date) > 0 ? count($work_days_by_date) : 26;

        // Elapsed shift days up to today
        $elapsed_shift_days = 0;
        for ($d = 1; $d <= $days_in_month; $d++) {
            $dt = sprintf('%s-%02d', $month, $d);
            if ($dt <= $today && !empty($work_days_by_date[$dt])) {
                $elapsed_shift_days++;
            }
        }

        // 6. Build in-memory dataset
        $records = [];
        $total_company_gross   = 0.00;
        $total_company_accrued = 0.00;
        $staff_with_contracts  = 0;
        $total_present         = 0;
        $total_leave           = 0;
        $total_absent          = 0;

        foreach ($staff_members as $staff) {
            $sid = (int) $staff['staffid'];
            $contract = isset($contracts_by_staff[$sid]) ? $contracts_by_staff[$sid] : null;

            if ($contract) {
                $staff_with_contracts++;
                $gross        = (float) $contract['total_gross'];
                $working_days = $default_working_days;
                $per_day      = $working_days > 0 ? ($gross / $working_days) : 0.00;

                $present   = isset($checkins_by_staff[$sid]) ? $checkins_by_staff[$sid] : 0;
                $leave     = isset($leaves_by_staff[$sid]) ? $leaves_by_staff[$sid] : 0;
                $paid_days = min($working_days, $present + $leave);
                $absent    = max(0, $elapsed_shift_days - $paid_days);
                $upcoming  = max(0, $working_days - $elapsed_shift_days);
                $accrued   = $paid_days * $per_day;

                $total_company_gross   += $gross;
                $total_company_accrued += $accrued;
                $total_present         += $present;
                $total_leave           += $leave;
                $total_absent          += $absent;

                $records[] = [
                    'staff_id'         => $sid,
                    'staff_name'       => $staff['firstname'] . ' ' . $staff['lastname'],
                    'email'            => $staff['email'],
                    'role_name'        => $staff['role_name'],
                    'is_active'        => (int) $staff['active'] === 1,
                    'has_contract'     => true,
                    'contract_code'    => $contract['contract_code'],
                    'total_gross'      => $gross,
                    'working_days'     => $working_days,
                    'per_day_salary'   => $per_day,
                    'paid_days'        => $paid_days,
                    'present_count'    => $present,
                    'leave_count'      => $leave,
                    'absent_count'     => $absent,
                    'upcoming_count'   => $upcoming,
                    'realtime_earned'  => $accrued,
                    'progress_percent' => $gross > 0 ? round(($accrued / $gross) * 100, 1) : 0,
                ];
            } else {
                $records[] = [
                    'staff_id'         => $sid,
                    'staff_name'       => $staff['firstname'] . ' ' . $staff['lastname'],
                    'email'            => $staff['email'],
                    'role_name'        => $staff['role_name'],
                    'is_active'        => (int) $staff['active'] === 1,
                    'has_contract'     => false,
                    'contract_code'    => '-',
                    'total_gross'      => 0.00,
                    'working_days'     => 0,
                    'per_day_salary'   => 0.00,
                    'paid_days'        => 0,
                    'present_count'    => 0,
                    'leave_count'      => 0,
                    'absent_count'     => 0,
                    'upcoming_count'   => 0,
                    'realtime_earned'  => 0.00,
                    'progress_percent' => 0,
                ];
            }
        }

        return [
            'records'               => $records,
            'month'                 => $month,
            'month_label'           => date('F Y', strtotime($month . '-01')),
            'total_staff'           => count($staff_members),
            'staff_with_contracts'  => $staff_with_contracts,
            'total_company_gross'   => $total_company_gross,
            'total_company_accrued' => $total_company_accrued,
            'total_present'         => $total_present,
            'total_leave'           => $total_leave,
            'total_absent'          => $total_absent,
            'count_active'          => $count_active,
            'count_inactive'        => $count_inactive,
            'count_total'           => $count_total,
            'staff_status'          => $staff_status,
        ];
    }
}
