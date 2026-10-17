<?php

defined('BASEPATH') or exit('No direct script access allowed');

define('REALTIME_SALARY_MODULE_NAME', 'realtime_salary');

hooks()->add_action('admin_init', 'realtime_salary_permissions');
hooks()->add_action('admin_init', 'realtime_salary_add_sidebar_menu');
hooks()->add_action('app_admin_head', 'realtime_salary_add_head_components');

register_activation_hook(REALTIME_SALARY_MODULE_NAME, 'realtime_salary_activation_hook');
register_uninstall_hook(REALTIME_SALARY_MODULE_NAME, 'realtime_salary_uninstall_hook');
register_language_files(REALTIME_SALARY_MODULE_NAME, [REALTIME_SALARY_MODULE_NAME]);

/**
 * Register module capabilities.
 */
function realtime_salary_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view_own' => _l('permission_view') . ' (' . _l('permission_own') . ')',
        'view'     => _l('permission_view') . ' (' . _l('permission_global') . ')',
    ];

    register_staff_capabilities('realtime_salary', $capabilities, _l('realtime_salary'));
}

/**
 * Add sidebar menu item.
 */
function realtime_salary_add_sidebar_menu()
{
    $CI = &get_instance();

    // Available to all active staff members
    if (!is_staff_logged_in()) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('realtime-salary', [
        'name'     => _l('realtime_salary_menu'),
        'href'     => admin_url('realtime_salary'),
        'icon'     => 'fa-solid fa-money-bill-wave',
        'position' => 58,
        'badge'    => [],
    ]);
}

/**
 * Load CSS assets and menu icon on admin pages.
 */
function realtime_salary_add_head_components()
{
    // Ensure sidebar icon displays with crisp styling across all themes and FA versions
    echo '<style>
        .menu-item-realtime-salary .menu-icon:before {
            content: "\f53a" !important;
            font-family: "Font Awesome 6 Free", "FontAwesome" !important;
            font-weight: 900 !important;
            display: inline-block;
        }
    </style>';

    $CI = &get_instance();
    if ($CI->router->fetch_class() == 'realtime_salary') {
        echo '<link href="' . module_dir_url('realtime_salary', 'assets/css/realtime_salary.css') . '?v=' . time() . '" rel="stylesheet" type="text/css" />';
    }
}

/**
 * Module activation hook.
 */
function realtime_salary_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
}

/**
 * Module uninstall hook.
 */
function realtime_salary_uninstall_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/uninstall.php';
}
