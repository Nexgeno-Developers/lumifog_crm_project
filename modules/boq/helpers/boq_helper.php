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

if (!defined('BOQ_TABLE_PREFS_META_KEY')) {
    define('BOQ_TABLE_PREFS_META_KEY', 'boq_table_prefs');
}

if (!function_exists('boq_sanitize_table_prefs')) {
    /**
     * Keep only well-formed column keys and sane widths from a BOQ table layout.
     *
     * @param mixed $prefs decoded JSON: ['hidden' => [key, ...], 'widths' => [key => px]]
     * @return array
     */
    function boq_sanitize_table_prefs($prefs)
    {
        $clean = ['hidden' => [], 'widths' => new stdClass()];
        if (!is_array($prefs)) {
            return $clean;
        }

        $valid_key = function ($key) {
            return is_string($key) && preg_match('/^[a-z_]{1,40}$/', $key);
        };

        if (!empty($prefs['hidden']) && is_array($prefs['hidden'])) {
            $clean['hidden'] = array_values(array_unique(array_filter($prefs['hidden'], $valid_key)));
        }

        if (!empty($prefs['widths']) && is_array($prefs['widths'])) {
            $widths = [];
            foreach ($prefs['widths'] as $key => $px) {
                if ($valid_key($key) && is_numeric($px)) {
                    $widths[$key] = max(50, min(1200, (int) $px));
                }
            }
            if ($widths) {
                $clean['widths'] = $widths;
            }
        }

        return $clean;
    }
}

if (!function_exists('boq_get_table_prefs')) {
    /**
     * The logged-in staff member's saved BOQ table layout.
     *
     * @return array
     */
    function boq_get_table_prefs()
    {
        $raw = get_staff_meta(get_staff_user_id(), BOQ_TABLE_PREFS_META_KEY);

        return boq_sanitize_table_prefs($raw ? json_decode($raw, true) : null);
    }
}
