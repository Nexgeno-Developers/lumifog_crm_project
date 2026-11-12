<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Project Profit & Loss
Description: Profit & loss dashboard on the project overview, with expenses linked to purchase orders so PO items bought by staff are not counted twice
Version: 1.0.2
Requires at least: 3.0.*
Author: Nexgeno
*/

define('PROJECT_PNL_MODULE_NAME', 'project_pnl');
define('PROJECT_PNL_VERSION', '1.0.2');

hooks()->add_action('admin_init', 'project_pnl_permissions');
hooks()->add_action('admin_project_overview_end_of_project_overview_right', 'project_pnl_render_dashboard');
hooks()->add_action('app_admin_head', 'project_pnl_head_assets');
hooks()->add_action('app_admin_footer', 'project_pnl_footer_assets');

// "Against Purchase Order" on expenses
hooks()->add_action('before_expense_form_template_close', 'project_pnl_expense_form_field');
hooks()->add_filter('before_expense_added', 'project_pnl_validate_expense_po');
hooks()->add_filter('before_expense_updated', 'project_pnl_validate_expense_po', 10, 2);

hooks()->add_filter('module_project_pnl_action_links', 'project_pnl_module_action_links');

register_activation_hook(PROJECT_PNL_MODULE_NAME, 'project_pnl_activation_hook');
register_language_files(PROJECT_PNL_MODULE_NAME, [PROJECT_PNL_MODULE_NAME]);

function project_pnl_activation_hook()
{
    require_once __DIR__ . '/install.php';
}

function project_pnl_permissions()
{
    register_staff_capabilities(PROJECT_PNL_MODULE_NAME, [
        'capabilities' => [
            'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
        ],
    ], _l('project_pnl'));
}

function project_pnl_module_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('project_pnl/settings') . '">' . _l('settings') . '</a>';

    return $actions;
}

function project_pnl_can_view()
{
    return staff_can('view', PROJECT_PNL_MODULE_NAME);
}

/**
 * Page the request is on: 'project' (project view), 'expense' (expense add/edit) or ''.
 */
function project_pnl_current_page()
{
    $CI     = &get_instance();
    $class  = $CI->router->fetch_class();
    $method = $CI->router->fetch_method();

    if ($class === 'projects' && $method === 'view') {
        return 'project';
    }
    if ($class === 'expenses' && $method === 'expense') {
        return 'expense';
    }

    return '';
}

function project_pnl_head_assets()
{
    if (project_pnl_current_page() !== 'project' || !project_pnl_can_view()) {
        return;
    }

    echo '<link href="' . module_dir_url(PROJECT_PNL_MODULE_NAME, 'assets/css/project_pnl.css') . '?v=' . PROJECT_PNL_VERSION . '" rel="stylesheet" type="text/css" />';
}

function project_pnl_footer_assets()
{
    $page = project_pnl_current_page();
    if ($page === '') {
        return;
    }

    // Expense form helper: on the expense page and the project page ("Record Expense" modal)
    if (staff_can('create', 'expenses') || staff_can('edit', 'expenses')) {
        $CI = &get_instance();
        $CI->load->model(PROJECT_PNL_MODULE_NAME . '/project_pnl_model');

        echo '<script>var project_pnl_expense_config = ' . json_encode([
            'options_url'         => admin_url('project_pnl/po_options/'),
            'required_categories' => $CI->project_pnl_model->get_po_required_categories(),
            'labels'              => [
                'field'       => _l('project_pnl_against_po'),
                'none'        => _l('project_pnl_not_against_po'),
                'help'        => _l('project_pnl_against_po_help'),
                'required'    => _l('project_pnl_po_required_msg'),
                'used'        => _l('project_pnl_po_used'),
                'pending'     => _l('project_pnl_po_pending'),
                'closed'      => _l('project_pnl_po_closed'),
                'no_project'  => _l('project_pnl_select_project_first'),
            ],
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
        echo '<script src="' . module_dir_url(PROJECT_PNL_MODULE_NAME, 'assets/js/expense_po.js') . '?v=' . PROJECT_PNL_VERSION . '"></script>';
    }

    if ($page === 'project' && project_pnl_can_view()) {
        echo '<script src="' . module_dir_url(PROJECT_PNL_MODULE_NAME, 'assets/js/project_pnl.js') . '?v=' . PROJECT_PNL_VERSION . '"></script>';
    }
}

/**
 * Placeholder inside the expense form; expense_po.js turns it into the PO dropdown
 * next to the Project field and fills it for the selected project.
 */
function project_pnl_expense_form_field($expense = null)
{
    $selected = ($expense && isset($expense->pur_order_id)) ? (int) $expense->pur_order_id : 0;
    $expenseId = $expense ? (int) $expense->expenseid : 0;

    echo '<div id="project-pnl-po-field" class="hide" data-selected="' . $selected . '" data-expense-id="' . $expenseId . '"></div>';
}

/**
 * Keep only a PO that belongs to the expense's project; clear it otherwise.
 */
function project_pnl_validate_expense_po($data, $id = null)
{
    if (!array_key_exists('pur_order_id', $data)) {
        return $data;
    }

    $CI = &get_instance();
    $CI->load->model(PROJECT_PNL_MODULE_NAME . '/project_pnl_model');

    if (!$CI->project_pnl_model->expense_link_available()) {
        unset($data['pur_order_id']);

        return $data;
    }

    $poId      = (int) $data['pur_order_id'];
    $projectId = (int) ($data['project_id'] ?? 0);

    if ($projectId === 0 && $id) {
        $CI->db->select('project_id')->where('id', (int) $id);
        $row       = $CI->db->get(db_prefix() . 'expenses')->row();
        $projectId = $row ? (int) $row->project_id : 0;
    }

    if ($poId === 0 || $projectId === 0 || !$CI->project_pnl_model->po_belongs_to_project($poId, $projectId)) {
        $data['pur_order_id'] = null;
    } else {
        $data['pur_order_id'] = $poId;
    }

    return $data;
}

/**
 * Rendered inside the overview's right column; project_pnl.js moves it below the
 * overview panel so it spans the full width after all project details.
 */
function project_pnl_render_dashboard($project)
{
    if (empty($project) || !project_pnl_can_view()) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model(PROJECT_PNL_MODULE_NAME . '/project_pnl_model');

    $pnl = $CI->project_pnl_model->get_project_pnl($project);

    $CI->load->view(PROJECT_PNL_MODULE_NAME . '/admin/dashboard', [
        'project'       => $project,
        'pnl'           => $pnl,
        'can_link'      => staff_can('edit', 'expenses'),
        'can_close_po'  => is_admin() || staff_can('edit', 'purchase_orders'),
    ]);
}
