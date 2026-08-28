<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: KPI Management (Nex KPI)
Description: KPI rules and transactions foundation
Version: 1.0.0
Requires at least: 2.3.*
Author: Nex KPI
*/

define('KPI_MANAGEMENT_MODULE_NAME', 'kpi_management');

register_language_files(KPI_MANAGEMENT_MODULE_NAME, ['kpi_notifications']);

hooks()->add_action('app_init', 'kpi_management_load_helpers');
hooks()->add_action('admin_init', 'kpi_management_init_menu_items');
hooks()->add_action('admin_init', 'kpi_management_permissions');
hooks()->add_action('admin_init', 'kpi_management_widget_notification_permissions');
hooks()->add_action('app_admin_head', 'kpi_management_add_notification_styles');
hooks()->add_action('app_admin_head', 'kpi_management_load_widget_css');
hooks()->add_action('app_admin_footer', 'kpi_management_add_notification_js');
hooks()->add_action('app_admin_footer', 'kpi_management_load_widget_js');
hooks()->add_action('admin_navbar_start', 'kpi_management_add_notification_icon');
hooks()->add_filter('get_dashboard_widgets', 'kpi_management_add_dashboard_widget');
//hooks()->add_action('admin_init', 'kpi_management_ensure_install');

/**
 * Register activation module hook
 */
register_activation_hook(KPI_MANAGEMENT_MODULE_NAME, 'kpi_management_module_activation_hook');

/**
 * Load helpers when KPI Management is active (init_kpi_transaction, attendance hooks).
 */
function kpi_management_load_helpers()
{
    $CI = &get_instance();

    if (!$CI->app_modules->is_active(KPI_MANAGEMENT_MODULE_NAME)) {
        return;
    }

    $CI->load->helper(KPI_MANAGEMENT_MODULE_NAME . '/kpi_management');
}

function kpi_management_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Safety net: ensure required tables exist (idempotent).
 * This prevents 500 errors if module was activated before install.php updates.
 */
// function kpi_management_ensure_install()
// {
//     $CI = &get_instance();

//     if (!$CI->db->table_exists(db_prefix() . 'kpi_rules') || !$CI->db->table_exists(db_prefix() . 'kpi_transactions')) {
//         require_once(__DIR__ . '/install.php');
//     }
// }

function kpi_management_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('kpi_management', $capabilities, 'KPI Management');
}

/**
 * Widget + notification permissions (kept as legacy kpi_* keys for existing role assignments).
 */
function kpi_management_widget_notification_permissions()
{
    register_staff_capabilities('kpi', [
        'capabilities' => [
            'approve_requests' => _l('kpi_approve_requests'),
        ],
    ], _l('kpi_management') . ' — Notifications');

    register_staff_capabilities('kpi_widget_overview', [
        'capabilities' => [
            'view_own' => _l('permission_view_own'),
            'view'     => _l('permission_view') . '(' . _l('permission_global') . ')',
        ],
    ], _l('KPI Widget Overview'));
}

function kpi_management_add_notification_styles()
{
    try {
        if (kpi_management_should_show_notifications()) {
            echo '<link href="' . module_dir_url(KPI_MANAGEMENT_MODULE_NAME, 'assets/css/kpi_notifications.css') . '" rel="stylesheet" />';
        }
    } catch (Exception $e) {
    } catch (Throwable $e) {
    }
}

function kpi_management_add_notification_js()
{
    try {
        if (kpi_management_should_show_notifications()) {
            echo '<script src="' . module_dir_url(KPI_MANAGEMENT_MODULE_NAME, 'assets/js/kpi_notifications.js') . '"></script>';
        }
    } catch (Exception $e) {
    } catch (Throwable $e) {
    }
}

function kpi_management_is_dashboard_request()
{
    $uri   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $clean = explode('?', $uri)[0];

    return (bool) (preg_match('#/admin/?$#', $clean) || preg_match('#/admin/dashboard#', $clean));
}

function kpi_management_load_widget_js()
{
    try {
        if (!kpi_widget_user_can_view()) {
            return;
        }
        if (kpi_management_is_dashboard_request()) {
            $js_path = module_dir_path(KPI_MANAGEMENT_MODULE_NAME, 'assets/js/kpi_widget.js');
            $js_ver  = is_file($js_path) ? (string) filemtime($js_path) : (string) time();
            echo '<script src="' . module_dir_url(KPI_MANAGEMENT_MODULE_NAME, 'assets/js/kpi_widget.js') . '?v=' . $js_ver . '"></script>';
        }
    } catch (Exception $e) {
    } catch (Throwable $e) {
    }
}

