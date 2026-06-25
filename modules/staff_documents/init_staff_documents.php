<?php

defined('BASEPATH') or exit('No direct script access allowed');

define('STAFF_DOCUMENTS_MODULE_NAME', 'staff_documents');
define('STAFF_DOCUMENTS_UPLOAD_PATH', FCPATH . 'modules/staff_documents/uploads/');

hooks()->add_action('admin_init', 'staff_documents_permissions');
hooks()->add_action('admin_init', 'staff_documents_add_sidebar_menu');

register_activation_hook(STAFF_DOCUMENTS_MODULE_NAME, 'staff_documents_activation_hook');
register_uninstall_hook(STAFF_DOCUMENTS_MODULE_NAME, 'staff_documents_uninstall_hook');
register_language_files(STAFF_DOCUMENTS_MODULE_NAME, [STAFF_DOCUMENTS_MODULE_NAME]);

/**
 * Register staff capabilities.
 */
function staff_documents_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view_own' => _l('permission_view') . '(' . _l('permission_own') . ')',
        'view'     => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create'   => _l('permission_create'),
        'delete'   => _l('permission_delete'),
    ];

    register_staff_capabilities('staff_documents', $capabilities, _l('staff_documents'));
}

/**
 * Add top-level sidebar menu item (between Utilities and Reports).
 */
function staff_documents_add_sidebar_menu()
{
    if (!staff_documents_can_view()) {
        return;
    }

    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('staff-documents', [
        'name'     => _l('staff_documents'),
        'href'     => admin_url('staff_documents'),
        'icon'     => 'fa fa-file-text',
        'position' => 57,
        'badge'    => [],
    ]);
}

/**
 * Module activation hook.
 */
function staff_documents_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
}

/**
 * Module uninstall hook.
 */
function staff_documents_uninstall_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/uninstall.php';
}

/**
 * Get upload path for a staff member.
 *
 * @param  int|string $staff_id
 * @return string
 */
function get_staff_documents_upload_path($staff_id = '')
{
    if ($staff_id === '') {
        $staff_id = get_staff_user_id();
    }

    return STAFF_DOCUMENTS_UPLOAD_PATH . (int) $staff_id . '/';
}

/**
 * Allowed file extensions whitelist.
 *
 * @return array
 */
function staff_documents_allowed_extensions()
{
    return ['pdf', 'csv', 'xls', 'xlsx'];
}

/**
 * Validate file extension against whitelist.
 *
 * @param  string $extension
 * @return bool
 */
function staff_documents_is_allowed_extension($extension)
{
    return in_array(strtolower($extension), staff_documents_allowed_extensions(), true);
}

/**
 * Get MIME type for a file type.
 *
 * @param  string $file_type
 * @return string
 */
function staff_documents_get_mime_type($file_type)
{
    $map = [
        'pdf'  => 'application/pdf',
        'csv'  => 'text/csv',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    $file_type = strtolower($file_type);

    return isset($map[$file_type]) ? $map[$file_type] : 'application/octet-stream';
}

/**
 * Check if staff can view documents (own or global).
 *
 * @param  string $staff_id
 * @return bool
 */
function staff_documents_can_view($staff_id = '')
{
    return has_permission('staff_documents', '', 'view', $staff_id)
        || has_permission('staff_documents', '', 'view_own', $staff_id);
}

/**
 * Check if staff has global view (all documents).
 *
 * @param  string $staff_id
 * @return bool
 */
function staff_documents_has_global_view($staff_id = '')
{
    return has_permission('staff_documents', '', 'view', $staff_id);
}
