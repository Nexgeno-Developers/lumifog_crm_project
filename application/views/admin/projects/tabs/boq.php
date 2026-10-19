<?php defined('BASEPATH') or exit('No direct script access allowed');

$can_edit_boq   = isset($can_edit_boq) ? $can_edit_boq : false;
$can_view_items = isset($can_view_items) ? $can_view_items : false;
$boq_items      = isset($boq_items) ? $boq_items : [];
$items_grouped  = isset($boq_items_grouped) ? $boq_items_grouped : [];
?>
<div id="project-boq-wrapper" class="project-boq-tab" data-project-id="<?= (int) $project->id; ?>"
    data-can-edit="<?= $can_edit_boq ? '1' : '0'; ?>"
    data-can-view-items="<?= $can_view_items ? '1' : '0'; ?>">
    <?php if (! $can_view_items) { ?>
    <div class="alert alert-warning">
        <?= _l('project_boq_items_permission'); ?>
    </div>
    <?php } ?>

    <div class="_buttons tw-mb-4">
        <?php if ($can_edit_boq && $can_view_items) { ?>
        <button type="button" class="btn btn-primary" id="project-boq-open-add" data-toggle="modal"
            data-target="#project_boq_modal">
            <i class="fa-regular fa-plus tw-mr-1"></i>
            <?= _l('project_boq_add_line'); ?>
        </button>
        <?php } ?>
        <?php if ($can_view_items) { ?>
        <button type="button" class="btn btn-default" id="project-boq-import-csv">
            <i class="fa-regular fa-file-excel tw-mr-1"></i>
            <?= _l('project_boq_import_csv'); ?>
        </button>
        <input type="file" id="project-boq-csv-file" accept=".csv, .xlsx, .xls" style="display:none">
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
    </div>

    <div class="panel_s">
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="project-boq-table">
                    <thead>
                        <tr>
                            <?php if ($can_view_items) { ?>
                            <th class="text-center" style="width:40px;">
                                <input type="checkbox" id="project-boq-check-all" aria-label="<?= e(_l('project_boq_select_rows')); ?>">
                            </th>
                            <?php } ?>
                            <th><?= _l('project_boq_item'); ?></th>
                            <th class="text-right" width="11%"><?= _l('project_boq_planned_qty'); ?></th>
                            <th class="text-right" width="11%"><?= _l('project_boq_revised_qty'); ?></th>
                            <th class="text-right" width="11%"><?= _l('project_boq_purchased_qty'); ?></th>
                            <th class="text-right" width="11%"><?= _l('project_boq_remaining_qty'); ?></th>
                            <?php if ($can_edit_boq && $can_view_items) { ?>
                            <th width="11%"><?= _l('options'); ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody id="project-boq-tbody">
                        <?php foreach ($boq_items as $row) {
                            $this->load->view('admin/projects/tabs/_boq_row', [
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

<style>
#project-boq-table th,
#project-boq-table td {
    vertical-align: middle !important;
    overflow-wrap: break-word !important;
    word-wrap: break-word !important;
    word-break: break-word !important;
}
#project-boq-table .project-boq-item-label {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    line-height: 1.4 !important;
    font-weight: 500;
}
</style>

<script>
var projectBoqLang = <?= json_encode([
    'confirm_delete' => _l('confirm_action_prompt'),
    'add_title'      => _l('project_boq_add_line'),
    'edit_title'     => _l('project_boq_edit_line'),
    'select_at_least_one' => _l('project_boq_select_at_least_one_line'),
]); ?>;
</script>
