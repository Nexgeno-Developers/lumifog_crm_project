<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ✅ ENHANCED KPI CALCULATION LOGIC
 */
function kpi_calculate_points($check_in_time, $shift_start_time)
{
    if (empty($check_in_time) || empty($shift_start_time)) {
        return ['points' => 0, 'color' => 'gray', 'status' => 'Absent'];
    }
    
    $check_in = strtotime($check_in_time);
    $shift_start = strtotime($shift_start_time);
    
    if ($check_in === false || $shift_start === false) {
        return ['points' => 0, 'color' => 'gray', 'status' => 'Invalid Time'];
    }
    
    $minutes_diff = round(($check_in - $shift_start) / 60);
    
    // Enhanced scoring logic
    if ($minutes_diff <= 0) {
        // Early or exactly on time
        return ['points' => 10, 'color' => 'green', 'status' => 'Excellent'];
    } elseif ($minutes_diff <= 10) {
        // Up to 10 minutes late
        return ['points' => 8, 'color' => 'green', 'status' => 'Good'];
    } elseif ($minutes_diff <= 20) {
        // 11-20 minutes late
        return ['points' => 5, 'color' => 'yellow', 'status' => 'Average'];
    } elseif ($minutes_diff <= 30) {
        // 21-30 minutes late
        return ['points' => 3, 'color' => 'yellow', 'status' => 'Late'];
    } else {
        // More than 30 minutes late
        return ['points' => 1, 'color' => 'red', 'status' => 'Very Late'];
    }
}

/**
 * ✅ GET COLOR CLASS FOR POINTS
 */
function kpi_get_color_class($points)
{
    if ($points >= 7) return 'success';
    if ($points >= 4) return 'warning';
    return 'danger';
}

/**
 * ✅ CHECK IF USER CAN VIEW ALL STAFF DATA
 */
function kpi_can_view_all_staff()
{
    return is_admin();
}