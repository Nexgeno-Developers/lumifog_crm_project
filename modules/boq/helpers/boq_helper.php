<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('get_boq_item_description')) {
    function get_boq_item_description($boq_item_id)
    {
        $CI = &get_instance();
        if (!isset($CI->project_boq_model)) {
            $CI->load->model('boq/Boq_model', 'project_boq_model');
        }

        $boq = $CI->project_boq_model->get((int) $boq_item_id);

        return $boq ? $boq->boq_description : '';
    }
}

if (!function_exists('is_boq_approver')) {
    function is_boq_approver($staff_id = null)
    {
        if (!$staff_id) {
            $staff_id = get_staff_user_id();
        }
        if (!$staff_id) {
            return false;
        }
        if (is_admin($staff_id)) {
            return true;
        }
        $approver_id = get_option('boq_revision_approver');
        if (!empty($approver_id)) {
            $approvers = array_map('trim', explode(',', (string) $approver_id));
            if (in_array((string) $staff_id, $approvers, true)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('get_boq_approver_ids')) {
    function get_boq_approver_ids()
    {
        $approver_id = get_option('boq_revision_approver');
        $ids = [];
        if (!empty($approver_id)) {
            $ids = array_map('intval', array_filter(array_map('trim', explode(',', (string) $approver_id))));
        }
        return $ids;
    }
}
