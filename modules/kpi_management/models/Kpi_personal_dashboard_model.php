<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_personal_dashboard_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('timesheets/timesheets_model');
        $this->load->model('staff_model');
        $this->load->model('departments_model');
    }

    /**
     * Build personal dashboard payload for one staff member and calendar month.
     *
     * @return array<string, mixed>
     */
    public function get_dashboard_data($staff_id, $year, $month)
    {
        $staff_id = (int) $staff_id;
        $year     = (int) $year;
        $month    = (int) $month;

        $period = $this->resolve_period($year, $month);

        $approvedTxs = $this->get_approved_transactions($staff_id, $period['from'], $period['to']);
        $monthlyScore = $this->sum_monthly_score($approvedTxs);

        if ($period['is_future']) {
            return $this->build_future_period_payload($period, $monthlyScore);
        }

        $staffContext              = $this->get_staff_context($staff_id);
        $checkinTxs                = $this->get_approved_checkin_transactions($staff_id, $period['from'], $period['to']);
        $attendanceModuleTxs       = $this->get_approved_attendance_module_transactions($staff_id, $period['from'], $period['to']);
        $activeAttendanceRules     = $this->get_active_attendance_rules();
        $checkinsByRel             = $this->get_checkins_by_rel_ids($this->collect_rel_ids($checkinTxs));

        $shiftRows               = $this->get_month_shift_rows($staffContext, $period['from'], $period['to']);
        $shiftRowIds             = $this->pluck_ids($shiftRows);
        $absoluteShiftDetails    = $this->get_month_absolute_shift_details($staff_id, $shiftRowIds, $period['from'], $period['to']);
        $weekdayShiftDetails     = $this->get_month_weekday_shift_details($staff_id, $shiftRowIds);
        $shiftTypesById          = $this->get_shift_types_by_ids($this->collect_shift_type_ids($absoluteShiftDetails, $weekdayShiftDetails));
        $holidaysByDate          = $this->get_holidays_by_date($staffContext, $period['from'], $period['to']);
        $timesheetLeavesByDate   = $this->get_timesheet_leave_dates($staff_id, $period['from'], $period['to']);
        $requisitionLeavesByDate = $this->get_requisition_leave_dates($staff_id, $period['from'], $period['to']);
        $checkinTxsByDate        = $this->group_transactions_by_date($checkinTxs);
        $attendanceModuleTxsByDate = $this->group_transactions_by_date($attendanceModuleTxs);

        $masterDates    = [];
        $onTimeDays     = 0;
        $lateDays       = 0;
        $absences       = 0;
        $bestStreak     = 0;
        $currentStreak  = 0;
        $chartLabels    = [];
        $chartPoints    = [];
        $chartColors    = [];

        foreach ($this->enumerate_dates($period['from'], $period['to']) as $date) {
            if (isset($holidaysByDate[$date]) || isset($timesheetLeavesByDate[$date]) || isset($requisitionLeavesByDate[$date])) {
                continue;
            }

            $shiftIds = $this->resolve_shift_ids_for_date(
                $date,
                $staffContext,
                $shiftRows,
                $absoluteShiftDetails,
                $weekdayShiftDetails
            );

            if (empty($shiftIds)) {
                continue;
            }

            $shiftStartTime          = $this->resolve_shift_start_time_from_ids($date, $shiftIds, $shiftTypesById);
            $dayCheckinTransactions  = $checkinTxsByDate[$date] ?? [];
            $dayAttendanceTransactions = $attendanceModuleTxsByDate[$date] ?? [];
            $classified              = $this->classify_day(
                $dayCheckinTransactions,
                $dayAttendanceTransactions,
                $checkinsByRel,
                $shiftStartTime,
                $activeAttendanceRules
            );
            $masterDates[] = [
                'date'               => $date,
                'date_display'       => _d($date),
                'shift_time'         => $shiftStartTime !== '' ? date('H:i', strtotime($shiftStartTime)) : '',
                'checkin_time'       => $classified['checkin_display'],
                'points'             => (int) $classified['points'],
                'status'             => $classified['status'],
                'remarks'            => $classified['remarks'],
                'status_label_class' => $this->status_label_class($classified['status']),
            ];

            $chartLabels[] = date('j', strtotime($date));
            $chartPoints[] = (int) $classified['points'];
            $chartColors[] = (int) $classified['points'] >= 0 ? 'rgba(40, 167, 69, 0.85)' : 'rgba(220, 53, 69, 0.85)';

            if ($classified['status'] === 'Late') {
                $lateDays++;
                $currentStreak = 0;
            } elseif ($classified['status'] === 'Absent') {
                $absences++;
                $currentStreak = 0;
            } else {
                $onTimeDays++;
                $currentStreak++;
                if ($currentStreak > $bestStreak) {
                    $bestStreak = $currentStreak;
                }
            }
        }

        $attendanceDayCount = count($masterDates);
        $onTimeRate         = $attendanceDayCount > 0 ? (int) round(($onTimeDays / $attendanceDayCount) * 100) : 0;

        return [
            'summary' => [
                'monthly_score'         => $monthlyScore,
                'on_time_days'          => $onTimeDays,
                'late_days'             => $lateDays,
                'absences'              => $absences,
                'best_streak'           => $bestStreak,
                'on_time_rate'          => $onTimeRate,
                'performance_grade'     => $this->get_attendance_performance_grade($onTimeRate),
                'attendance_day_count'  => $attendanceDayCount,
            ],
            'dates' => $masterDates,
            'chart' => [
                'labels' => $chartLabels,
                'points' => $chartPoints,
                'colors' => $chartColors,
            ],
            'period' => [
                'from'          => $period['from'],
                'to'            => $period['to'],
                'full_month_to' => $period['full_month_to'],
                'label'         => date('F Y', strtotime($period['from'])),
                'is_capped'     => $period['is_capped'],
                'is_future'     => $period['is_future'],
            ],
        ];
    }

    /**
     * @return array{from:string,to:string,full_month_to:string,is_capped:bool,is_future:bool}
     */
    private function resolve_period($year, $month)
    {
        $fromDate    = sprintf('%04d-%02d-01', (int) $year, (int) $month);
        $fullMonthTo = date('Y-m-t', strtotime($fromDate));
        $today       = date('Y-m-d');
        $requestedYm = sprintf('%04d-%02d', (int) $year, (int) $month);
        $currentYm   = date('Y-m');
        $isFuture    = $requestedYm > $currentYm;
        $isCurrent   = $requestedYm === $currentYm;
        $toDate      = $isFuture ? $fullMonthTo : ($isCurrent ? min($fullMonthTo, $today) : $fullMonthTo);

        return [
            'from'          => $fromDate,
            'to'            => $toDate,
            'full_month_to' => $fullMonthTo,
            'is_capped'     => $isCurrent && $toDate !== $fullMonthTo,
            'is_future'     => $isFuture,
        ];
    }

    /**
     * @param array{from:string,to:string,full_month_to:string,is_capped:bool,is_future:bool} $period
     * @return array<string, mixed>
     */
    private function build_future_period_payload(array $period, $monthlyScore)
    {
        return [
            'summary' => [
                'monthly_score'        => (int) $monthlyScore,
                'on_time_days'         => 0,
                'late_days'            => 0,
                'absences'             => 0,
                'best_streak'          => 0,
                'on_time_rate'         => 0,
                'performance_grade'    => $this->get_attendance_performance_grade(0),
                'attendance_day_count' => 0,
            ],
            'dates' => [],
            'chart' => [
                'labels' => [],
                'points' => [],
                'colors' => [],
            ],
            'period' => [
                'from'          => $period['from'],
                'to'            => $period['to'],
                'full_month_to' => $period['full_month_to'],
                'label'         => date('F Y', strtotime($period['from'])),
                'is_capped'     => false,
                'is_future'     => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function get_staff_context($staff_id)
    {
        $staff       = $this->staff_model->get((int) $staff_id);
        $departments = $this->departments_model->get_staff_departments((int) $staff_id, true);
        $departmentIds = [];

        if (is_array($departments)) {
            foreach ($departments as $departmentId) {
                $departmentIds[] = (int) $departmentId;
            }
        }

        return [
            'staff_id'         => (int) $staff_id,
            'role_id'          => $staff ? (int) ($staff->role ?? 0) : 0,
            'department_ids'   => array_values(array_unique(array_filter($departmentIds))),
            'primary_department_id' => !empty($departmentIds) ? (int) $departmentIds[0] : 0,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function get_approved_transactions($staff_id, $fromDate, $toDate)
    {
        $txTable   = db_prefix() . 'kpi_transactions';
        $ruleTable = db_prefix() . 'kpi_rules';

        $this->db->select($txTable . '.*, ' . $ruleTable . '.rules_json as rules_json');
        $this->db->from($txTable);
        $this->db->join($ruleTable, $ruleTable . '.id = ' . $txTable . '.kpi_rule_id', 'left');
        $this->db->where($txTable . '.staff_id', (int) $staff_id);
        $this->db->where($txTable . '.status', 'approved');
        $this->db->where($txTable . '.transaction_date >=', $fromDate);
        $this->db->where($txTable . '.transaction_date <=', $toDate);
        $this->db->order_by($txTable . '.transaction_date', 'ASC');
        $this->db->order_by($txTable . '.id', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function get_approved_checkin_transactions($staff_id, $fromDate, $toDate)
    {
        $txTable   = db_prefix() . 'kpi_transactions';
        $ruleTable = db_prefix() . 'kpi_rules';

        $this->db->select($txTable . '.*, ' . $ruleTable . '.rules_json as rules_json, ' . $ruleTable . '.module as rule_module');
        $this->db->from($txTable);
        $this->db->join($ruleTable, $ruleTable . '.id = ' . $txTable . '.kpi_rule_id', 'left');
        $this->db->where($txTable . '.staff_id', (int) $staff_id);
        $this->db->where($txTable . '.status', 'approved');
        $this->db->where($txTable . '.rel_type', 'check_in_out');
        $this->db->where($txTable . '.transaction_date >=', $fromDate);
        $this->db->where($txTable . '.transaction_date <=', $toDate);
        $this->db->order_by($txTable . '.transaction_date', 'ASC');
        $this->db->order_by($txTable . '.id', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * All approved attendance-module transactions for chart/table point totals.
     *
     * @return list<array<string, mixed>>
     */
    private function get_approved_attendance_module_transactions($staff_id, $fromDate, $toDate)
    {
        $txTable   = db_prefix() . 'kpi_transactions';
        $ruleTable = db_prefix() . 'kpi_rules';

        $this->db->select($txTable . '.*, ' . $ruleTable . '.rules_json as rules_json, ' . $ruleTable . '.module as rule_module');
        $this->db->from($txTable);
        $this->db->join($ruleTable, $ruleTable . '.id = ' . $txTable . '.kpi_rule_id', 'inner');
        $this->db->where($txTable . '.staff_id', (int) $staff_id);
        $this->db->where($txTable . '.status', 'approved');
        $this->db->where($ruleTable . '.module', 'attendance');
        $this->db->where($txTable . '.transaction_date >=', $fromDate);
        $this->db->where($txTable . '.transaction_date <=', $toDate);
        $this->db->order_by($txTable . '.transaction_date', 'ASC');
        $this->db->order_by($txTable . '.id', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function get_active_attendance_rules()
    {
        $this->db->from(db_prefix() . 'kpi_rules');
        $this->db->where('module', 'attendance');
        $this->db->where('is_active', 1);
        $this->db->order_by('id', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * @param list<array<string, mixed>> $transactions
     * @return array<string, list<array<string, mixed>>>
     */
    private function group_transactions_by_date(array $transactions)
    {
        $grouped = [];

        foreach ($transactions as $transaction) {
            $txDate = (string) ($transaction['transaction_date'] ?? '');
            if ($txDate === '') {
                continue;
            }

            if (!isset($grouped[$txDate])) {
                $grouped[$txDate] = [];
            }

            $grouped[$txDate][] = $transaction;
        }

        return $grouped;
    }

    /**
     * @param list<array<string, mixed>> $transactions
     */
    private function sum_monthly_score(array $transactions)
    {
        $total = 0;

        foreach ($transactions as $transaction) {
            $total += (int) ($transaction['points'] ?? 0);
        }

        return $total;
    }

    /**
     * @param list<array<string, mixed>> $transactions
     * @return list<int>
     */
    private function collect_rel_ids(array $transactions)
    {
        $ids = [];

        foreach ($transactions as $transaction) {
            $relId = (int) ($transaction['rel_id'] ?? 0);
            if ($relId > 0) {
                $ids[] = $relId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function get_checkins_by_rel_ids(array $relIds)
    {
        if (empty($relIds)) {
            return [];
        }

        $this->db->select('id, date');
        $this->db->from(db_prefix() . 'check_in_out');
        $this->db->where_in('id', $relIds);
        $rows = $this->db->get()->result_array();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['id']] = $row;
        }

        return $indexed;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function get_month_shift_rows(array $staffContext, $fromDate, $toDate)
    {
        $staffId       = (int) $staffContext['staff_id'];
        $roleId        = (int) $staffContext['role_id'];
        $departmentIds = $staffContext['department_ids'];

        $this->db->from(db_prefix() . 'work_shift');
        $this->db->where('from_date <=', $toDate);
        $this->db->where('to_date >=', $fromDate);
        $this->db->group_start();
        $this->db->where('FIND_IN_SET(' . $staffId . ', staff) > 0', null, false);
        $this->db->or_group_start();
        $this->db->where('(staff = "" OR staff IS NULL)', null, false);
        if ($roleId > 0) {
            $this->db->where('FIND_IN_SET(' . $roleId . ', position) > 0', null, false);
        } else {
            $this->db->where('(position = "" OR position IS NULL)', null, false);
        }
        if (!empty($departmentIds)) {
            $this->db->group_start();
            foreach ($departmentIds as $index => $departmentId) {
                if ($index === 0) {
                    $this->db->where('FIND_IN_SET(' . (int) $departmentId . ', department) > 0', null, false);
                } else {
                    $this->db->or_where('FIND_IN_SET(' . (int) $departmentId . ', department) > 0', null, false);
                }
            }
            $this->db->group_end();
        } else {
            $this->db->where('(department = "" OR department IS NULL)', null, false);
        }
        $this->db->group_end();
        $this->db->group_end();
        $this->db->order_by('id', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * @param list<int> $shiftRowIds
     * @return array<int, array<string, array{staff:array<int,int>,default:int}>>
     */
    private function get_month_absolute_shift_details($staff_id, array $shiftRowIds, $fromDate, $toDate)
    {
        if (empty($shiftRowIds)) {
            return [];
        }

        $this->db->select('work_shift_id, date, staff_id, shift_id');
        $this->db->from(db_prefix() . 'work_shift_detail');
        $this->db->where_in('work_shift_id', $shiftRowIds);
        $this->db->where('date >=', $fromDate);
        $this->db->where('date <=', $toDate);
        $this->db->group_start();
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->or_where('staff_id', 0);
        $this->db->group_end();
        $rows = $this->db->get()->result_array();

        $indexed = [];
        foreach ($rows as $row) {
            $workShiftId = (int) ($row['work_shift_id'] ?? 0);
            $date        = (string) ($row['date'] ?? '');
            $targetStaff = (int) ($row['staff_id'] ?? 0);
            $shiftId     = (int) ($row['shift_id'] ?? 0);

            if ($workShiftId <= 0 || $date === '' || $shiftId <= 0) {
                continue;
            }

            if (!isset($indexed[$workShiftId][$date])) {
                $indexed[$workShiftId][$date] = [
                    'staff'   => [],
                    'default' => 0,
                ];
            }

            if ($targetStaff === (int) $staff_id) {
                $indexed[$workShiftId][$date]['staff'][$targetStaff] = $shiftId;
            } elseif ($targetStaff === 0 && $indexed[$workShiftId][$date]['default'] === 0) {
                $indexed[$workShiftId][$date]['default'] = $shiftId;
            }
        }

        return $indexed;
    }

    /**
     * @param list<int> $shiftRowIds
     * @return array<int, array<int, array{staff:array<int,int>,default:int}>>
     */
    private function get_month_weekday_shift_details($staff_id, array $shiftRowIds)
    {
        if (empty($shiftRowIds)) {
            return [];
        }

        $this->db->select('work_shift_id, number, staff_id, shift_id');
        $this->db->from(db_prefix() . 'work_shift_detail_number_day');
        $this->db->where_in('work_shift_id', $shiftRowIds);
        $this->db->group_start();
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->or_where('staff_id', 0);
        $this->db->group_end();
        $rows = $this->db->get()->result_array();

        $indexed = [];
        foreach ($rows as $row) {
            $workShiftId = (int) ($row['work_shift_id'] ?? 0);
            $weekday     = (int) ($row['number'] ?? 0);
            $targetStaff = (int) ($row['staff_id'] ?? 0);
            $shiftId     = (int) ($row['shift_id'] ?? 0);

            if ($workShiftId <= 0 || $weekday <= 0 || $shiftId <= 0) {
                continue;
            }

            if (!isset($indexed[$workShiftId][$weekday])) {
                $indexed[$workShiftId][$weekday] = [
                    'staff'   => [],
                    'default' => 0,
                ];
            }

            if ($targetStaff === (int) $staff_id) {
                $indexed[$workShiftId][$weekday]['staff'][$targetStaff] = $shiftId;
            } elseif ($targetStaff === 0 && $indexed[$workShiftId][$weekday]['default'] === 0) {
                $indexed[$workShiftId][$weekday]['default'] = $shiftId;
            }
        }

        return $indexed;
    }

    /**
     * @param array<int, array<string, array{staff:array<int,int>,default:int}>> $absoluteShiftDetails
     * @param array<int, array<int, array{staff:array<int,int>,default:int}>> $weekdayShiftDetails
     * @return list<int>
     */
    private function collect_shift_type_ids(array $absoluteShiftDetails, array $weekdayShiftDetails)
    {
        $shiftIds = [];

        foreach ($absoluteShiftDetails as $byDate) {
            foreach ($byDate as $detail) {
                foreach ($detail['staff'] as $shiftId) {
                    $shiftIds[] = (int) $shiftId;
                }
                if (!empty($detail['default'])) {
                    $shiftIds[] = (int) $detail['default'];
                }
            }
        }

        foreach ($weekdayShiftDetails as $byWeekday) {
            foreach ($byWeekday as $detail) {
                foreach ($detail['staff'] as $shiftId) {
                    $shiftIds[] = (int) $shiftId;
                }
                if (!empty($detail['default'])) {
                    $shiftIds[] = (int) $detail['default'];
                }
            }
        }

        return array_values(array_unique(array_filter($shiftIds)));
    }

    /**
     * @param list<int> $shiftTypeIds
     * @return array<int, object>
     */
    private function get_shift_types_by_ids(array $shiftTypeIds)
    {
        if (empty($shiftTypeIds)) {
            return [];
        }

        $this->db->where_in('id', $shiftTypeIds);
        $rows = $this->db->get(db_prefix() . 'shift_type')->result();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row->id] = $row;
        }

        return $indexed;
    }

    /**
     * @return array<string, object>
     */
    private function get_holidays_by_date(array $staffContext, $fromDate, $toDate)
    {
        $primaryDepartmentId = (int) $staffContext['primary_department_id'];
        $roleId              = (int) $staffContext['role_id'];
        $monthDays           = [];

        foreach ($this->enumerate_dates($fromDate, $toDate) as $date) {
            $monthDays[] = date('m-d', strtotime($date));
        }

        $this->db->from(db_prefix() . 'day_off');
        $this->db->group_start();
        $this->db->group_start();
        $this->db->where('repeat_by_year', 0);
        $this->db->where('break_date >=', $fromDate);
        $this->db->where('break_date <=', $toDate);
        $this->db->group_end();
        $this->db->or_group_start();
        $this->db->where('repeat_by_year', 1);
        if (!empty($monthDays)) {
            $escapedMonthDays = array_map([$this->db, 'escape'], array_values(array_unique($monthDays)));
            $this->db->where('DATE_FORMAT(break_date, "%m-%d") IN (' . implode(',', $escapedMonthDays) . ')', null, false);
        }
        $this->db->group_end();
        $this->db->group_end();
        $rows = $this->db->get()->result();

        $byDate = [];
        foreach ($this->enumerate_dates($fromDate, $toDate) as $date) {
            $targetedExact   = null;
            $globalExact     = null;
            $targetedRepeat  = null;
            $globalRepeat    = null;
            $dayKey          = date('m-d', strtotime($date));

            foreach ($rows as $row) {
                if (!$this->holiday_matches_date($row, $date, $dayKey)) {
                    continue;
                }

                $isTargeted = $this->holiday_matches_scope($row, $primaryDepartmentId, $roleId);
                $isGlobal   = $this->holiday_is_global($row);

                if ((int) $row->repeat_by_year === 0 && $isTargeted && $targetedExact === null) {
                    $targetedExact = $row;
                } elseif ((int) $row->repeat_by_year === 0 && $isGlobal && $globalExact === null) {
                    $globalExact = $row;
                } elseif ((int) $row->repeat_by_year === 1 && $isTargeted && $targetedRepeat === null) {
                    $targetedRepeat = $row;
                } elseif ((int) $row->repeat_by_year === 1 && $isGlobal && $globalRepeat === null) {
                    $globalRepeat = $row;
                }
            }

            $match = $targetedExact ?: $globalExact ?: $targetedRepeat ?: $globalRepeat;
            if ($match !== null) {
                $byDate[$date] = $match;
            }
        }

        return $byDate;
    }

    /**
     * @return array<string, true>
     */
    private function get_timesheet_leave_dates($staff_id, $fromDate, $toDate)
    {
        $this->db->select('date_work');
        $this->db->from(db_prefix() . 'timesheets_timesheet');
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('relate_type', 'leave');
        $this->db->where('date_work >=', $fromDate);
        $this->db->where('date_work <=', $toDate);
        $rows = $this->db->get()->result_array();

        $dates = [];
        foreach ($rows as $row) {
            $date = (string) ($row['date_work'] ?? '');
            if ($date !== '') {
                $dates[$date] = true;
            }
        }

        return $dates;
    }

    /**
     * @return array<string, true>
     */
    private function get_requisition_leave_dates($staff_id, $fromDate, $toDate)
    {
        $this->db->select('start_time, end_time');
        $this->db->from(db_prefix() . 'timesheets_requisition_leave');
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('rel_type', 1);
        $this->db->where('status', 1);
        $this->db->where('start_time <=', $toDate . ' 23:59:59');
        $this->db->where('end_time >=', $fromDate . ' 00:00:00');
        $rows = $this->db->get()->result_array();

        $dates = [];
        foreach ($rows as $row) {
            $startDate = date('Y-m-d', strtotime((string) $row['start_time']));
            $endDate   = date('Y-m-d', strtotime((string) $row['end_time']));
            $rangeFrom = $startDate > $fromDate ? $startDate : $fromDate;
            $rangeTo   = $endDate < $toDate ? $endDate : $toDate;

            foreach ($this->enumerate_dates($rangeFrom, $rangeTo) as $date) {
                $dates[$date] = true;
            }
        }

        return $dates;
    }

    /**
     * @param list<array<string, mixed>> $shiftRows
     * @param array<int, array<string, array{staff:array<int,int>,default:int}>> $absoluteShiftDetails
     * @param array<int, array<int, array{staff:array<int,int>,default:int}>> $weekdayShiftDetails
     * @return list<int>
     */
    private function resolve_shift_ids_for_date($date, array $staffContext, array $shiftRows, array $absoluteShiftDetails, array $weekdayShiftDetails)
    {
        $matchingRows = $this->select_matching_shift_rows_for_date($date, $staffContext, $shiftRows);
        $shiftIds     = [];
        $weekday      = (int) date('N', strtotime($date));
        $staffId      = (int) $staffContext['staff_id'];

        foreach ($matchingRows as $row) {
            $workShiftId = (int) ($row['id'] ?? 0);
            $shiftId     = 0;

            if (($row['type_shiftwork'] ?? '') === 'by_absolute_time') {
                $shiftId = $this->resolve_detail_shift_id($absoluteShiftDetails[$workShiftId][$date] ?? null, $staffId);
            } else {
                $shiftId = $this->resolve_detail_shift_id($weekdayShiftDetails[$workShiftId][$weekday] ?? null, $staffId);
            }

            if ($shiftId > 0 && !in_array($shiftId, $shiftIds, true)) {
                $shiftIds[] = $shiftId;
            }
        }

        return $shiftIds;
    }

    /**
     * @param list<array<string, mixed>> $shiftRows
     * @return list<array<string, mixed>>
     */
    private function select_matching_shift_rows_for_date($date, array $staffContext, array $shiftRows)
    {
        $matched = [
            'direct_staff' => [],
            'role_dept'    => [],
            'dept_only'    => [],
            'role_only'    => [],
            'global'       => [],
        ];

        foreach ($shiftRows as $row) {
            if (!$this->shift_row_is_active_on_date($row, $date)) {
                continue;
            }

            $category = $this->categorize_shift_row($row, $staffContext);
            if ($category !== null) {
                $matched[$category][] = $row;
            }
        }

        foreach (['direct_staff', 'role_dept', 'dept_only', 'role_only', 'global'] as $category) {
            if (!empty($matched[$category])) {
                return $matched[$category];
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $row
     * @return string|null
     */
    private function categorize_shift_row(array $row, array $staffContext)
    {
        $staffId       = (int) $staffContext['staff_id'];
        $roleId        = (int) $staffContext['role_id'];
        $departmentIds = $staffContext['department_ids'];
        $staffField    = (string) ($row['staff'] ?? '');
        $positionField = (string) ($row['position'] ?? '');
        $departmentField = (string) ($row['department'] ?? '');

        if ($this->csv_contains_value($staffField, $staffId)) {
            return 'direct_staff';
        }

        $staffEmpty      = $this->csv_field_is_empty($staffField);
        $positionEmpty   = $this->csv_field_is_empty($positionField);
        $departmentEmpty = $this->csv_field_is_empty($departmentField);
        $roleMatches     = $roleId > 0 && $this->csv_contains_value($positionField, $roleId);
        $deptMatches     = $this->csv_contains_any($departmentField, $departmentIds);

        if ($staffEmpty && $roleMatches && $deptMatches) {
            return 'role_dept';
        }

        if ($staffEmpty && $positionEmpty && $deptMatches) {
            return 'dept_only';
        }

        if ($staffEmpty && $roleMatches && $departmentEmpty) {
            return 'role_only';
        }

        if ($staffEmpty && $positionEmpty && $departmentEmpty) {
            return 'global';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function shift_row_is_active_on_date(array $row, $date)
    {
        $fromDate = (string) ($row['from_date'] ?? '');
        $toDate   = (string) ($row['to_date'] ?? '');

        if ($fromDate !== '' && $date < $fromDate) {
            return false;
        }

        if ($toDate !== '' && $date > $toDate) {
            return false;
        }

        return true;
    }

    /**
     * @param array{staff:array<int,int>,default:int}|null $detail
     */
    private function resolve_detail_shift_id($detail, $staffId)
    {
        if (!is_array($detail)) {
            return 0;
        }

        $staffId = (int) $staffId;
        if (isset($detail['staff'][$staffId])) {
            return (int) $detail['staff'][$staffId];
        }

        return (int) ($detail['default'] ?? 0);
    }

    /**
     * Mirrors kpi_attendance_after_checkin: use the first returned shift id only.
     *
     * @param list<int> $shiftIds
     * @param array<int, object> $shiftTypesById
     */
    private function resolve_shift_start_time_from_ids($date, array $shiftIds, array $shiftTypesById)
    {
        if (empty($shiftIds)) {
            return '';
        }

        $firstShiftId = (int) $shiftIds[0];
        if (!isset($shiftTypesById[$firstShiftId])) {
            return '';
        }

        $shiftType = $shiftTypesById[$firstShiftId];

        if (function_exists('kpi_resolve_shift_start_time')) {
            return kpi_resolve_shift_start_time($shiftType, $date);
        }

        $stored = trim((string) ($shiftType->time_start_work ?? ''));

        if ($stored === '') {
            return '';
        }

        $parts = explode(' ', $stored);
        $time  = isset($parts[1]) ? $parts[1] : $parts[0];

        return $date . ' ' . $time;
    }

    /**
     * @param list<array<string, mixed>> $checkinTransactions
     * @param list<array<string, mixed>> $attendanceModuleTransactions
     * @param array<int, array<string, mixed>> $checkinsByRelId
     * @param list<array<string, mixed>> $activeAttendanceRules
     * @return array{status:string,points:int,remarks:string,checkin_display:string,checkin_time:string}
     */
    private function classify_day(
        array $checkinTransactions,
        array $attendanceModuleTransactions,
        array $checkinsByRelId,
        $shiftStartTime,
        array $activeAttendanceRules
    ) {
        $points  = function_exists('kpi_sum_unique_transaction_points')
            ? kpi_sum_unique_transaction_points($checkinTransactions, $attendanceModuleTransactions)
            : 0;

        if (!function_exists('kpi_sum_unique_transaction_points')) {
            foreach ($attendanceModuleTransactions as $transaction) {
                $points += (int) ($transaction['points'] ?? 0);
            }
            foreach ($checkinTransactions as $transaction) {
                $points += (int) ($transaction['points'] ?? 0);
            }
        }

        $remarks = '';

        foreach ($attendanceModuleTransactions as $transaction) {
            if ($remarks === '' && isset($transaction['remarks']) && $transaction['remarks'] !== null && $transaction['remarks'] !== '') {
                $remarks = (string) $transaction['remarks'];
            }
        }

        if (empty($checkinTransactions)) {
            return [
                'status'          => 'Absent',
                'points'          => $points,
                'remarks'         => $remarks,
                'checkin_display' => '',
                'checkin_time'    => '',
            ];
        }

        $ruleKeys    = [];
        $checkinTime = '';

        foreach ($checkinTransactions as $transaction) {
            $ruleKeys[] = $this->extract_rule_key($transaction['rules_json'] ?? null);

            if ($checkinTime === '') {
                $relId = (int) ($transaction['rel_id'] ?? 0);
                if ($relId > 0 && isset($checkinsByRelId[$relId])) {
                    $checkinTime = (string) ($checkinsByRelId[$relId]['date'] ?? '');
                }
            }

            if ($remarks === '' && isset($transaction['remarks']) && $transaction['remarks'] !== null && $transaction['remarks'] !== '') {
                $remarks = (string) $transaction['remarks'];
            }
        }

        $checkinDisplay = $checkinTime !== '' ? date('H:i', strtotime($checkinTime)) : '';
        $lateRuleKeys   = $this->collect_rule_keys_by_name($activeAttendanceRules, 'late_arrival');
        $earlyRuleKeys  = $this->collect_rule_keys_by_name($activeAttendanceRules, 'early_and_on_time_arrival');

        if ($this->rule_keys_intersect($ruleKeys, $lateRuleKeys)) {
            return [
                'status'          => 'Late',
                'points'          => $points,
                'remarks'         => $remarks,
                'checkin_display' => $checkinDisplay,
                'checkin_time'    => $checkinTime,
            ];
        }

        if ($this->rule_keys_intersect($ruleKeys, $earlyRuleKeys)) {
            $diff = $this->minutes_after_shift_start($checkinTime, $shiftStartTime);

            return [
                'status'          => $diff <= 0 ? 'Early' : 'On Time',
                'points'          => $points,
                'remarks'         => $remarks,
                'checkin_display' => $checkinDisplay,
                'checkin_time'    => $checkinTime,
            ];
        }

        return [
            'status'          => 'On Time',
            'points'          => $points,
            'remarks'         => $remarks,
            'checkin_display' => $checkinDisplay,
            'checkin_time'    => $checkinTime,
        ];
    }

    /**
     * @param list<array<string, mixed>> $activeRules
     * @return list<string>
     */
    private function collect_rule_keys_by_name(array $activeRules, $ruleName)
    {
        $keys = [];

        foreach ($activeRules as $rule) {
            if ($this->extract_rule_key($rule['rules_json'] ?? null) === $ruleName) {
                $keys[] = $ruleName;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param list<string> $observedRuleKeys
     * @param list<string> $targetRuleKeys
     */
    private function rule_keys_intersect(array $observedRuleKeys, array $targetRuleKeys)
    {
        foreach ($observedRuleKeys as $ruleKey) {
            if ($ruleKey !== '' && in_array($ruleKey, $targetRuleKeys, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mirrors Kpi_rule_engine::timeDiff (private) — round((checkin - shift_start) / 60).
     */
    private function minutes_after_shift_start($checkinTime, $shiftStartTime)
    {
        if ($checkinTime === '' || $shiftStartTime === '') {
            return 0;
        }

        return (int) round((strtotime($checkinTime) - strtotime($shiftStartTime)) / 60);
    }

    /**
     * @param array<string, mixed>|string|null $rulesJson
     */
    private function extract_rule_key($rulesJson)
    {
        if (is_array($rulesJson)) {
            return (string) ($rulesJson['rule'] ?? '');
        }

        if (!is_string($rulesJson) || $rulesJson === '') {
            return '';
        }

        $decoded = json_decode($rulesJson, true);

        return is_array($decoded) ? (string) ($decoded['rule'] ?? '') : '';
    }

    private function get_attendance_performance_grade($onTimeRate)
    {
        $rate = (int) $onTimeRate;

        if ($rate >= 90) {
            return 'Excellent';
        }
        if ($rate >= 75) {
            return 'Good';
        }
        if ($rate >= 50) {
            return 'Average';
        }

        return 'Needs Improvement';
    }

    private function status_label_class($status)
    {
        switch ($status) {
            case 'Early':
                return 'label-success';
            case 'On Time':
                return 'label-info';
            case 'Late':
                return 'label-warning';
            case 'Absent':
                return 'label-danger';
            default:
                return 'label-default';
        }
    }

    /**
     * @return list<string>
     */
    private function enumerate_dates($fromDate, $toDate)
    {
        if ($fromDate === '' || $toDate === '' || $fromDate > $toDate) {
            return [];
        }

        $dates   = [];
        $current = $fromDate;

        while ($current <= $toDate) {
            $dates[] = $current;
            $current = date('Y-m-d', strtotime($current . ' +1 day'));
        }

        return $dates;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<int>
     */
    private function pluck_ids(array $rows)
    {
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function csv_field_is_empty($value)
    {
        $value = trim((string) $value);

        return $value === '' || $value === '0';
    }

    private function csv_contains_value($csv, $needle)
    {
        $needle = (string) (int) $needle;
        if ($needle === '0') {
            return false;
        }

        foreach (explode(',', (string) $csv) as $part) {
            if (trim($part) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<int> $needles
     */
    private function csv_contains_any($csv, array $needles)
    {
        foreach ($needles as $needle) {
            if ($this->csv_contains_value($csv, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function holiday_is_global($row)
    {
        return $this->csv_field_is_empty($row->department ?? '') && $this->csv_field_is_empty($row->position ?? '');
    }

    private function holiday_matches_scope($row, $primaryDepartmentId, $roleId)
    {
        $departmentMatches = $primaryDepartmentId > 0 && $this->csv_contains_value((string) ($row->department ?? ''), $primaryDepartmentId);
        $roleMatches       = $roleId > 0 && $this->csv_contains_value((string) ($row->position ?? ''), $roleId);

        if ($primaryDepartmentId > 0 && $roleId > 0) {
            return $departmentMatches || $roleMatches;
        }
        if ($primaryDepartmentId <= 0 && $roleId > 0) {
            return $roleMatches;
        }
        if ($primaryDepartmentId > 0 && $roleId <= 0) {
            return $departmentMatches;
        }

        return false;
    }

    private function holiday_matches_date($row, $date, $monthDay)
    {
        if ((int) ($row->repeat_by_year ?? 0) === 0) {
            return (string) ($row->break_date ?? '') === $date;
        }

        return date('m-d', strtotime((string) $row->break_date)) === $monthDay;
    }
}
