<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Project_boq extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
        $this->load->model('project_boq_model');
    }

    /**
     * AJAX: list BOQ rows for a project (JSON).
     *
     * @param int $project_id
     */
    public function index($project_id)
    {
        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        $project_id = (int) $project_id;

        if (! $this->can_access_project($project_id)) {
            ajax_access_denied();
        }

        $items = $this->project_boq_model->get_by_project_id($project_id);

        $this->json_response([
            'success' => true,
            'data'    => $items,
        ]);
    }

    /**
     * AJAX: list BOQ items with remaining qty for purchase forms.
     *
     * Returns only rows where remaining_qty > 0, grouped by item_id.
     *
     * @param int $project_id
     */
    public function available_items($project_id)
    {
        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        $project_id = (int) $project_id;
        if ($project_id < 1 || ! $this->can_access_project($project_id)) {
            ajax_access_denied();
        }

        $boq_rows = $this->project_boq_model->get_by_project_id($project_id);
        $grouped  = [];

        foreach ($boq_rows as $row) {
            $item_id = isset($row['item_id']) ? (int) $row['item_id'] : 0;
            if ($item_id < 1) {
                continue;
            }

            $revised   = isset($row['revised_qty']) ? (float) $row['revised_qty'] : 0;
            $purchased = isset($row['purchased_qty']) ? (float) $row['purchased_qty'] : 0;
            $remaining = $revised - $purchased;
            if ($remaining <= 0) {
                continue;
            }

            if (! isset($grouped[$item_id])) {
                $grouped[$item_id] = [
                    'item_id'      => $item_id,
                    'item_name'    => (string) ($row['item_description'] ?? ''),
                    'remaining_qty'=> 0,
                ];
            }

            $grouped[$item_id]['remaining_qty'] += $remaining;
        }

        foreach ($grouped as &$item) {
            $item['remaining_qty'] = (float) number_format((float) $item['remaining_qty'], get_decimal_places(), '.', '');
        }
        unset($item);

        $this->json_response([
            'success' => true,
            'data'    => array_values($grouped),
        ]);
    }

    /**
     * AJAX: add BOQ line.
     */
    public function add()
    {
        if (! $this->input->is_ajax_request() || ! $this->input->post()) {
            show_404();
        }

        $project_id = (int) $this->input->post('project_id');
        $item_id    = (int) $this->input->post('item_id');
        $planned    = $this->normalize_qty($this->input->post('planned_qty'));

        if (! $this->can_edit_project($project_id)) {
            ajax_access_denied();
        }

        $validation = $this->validate_planned_qty_for_add($planned);
        if ($validation !== true) {
            $this->json_response(['success' => false, 'message' => $validation]);

            exit;
        }

        if ($item_id < 1 || ! $this->item_exists($item_id)) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_invalid_item')]);

            exit;
        }

        $id = $this->project_boq_model->add([
            'project_id'  => $project_id,
            'item_id'     => $item_id,
            'planned_qty' => $planned,
            'revised_qty' => $planned,
        ]);

        if (! $id) {
            $this->json_response(['success' => false, 'message' => _l('something_went_wrong')]);

            exit;
        }

        $row = $this->project_boq_model->get($id);

        $this->json_response([
            'success' => true,
            'message' => _l('added_successfully', _l('project_boq_line')),
            'row'     => $row,
        ]);
    }

    /**
     * AJAX: update BOQ line (revised_qty and/or item only; planned_qty is immutable).
     */
    public function update()
    {
        if (! $this->input->is_ajax_request() || ! $this->input->post()) {
            show_404();
        }

        $id         = (int) $this->input->post('id');
        $project_id = (int) $this->input->post('project_id');
        $revised    = $this->normalize_qty($this->input->post('revised_qty'));

        $posted_item = $this->input->post('item_id');
        $item_id     = ($posted_item === null || $posted_item === '' || $posted_item === false)
            ? null
            : (int) $posted_item;

        $existing = $this->project_boq_model->get($id);

        if (! $existing || (int) $existing->project_id !== $project_id) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_line_not_found')]);

            exit;
        }

        if (! $this->can_edit_project($project_id)) {
            ajax_access_denied();
        }

        $purchased = (float) $existing->purchased_qty;

        $validation = $this->validate_revised_qty($revised, $purchased);
        if ($validation !== true) {
            $this->json_response(['success' => false, 'message' => $validation]);

            exit;
        }

        if ($item_id !== null && $item_id > 0 && ! $this->item_exists($item_id)) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_invalid_item')]);

            exit;
        }

        $data = ['revised_qty' => $revised];
        if ($item_id !== null && $item_id > 0) {
            $data['item_id'] = $item_id;
        }

        $this->project_boq_model->update_row($id, $data);

        $row = $this->project_boq_model->get($id);

        $this->json_response([
            'success' => true,
            'message' => _l('updated_successfully', _l('project_boq_line')),
            'row'     => $row,
        ]);
    }

    /**
     * AJAX: delete BOQ line.
     */
    public function delete()
    {
        if (! $this->input->is_ajax_request() || ! $this->input->post()) {
            show_404();
        }

        $id         = (int) $this->input->post('id');
        $project_id = (int) $this->input->post('project_id');

        $existing = $this->project_boq_model->get($id);

        if (! $existing || (int) $existing->project_id !== $project_id) {
            $this->json_response(['success' => false, 'message' => _l('project_boq_line_not_found')]);

            exit;
        }

        if (! $this->can_edit_project($project_id)) {
            ajax_access_denied();
        }

        $ok = $this->project_boq_model->delete($id);

        $this->json_response([
            'success' => $ok,
            'message' => $ok ? _l('deleted', _l('project_boq_line')) : _l('problem_deleting', _l('project_boq_line_lowercase')),
        ]);
    }

    /**
     * @param array|object $data
     */
    protected function json_response($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }

    protected function can_access_project($project_id)
    {
        return staff_can('view', 'projects') || $this->projects_model->is_member($project_id);
    }

    protected function can_edit_project($project_id)
    {
        return staff_can('edit', 'projects');
    }

    protected function item_exists($item_id)
    {
        return total_rows(db_prefix() . 'items', ['id' => $item_id]) > 0;
    }

    /**
     * @param mixed $value
     * @return string decimal string for DB
     */
    protected function normalize_qty($value)
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return number_format((float) $value, get_decimal_places(), '.', '');
    }

    /**
     * Baseline planned qty on create only (purchased is always 0).
     *
     * @param string $planned normalized decimal string
     * @return true|string
     */
    protected function validate_planned_qty_for_add($planned)
    {
        $planned = (float) $planned;

        if ($planned < 0) {
            return _l('project_boq_planned_qty_non_negative');
        }

        return true;
    }

    /**
     * Revised qty must be >= purchased_qty.
     *
     * @param string $revised normalized decimal string
     * @param float  $purchased
     * @return true|string
     */
    protected function validate_revised_qty($revised, $purchased)
    {
        $revised   = (float) $revised;
        $purchased = (float) $purchased;

        if ($revised < 0) {
            return _l('project_boq_revised_qty_non_negative');
        }

        if ($revised < $purchased) {
            return _l('project_boq_revised_not_below_purchased');
        }

        return true;
    }
}
