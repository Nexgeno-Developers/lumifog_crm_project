<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: KPI
Description: Attendance KPI and performance tracking
Version: 1.0.0
Author: Faraz Zaveri
*/

// ------------------------------------------------------
// ✅ MODULE ACTIVATION HOOK
// ------------------------------------------------------
register_activation_hook('KPI', 'kpi_activate_module');

function kpi_activate_module() {
    $CI = &get_instance();
    
    // Install permissions using Perfex standard
    kpi_install_permissions();
    
    log_activity('KPI Module Activated');
}

// ------------------------------------------------------
// ✅ PERMISSIONS INSTALLATION (Perfex Standard)
// ------------------------------------------------------
function kpi_install_permissions() {
    $CI = &get_instance();
    
    // Check if KPI permission already exists
    $existing = $CI->db->where('short_name', 'kpi')->get(db_prefix() . 'permissions')->row();
    
    if (!$existing) {
        // Insert main permission
        $CI->db->insert(db_prefix() . 'permissions', [
            'name' => 'KPI',
            'short_name' => 'kpi',
            'is_global' => 0
        ]);
        
        $permission_id = $CI->db->insert_id();
        
        // ✅ ONLY INSERT THE 2 PERMISSIONS WE ACTUALLY USE
        $capabilities = [
            ['capability' => 'view_own', 'name' => 'View (Own)'],
            ['capability' => 'view_global', 'name' => 'View (Global)'],
        ];
        
        foreach ($capabilities as $capability) {
            $CI->db->insert(db_prefix() . 'permissions_actions', [
                'permission_id' => $permission_id,
                'capability' => $capability['capability'],
                'name' => $capability['name']
            ]);
        }
        
        log_activity('KPI Permissions Installed');
    }
}

// ------------------------------------------------------
// ✅ REGISTER PERMISSIONS WITH PERFEX
// ------------------------------------------------------
hooks()->add_filter('staff_permissions', 'kpi_add_staff_permissions');

function kpi_add_staff_permissions($permissions, $data = []) {
    $viewGlobalName = _l('permission_view') . '(' . _l('permission_global') . ')';
    
    $kpiPermissions = [
        'kpi' => [
            'name'         => 'KPI',
            'capabilities' => [
                'view_own' => _l('permission_view_own'),
              
            ],
            'help' => [
                'view_own' => 'Can only view their own KPI data',
                
            ],
        ],
    ];
    
    return array_merge($permissions, $kpiPermissions);
}

// ------------------------------------------------------
// ✅ ADMIN MENU STRUCTURE - FIXED PERMISSION CHECK
// ------------------------------------------------------
hooks()->add_action('admin_init', 'kpi_init_menu_items');

function kpi_init_menu_items() {
    $CI = &get_instance();
    
    // ✅ FIXED: Check for the correct permission names
    // Show for ALL admins and staff with KPI permissions
    if (is_admin() || staff_can('view_own', 'kpi') || staff_can('view_global', 'kpi')) {
        $CI->app_menu->add_sidebar_menu_item('kpi', [
            'name'     => 'KPI',
            'icon'     => 'fa fa-chart-bar',
            'position' => 35,
        ]);
        
        $CI->app_menu->add_sidebar_children_item('kpi', [
            'slug'     => 'attendance-kpi',
            'name'     => 'Attendance KPI',
            'href'     => admin_url('kpi'),
            'position' => 1,
        ]);
    }
}