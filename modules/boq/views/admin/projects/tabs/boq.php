<?php defined('BASEPATH') or exit('No direct script access allowed');

$can_edit_boq      = isset($can_edit_boq) ? $can_edit_boq : false;
$can_view_items    = isset($can_view_items) ? $can_view_items : false;
$boq_items         = isset($boq_items) ? $boq_items : [];
$items_grouped     = isset($boq_items_grouped) ? $boq_items_grouped : [];
$can_approve_boq   = !empty($can_approve_boq) || is_boq_approver();
$is_project_member = !empty($is_project_member) || (isset($project) && $this->projects_model->is_member($project->id));
$can_revise_boq    = $can_edit_boq || $is_project_member;
$pending_revisions = $pending_revisions ?? [];

$CI = &get_instance();
if (!isset($CI->project_boq_model)) {
    $CI->load->model('boq/Boq_model', 'project_boq_model');
}
$project_restriction_val = method_exists($CI->project_boq_model, 'get_project_po_restriction_setting')
    ? $CI->project_boq_model->get_project_po_restriction_setting($project->id)
    : 'inherit';
$global_restriction_enabled = method_exists($CI->project_boq_model, 'is_po_restriction_enabled')
    ? $CI->project_boq_model->is_po_restriction_enabled(null)
    : true;
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
        <?php if ($can_view_items && ($can_edit_boq || is_admin() || staff_can('edit', 'projects'))) { ?>
        <button type="button" class="btn btn-info" id="project-boq-sync-pos" data-toggle="tooltip"
            data-placement="top" title="<?= _l('project_boq_sync_past_pos_tooltip'); ?>">
            <i class="fa fa-sync tw-mr-1"></i>
            <?= _l('project_boq_sync_past_pos'); ?>
        </button>
        <?php } ?>
        <?php
        $revised_count = 0;
        foreach ($boq_items as $bItem) {
            $bPlan = (float) ($bItem['planned_qty'] ?? 0);
            $bOrd  = (isset($bItem['order_quantity']) && (float) $bItem['order_quantity'] > 0) ? (float) $bItem['order_quantity'] : $bPlan;
            $bRev  = (float) ($bItem['_revised_for_display'] ?? $bItem['revised_qty'] ?? $bOrd);
            if (abs($bRev - $bOrd) > 0.0001) {
                $revised_count++;
            }
        }
        ?>
        <span id="project-boq-revised-summary" class="label label-warning" style="display: <?= $revised_count > 0 ? 'inline-flex' : 'none'; ?>; align-items: center; gap: 5px; font-size: 12px; padding: 6px 11px; border-radius: 4px; vertical-align: middle; background-color: #fef3c7; color: #b45309; border: 1px solid #fcd34d; font-weight: 600; margin-left: 6px;">
            <i class="fa-solid fa-arrows-rotate"></i>
            <span class="boq-revised-count-text">
                <?= sprintf($revised_count === 1 ? _l('project_boq_items_revised_summary') : _l('project_boq_items_revised_summary_plural'), $revised_count); ?>
            </span>
        </span>

        <?php if (is_admin() || staff_can('edit', 'projects')) { ?>
        <div class="pull-right tw-flex tw-items-center" style="margin-top: 5px; gap: 8px;">
            <label class="tw-mb-0 text-muted" style="font-weight: 500; font-size: 13px;" data-toggle="tooltip" data-placement="left" title="<?= _l('project_boq_po_restriction_tooltip'); ?>">
                <i class="fa-solid fa-shield-halved tw-mr-1 text-primary"></i><?= _l('project_boq_po_restriction'); ?>:
            </label>
            <select id="project_boq_project_restriction_select" class="selectpicker" data-width="auto" data-style="btn-default btn-xs">
                <option value="inherit" <?= $project_restriction_val === 'inherit' ? 'selected' : ''; ?>>
                    <?= _l('project_boq_restriction_inherit'); ?> (<?= $global_restriction_enabled ? _l('project_boq_restriction_enforced') : _l('project_boq_restriction_disabled'); ?>)
                </option>
                <option value="1" <?= $project_restriction_val === '1' ? 'selected' : ''; ?>>
                    <?= _l('project_boq_restriction_enforce_always'); ?>
                </option>
                <option value="0" <?= $project_restriction_val === '0' ? 'selected' : ''; ?>>
                    <?= _l('project_boq_restriction_exempt'); ?>
                </option>
            </select>
        </div>
        <?php } ?>
    </div>

    <div class="panel_s">
        <div class="panel-body">
            <div class="table-responsive project-boq-table-responsive" style="overflow-x:auto;">
                <table class="table table-bordered table-hover" id="project-boq-table" style="table-layout:fixed; width:3090px; min-width:3090px;">
                    <thead>
                        <tr>
                            <?php if ($can_view_items) { ?>
                            <th class="text-center col-boq-check" style="width:45px;">
                                <input type="checkbox" id="project-boq-check-all" aria-label="<?= e(_l('project_boq_select_rows')); ?>">
                            </th>
                            <?php } ?>
                            <th class="col-boq-internal-ref" style="width:115px;"><?= _l('project_boq_internal_ref_no'); ?></th>
                            <th class="col-boq-client-ref" style="width:115px;"><?= _l('project_boq_client_ref_no'); ?></th>
                            <th class="text-center col-boq-crm-item-id" style="width:90px;"><?= _l('project_boq_crm_item_id'); ?></th>
                            <th class="col-boq-item" style="width:220px;"><?= _l('project_boq_crm_item'); ?></th>
                            <th class="col-boq-desc" style="width:240px;"><?= _l('project_boq_description'); ?></th>
                            <th class="col-boq-unit" style="width:85px;"><?= _l('project_boq_unit'); ?></th>
                            <th class="text-right col-boq-order-qty" style="width:110px;"><?= _l('project_boq_order_quantity'); ?></th>
                            <th class="text-right col-boq-supply-rate" style="width:110px;"><?= _l('project_boq_supply_rate'); ?></th>
                            <th class="text-right col-boq-itc-rate" style="width:100px;"><?= _l('project_boq_itc_rate'); ?></th>
                            <th class="text-right col-boq-supply-amount" style="width:125px;"><?= _l('project_boq_supply_amount'); ?></th>
                            <th class="text-right col-boq-itc-amount" style="width:125px;"><?= _l('project_boq_itc_amount'); ?></th>
                            <th class="text-right col-boq-tax-rate" style="width:85px;"><?= _l('project_boq_tax_rate'); ?></th>
                            <th class="text-center col-boq-summary" style="width:95px;"><?= _l('project_boq_summary_item'); ?></th>
                            <th class="boq-internal-col col-boq-int-client-ref" style="width:115px;"><?= _l('project_boq_internal_client_ref_no'); ?></th>
                            <th class="boq-internal-col col-boq-int-desc" style="width:220px;"><?= _l('project_boq_internal_description'); ?></th>
                            <th class="boq-internal-col col-boq-int-unit" style="width:85px;"><?= _l('project_boq_internal_unit'); ?></th>
                            <th class="text-right boq-internal-col col-boq-int-qty" style="width:105px;"><?= _l('project_boq_internal_quantity'); ?></th>
                            <th class="text-right boq-internal-col col-boq-int-mat-rate" style="width:110px;"><?= _l('project_boq_internal_material_rate'); ?></th>
                            <th class="text-right boq-internal-col col-boq-int-lab-rate" style="width:110px;"><?= _l('project_boq_internal_labour_rate'); ?></th>
                            <th class="text-right boq-internal-col col-boq-int-supply-amt" style="width:125px;"><?= _l('project_boq_internal_supply_amount'); ?></th>
                            <th class="text-right boq-internal-col col-boq-int-itc-amt" style="width:125px;"><?= _l('project_boq_internal_itc_amount'); ?></th>
                            <th class="text-right col-boq-purchased" style="width:110px;"><?= _l('project_boq_purchased_qty'); ?></th>
                            <th class="text-right col-boq-remaining" style="width:110px;"><?= _l('project_boq_remaining_qty'); ?></th>
                            <th class="text-right col-boq-revised" style="width:110px;"><?= _l('project_boq_revised_qty'); ?></th>
                            <?php if ($can_view_items) { ?>
                            <th class="text-center col-boq-options" style="width:90px;"><?= _l('options'); ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody id="project-boq-tbody">
                        <?php foreach ($boq_items as $row) {
                            $this->load->view('boq/admin/projects/tabs/_boq_row', [
                                'row'               => $row,
                                'can_edit_boq'      => $can_edit_boq,
                                'can_view_items'    => $can_view_items,
                                'can_revise_boq'    => $can_revise_boq,
                                'pending_revisions' => $pending_revisions,
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

    <!-- Floating / Sticky Horizontal Scrollbar (Excel-like bottom bar) -->
    <div id="project-boq-floating-scroll" style="display: none; position: fixed; bottom: 0; z-index: 999; height: 16px; overflow-x: auto; overflow-y: hidden; background: #f8fafc; border-top: 1px solid #cbd5e1; box-shadow: 0 -4px 12px rgba(0,0,0,0.15);">
        <div id="project-boq-floating-scroll-inner" style="height: 1px; width: 3090px;"></div>
    </div>

    <style>
    /* Excel-style Floating Horizontal Scrollbar */
    #project-boq-floating-scroll,
    .project-boq-table-responsive {
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #f1f5f9;
    }
    #project-boq-floating-scroll::-webkit-scrollbar,
    .project-boq-table-responsive::-webkit-scrollbar {
        height: 14px !important;
    }
    #project-boq-floating-scroll::-webkit-scrollbar-track,
    .project-boq-table-responsive::-webkit-scrollbar-track {
        background: #f1f5f9 !important;
        border-radius: 4px;
    }
    #project-boq-floating-scroll::-webkit-scrollbar-thumb,
    .project-boq-table-responsive::-webkit-scrollbar-thumb {
        background: #94a3b8 !important;
        border-radius: 4px;
        border: 2px solid #f1f5f9 !important;
    }
    #project-boq-floating-scroll::-webkit-scrollbar-thumb:hover,
    .project-boq-table-responsive::-webkit-scrollbar-thumb:hover {
        background: #64748b !important;
    }
    #project-boq-table {
        table-layout: fixed !important;
        width: 3090px !important;
        min-width: 3090px !important;
        margin-bottom: 0 !important;
    }
    #project-boq-table th,
    #project-boq-table td {
        vertical-align: middle !important;
        overflow-wrap: break-word !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
    }
    #project-boq-table.table > thead > tr > th {
        background-color: #2c3e50 !important;
        color: #ffffff !important;
        border-color: #1a252f !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 10px 8px;
        white-space: normal !important;
        vertical-align: middle !important;
        line-height: 1.3 !important;
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
    #project-boq-table td {
        padding: 8px 10px !important;
        white-space: normal !important;
        font-size: 13px !important;
        line-height: 1.4 !important;
    }
    #project-boq-table td.text-right {
        white-space: nowrap !important;
    }
    #project-boq-table .project-boq-item-label {
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        line-height: 1.4 !important;
        font-weight: 500;
    }
    #project-boq-table .project-boq-desc-cell {
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        line-height: 1.4 !important;
    }
    #project-boq-table .project-boq-internal-desc-cell {
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        line-height: 1.4 !important;
    }
    #project-boq-table tr.boq-row-revised {
        background-color: #fffdf5 !important;
    }
    #project-boq-table tr.boq-row-revised:hover {
        background-color: #fef9c3 !important;
    }
    #project-boq-table tr.boq-row-revised > td:first-child {
        border-left: 4px solid #f59e0b !important;
    }
    #project-boq-table tr.boq-row-manual {
        background-color: #f0fdf4 !important;
    }
    #project-boq-table tr.boq-row-manual:hover {
        background-color: #dcfce7 !important;
    }
    #project-boq-table tr.boq-row-manual > td:first-child {
        border-left: 4px solid #10b981 !important;
    }
    #project-boq-table tr.boq-row-manual.boq-row-revised > td:first-child {
        border-left: 4px solid #f59e0b !important;
    }
    #project-boq-table td.boq-revised-cell-highlight {
        background-color: #fef9c3 !important;
    }
    .boq-revised-chip,
    .boq-revised-subbadge {
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        white-space: nowrap;
    }
    .boq-manual-chip {
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        white-space: nowrap;
    }
    #project-boq-table th.col-boq-revised,
    #project-boq-table td.project-boq-revised-cell {
        width: 110px !important;
        min-width: 110px !important;
        max-width: 110px !important;
        white-space: nowrap !important;
        box-sizing: border-box !important;
    }
    </style>

    <?php if (!empty($pending_requests)): ?>
    <div class="panel_s tw-mt-4">
        <div class="panel-heading" style="font-weight: 600; display: flex; align-items: center; justify-content: space-between;">
            <span><i class="fa-solid fa-clock-rotate-left tw-mr-1 text-warning"></i> <?= _l('project_boq_pending_requests'); ?> (<?= count($pending_requests); ?>)</span>
        </div>
        <div class="panel-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th><?= _l('project_boq_item'); ?></th>
                        <th><?= _l('project_boq_request_type'); ?></th>
                        <th><?= _l('project_boq_requested_details'); ?></th>
                        <th><?= _l('project_boq_requested_by'); ?></th>
                        <th><?= _l('project_boq_request_date'); ?></th>
                        <th class="text-center"><?= $can_approve_boq ? _l('options') : _l('status'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_requests as $req): 
                        $reqData = json_decode($req['new_data'] ?? '{}', true);
                    ?>
                    <tr>
                        <td>
                            <?php if ($req['boq_item_id']): ?>
                                <strong><?= e(get_boq_item_description($req['boq_item_id'])); ?></strong>
                            <?php else: ?>
                                <span class="label label-info"><?= _l('project_boq_new_item'); ?></span>
                                (Item #<?= e($reqData['item_id'] ?? ''); ?>)
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($req['request_type'] === 'change_qty'): ?>
                                <span class="label label-warning"><?= _l('project_boq_request_type_change_qty'); ?></span>
                            <?php else: ?>
                                <span class="label label-success"><?= _l('project_boq_request_type_add_item'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($req['request_type'] === 'change_qty' && $req['boq_item_id']): 
                                $boqItem = $this->project_boq_model->get($req['boq_item_id']);
                                $curQty = $boqItem ? (float)$boqItem->revised_qty : 0;
                                $reqQty = (float)($reqData['revised_qty'] ?? $reqData['order_quantity'] ?? 0);
                            ?>
                                <span>Current: <strong><?= app_format_number($curQty); ?></strong> &rarr; Requested: <strong class="text-primary"><?= app_format_number($reqQty); ?></strong></span>
                            <?php elseif ($req['request_type'] === 'add_item'): ?>
                                <span>Qty: <strong><?= app_format_number($reqData['order_quantity'] ?? 0); ?></strong></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(get_staff_full_name($req['requested_by'])); ?></td>
                        <td><?= e(_dt($req['created_at'])); ?></td>
                        <td class="text-center">
                            <?php if ($can_approve_boq): ?>
                            <button class="btn btn-xs btn-success boq-approve-request"
                                    data-request-id="<?= e($req['id']); ?>">
                                <i class="fa fa-check"></i> <?= _l('approve'); ?>
                            </button>
                            <button class="btn btn-xs btn-danger boq-reject-request"
                                    data-request-id="<?= e($req['id']); ?>">
                                <i class="fa fa-times"></i> <?= _l('reject'); ?>
                            </button>
                            <?php else: ?>
                            <span class="label label-warning"><?= _l('pending'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($can_revise_boq && $can_view_items) { ?>
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
                <div class="alert alert-info" id="project-boq-modal-approval-notice" style="margin-bottom:12px; font-size:12px; padding:8px 12px; display:none;">
                    <i class="fa fa-info-circle"></i> <?= _l('project_boq_revision_approval_notice'); ?>
                </div>
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
                    <!-- SECTION 1: CLIENT / BOQ FORMAT (Columns A - M) -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px 4px 14px; margin-bottom: 14px;">
                        <div style="font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fa-solid fa-table tw-mr-1 text-primary"></i> BOQ Item &amp; Client Pricing (Columns A – M)</span>
                            <span class="text-muted" style="font-size: 11px; font-weight: normal;">Matches Excel Columns</span>
                        </div>

                        <!-- Row 1: Internal Ref No (A), Client Ref No (B), CRM ITEM ID (C), CRM Item (D) -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?= _l('project_boq_internal_ref_no'); ?> <small class="text-muted">(Col A)</small></label>
                                    <input type="text" class="form-control" name="internal_ref_no" id="project-boq-request-internal-ref-no" placeholder="Internal Ref">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?= _l('project_boq_client_ref_no'); ?> <small class="text-muted">(Col B)</small></label>
                                    <input type="text" class="form-control" name="client_ref_no" id="project-boq-request-client-ref-no" placeholder="Client Ref">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>CRM ITEM ID <small class="text-muted">(Col C)</small></label>
                                    <input type="text" class="form-control" id="project-boq-request-crm-item-id" readonly placeholder="Auto" style="background:#e8f8f5; font-weight:700; color:#16a34a; text-align:center;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?= _l('project_boq_item'); ?> <small class="text-muted">(Col D)</small> <span class="text-danger">*</span></label>
                                    <select id="project-boq-request-item-id" name="item_id" class="selectpicker" data-width="100%"
                                        data-live-search="true" data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>" required>
                                        <option value=""></option>
                                        <?php foreach ($items_grouped as $group_id => $_items) { ?>
                                        <optgroup label="<?= e($_items[0]['group_name'] ?? ''); ?>">
                                            <?php foreach ($_items as $item) { ?>
                                            <option value="<?= (int) $item['id']; ?>"
                                                data-rate="<?= e($item['rate'] ?? ''); ?>"
                                                data-unit="<?= e($item['unit'] ?? ''); ?>"
                                                data-desc="<?= e($item['description'] ?? ''); ?>">
                                                <?= e($item['description']); ?>
                                            </option>
                                            <?php } ?>
                                        </optgroup>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: BOQ Description (E), BOQ Unit (F), Order Quantity (G) -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= _l('project_boq_description'); ?> <small class="text-muted">(Col E)</small></label>
                                    <input type="text" class="form-control" name="boq_description" id="project-boq-request-description" placeholder="Description of item">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?= _l('project_boq_unit'); ?> <small class="text-muted">(Col F)</small></label>
                                    <input type="text" class="form-control" name="boq_unit" id="project-boq-request-unit" placeholder="e.g. Nos, Mtr">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?= _l('project_boq_order_qty'); ?> <small class="text-muted">(Col G)</small> <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="order_quantity" id="project-boq-request-planned-qty"
                                        step="any" min="0" value="0" required placeholder="0">
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Rates and Auto Amounts (H, I, J, K) -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?= _l('project_boq_supply_rate'); ?> <small class="text-muted">(Col H)</small></label>
                                    <input type="number" class="form-control" name="supply_rate" id="project-boq-request-supply-rate" step="any" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?= _l('project_boq_supply_amount'); ?> <small class="text-muted">(Col J = G*H)</small></label>
                                    <input type="number" class="form-control" name="supply_amount" id="project-boq-request-supply-amount" step="any" min="0" placeholder="Auto" style="background:#f1f5f9; font-weight:600;">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?= _l('project_boq_itc_rate'); ?> <small class="text-muted">(Col I)</small></label>
                                    <input type="number" class="form-control" name="itc_rate" id="project-boq-request-itc-rate" step="any" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?= _l('project_boq_itc_amount'); ?> <small class="text-muted">(Col K = G*I)</small></label>
                                    <input type="number" class="form-control" name="itc_amount" id="project-boq-request-itc-amount" step="any" min="0" placeholder="Auto" style="background:#f1f5f9; font-weight:600;">
                                </div>
                            </div>
                        </div>

                        <!-- Row 4: TAX (L) and Summary Item (M) -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>TAX (18%) <small class="text-muted">(Col L = (J+K)*18%)</small></label>
                                    <input type="number" class="form-control" name="tax_rate" id="project-boq-request-tax-rate" step="any" min="0" placeholder="Auto: (Supply + ITC) * 18%" style="background:#f1f5f9; font-weight:600;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= _l('project_boq_summary_item'); ?> <small class="text-muted">(Col M)</small></label>
                                    <select name="is_summary_item" id="project-boq-request-summary-item" class="selectpicker" data-width="100%">
                                        <option value="0"><?= _l('no'); ?></option>
                                        <option value="1"><?= _l('yes'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: INTERNAL ESTIMATES (Columns N - U) -->
                    <div style="background: #fefce8; border: 1px solid #fef08a; border-radius: 6px; padding: 12px 14px 4px 14px; margin-bottom: 6px;">
                        <div style="font-weight: 700; font-size: 13px; color: #854d0e; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fa-solid fa-lock tw-mr-1" style="color: #ca8a04;"></i> Internal Estimates (Columns N – U)</span>
                            <span class="text-muted" style="font-size: 11px; font-weight: normal; color: #a16207;">Internal Only</span>
                        </div>

                        <!-- Row 5: (internal)Client Ref (N), (internal)Description (O), (internal)Unit (P) -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?= _l('project_boq_internal_client_ref_no'); ?> <small class="text-muted">(Col N)</small></label>
                                    <input type="text" class="form-control" name="internal_client_ref_no" id="project-boq-request-int-client-ref" placeholder="Internal Client Ref">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label><?= _l('project_boq_internal_description'); ?> <small class="text-muted">(Col O)</small></label>
                                    <input type="text" class="form-control" name="internal_description" id="project-boq-request-int-desc" placeholder="Internal Description">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><?= _l('project_boq_internal_unit'); ?> <small class="text-muted">(Col P)</small></label>
                                    <input type="text" class="form-control" name="internal_unit" id="project-boq-request-int-unit" placeholder="Unit">
                                </div>
                            </div>
                        </div>

                        <!-- Row 6: (internal)Quantity (Q), (internal)Material Rate (R), (internal)Labour Rate (S) -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?= _l('project_boq_internal_quantity'); ?> <small class="text-muted">(Col Q)</small></label>
                                    <input type="number" class="form-control" name="internal_quantity" id="project-boq-request-int-qty" step="any" min="0" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?= _l('project_boq_internal_material_rate'); ?> <small class="text-muted">(Col R)</small></label>
                                    <input type="number" class="form-control" name="internal_material_rate" id="project-boq-request-int-mat-rate" step="any" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><?= _l('project_boq_internal_labour_rate'); ?> <small class="text-muted">(Col S)</small></label>
                                    <input type="number" class="form-control" name="internal_labour_rate" id="project-boq-request-int-lab-rate" step="any" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <!-- Row 7: (internal)Supply Amount (T = Q*R), (internal)ITC Amount (U = Q*S) -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= _l('project_boq_internal_supply_amount'); ?> <small class="text-muted">(Col T = Q*R)</small></label>
                                    <input type="number" class="form-control" name="internal_supply_amount" id="project-boq-request-int-supply-amt" step="any" min="0" placeholder="Auto" style="background:#fef9c3; font-weight:600;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= _l('project_boq_internal_itc_amount'); ?> <small class="text-muted">(Col U = Q*S)</small></label>
                                    <input type="number" class="form-control" name="internal_itc_amount" id="project-boq-request-int-itc-amt" step="any" min="0" placeholder="Auto" style="background:#fef9c3; font-weight:600;">
                                </div>
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

<div class="modal fade" id="project_boq_import_progress_modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document" style="max-width: 480px; margin-top: 15vh;">
        <div class="modal-content" style="border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
            <div class="modal-header" style="border-bottom: 1px solid #eef2f5; padding: 15px 20px;">
                <h4 class="modal-title" style="font-weight: 600; font-size: 16px; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-cloud-arrow-up text-primary"></i>
                    <span><?= _l('project_boq_import_csv'); ?></span>
                </h4>
            </div>
            <div class="modal-body" style="padding: 24px 20px;">
                <div class="text-center" style="margin-bottom: 16px;">
                    <div id="project-boq-import-status" style="font-size: 14px; font-weight: 500; color: #333; margin-bottom: 6px;">
                        Uploading file (0%)...
                    </div>
                    <div id="project-boq-import-filename" class="text-muted" style="font-size: 12px; word-break: break-all;">
                    </div>
                </div>
                <div class="progress" style="height: 22px; border-radius: 11px; background-color: #f0f2f5; margin-bottom: 12px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
                    <div id="project-boq-import-progress-bar"
                         class="progress-bar progress-bar-primary progress-bar-striped active"
                         role="progressbar"
                         aria-valuenow="0"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         style="width: 0%; font-size: 12px; line-height: 22px; font-weight: 600; transition: width 0.3s ease;">
                        0%
                    </div>
                </div>
                <p class="text-center text-muted" style="font-size: 12px; margin-bottom: 0;">
                    <i class="fa-regular fa-clock tw-mr-1"></i>
                    <span id="project-boq-import-hint">Please keep this window open while the file is processed.</span>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="project_boq_sync_result_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <i class="fa fa-sync text-primary tw-mr-1"></i>
                    <?= _l('project_boq_sync_past_pos'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <div id="project-boq-sync-summary-alert" class="alert alert-success"></div>
                <div id="project-boq-sync-unmatched-wrapper" style="display:none;">
                    <div class="alert alert-warning">
                        <strong><i class="fa fa-exclamation-triangle tw-mr-1"></i> <?= _l('project_boq_sync_unmatched_notice'); ?></strong>
                        <p class="tw-mb-0"><?= _l('project_boq_sync_unmatched_desc'); ?></p>
                    </div>
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-bordered table-striped" id="project-boq-sync-unmatched-table">
                            <thead>
                                <tr>
                                    <th style="width: 25%;"><?= _l('project_boq_sync_po_number'); ?></th>
                                    <th><?= _l('project_boq_sync_item_name'); ?></th>
                                    <th class="text-right" style="width: 20%;"><?= _l('project_boq_sync_quantity'); ?></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?= _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
var projectBoqCanApprove = <?= $can_approve_boq ? 'true' : 'false'; ?>;
var projectBoqLang = <?= json_encode([
    'confirm_delete'              => _l('confirm_action_prompt'),
    'add_title'                   => _l('project_boq_add_line'),
    'edit_title'                  => _l('project_boq_edit_line'),
    'select_at_least_one'         => _l('project_boq_select_at_least_one_line'),
    'review_notes'                => _l('project_boq_review_notes'),
    'request_edit'                => _l('project_boq_request_edit'),
    'request_add_item'            => _l('project_boq_request_add_item'),
    'revised_badge'               => _l('project_boq_revised_badge'),
    'revised_tooltip'             => _l('project_boq_revised_tooltip'),
    'items_revised_summary'        => _l('project_boq_items_revised_summary'),
    'items_revised_summary_plural' => _l('project_boq_items_revised_summary_plural'),
    'pending'                     => _l('pending'),
    'pending_approval_badge'      => _l('project_boq_pending_approval_badge'),
    'sync_confirm'                => _l('project_boq_sync_confirm'),
    'syncing'                     => _l('project_boq_syncing'),
]); ?>;
</script>
