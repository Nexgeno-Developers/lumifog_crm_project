<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: BOQ
Description: Bill of Quantities tracking for projects.
Version: 1.0.0
Requires at least: 2.3.*
Author: Lumi
Author URI: https://lumifog.com
*/

define('BOQ_MODULE_NAME', 'boq');

hooks()->add_action('admin_init', 'boq_module_init');
hooks()->add_action('after_email_templates', 'boq_add_email_templates');
hooks()->add_filter('project_tabs', 'boq_add_project_tab');

register_activation_hook(BOQ_MODULE_NAME, 'boq_module_activation_hook');
register_language_files(BOQ_MODULE_NAME, [BOQ_MODULE_NAME]);

$CI = & get_instance();
$CI->load->helper(BOQ_MODULE_NAME . '/boq');

function boq_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

function boq_module_init()
{
    $CI = &get_instance();
    $CI->app_tabs->add_project_tab('project_boq', [
        'name'     => _l('project_boq'),
        'icon'     => 'fa-solid fa-list-ol',
        'view'     => 'boq/admin/projects/tabs/boq',
        'position' => 52,
    ]);

    register_staff_capabilities('view_boq', [
        'capabilities' => [
            'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
            'edit' => _l('permission_edit'),
        ],
    ], _l('project_boq'));

    $CI->app->add_settings_section_child('other', 'boq', [
        'name'     => _l('project_boq'),
        'view'     => 'boq/settings',
        'position' => 25,
        'icon'     => 'fa-solid fa-list-ol',
    ]);
}

function boq_add_email_templates()
{
    $CI = &get_instance();
    $data['boq_templates'] = $CI->emails_model->get(['type' => 'boq', 'language' => 'english']);
    $CI->load->view('boq/boq_email_templates', $data);
}

function boq_add_project_tab($tabs)
{
    if (!isset($tabs['project_boq'])) {
        $tabs['project_boq'] = [
            'slug'     => 'project_boq',
            'name'     => _l('project_boq'),
            'icon'     => 'fa-solid fa-list-ol',
            'view'     => 'boq/admin/projects/tabs/boq',
            'position' => 52,
            'visible'  => true,
            'children' => [],
            'badge'    => [],
        ];
    } elseif (empty($tabs['project_boq']['slug'])) {
        $tabs['project_boq']['slug'] = 'project_boq';
    }

    return $tabs;
}
