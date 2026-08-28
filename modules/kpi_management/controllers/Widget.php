<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Widget extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('kpi_management/kpi_widget_model');
    }

    /**
     * Get staff list for widget stats popups (AJAX)
     */
    public function get_staff_list()
    {
        if (!is_staff_logged_in()) {
            echo json_encode(['success' => false, 'message' => 'Access denied', 'staff' => []]);
            return;
        }

        if (!kpi_widget_user_can_view_global()) {
            echo json_encode(['success' => false, 'message' => 'Access denied', 'staff' => []]);
            return;
        }

        $type = $this->input->get('type');

        $map = [
            'active'   => ['method' => 'get_active_staff_list', 'title' => 'Active Staff'],
            'present'  => ['method' => 'get_present_staff_list', 'title' => 'Present Staff'],
            'absent'   => ['method' => 'get_absent_staff_list', 'title' => 'Absent Staff'],
            'on_leave' => ['method' => 'get_on_leave_staff_list', 'title' => 'Staff On Leave'],
        ];

        if (!isset($map[$type])) {
            echo json_encode(['success' => false, 'message' => 'Invalid type', 'staff' => []]);
            return;
        }

        $method = $map[$type]['method'];
        $staff_list = $this->kpi_widget_model->{$method}();

        // Ensure absent/present rows always expose shift_start_time for the modal column.
        if ($type === 'absent' || $type === 'present') {
            foreach ($staff_list as &$staff_row) {
                if (!array_key_exists('shift_start_time', $staff_row)) {
                    $staff_row['shift_start_time'] = '-';
                }

                if ($type === 'present') {
                    $staff_row['kpi_badge_color'] = get_attendance_badge_color(
                        array_key_exists('kpi_points', $staff_row) ? $staff_row['kpi_points'] : null
                    );
                }
            }
            unset($staff_row);
        }

        echo json_encode([
            'success' => true,
            'type'    => $type,
            'title'   => $map[$type]['title'],
            'staff'   => $staff_list,
        ]);
    }
}
