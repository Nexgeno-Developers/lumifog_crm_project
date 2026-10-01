<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Lead Customer Linker
Description: Convert and link repeat leads to existing customer records without creating duplicate clients, with full lead history on customer profiles.
Version: 1.0.0
Requires at least: 2.3.*
Author: Nexgeno Technology Pvt Ltd
*/

define('LEAD_CUSTOMER_LINKER_MODULE_NAME', 'lead_customer_linker');

/**
 * Register activation module hook
 */
register_activation_hook(LEAD_CUSTOMER_LINKER_MODULE_NAME, 'lead_customer_linker_module_activation_hook');

function lead_customer_linker_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files if any
 */
register_language_files(LEAD_CUSTOMER_LINKER_MODULE_NAME, [LEAD_CUSTOMER_LINKER_MODULE_NAME]);

/**
 * Hooks
 */
hooks()->add_action('admin_init', 'lead_customer_linker_init_customer_profile_tabs');
hooks()->add_action('app_admin_footer', 'lead_customer_linker_load_assets');

/**
 * Add "Leads" tab to Customer Profile
 */
function lead_customer_linker_init_customer_profile_tabs()
{
    $CI = &get_instance();

    $badge = '';
    if ($client = get_client()) {
        $client_id     = (int) $client->userid;
        $origin_leadid = !empty($client->leadid) ? (int) $client->leadid : 0;

        $CI->db->from(db_prefix() . 'leads');
        $CI->db->group_start();
        $CI->db->where('client_id', $client_id);
        if ($origin_leadid > 0) {
            $CI->db->or_where('id', $origin_leadid);
        }
        $CI->db->group_end();
        $total_leads = $CI->db->count_all_results();

        if ($total_leads > 0) {
            $badge = ' <span class="badge badge-default">' . $total_leads . '</span>';
        }
    }

    $CI->app_tabs->add_customer_profile_tab('leads', [
        'name'     => _l('leads') . $badge,
        'icon'     => 'fa-solid fa-crosshairs',
        'view'     => 'lead_customer_linker/client_leads_tab',
        'position' => 32,
        'visible'  => (staff_can('view', 'leads') || staff_can('view_own', 'leads')),
        'badge'    => [],
    ]);
}

/**
 * Load JavaScript & Convert Modal Extensions on Admin pages
 */
function lead_customer_linker_load_assets()
{
    $CI = &get_instance();

    // Check if on leads page or client page
    $view_path = module_views_path(LEAD_CUSTOMER_LINKER_MODULE_NAME, 'convert_modal_extension.php');
    if (file_exists($view_path)) {
        $CI->load->view('lead_customer_linker/convert_modal_extension');
    }

    $js_path = module_dir_path(LEAD_CUSTOMER_LINKER_MODULE_NAME, 'assets/js/lead_customer_linker.js');
    $js_ver  = file_exists($js_path) ? filemtime($js_path) : time();
    $js_url  = module_dir_url(LEAD_CUSTOMER_LINKER_MODULE_NAME, 'assets/js/lead_customer_linker.js');
    echo '<script src="' . $js_url . '?v=' . $js_ver . '"></script>';
}
