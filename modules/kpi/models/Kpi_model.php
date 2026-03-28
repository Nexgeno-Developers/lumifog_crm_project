<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * MAIN FUNCTION - Get REAL attendance data WITH EXTRA FILTERS
     */
   /**
 * MAIN FUNCTION - Get REAL attendance data WITH EXTRA FILTERS
 */
public function get_datatable_data($params = [])
{
    $view = isset($params['view']) ? $params['view'] : 'daily';
    $month = isset($params['month']) ? $params['month'] : null;
    $staff_id = isset($params['staff_id']) ? $params['staff_id'] : null;
    $from_date = isset($params['from_date']) ? $params['from_date'] : null;
    $to_date = isset($params['to_date']) ? $params['to_date'] : null;
    $status_filter = isset($params['status_filter']) ? $params['status_filter'] : null;
    $start = isset($params['start']) ? $params['start'] : 0;
    $length = isset($params['length']) ? $params['length'] : 35;
    $search = isset($params['search']) ? $params['search'] : '';
    $can_view_global = isset($params['can_view_global']) ? $params['can_view_global'] : (is_admin() || staff_can('view_global', 'kpi'));

    // Auto-detect view type based on filters
    if (empty($staff_id) && $view === 'daily') {
        // If all staff selected, force monthly view
        $view = 'monthly';
    }

    // Now determine which data method to use based on the actual view and parameters
    if ($view === 'monthly' && $month) {
        $data = $this->get_monthly_data($month, $staff_id, $status_filter, $search, $can_view_global);
    } 
    // All Staff + Single Day - show daily data with check-in/check-out
    elseif (empty($staff_id) && !empty($from_date) && !empty($to_date) && $from_date == $to_date) {
        $data = $this->get_daily_single_data($from_date, $staff_id, $status_filter, $search, $can_view_global);
    }
    // All Staff + Date Range - show range total
    elseif (empty($staff_id) && !empty($from_date) && !empty($to_date) && $from_date != $to_date) {
        $data = $this->get_range_total_data($from_date, $to_date, $staff_id, $status_filter, $search, $can_view_global);
    }
    // Specific Staff + Daily View + Date Range
    elseif ($view === 'daily' && !empty($staff_id) && !empty($from_date) && !empty($to_date) && $from_date != $to_date) {
        $data = $this->get_daily_range_data($from_date, $to_date, $staff_id, $status_filter, $search, $can_view_global);
    } 
    // Specific Staff + Daily View + Single Day
    elseif ($view === 'daily' && !empty($staff_id) && !empty($from_date) && !empty($to_date) && $from_date == $to_date) {
        $data = $this->get_daily_single_data($from_date, $staff_id, $status_filter, $search, $can_view_global);
    } 
    // Specific Staff + Daily View with no date range
    elseif ($view === 'daily' && !empty($staff_id)) {
        $today = date('Y-m-d');
        $data = $this->get_daily_single_data($today, $staff_id, $status_filter, $search, $can_view_global);
    } 
    // All other cases (monthly view, all staff with month, etc.)
    else {
        if ($month) {
            $start_date = $month . '-01';
            if ($month == date('Y-m')) {
                $end_date = date('Y-m-d');
            } else {
                $end_date = date('Y-m-t', strtotime($start_date));
            }
        } else if ($from_date && $to_date) {
            $start_date = $from_date;
            $end_date = $to_date;
        } else {
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
        }
        $data = $this->get_range_total_data($start_date, $end_date, $staff_id, $status_filter, $search, $can_view_global);
    }

    // ✅ APPLY PAGINATION
    if ($length != -1) {
        $data = array_slice($data, $start, $length);
    }

    return $data ?: [];
}
    /**
     * ✅ COUNT ALL RECORDS (without search)
     */
    public function count_all_records($params = [])
    {
        $view = isset($params['view']) ? $params['view'] : 'daily';
        $month = isset($params['month']) ? $params['month'] : null;
        $staff_id = isset($params['staff_id']) ? $params['staff_id'] : null;
        $from_date = isset($params['from_date']) ? $params['from_date'] : null;
        $to_date = isset($params['to_date']) ? $params['to_date'] : null;
        $status_filter = isset($params['status_filter']) ? $params['status_filter'] : null;
        $can_view_global = isset($params['can_view_global']) ? $params['can_view_global'] : (is_admin() || staff_can('view_global', 'kpi'));

        // Auto-detect view type based on filters
        if (empty($staff_id) && $view === 'daily') {
            $view = 'monthly';
        }

        // Now determine which data method to use based on the actual view and parameters
        if ($view === 'monthly' && $month) {
            $data = $this->get_monthly_data($month, $staff_id, $status_filter, '', $can_view_global);
        } elseif ($view === 'daily' && !empty($staff_id) && !empty($from_date) && !empty($to_date) && $from_date != $to_date) {
            $data = $this->get_daily_range_data($from_date, $to_date, $staff_id, $status_filter, '', $can_view_global);
        } elseif ($view === 'daily' && !empty($staff_id) && !empty($from_date) && !empty($to_date) && $from_date == $to_date) {
            $data = $this->get_daily_single_data($from_date, $staff_id, $status_filter, '', $can_view_global);
        } elseif ($view === 'daily' && !empty($staff_id)) {
            $today = date('Y-m-d');
            $data = $this->get_daily_single_data($today, $staff_id, $status_filter, '', $can_view_global);
        } else {
            if ($month) {
                $start_date = $month . '-01';
                if ($month == date('Y-m')) {
                    $end_date = date('Y-m-d');
                } else {
                    $end_date = date('Y-m-t', strtotime($start_date));
                }
            } else if ($from_date && $to_date) {
                $start_date = $from_date;
                $end_date = $to_date;
            } else {
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d');
            }
            $data = $this->get_range_total_data($start_date, $end_date, $staff_id, $status_filter, '', $can_view_global);
        }

        return count($data);
    }

    /**
     * ✅ COUNT FILTERED RECORDS (with search)
     */
    public function count_filtered_records($params = [])
    {
        $view = isset($params['view']) ? $params['view'] : 'daily';
        $month = isset($params['month']) ? $params['month'] : null;
        $staff_id = isset($params['staff_id']) ? $params['staff_id'] : null;
        $from_date = isset($params['from_date']) ? $params['from_date'] : null;
        $to_date = isset($params['to_date']) ? $params['to_date'] : null;
        $status_filter = isset($params['status_filter']) ? $params['status_filter'] : null;
        $search = isset($params['search']) ? $params['search'] : '';
        $can_view_global = isset($params['can_view_global']) ? $params['can_view_global'] : (is_admin() || staff_can('view_global', 'kpi'));

        // Auto-detect view type based on filters
        if (empty($staff_id) && $view === 'daily') {
            $view = 'monthly';
        }

        // Now determine which data method to use based on the actual view and parameters
        if ($view === 'monthly' && $month) {
            $data = $this->get_monthly_data($month, $staff_id, $status_filter, $search, $can_view_global);
        } elseif ($view === 'daily' && !empty($staff_id) && !empty($from_date) && !empty($to_date) && $from_date != $to_date) {
            $data = $this->get_daily_range_data($from_date, $to_date, $staff_id, $status_filter, $search, $can_view_global);
        } elseif ($view === 'daily' && !empty($staff_id) && !empty($from_date) && !empty($to_date) && $from_date == $to_date) {
            $data = $this->get_daily_single_data($from_date, $staff_id, $status_filter, $search, $can_view_global);
        } elseif ($view === 'daily' && !empty($staff_id)) {
            $today = date('Y-m-d');
            $data = $this->get_daily_single_data($today, $staff_id, $status_filter, $search, $can_view_global);
        } else {
            if ($month) {
                $start_date = $month . '-01';
                if ($month == date('Y-m')) {
                    $end_date = date('Y-m-d');
                } else {
                    $end_date = date('Y-m-t', strtotime($start_date));
                }
            } else if ($from_date && $to_date) {
                $start_date = $from_date;
                $end_date = $to_date;
            } else {
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d');
            }
            $data = $this->get_range_total_data($start_date, $end_date, $staff_id, $status_filter, $search, $can_view_global);
        }

        return count($data);
    }

    /**
     * ✅ GET DAILY SINGLE DAY DATA (with check-in/check-out details)
     */
    private function get_daily_single_data($date, $staff_id = null, $status_filter = null, $search = '', $can_view_global = false)
    {
        $staff_condition = "";
        $params = [];
        
        if ($staff_id) {
            $staff_condition = "AND s.staffid = ?";
            $params[] = $staff_id;
        } elseif (!$can_view_global) {
            $staff_condition = "AND s.staffid = ?";
            $params[] = get_staff_user_id();
        } else {
            $staff_condition = "AND s.active = 1";
        }

        $search_condition = "";
        if (!empty($search)) {
            $search_condition = "AND (s.firstname LIKE ? OR s.lastname LIKE ?)";
            $search_term = "%{$search}%";
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $sql = "
            SELECT
                s.staffid,
                s.firstname,
                s.lastname,
                ? as att_date,
                cio.first_checkin,
                cio.last_checkout,
                cio.total_minutes
            FROM " . db_prefix() . "staff s
            LEFT JOIN (
                SELECT 
                    staff_id,
                    DATE(date) AS att_date,
                    MIN(CASE WHEN type_check = 1 THEN date END) AS first_checkin,
                    MAX(CASE WHEN type_check = 2 THEN date END) AS last_checkout,
                    TIMESTAMPDIFF(MINUTE, 
                        MIN(CASE WHEN type_check = 1 THEN date END), 
                        MAX(CASE WHEN type_check = 2 THEN date END)
                    ) AS total_minutes
                FROM " . db_prefix() . "check_in_out 
                WHERE DATE(date) = ?
                GROUP BY staff_id, DATE(date)
            ) cio ON cio.staff_id = s.staffid
            WHERE 1=1
            {$staff_condition}
            {$search_condition}
            ORDER BY s.firstname ASC
        ";

        array_unshift($params, $date, $date);
        
        $rows = $this->db->query($sql, $params)->result_array();

        if (empty($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $check_in_time = $row['first_checkin'] ? date('H:i', strtotime($row['first_checkin'])) : '--';
            $check_out_time = $row['last_checkout'] ? date('H:i', strtotime($row['last_checkout'])) : '--';
            
            // Calculate hours and minutes from total minutes
            $total_hours = '--';
            if ($row['total_minutes'] && $row['total_minutes'] > 0) {
                $hours = floor($row['total_minutes'] / 60);
                $minutes = $row['total_minutes'] % 60;
                if ($minutes > 0) {
                    $total_hours = $hours . ' hrs ' . $minutes . ' mins';
                } else {
                    $total_hours = $hours . ' hrs';
                }
            }
            
            // Calculate status based on check-in time for single day view
            $shift = $this->get_shift_for_staff($row['staffid']);
            $shift_start = $shift && !empty($shift['time_start_work']) ? $shift['time_start_work'] : '09:00';
            
            if ($check_in_time !== '--' && $shift_start) {
                $status = $this->kpi_get_status($shift_start, $check_in_time);
            } else {
                $status = ['color' => 'gray', 'points' => 0];
            }

            // Apply status filter
            if ($status_filter && $status_filter !== 'all') {
                if ($status_filter === 'present' && $status['color'] === 'gray') continue;
                if ($status_filter === 'absent' && $status['color'] !== 'gray') continue;
                if ($status_filter === 'green' && $status['color'] !== 'green') continue;
                if ($status_filter === 'yellow' && $status['color'] !== 'yellow') continue;
                if ($status_filter === 'red' && $status['color'] !== 'red') continue;
            }

            $result[] = [
                'staff_name'   => trim($row['firstname'] . ' ' . $row['lastname']),
                'date'         => $row['att_date'],
                'check_in'     => $check_in_time,
                'check_out'    => $check_out_time,
                'total_hours'  => $total_hours,
                'color'        => $status['color']
            ];
        }

        return $result;
    }

    /**
     * ✅ GET DAILY RANGE DATA (multiple days in daily view - show daily breakdown with check-in/check-out)
     */
    private function get_daily_range_data($start_date, $end_date, $staff_id = null, $status_filter = null, $search = '', $can_view_global = false)
    {
        $staff_condition = "";
        $params = [];
        
        if ($staff_id) {
            $staff_condition = "AND s.staffid = ?";
            $params[] = $staff_id;
        } elseif (!$can_view_global) {
            $staff_condition = "AND s.staffid = ?";
            $params[] = get_staff_user_id();
        } else {
            $staff_condition = "AND s.active = 1";
        }

        $search_condition = "";
        if (!empty($search)) {
            $search_condition = "AND (s.firstname LIKE ? OR s.lastname LIKE ? OR dates.date LIKE ?)";
            $search_term = "%{$search}%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $sql = "
            SELECT
                s.staffid,
                s.firstname,
                s.lastname,
                dates.date AS att_date,
                cio.first_checkin,
                cio.last_checkout,
                cio.total_minutes
            FROM " . db_prefix() . "staff s
            CROSS JOIN (
                SELECT DATE(?) + INTERVAL (a.a + (10 * b.a)) DAY as date
                FROM 
                (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
                CROSS JOIN 
                (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
            ) dates
            LEFT JOIN (
                SELECT 
                    staff_id,
                    DATE(date) AS att_date,
                    MIN(CASE WHEN type_check = 1 THEN date END) AS first_checkin,
                    MAX(CASE WHEN type_check = 2 THEN date END) AS last_checkout,
                    TIMESTAMPDIFF(MINUTE, 
                        MIN(CASE WHEN type_check = 1 THEN date END), 
                        MAX(CASE WHEN type_check = 2 THEN date END)
                    ) AS total_minutes
                FROM " . db_prefix() . "check_in_out 
                WHERE DATE(date) >= ? AND DATE(date) <= ?
                GROUP BY staff_id, DATE(date)
            ) cio ON cio.staff_id = s.staffid AND cio.att_date = dates.date
            WHERE dates.date BETWEEN ? AND ?
            AND dates.date <= CURDATE()
            {$staff_condition}
            {$search_condition}
            ORDER BY s.firstname ASC, dates.date DESC
        ";

        array_unshift($params, $start_date, $start_date, $end_date, $start_date, $end_date);
        
        $rows = $this->db->query($sql, $params)->result_array();

        if (empty($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $check_in_time = $row['first_checkin'] ? date('H:i', strtotime($row['first_checkin'])) : '--';
            $check_out_time = $row['last_checkout'] ? date('H:i', strtotime($row['last_checkout'])) : '--';
            
            // Calculate hours and minutes from total minutes
            $total_hours = '--';
            if ($row['total_minutes'] && $row['total_minutes'] > 0) {
                $hours = floor($row['total_minutes'] / 60);
                $minutes = $row['total_minutes'] % 60;
                if ($minutes > 0) {
                    $total_hours = $hours . ' hrs ' . $minutes . ' mins';
                } else {
                    $total_hours = $hours . ' hrs';
                }
            }
            
            // For daily range view, calculate status for each day
            $shift = $this->get_shift_for_staff($row['staffid']);
            $shift_start = $shift && !empty($shift['time_start_work']) ? $shift['time_start_work'] : '09:00';
            
            if ($check_in_time !== '--' && $shift_start) {
                $status = $this->kpi_get_status($shift_start, $check_in_time);
            } else {
                $status = ['color' => 'gray', 'points' => 0];
            }

            // Apply status filter
            if ($status_filter && $status_filter !== 'all') {
                if ($status_filter === 'present' && $status['color'] === 'gray') continue;
                if ($status_filter === 'absent' && $status['color'] !== 'gray') continue;
                if ($status_filter === 'green' && $status['color'] !== 'green') continue;
                if ($status_filter === 'yellow' && $status['color'] !== 'yellow') continue;
                if ($status_filter === 'red' && $status['color'] !== 'red') continue;
            }

            $result[] = [
                'staff_name'   => trim($row['firstname'] . ' ' . $row['lastname']),
                'date'         => $row['att_date'],
                'check_in'     => $check_in_time,
                'check_out'    => $check_out_time,
                'total_hours'  => $total_hours,
                'color'        => $status['color']
            ];
        }

        return $result;
    }

    /**
     * ✅ GET RANGE TOTAL DATA (multiple days - show total hours only)
     */
    private function get_range_total_data($start_date, $end_date, $staff_id = null, $status_filter = null, $search = '', $can_view_global = false)
    {
        $staff_condition = "";
        $params = [$start_date, $end_date];
        
        if ($staff_id) {
            $staff_condition = "AND s.staffid = ?";
            $params[] = $staff_id;
        } elseif (!$can_view_global) {
            $staff_condition = "AND s.staffid = ?";
            $params[] = get_staff_user_id();
        } else {
            $staff_condition = "AND s.active = 1";
        }

        $search_condition = "";
        if (!empty($search)) {
            $search_condition = "AND (s.firstname LIKE ? OR s.lastname LIKE ?)";
            $search_term = "%{$search}%";
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $sql = "
            SELECT
                s.staffid,
                s.firstname,
                s.lastname,
                SUM(cio.total_minutes) as total_minutes_sum,
                COUNT(cio.att_date) as working_days
            FROM " . db_prefix() . "staff s
            LEFT JOIN (
                SELECT 
                    staff_id,
                    DATE(date) AS att_date,
                    TIMESTAMPDIFF(MINUTE, 
                        MIN(CASE WHEN type_check = 1 THEN date END), 
                        MAX(CASE WHEN type_check = 2 THEN date END)
                    ) AS total_minutes
                FROM " . db_prefix() . "check_in_out 
                WHERE DATE(date) BETWEEN ? AND ?
                GROUP BY staff_id, DATE(date)
            ) cio ON cio.staff_id = s.staffid
            WHERE 1=1
            {$staff_condition}
            {$search_condition}
            GROUP BY s.staffid, s.firstname, s.lastname
            ORDER BY s.firstname ASC
        ";

        $rows = $this->db->query($sql, $params)->result_array();

        if (empty($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            // Calculate hours and minutes from total minutes
            $total_hours = '0 hrs';
            if ($row['total_minutes_sum'] && $row['total_minutes_sum'] > 0) {
                $hours = floor($row['total_minutes_sum'] / 60);
                $minutes = $row['total_minutes_sum'] % 60;
                if ($minutes > 0) {
                    $total_hours = $hours . ' hrs ' . $minutes . ' mins';
                } else {
                    $total_hours = $hours . ' hrs';
                }
            }
            
            $date_range = _d($start_date) . ' to ' . _d($end_date);
            
            // Calculate color based on average daily hours
            $working_days = $row['working_days'] ?: 1;
            $avg_daily_minutes = $row['total_minutes_sum'] / $working_days;
            $avg_daily_hours = $avg_daily_minutes / 60;
            $color = $this->get_color_from_hours($avg_daily_hours);

            // Apply status filter
            if ($status_filter && $status_filter !== 'all') {
                if ($status_filter === 'present' && $color === 'gray') continue;
                if ($status_filter === 'absent' && $color !== 'gray') continue;
                if ($status_filter === 'green' && $color !== 'green') continue;
                if ($status_filter === 'yellow' && $color !== 'yellow') continue;
                if ($status_filter === 'red' && $color !== 'red') continue;
            }

            $result[] = [
                'staff_name'   => trim($row['firstname'] . ' ' . $row['lastname']),
                'date'         => $date_range,
                'check_in'     => '--',
                'check_out'    => '--',
                'total_hours'  => $total_hours,
                'color'        => $color
            ];
        }

        return $result;
    }

    /**
     * ✅ GET MONTHLY DATA
     */
    private function get_monthly_data($month, $staff_id = null, $status_filter = null, $search = '', $can_view_global = false)
    {
        $start_date = $month . '-01';
        if ($month == date('Y-m')) {
            $end_date = date('Y-m-d');
        } else {
            $end_date = date('Y-m-t', strtotime($start_date));
        }

        return $this->get_range_total_data($start_date, $end_date, $staff_id, $status_filter, $search, $can_view_global);
    }

    /**
     * ✅ GET COLOR FROM TOTAL HOURS
     */
    private function get_color_from_hours($hours)
    {
        if ($hours >= 8) return 'green'; // Good daily average
        if ($hours >= 6) return 'yellow'; // Average daily hours
        if ($hours > 0) return 'red'; // Low daily hours
        return 'gray'; // Absent
    }

    /**
     * ✅ ENHANCED SHIFT FOR STAFF WITH 10AM OVERRIDE
     */
    private function get_shift_for_staff($staff_id)
    {
        $manual_late_shift_staff = [10, 11, 12, 13, 14];
        
        if (in_array($staff_id, $manual_late_shift_staff)) {
            return ['time_start_work' => '10:00', 'time_end_work' => '19:00'];
        }
        
        $sql = "
            SELECT st.time_start_work, st.time_end_work
            FROM " . db_prefix() . "work_shift ws
            LEFT JOIN " . db_prefix() . "shift_type st ON st.id = ws.shift_type
            WHERE (ws.staff LIKE '%\"$staff_id\"%' OR FIND_IN_SET(?, ws.staff))
            AND ? BETWEEN ws.from_date AND ws.to_date
            ORDER BY ws.id DESC
            LIMIT 1
        ";
        
        $shift = $this->db->query($sql, [$staff_id, date('Y-m-d')])->row_array();
        
        if (!$shift || empty($shift['time_start_work'])) {
            return ['time_start_work' => '09:00', 'time_end_work' => '18:00'];
        }
        
        return $shift;
    }

    /**
     * ✅ SIMPLIFIED KPI CALCULATION - 4 CATEGORIES ONLY
     */
    private function kpi_get_status($shift_start, $check_in_time)
    {
        if (empty($check_in_time) || $check_in_time === '--') {
            return ['color' => 'gray', 'points' => 0];
        }

        $shift_time = strtotime($shift_start);
        $check_in = strtotime($check_in_time);
        
        if ($shift_time === false || $check_in === false) {
            return ['color' => 'gray', 'points' => 0];
        }

        $minutes_diff = round(($check_in - $shift_time) / 60);

        if ($minutes_diff <= 10) {
            return ['color' => 'green', 'points' => 10];
        } elseif ($minutes_diff <= 20) {
            return ['color' => 'yellow', 'points' => 7];
        } else {
            return ['color' => 'red', 'points' => 4];
        }
    }
}