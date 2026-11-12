<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Project Profit & Loss
Description: Profit & loss dashboard on the project overview (sales invoices vs approved purchase orders and expenses)
Version: 1.0.1
Requires at least: 3.0.*
Author: Nexgeno
*/

define('PROJECT_PNL_MODULE_NAME', 'project_pnl');
define('PROJECT_PNL_VERSION', '1.0.1');

hooks()->add_action('admin_init', 'project_pnl_permissions');
hooks()->add_action('admin_project_overview_end_of_project_overview_right', 'project_pnl_render_dashboard');
hooks()->add_action('app_admin_head', 'project_pnl_head_assets');
hooks()->add_action('app_admin_footer', 'project_pnl_footer_assets');

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

/**
 * Only the admin project view page needs the dashboard assets.
 */
function project_pnl_is_project_view_page()
{
    $CI = &get_instance();

    return $CI->router->fetch_class() === 'projects' && $CI->router->fetch_method() === 'view';
}

function project_pnl_can_view()
{
    return staff_can('view', PROJECT_PNL_MODULE_NAME);
}

function project_pnl_head_assets()
{
    if (!project_pnl_is_project_view_page() || !project_pnl_can_view()) {
        return;
    }

    echo '<link href="' . module_dir_url(PROJECT_PNL_MODULE_NAME, 'assets/css/project_pnl.css') . '?v=' . PROJECT_PNL_VERSION . '" rel="stylesheet" type="text/css" />';
}

function project_pnl_footer_assets()
{
    if (!project_pnl_is_project_view_page() || !project_pnl_can_view()) {
        return;
    }

    echo '<script src="' . module_dir_url(PROJECT_PNL_MODULE_NAME, 'assets/js/project_pnl.js') . '?v=' . PROJECT_PNL_VERSION . '"></script>';
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
        'project' => $project,
        'pnl'     => $pnl,
    ]);
}
