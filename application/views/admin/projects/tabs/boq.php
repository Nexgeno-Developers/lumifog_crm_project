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
    </div>

    <div class="panel_s">
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="project-boq-table">
                    <thead>
                        <tr>
                            <th><?= _l('project_boq_item'); ?></th>
                            <th class="text-right" width="14%"><?= _l('project_boq_planned_qty'); ?></th>
                            <th class="text-right" width="14%"><?= _l('project_boq_purchased_qty'); ?></th>
                            <th class="text-right" width="14%"><?= _l('project_boq_remaining_qty'); ?></th>
                            <?php if ($can_edit_boq && $can_view_items) { ?>
                            <th width="12%"><?= _l('options'); ?></th>
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
                <div class="form-group">
                    <label for="project-boq-modal-planned"><?= _l('project_boq_planned_qty'); ?></label>
                    <input type="number" class="form-control" name="planned_qty" id="project-boq-modal-planned"
                        step="any" min="0" value="0" required>
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

<script>
var projectBoqLang = <?= json_encode([
    'confirm_delete' => _l('confirm_action_prompt'),
    'add_title'      => _l('project_boq_add_line'),
    'edit_title'     => _l('project_boq_edit_line'),
]); ?>;
</script>
