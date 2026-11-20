<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Serves an expense receipt inline so it can be previewed in the browser
 * (core download/file/expense always forces a download).
 *
 * URL: admin/purchase/expense_receipt/preview/{expense_id}
 */
class Expense_receipt extends AdminController
{
    /**
     * File types that browsers can display safely inline.
     * SVG / HTML are deliberately excluded (they can run script).
     */
    private const INLINE_TYPES = [
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
    ];

    public function preview($expense_id = '')
    {
        $expense_id = (int) $expense_id;
        if ($expense_id <= 0) {
            show_404();
        }

        // Same rule as core Expenses::pdf(): full view, or the staff member who created it
        $this->db->select('id, addedfrom');
        $this->db->where('id', $expense_id);
        $expense = $this->db->get(db_prefix() . 'expenses')->row();

        if (!$expense) {
            show_404();
        }

        if (staff_cant('view', 'expenses') && (int) $expense->addedfrom !== (int) get_staff_user_id()) {
            access_denied('expenses');
        }

        $this->db->where('rel_id', $expense_id);
        $this->db->where('rel_type', 'expense');
        $file = $this->db->get(db_prefix() . 'files')->row();

        if (!$file) {
            show_404();
        }

        $ext = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));

        // Not previewable: fall back to the normal core download
        if (!isset(self::INLINE_TYPES[$ext])) {
            redirect(site_url('download/file/expense/' . $expense_id));
        }

        $base = realpath(get_upload_path_by_type('expense') . $expense_id);
        $path = realpath(get_upload_path_by_type('expense') . $expense_id . '/' . $file->file_name);

        // Block path traversal and missing files
        if ($base === false || $path === false || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
            show_404();
        }

        $filename = str_replace(['"', "\r", "\n"], '', basename($file->file_name));

        header('Content-Type: ' . self::INLINE_TYPES[$ext]);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');

        if (ob_get_level()) {
            ob_end_clean();
        }
        readfile($path);
        exit;
    }
}
