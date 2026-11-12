<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Project_pnl extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('project_pnl/project_pnl_model');
        $this->load->model('projects_model');
    }

    /**
     * Staff may use the project's finance data if they can see the project.
     */
    private function can_access_project($projectId)
    {
        if (staff_can('view', 'projects')) {
            return true;
        }

        return (bool) $this->projects_model->is_member($projectId, get_staff_user_id());
    }

    /**
     * JSON: POs of a project for the expense form "Against Purchase Order" dropdown.
     */
    public function po_options($projectId = 0, $expenseId = 0)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        if (staff_cant('create', 'expenses') && staff_cant('edit', 'expenses')) {
            ajax_access_denied();
        }

        $projectId = (int) $projectId;
        if ($projectId <= 0 || !$this->can_access_project($projectId)) {
            echo json_encode(['options' => []]);

            return;
        }

        $this->load->model('projects_model');
        $currency = $this->projects_model->get_currency($projectId);

        $options = [];
        foreach ($this->project_pnl_model->get_po_options($projectId, (int) $expenseId) as $po) {
            $po['value_formatted'] = app_format_money($po['value'], $currency);
            $po['spent_formatted'] = app_format_money($po['spent'], $currency);
            $options[]             = $po;
        }

        echo json_encode(['options' => $options]);
    }

    /**
     * Screen to link a project's existing expenses to its purchase orders.
     */
    public function link_expenses($projectId = 0)
    {
        $projectId = (int) $projectId;
        $project   = $this->projects_model->get($projectId);

        if (!$project || staff_cant('edit', 'expenses') || !project_pnl_can_view() || !$this->can_access_project($projectId)) {
            access_denied('project_pnl');
        }
        if (!$this->project_pnl_model->expense_link_available()) {
            set_alert('warning', _l('project_pnl_upgrade_required'));
            redirect(admin_url('projects/view/' . $projectId));
        }

        if ($this->input->post()) {
            $links   = $this->input->post('links');
            $updated = $this->project_pnl_model->save_expense_links($projectId, is_array($links) ? $links : []);
            set_alert('success', _l('project_pnl_links_saved', (string) $updated));
            redirect(admin_url('project_pnl/link_expenses/' . $projectId));
        }

        $currency = $this->projects_model->get_currency($projectId);

        $data['title']        = _l('project_pnl_link_expenses') . ' - ' . $project->name;
        $data['project']      = $project;
        $data['currency']     = $currency;
        $data['expenses']     = $this->project_pnl_model->get_project_expenses_for_linking($projectId);
        $data['po_options']   = $this->project_pnl_model->get_po_options($projectId);
        $data['required_cat'] = $this->project_pnl_model->get_po_required_categories();

        $this->load->view('project_pnl/admin/link_expenses', $data);
    }

    /**
     * POST: close / reopen a PO for costing (closed POs stop counting their unused balance).
     */
    public function toggle_po_close()
    {
        if (!$this->input->post()) {
            show_404();
        }

        $poId      = (int) $this->input->post('pur_order_id');
        $closed    = (int) $this->input->post('closed') === 1;
        $projectId = $this->project_pnl_model->get_po_project_id($poId);

        if ($projectId === 0 || !project_pnl_can_view() || !$this->can_access_project($projectId)
            || (!is_admin() && staff_cant('edit', 'purchase_orders'))) {
            access_denied('project_pnl');
        }

        $this->project_pnl_model->set_po_closed($poId, $closed);
        set_alert('success', $closed ? _l('project_pnl_po_closed_msg') : _l('project_pnl_po_reopened_msg'));
        redirect(admin_url('projects/view/' . $projectId));
    }

    /**
     * Module settings: expense categories where a PO must be selected.
     */
    public function settings()
    {
        if (!is_admin()) {
            access_denied('project_pnl');
        }

        $this->load->model('expenses_model');

        if ($this->input->post()) {
            $ids = $this->input->post('po_required_categories');
            $ids = is_array($ids) ? array_values(array_unique(array_map('intval', $ids))) : [];
            update_option('project_pnl_po_required_categories', json_encode($ids));
            set_alert('success', _l('settings_updated'));
            redirect(admin_url('project_pnl/settings'));
        }

        $data['title']      = _l('project_pnl') . ' - ' . _l('settings');
        $data['categories'] = $this->expenses_model->get_category();
        $data['selected']   = $this->project_pnl_model->get_po_required_categories();

        $this->load->view('project_pnl/admin/settings', $data);
    }
}