/**
 * Widget CSS cannot ship inside the widget view: render_dashboard_widgets() keeps
 * only the view's first root element, so a trailing <style> block never renders.
 */
function kpi_management_load_widget_css()
{
    try {
        if (!kpi_widget_user_can_view()) {
            return;
        }
        if (kpi_management_is_dashboard_request()) {
            $css_path = module_dir_path(KPI_MANAGEMENT_MODULE_NAME, 'assets/css/kpi_widget.css');
            $css_ver  = is_file($css_path) ? (string) filemtime($css_path) : (string) time();
            echo '<link rel="stylesheet" href="' . module_dir_url(KPI_MANAGEMENT_MODULE_NAME, 'assets/css/kpi_widget.css') . '?v=' . $css_ver . '" />';
        }
    } catch (Exception $e) {
    } catch (Throwable $e) {
    }
}

function kpi_management_add_dashboard_widget($widgets)
{
    try {
        if (kpi_widget_user_can_view()) {
            array_unshift($widgets, [
                'path'      => 'kpi_management/kpi_widget',
                'container' => 'top-12',
            ]);
        }
    } catch (Exception $e) {
    } catch (Throwable $e) {
    }

    return $widgets;
}

function kpi_management_add_notification_icon()
{
    $output = '';
    try {
        if (!kpi_management_should_show_notifications()) {
            return;
        }

        $CI = &get_instance();
        $CI->load->model('kpi_management/kpi_notifications_model');

        $old_debug = $CI->db->db_debug;
        $CI->db->db_debug = false;

        $pending_ot_count    = 0;
        $pending_pl_count    = 0;
        $open_tickets_count  = 0;
        $pending_ot_requests = [];
        $pending_pl_requests = [];
        $open_tickets        = [];

        try { $pending_ot_count    = (int) $CI->kpi_notifications_model->get_pending_ot_count(); } catch (Throwable $e) { log_message('error', 'KPI Management OT count: ' . $e->getMessage()); }
        try { $pending_pl_count    = (int) $CI->kpi_notifications_model->get_pending_pl_count(); } catch (Throwable $e) { log_message('error', 'KPI Management PL count: ' . $e->getMessage()); }
        try { $open_tickets_count  = (int) $CI->kpi_notifications_model->get_open_tickets_count(); } catch (Throwable $e) { log_message('error', 'KPI Management Tickets count: ' . $e->getMessage()); }
        try { $pending_ot_requests = $CI->kpi_notifications_model->get_pending_ot_requests(5) ?: []; } catch (Throwable $e) { log_message('error', 'KPI Management OT list: ' . $e->getMessage()); }
        try { $pending_pl_requests = $CI->kpi_notifications_model->get_pending_pl_requests(5) ?: []; } catch (Throwable $e) { log_message('error', 'KPI Management PL list: ' . $e->getMessage()); }
        try { $open_tickets        = $CI->kpi_notifications_model->get_open_tickets_for_staff(5) ?: []; } catch (Throwable $e) { log_message('error', 'KPI Management Tickets list: ' . $e->getMessage()); }

        $CI->db->db_debug = $old_debug;

        $data = [
            'pending_ot_count'       => $pending_ot_count,
            'pending_pl_count'       => $pending_pl_count,
            'open_tickets_count'     => $open_tickets_count,
            'total_pending_requests' => $pending_ot_count + $pending_pl_count + $open_tickets_count,
            'pending_ot_requests'    => $pending_ot_requests,
            'pending_pl_requests'    => $pending_pl_requests,
            'open_tickets'           => $open_tickets,
        ];

        ob_start();
        try {
            $CI->load->view('kpi_management/notifications/notification_icon', $data);
            $output = ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            log_message('error', 'KPI Management notification view error: ' . $e->getMessage());
            return;
        }
    } catch (Exception $e) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        log_message('error', 'KPI Management icon error: ' . $e->getMessage());
        return;
    } catch (Throwable $e) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        log_message('error', 'KPI Management icon throwable: ' . $e->getMessage());
        return;
    }

    if (!empty($output)) {
        echo $output;
    }
}

