<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Emergency Contacts
Description: Adds an Emergency Contact tab to the HR Profile staff member view.
Version: 1.0.0
Requires at least: 2.3.*
Author: Custom
Author URI: https://example.com
*/

define('EMERGENCY_CONTACTS_MODULE_NAME', 'emergency_contacts');

/**
 * Register activation module hook
 */
register_activation_hook(EMERGENCY_CONTACTS_MODULE_NAME, 'emergency_contacts_module_activation_hook');

function emergency_contacts_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files
 */
register_language_files(EMERGENCY_CONTACTS_MODULE_NAME, [EMERGENCY_CONTACTS_MODULE_NAME]);

hooks()->add_filter('hr_profile_tab_name', 'ec_add_tab_name', 10);
hooks()->add_filter('hr_profile_tab_content', 'ec_add_tab_content', 10);
hooks()->add_action('hr_profile_load_js_file', 'ec_load_js_file');

function ec_add_tab_name($tab_names)
{
    if (!in_array('emergency_contact', $tab_names, true)) {
        $tab_names[] = 'emergency_contact';
    }

    return $tab_names;
}

function ec_add_tab_content($tab_content_link)
{
    if (strpos($tab_content_link, 'hr_record/includes/emergency_contact') !== false) {
        $tab_content_link = 'emergency_contacts/tab_content';
    }

    return $tab_content_link;
}

function ec_load_js_file($group_name)
{
    if ($group_name === 'emergency_contact') {
        echo require 'modules/emergency_contacts/assets/js/ec_js.php';
    }
}
