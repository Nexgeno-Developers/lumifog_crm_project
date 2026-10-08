<?php defined('BASEPATH') or exit('No direct script access allowed');

$can_edit_boq   = isset($can_edit_boq) ? $can_edit_boq : false;
$can_view_items = isset($can_view_items) ? $can_view_items : false;
$boq_items      = isset($boq_items) ? $boq_items : [];
$items_grouped  = isset($boq_items_grouped) ? $boq_items_grouped : [];
if (!isset($po_restriction_enabled)) {
    $CI = &get_instance();
    if (!isset($CI->project_boq_model)) {
        $CI->load->model('boq/Boq_model', 'project_boq_model');
    }
    $po_restriction_enabled = (isset($CI->project_boq_model) && method_exists($CI->project_boq_model, 'is_po_restriction_enabled'))
        ? $CI->project_boq_model->is_po_restriction_enabled($project->id)
        : true;
}
?>
<div id="project-boq-wrapper" class="project-boq-tab" data-project-id="<?= (int) $project->id; ?>"
    data-can-edit="<?= $can_edit_boq ? '1' : '0'; ?>"
    data-can-view-items="<?= $can_view_items ? '1' : '0'; ?>">
    <?php if (! $can_view_items) { ?>
    <div class="alert alert-warning">
        <?= _l('project_boq_items_permission'); ?>
    </div>
    <?php } ?>

    <div class="_buttons tw-mb-4 clearfix">
        <?php if ($can_view_items) { ?>
        <button type="button" class="btn btn-primary" id="project-boq-open-add" data-toggle="modal"
            data-target="#project_boq_request_modal" data-request-mode="add_item">
            <i class="fa-regular fa-plus tw-mr-1"></i>
            <?= _l('project_boq_add_line'); ?>
        </button>
        <button type="button" class="btn btn-default" id="project-boq-import-csv">
            <i class="fa-regular fa-file-csv tw-mr-1"></i>
            <?= _l('project_boq_import_csv'); ?>
        </button>
        <input type="file" id="project-boq-csv-file" accept=".csv, .xlsx, .xls" style="display:none">
        <?php } ?>
        <?php if ($can_view_items) { ?>
        <button type="button" class="btn btn-default" id="project-boq-purchase-request">
            <i class="fa-regular fa-file-lines tw-mr-1"></i>
            <?= _l('project_boq_purchase_request'); ?>
        </button>
        <a href="<?= admin_url('project_boq/download_template/' . $project->id); ?>"
           class="btn btn-default" id="project-boq-download-template">
            <i class="fa-regular fa-file-excel tw-mr-1"></i>
            <?= _l('project_boq_download_template'); ?>
        </a>
        <?php } ?>
        <?php if (is_admin()) { ?>
        <div class="pull-right tw-flex tw-items-center" style="margin-top: 5px;">
            <label for="project_boq_po_restriction_toggle" class="tw-mb-0 tw-mr-2 text-muted" style="font-weight: 500; font-size: 13px; cursor: pointer; user-select: none;">
                <i class="fa-solid fa-shield-halved tw-mr-1 text-primary"></i><?= _l('project_boq_po_restriction'); ?>
            </label>
            <div class="onoffswitch" style="display: inline-block; vertical-align: middle;">
                <input type="checkbox" 
                       name="project_boq_po_restriction" 
                       class="onoffswitch-checkbox" 
                       id="project_boq_po_restriction_toggle" 
                       data-project-id="<?= (int) $project->id; ?>" 
                       <?= $po_restriction_enabled ? 'checked' : ''; ?>>
                <label class="onoffswitch-label" for="project_boq_po_restriction_toggle">
                    <span class="onoffswitch-inner"></span>
                    <span class="onoffswitch-switch"></span>
                </label>
            </div>
        </div>
        <?php } ?>
    </div>

    <div class="panel_s">
        <div class="panel-body">
            <div class="table-responsive" style="overflow-x:auto;">
                <table class="table table-bordered table-hover" id="project-boq-table" style="min-width:2200px; white-space:nowrap;">
                    <thead>
                        <tr>
                            <?php if ($can_view_items) { ?>
                            <th class="text-center" style="width:40px;">
                                <input type="checkbox" id="project-boq-check-all" aria-label="<?= e(_l('project_boq_select_rows')); ?>">
                            </th>
                            <?php } ?>
                            <th><?= _l('project_boq_item'); ?></th>
                            <th class="text-right"><?= _l('project_boq_planned_qty'); ?></th>
                            <th class="text-right"><?= _l('project_boq_revised_qty'); ?></th>
                            <th><?= _l('project_boq_internal_ref_no'); ?></th>
                            <th><?= _l('project_boq_client_ref_no'); ?></th>
                            <th><?= _l('project_boq_unit'); ?></th>
                            <th class="text-right"><?= _l('project_boq_supply_rate'); ?></th>
                            <th class="text-right"><?= _l('project_boq_itc_rate'); ?></th>
                            <th class="text-right"><?= _l('project_boq_supply_amount'); ?></th>
                            <th class="text-right"><?= _l('project_boq_itc_amount'); ?></th>
                            <th class="text-right"><?= _l('project_boq_tax_rate'); ?></th>
                            <th class="text-center"><?= _l('project_boq_summary_item'); ?></th>
                            <th class="boq-internal-col"><?= _l('project_boq_internal_client_ref_no'); ?></th>
                            <th class="boq-internal-col"><?= _l('project_boq_internal_description'); ?></th>
                            <th class="boq-internal-col"><?= _l('project_boq_internal_unit'); ?></th>
                            <th class="text-right boq-internal-col"><?= _l('project_boq_internal_quantity'); ?></th>
                            <th class="text-right boq-internal-col"><?= _l('project_boq_internal_material_rate'); ?></th>
                            <th class="text-right boq-internal-col"><?= _l('project_boq_internal_labour_rate'); ?></th>
                            <th class="text-right boq-internal-col"><?= _l('project_boq_internal_supply_amount'); ?></th>
                            <th class="text-right boq-internal-col"><?= _l('project_boq_internal_itc_amount'); ?></th>
                            <th class="text-right"><?= _l('project_boq_purchased_qty'); ?></th>
                            <th class="text-right"><?= _l('project_boq_remaining_qty'); ?></th>
                            <?php if ($can_edit_boq && $can_view_items) { ?>
                            <th width="11%"><?= _l('options'); ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody id="project-boq-tbody">
                        <?php foreach ($boq_items as $row) {
                            $this->load->view('boq/admin/projects/tabs/_boq_row', [
                                'row'            => $row,
                                'can_edit_boq'   => $can_edit_boq,
                                'can_view_items' => $can_view_items,
                            ]);
                        } ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($boq_items) === 0) { ?>
            <p class="text-muted tw-mb-0" id="project-boq-empty"><?= _l('project_boq_no_lines'); ?></p>
            <?php } ?>
        </div>
    </div>

    <style>
    #project-boq-table.table > thead > tr > th {
        background-color: #2c3e50 !important;
        color: #ffffff !important;
        border-color: #1a252f !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 10px 8px;
        white-space: nowrap;
    }
    #project-boq-table thead th .selectpicker {
        background-color: #34495e;
        border: none;
        color: #ffffff;
        font-weight: 600;
    }
    #project-boq-tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    #project-boq-tbody tr:nth-child(odd) {
        background-color: #ffffff;
    }
    #project-boq-table .boq-internal-col {
        background-color: #eef3fb;
        border-left: 2px solid #b8d4f0;
    }
    #project-boq-table .boq-internal-col:first-child {
        border-left: none;
    }
    </style>

    <?php if (is_admin() && !empty($pending_requests)): ?>
    <div class="panel_s tw-mt-4">
        <div class="panel-heading"><?= _l('project_boq_pending_requests'); ?></div>
        <div class="panel-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th><?= _l('project_boq_item'); ?></th>
                        <th><?= _l('project_boq_request_type'); ?></th>
                        <th><?= _l('project_boq_requested_by'); ?></th>
                        <th><?= _l('project_boq_request_date'); ?></th>
                        <th><?= _l('options'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_requests as $req): ?>
                    <tr>
                        <td>
                            <?php if ($req['boq_item_id']): ?>
                                <?= e(get_boq_item_description($req['boq_item_id'])); ?>
                            <?php else: ?>
                                <?= _l('project_boq_new_item'); ?>
                                (<?= e(json_decode($req['new_data'], true)['item_id'] ?? ''); ?>)
                            <?php endif; ?>
                        </td>
                        <td><?= e(_l('project_boq_request_type_' . $req['request_type'])); ?></td>
                        <td><?= e(get_staff_full_name($req['requested_by'])); ?></td>
                        <td><?= e(_dt($req['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-success boq-approve-request"
                                    data-request-id="<?= e($req['id']); ?>"><?= _l('approve'); ?></button>
                            <button class="btn btn-sm btn-danger boq-reject-request"
                                    data-request-id="<?= e($req['id']); ?>"><?= _l('reject'); ?></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($can_edit_boq && $can_view_items) { ?>
<div class="modal fade" id="project_boq_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <?= form_open('#', ['id' => 'project-boq-modal-form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="project-boq-modal-title"><?= _l('project_boq_add_line'); ?></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="boq_id" id="project-boq-modal-id" value="">
                <div class="form-group">
                    <label for="project-boq-modal-item"><?= _l('project_boq_item'); ?></label>
                    <select id="project-boq-modal-item" name="item_id" class="selectpicker" data-width="100%"
                        data-live-search="true"
                        data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>" required>
                        <option value=""></option>
                        <?php foreach ($items_grouped as $group_id => $_items) { ?>
                        <optgroup label="<?= e($_items[0]['group_name'] ?? ''); ?>">
                            <?php foreach ($_items as $item) { ?>
                            <option value="<?= (int) $item['id']; ?>">
                                <?= e($item['description']); ?>
                            </option>
                            <?php } ?>
                        </optgroup>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group" id="project-boq-modal-planned-group">
                    <label for="project-boq-modal-planned"><?= _l('project_boq_planned_qty'); ?></label>
                    <input type="number" class="form-control" name="planned_qty" id="project-boq-modal-planned"
                        step="any" min="0" value="0" required>
                </div>
                <div class="form-group hide" id="project-boq-modal-planned-readonly-group">
                    <label for="project-boq-modal-planned-readonly"><?= _l('project_boq_planned_qty'); ?></label>
                    <input type="text" class="form-control" id="project-boq-modal-planned-readonly" readonly
                        tabindex="-1">
                </div>
                <div class="form-group hide" id="project-boq-modal-revised-group">
                    <label for="project-boq-modal-revised"><?= _l('project_boq_revised_qty'); ?></label>
                    <input type="number" class="form-control" name="revised_qty" id="project-boq-modal-revised"
                        step="any" min="0" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="submit" class="btn btn-primary" id="project-boq-modal-save"><?= _l('submit'); ?></button>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>
<?php } ?>

<?php if ($can_view_items) { ?>
<div class="modal fade" id="project_boq_request_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document" style="max-width:900px;">
        <?= form_open('#', ['id' => 'project-boq-request-form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="project-boq-request-modal-title"><?= _l('project_boq_request_edit'); ?></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="project_id" id="project-boq-request-project-id" value="<?= (int) $project->id; ?>">
                <input type="hidden" name="boq_item_id" id="project-boq-request-boq-id" value="">
                <input type="hidden" name="request_type" id="project-boq-request-type" value="add_item">

                <div id="project-boq-request-add-fields">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_internal_ref_no'); ?></label>
                                <input type="text" class="form-control" name="internal_ref_no" id="project-boq-request-internal-ref-no">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_client_ref_no'); ?></label>
                                <input type="text" class="form-control" name="client_ref_no" id="project-boq-request-client-ref-no">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_item'); ?> <span class="text-danger">*</span></label>
                                <select id="project-boq-request-item-id" name="item_id" class="selectpicker" data-width="100%"
                                    data-live-search="true" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>" required>
                                    <option value=""></option>
                                    <?php foreach ($items_grouped as $group_id => $_items) { ?>
                                    <optgroup label="<?= e($_items[0]['group_name'] ?? ''); ?>">
                                        <?php foreach ($_items as $item) { ?>
                                        <option value="<?= (int) $item['id']; ?>">
                                            <?= e($item['description']); ?>
                                        </option>
                                        <?php } ?>
                                    </optgroup>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= _l('project_boq_description'); ?></label>
                                <input type="text" class="form-control" name="boq_description" id="project-boq-request-description">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?= _l('project_boq_unit'); ?></label>
                                <input type="text" class="form-control" name="boq_unit" id="project-boq-request-unit">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><?= _l('project_boq_planned_qty'); ?> <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="order_quantity" id="project-boq-request-planned-qty"
                                    step="any" min="0" value="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_supply_rate'); ?></label>
                                <input type="number" class="form-control" name="supply_rate" id="project-boq-request-supply-rate" step="any" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_itc_rate'); ?></label>
                                <input type="number" class="form-control" name="itc_rate" id="project-boq-request-itc-rate" step="any" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_tax_rate'); ?></label>
                                <input type="number" class="form-control" name="tax_rate" id="project-boq-request-tax-rate" step="any" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_supply_amount'); ?></label>
                                <input type="number" class="form-control" name="supply_amount" id="project-boq-request-supply-amount" step="any" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_itc_amount'); ?></label>
                                <input type="number" class="form-control" name="itc_amount" id="project-boq-request-itc-amount" step="any" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_summary_item'); ?></label>
                                <select name="is_summary_item" id="project-boq-request-summary-item" class="selectpicker" data-width="100%">
                                    <option value="0"><?= _l('no'); ?></option>
                                    <option value="1"><?= _l('yes'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_internal_client_ref_no'); ?></label>
                                <input type="text" class="form-control" name="internal_client_ref_no" id="project-boq-request-int-client-ref">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_internal_description'); ?></label>
                                <input type="text" class="form-control" name="internal_description" id="project-boq-request-int-desc">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_internal_unit'); ?></label>
                                <input type="text" class="form-control" name="internal_unit" id="project-boq-request-int-unit">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_internal_quantity'); ?></label>
                                <input type="number" class="form-control" name="internal_quantity" id="project-boq-request-int-qty" step="any" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_internal_material_rate'); ?></label>
                                <input type="number" class="form-control" name="internal_material_rate" id="project-boq-request-int-mat-rate" step="any" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= _l('project_boq_internal_labour_rate'); ?></label>
                                <input type="number" class="form-control" name="internal_labour_rate" id="project-boq-request-int-lab-rate" step="any" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= _l('project_boq_internal_supply_amount'); ?></label>
                                <input type="number" class="form-control" name="internal_supply_amount" id="project-boq-request-int-supply-amt" step="any" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= _l('project_boq_internal_itc_amount'); ?></label>
                                <input type="number" class="form-control" name="internal_itc_amount" id="project-boq-request-int-itc-amt" step="any" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="project-boq-request-edit-fields" class="hide">
                    <div class="form-group">
                        <label><?= _l('project_boq_item'); ?></label>
                        <input type="text" class="form-control" id="project-boq-request-edit-item-name" readonly tabindex="-1">
                    </div>
                    <div class="form-group">
                        <label><?= _l('project_boq_planned_qty'); ?></label>
                        <input type="text" class="form-control" id="project-boq-request-edit-planned" readonly tabindex="-1">
                    </div>
                    <div class="form-group">
                        <label for="project-boq-request-edit-revised"><?= _l('project_boq_revised_qty'); ?></label>
                        <input type="number" class="form-control" name="order_quantity" id="project-boq-request-edit-revised"
                            step="any" min="0" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="submit" class="btn btn-primary" id="project-boq-request-submit"><?= _l('submit'); ?></button>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>
<?php } ?>

<script>
var projectBoqLang = <?= json_encode([
    'confirm_delete'      => _l('confirm_action_prompt'),
    'add_title'           => _l('project_boq_add_line'),
    'edit_title'          => _l('project_boq_edit_line'),
    'select_at_least_one' => _l('project_boq_select_at_least_one_line'),
    'review_notes'        => _l('project_boq_review_notes'),
    'request_edit'        => _l('project_boq_request_edit'),
    'request_add_item'    => _l('project_boq_request_add_item'),
]); ?>;
</script>
