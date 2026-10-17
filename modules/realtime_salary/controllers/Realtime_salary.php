<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Realtime_salary extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('realtime_salary_model');
    }

    /**
     * Main entry point
     */
    public function index()
    {
        $logged_in_staff_id = get_staff_user_id();
        $can_view_others    = is_admin() || has_permission('realtime_salary', '', 'view');

        // Determine month (default to current month)
        $current_month = date('Y-m');
        $requested_month = $this->input->get('month');

        if (!empty($requested_month) && preg_match('/^\d{4}-\d{2}$/', $requested_month)) {
            $selected_month = $requested_month;
        } else {
            $selected_month = $current_month;
        }

        // Future month guard: strictly prevent navigating to the future
        if ($selected_month > $current_month) {
            set_alert('warning', _l('realtime_salary_future_month_error'));
            redirect(admin_url('realtime_salary?month=' . $current_month));
        }

        // Calculate navigation months
        $prev_month = date('Y-m', strtotime($selected_month . '-01 -1 month'));
        $next_month = date('Y-m', strtotime($selected_month . '-01 +1 month'));
        $can_go_next = ($next_month <= $current_month);

        $view_mode = $this->input->get('view');
        $target_staff_id = $this->input->get('staff_id');

        // SCENARIO 1: Admin viewing specific staff member OR admin clicked "My Salary"
        if ($can_view_others) {
            if (!empty($target_staff_id) && is_numeric($target_staff_id)) {
                // Admin viewing a specific staff member's breakdown
                $staff_id = (int) $target_staff_id;
                $salary_data = $this->realtime_salary_model->get_realtime_salary_data($staff_id, $selected_month);

                $data = [
                    'title'                   => _l('realtime_salary') . ' - ' . ($salary_data['has_contract'] ? $salary_data['staff']->firstname . ' ' . $salary_data['staff']->lastname : 'Staff #' . $staff_id),
                    'staff_id'                => $staff_id,
                    'can_view_others'         => true,
                    'is_admin_viewing_staff'  => true,
                    'staff_list'              => $this->realtime_salary_model->get_staff_selector_list(),
                    'selected_month'          => $selected_month,
                    'current_month'           => $current_month,
                    'prev_month'              => $prev_month,
                    'next_month'              => $next_month,
                    'can_go_next'             => $can_go_next,
                    'salary_data'             => $salary_data,
                    'active_tab'              => 'single_staff',
                ];

                $this->load->view('realtime_salary/view_salary', $data);
                return;
            }

            if ($view_mode === 'my_salary') {
                // Admin viewing their own personal salary breakdown
                $salary_data = $this->realtime_salary_model->get_realtime_salary_data($logged_in_staff_id, $selected_month);

                $data = [
                    'title'                   => _l('realtime_salary_my_salary'),
                    'staff_id'                => $logged_in_staff_id,
                    'can_view_others'         => true,
                    'is_admin_viewing_staff'  => false,
                    'staff_list'              => $this->realtime_salary_model->get_staff_selector_list(),
                    'selected_month'          => $selected_month,
                    'current_month'           => $current_month,
                    'prev_month'              => $prev_month,
                    'next_month'              => $next_month,
                    'can_go_next'             => $can_go_next,
                    'salary_data'             => $salary_data,
                    'active_tab'              => 'my_salary',
                ];

                $this->load->view('realtime_salary/view_salary', $data);
                return;
            }

            // SCENARIO 2: Default Admin View -> Company Overview of All Staff
            $staff_status = $this->input->get('status');
            if (!in_array($staff_status, ['active', 'inactive', 'all'])) {
                $staff_status = 'active';
            }

            $overview = $this->realtime_salary_model->get_all_staff_realtime_overview($selected_month, $staff_status);

            $data = [
                'title'            => _l('realtime_salary_admin_title'),
                'can_view_others'  => true,
                'selected_month'   => $selected_month,
                'current_month'    => $current_month,
                'prev_month'       => $prev_month,
                'next_month'       => $next_month,
                'can_go_next'      => $can_go_next,
                'staff_status'     => $staff_status,
                'overview'         => $overview,
                'active_tab'       => 'all_staff',
            ];

            $this->load->view('realtime_salary/manage', $data);
            return;
        }

        // SCENARIO 3: Regular staff member -> Always their own salary view
        $salary_data = $this->realtime_salary_model->get_realtime_salary_data($logged_in_staff_id, $selected_month);

        $data = [
            'title'                   => _l('realtime_salary'),
            'staff_id'                => $logged_in_staff_id,
            'can_view_others'         => false,
            'is_admin_viewing_staff'  => false,
            'staff_list'              => [],
            'selected_month'          => $selected_month,
            'current_month'           => $current_month,
            'prev_month'              => $prev_month,
            'next_month'              => $next_month,
            'can_go_next'             => $can_go_next,
            'salary_data'             => $salary_data,
            'active_tab'              => 'my_salary',
        ];

        $this->load->view('realtime_salary/view_salary', $data);
    }
}
