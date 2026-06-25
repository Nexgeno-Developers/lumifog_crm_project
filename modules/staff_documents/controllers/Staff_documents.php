<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Staff_documents extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('staff_documents_model');
    }

    /**
     * Main documents management page.
     */
    public function index()
    {
        if (!staff_documents_can_view()) {
            access_denied('staff_documents');
        }

        if ($this->input->is_ajax_request()) {
            echo json_encode([
                'success'   => true,
                'documents' => $this->format_documents_for_json($this->get_documents_for_user()),
            ]);

            return;
        }

        $data['title']              = _l('staff_documents_manage');
        $data['has_global_view']    = staff_documents_has_global_view();
        $data['filters']            = $this->get_filters();
        $data['documents']          = $this->get_documents_for_user($data['filters']);

        if ($data['has_global_view']) {
            $this->load->model('staff_model');
            $data['staff_members'] = $this->staff_model->get('', ['active' => 1, 'is_not_staff' => 0]);
        }

        $this->app_css->add(
            'staff-documents-css',
            module_dir_url('staff_documents', 'assets/css/staff_documents.css'),
            'admin',
            ['app-css']
        );
        $this->app_scripts->add(
            'staff-documents-js',
            module_dir_url('staff_documents', 'assets/js/staff_documents.js'),
            'admin',
            ['app-js']
        );

        $this->load->view('manage', $data);
    }

    /**
     * AJAX file upload handler.
     */
    public function upload()
    {
        if (!has_permission('staff_documents', '', 'create')) {
            $this->json_response(['success' => false, 'message' => _l('access_denied')], 403);
        }

        $staff_id = get_staff_user_id();

        if (!isset($_FILES['document_file']) || empty($_FILES['document_file']['name'])) {
            $this->json_response(['success' => false, 'message' => _l('staff_documents_upload_failed')]);
        }

        $original_name = $_FILES['document_file']['name'];
        $extension     = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if (!staff_documents_is_allowed_extension($extension)) {
            $this->json_response(['success' => false, 'message' => _l('staff_documents_invalid_file_type')]);
        }

        $upload_path = get_staff_documents_upload_path($staff_id);

        if (!is_dir($upload_path)) {
            _maybe_create_upload_path($upload_path);
        }

        if (!is_dir($upload_path) || !is_writable($upload_path)) {
            $this->json_response(['success' => false, 'message' => _l('staff_documents_upload_not_writable')]);
        }

        $this->load->library('upload');

        $config = [
            'upload_path'   => $upload_path,
            'allowed_types' => 'pdf|csv|xls|xlsx',
            'max_size'      => 10240,
            'encrypt_name'  => true,
        ];

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('document_file')) {
            $error = $this->upload->display_errors('', '');

            if (stripos($error, 'larger') !== false) {
                $error = _l('staff_documents_file_too_large');
            }

            $this->json_response(['success' => false, 'message' => $error]);
        }

        $upload_data = $this->upload->data();
        $stored_ext  = strtolower(ltrim($upload_data['file_ext'], '.'));

        if (!staff_documents_is_allowed_extension($stored_ext)) {
            @unlink($upload_data['full_path']);
            $this->json_response(['success' => false, 'message' => _l('staff_documents_invalid_file_type')]);
        }

        $description = $this->input->post('description', true);
        $description = strip_tags($description);

        $insert_id = $this->staff_documents_model->add([
            'staff_id'      => $staff_id,
            'file_name'     => $upload_data['file_name'],
            'original_name' => $original_name,
            'file_type'     => $stored_ext,
            'file_size'     => (int) $upload_data['file_size'],
            'description'   => $description,
        ]);

        if ($insert_id) {
            $this->json_response([
                'success' => true,
                'message' => _l('staff_documents_upload_success'),
            ]);
        }

        @unlink($upload_data['full_path']);
        $this->json_response(['success' => false, 'message' => _l('staff_documents_upload_failed')]);
    }

    /**
     * Serve file inline for preview (PDF iframe).
     *
     * @param int $id
     */
    public function serve($id)
    {
        if (!staff_documents_can_view()) {
            access_denied('staff_documents');
        }

        $document = $this->get_accessible_document($id);

        if (!$document) {
            show_404();
        }

        $file_path = $this->staff_documents_model->get_file_path($document);

        if (!is_file($file_path)) {
            show_404();
        }

        $mime_type = staff_documents_get_mime_type($document['file_type']);

        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: inline; filename="' . rawurlencode($document['original_name']) . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($file_path);
        exit;
    }

    /**
     * Force download a document.
     *
     * @param int $id
     */
    public function download($id)
    {
        if (!staff_documents_can_view()) {
            access_denied('staff_documents');
        }

        $document = $this->get_accessible_document($id);

        if (!$document) {
            show_404();
        }

        $file_path = $this->staff_documents_model->get_file_path($document);

        if (!is_file($file_path)) {
            show_404();
        }

        $this->load->helper('download');

        $mime_type = staff_documents_get_mime_type($document['file_type']);

        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . rawurlencode($document['original_name']) . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($file_path);
        exit;
    }

    /**
     * AJAX delete handler.
     *
     * @param int $id
     */
    public function delete($id)
    {
        if (!has_permission('staff_documents', '', 'delete')) {
            access_denied('staff_documents');
        }

        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $document = $this->get_accessible_document($id);

        if (!$document) {
            echo json_encode(['success' => false, 'message' => _l('staff_documents_delete_failed')]);
            die;
        }

        if (!$this->can_delete_document($document)) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            die;
        }

        $file_path = $this->staff_documents_model->get_file_path($document);

        if (is_file($file_path)) {
            @unlink($file_path);
        }

        $success = $this->staff_documents_model->delete($id);

        echo json_encode([
            'success' => $success,
            'message' => $success ? _l('staff_documents_delete_success') : _l('staff_documents_delete_failed'),
        ]);
        die;
    }

    /**
     * Get documents list based on view permission.
     *
     * @param  array|null $filters
     * @return array
     */
    private function get_documents_for_user($filters = null)
    {
        if ($filters === null) {
            $filters = $this->get_filters();
        }

        if (staff_documents_has_global_view()) {
            return $this->staff_documents_model->get_all($filters);
        }

        return $this->staff_documents_model->get_by_staff(get_staff_user_id());
    }

    /**
     * Parse global-view filter values from GET request.
     *
     * @return array
     */
    private function get_filters()
    {
        $filters = [
            'staff_id'          => '',
            'date_from'         => '',
            'date_to'           => '',
            'date_from_display' => '',
            'date_to_display'   => '',
        ];

        if (!staff_documents_has_global_view()) {
            return $filters;
        }

        $staff_id = $this->input->get('staff_id');
        if ($staff_id !== null && $staff_id !== '') {
            $filters['staff_id'] = (int) $staff_id;
        }

        $date_from = $this->input->get('date_from');
        if ($date_from) {
            $date_from = strip_tags($date_from);
            $sql_date  = to_sql_date($date_from);
            if ($sql_date) {
                $filters['date_from']         = $sql_date;
                $filters['date_from_display'] = _d($sql_date);
            }
        }

        $date_to = $this->input->get('date_to');
        if ($date_to) {
            $date_to  = strip_tags($date_to);
            $sql_date = to_sql_date($date_to);
            if ($sql_date) {
                $filters['date_to']         = $sql_date;
                $filters['date_to_display'] = _d($sql_date);
            }
        }

        return $filters;
    }

    /**
     * Verify document access (IDOR + view_own / view global).
     *
     * @param  int $id
     * @return array|false
     */
    private function get_accessible_document($id)
    {
        $document = $this->staff_documents_model->get($id);

        if (!$document) {
            return false;
        }

        if (staff_documents_has_global_view()) {
            return $document;
        }

        if (has_permission('staff_documents', '', 'view_own')
            && (int) $document['staff_id'] === (int) get_staff_user_id()) {
            return $document;
        }

        return false;
    }

    /**
     * Check if current user may delete a document.
     *
     * @param  array $document
     * @return bool
     */
    private function can_delete_document($document)
    {
        if (!has_permission('staff_documents', '', 'delete')) {
            return false;
        }

        if (staff_documents_has_global_view()) {
            return true;
        }

        return (int) $document['staff_id'] === (int) get_staff_user_id();
    }

    /**
     * Format documents array for JSON response.
     *
     * @param  array $documents
     * @return array
     */
    private function format_documents_for_json($documents)
    {
        $formatted = [];

        foreach ($documents as $document) {
            $formatted[] = $this->format_single_document($document);
        }

        return $formatted;
    }

    /**
     * Format a single document for JSON response.
     *
     * @param  array $document
     * @return array
     */
    private function format_single_document($document)
    {
        $formatted = [
            'id'            => (int) $document['id'],
            'original_name' => htmlspecialchars($document['original_name'], ENT_QUOTES, 'UTF-8'),
            'file_type'     => htmlspecialchars($document['file_type'], ENT_QUOTES, 'UTF-8'),
            'file_size'     => (int) $document['file_size'],
            'description'   => htmlspecialchars($document['description'], ENT_QUOTES, 'UTF-8'),
            'dateadded'     => _dt($document['dateadded']),
            'serve_url'     => admin_url('staff_documents/serve/' . (int) $document['id']),
            'download_url'  => admin_url('staff_documents/download/' . (int) $document['id']),
            'can_delete'    => $this->can_delete_document($document),
        ];

        if (staff_documents_has_global_view() && isset($document['firstname'])) {
            $formatted['staff_name'] = htmlspecialchars(
                trim($document['firstname'] . ' ' . $document['lastname']),
                ENT_QUOTES,
                'UTF-8'
            );
        }

        return $formatted;
    }

    /**
     * Output JSON response with correct headers.
     *
     * @param array $data
     * @param int   $status_code
     */
    private function json_response($data, $status_code = 200)
    {
        $this->output->set_status_header($status_code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        die;
    }
}
