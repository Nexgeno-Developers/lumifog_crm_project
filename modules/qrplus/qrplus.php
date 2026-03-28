<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: QRPlus
Description: QRPlus is the ultimate QR code module for Perfex CRM. Generate and manage QR codes effortlessly, expanding your CRM's capabilities. Enhance customer engagement and streamline operations with ease. Boost efficiency and stay ahead with QRPlus.
Version: 1.1.2
Author: LenzCreative
Author URI: https://codecanyon.net/user/lenzcreativee/portfolio
Requires at least: 1.0.*
*/

define('QRPLUS_MODULE_NAME', 'qrplus');

hooks()->add_action('admin_init', 'qrplus_module_init_menu_items');
hooks()->add_action('admin_init', 'qrplus_permissions');

include( __DIR__ . '/vendor/autoload.php');

/**
 * Load the module helper
 */
$CI = & get_instance();
$CI->load->helper(QRPLUS_MODULE_NAME . '/qrplus'); //on module main file

function qrplus_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete')
    ];

    register_staff_capabilities('qrplus', $capabilities, _l('qrplus'));
}

/**
 * Register activation module hook
 */
register_activation_hook(QRPLUS_MODULE_NAME, 'qrplus_module_activation_hook');

function qrplus_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(QRPLUS_MODULE_NAME, [QRPLUS_MODULE_NAME]);

/**
 * Init module menu items in setup in admin_init hook
 * @return null
 */
function qrplus_module_init_menu_items()
{
    $CI = &get_instance();

    if (has_permission('qrplus', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('qrplus', [
            'slug' => 'qrplus',
            'name' => _l('qrplus'),
            'position' => 6,
            'href'     => admin_url('qrplus'),
            'icon' => 'fas fa-qrcode'
        ]);
    }

}