function kpi_management_should_show_notifications()
{
    static $cached_result = null;
    if ($cached_result !== null) {
        return $cached_result;
    }

    $cached_result = false;

    try {
        if (!function_exists('is_staff_logged_in') || !is_staff_logged_in()) {
            return false;
        }

        $CI = &get_instance();

        if (!isset($CI->db) || !is_object($CI->db)) {
            return false;
        }

        if (isset($CI->db->conn_id) && empty($CI->db->conn_id) && $CI->db->conn_id !== 0) {
            return false;
        }

        $current_staff_id = get_staff_user_id();
        if (!$current_staff_id) {
            return false;
        }

        if (is_admin() || has_permission('kpi', '', 'approve_requests')) {
            $cached_result = true;
            return true;
        }

        $old_debug = $CI->db->db_debug;
        $CI->db->db_debug = false;

        if ($CI->db->table_exists(db_prefix() . 'timesheets_approval_details')) {
            $q = $CI->db->select('staffid')
                ->from(db_prefix() . 'timesheets_approval_details')
                ->where('staffid', $current_staff_id)
                ->limit(1)
                ->get();
            if ($q && $q->num_rows() > 0) {
                $CI->db->db_debug = $old_debug;
                $cached_result = true;
                return true;
            }
        }

        $has_team_manage_col = false;
        try {
            $cols = $CI->db->list_fields(db_prefix() . 'staff');
            $has_team_manage_col = is_array($cols) && in_array('team_manage', $cols);
        } catch (Throwable $e) {
        }

        if ($has_team_manage_col) {
            $q = $CI->db->select('staffid')
                ->from(db_prefix() . 'staff')
                ->where('team_manage', $current_staff_id)
                ->limit(1)
                ->get();
            if ($q && $q->num_rows() > 0) {
                $CI->db->db_debug = $old_debug;
                $cached_result = true;
                return true;
            }
        }

        if ($CI->db->table_exists(db_prefix() . 'staff_departments')) {
            $cols2 = [];
            try {
                $cols2 = $CI->db->list_fields(db_prefix() . 'staff_departments');
            } catch (Throwable $e) {
            }
            if (in_array('manager_id', (array) $cols2)) {
                $q = $CI->db->select('departmentid')
                    ->from(db_prefix() . 'staff_departments')
                    ->where('manager_id', $current_staff_id)
                    ->limit(1)
                    ->get();
                if ($q && $q->num_rows() > 0) {
                    $CI->db->db_debug = $old_debug;
                    $cached_result = true;
                    return true;
                }
            }
        }

        $CI->db->db_debug = $old_debug;
        $cached_result = false;
        return false;
    } catch (Exception $e) {
        log_message('error', 'KPI Management should_show_notifications error: ' . $e->getMessage());
        $cached_result = false;
        return false;
    } catch (Throwable $e) {
        log_message('error', 'KPI Management should_show_notifications throwable: ' . $e->getMessage());
        $cached_result = false;
        return false;
    }
}

/**
 * Init module menu items in admin area.
 * Parent + Personal Dashboard: all logged-in staff.
 * Admin items (Dashboard, Rules, Transactions, Reports): kpi_management view permission only.
 */
function kpi_management_init_menu_items()
{
    if (!is_staff_logged_in()) {
        return;
    }

    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('kpi_management', [
        'slug'     => 'kpi-management',
        'name'     => 'KPI Management',
        'icon'     => 'fa-solid fa-chart-line',
        'position' => 35,
    ]);

    $CI->app_menu->add_sidebar_children_item('kpi_management', [
        'slug'     => 'kpi_management_personal_dashboard',
        'name'     => 'Personal Dashboard',
        'icon'     => 'fa-solid fa-user-chart',
        'href'     => admin_url('kpi_management/personal_dashboard'),
        'position' => 1,
    ]);

    if (!has_permission('kpi_management', '', 'view')) {
        return;
    }

    $CI->app_menu->add_sidebar_children_item('kpi_management', [
        'slug'     => 'kpi_management_dashboard',
        'name'     => _l('dashboard'),
        'icon'     => 'fa fa-home',
        'href'     => admin_url('kpi_management'),
        'position' => 2,
    ]);

    $CI->app_menu->add_sidebar_children_item('kpi_management', [
        'slug'     => 'kpi_management_rules',
        'name'     => 'Rules',
        'icon'     => 'fa-regular fa-rectangle-list',
        'href'     => admin_url('kpi_management/rules'),
        'position' => 3,
    ]);

    $CI->app_menu->add_sidebar_children_item('kpi_management', [
        'slug'     => 'kpi_management_transactions',
        'name'     => 'Transactions',
        'icon'     => 'fa-solid fa-right-left',
        'href'     => admin_url('kpi_management/transactions'),
        'position' => 4,
    ]);

    $CI->app_menu->add_sidebar_children_item('kpi_management', [
        'slug'     => 'kpi_management_reports',
        'name'     => 'Reports',
        'icon'     => 'fa-solid fa-chart-pie',
        'href'     => admin_url('kpi_management/reports'),
        'position' => 5,
    ]);
}

