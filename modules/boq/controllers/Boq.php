<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Per-staff BOQ preferences (reached at admin/boq/...).
 */
class Boq extends AdminController
{
    /**
     * AJAX: save the logged-in staff member's BOQ table layout (hidden columns + column widths).
     * The layout only affects the staff member's own view, so no extra permission is needed.
     */
    public function save_table_prefs()
    {
        if (! $this->input->is_ajax_request() || ! $this->input->post()) {
            show_404();
        }

        $prefs = boq_sanitize_table_prefs(json_decode((string) $this->input->post('prefs'), true));
        update_staff_meta(get_staff_user_id(), BOQ_TABLE_PREFS_META_KEY, json_encode($prefs));

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}
