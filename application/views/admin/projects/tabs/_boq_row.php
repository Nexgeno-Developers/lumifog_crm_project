<?php defined('BASEPATH') or exit('No direct script access allowed');

$row            = $row ?? [];
$can_edit_boq   = ! empty($can_edit_boq);
$can_view_items = ! empty($can_view_items);
$id             = (int) ($row['id'] ?? 0);
$planned        = (float) ($row['planned_qty'] ?? 0);
$revised        = isset($row['revised_qty']) ? (float) $row['revised_qty'] : $planned;
$purchased      = (float) ($row['purchased_qty'] ?? 0);
$remaining      = isset($row['remaining_qty']) ? (float) $row['remaining_qty'] : ($revised - $purchased);
?>
<tr data-boq-id="<?= e($id); ?>" data-purchased="<?= e($purchased); ?>"
    data-revised="<?= e($revised); ?>"
    data-item-id="<?= (int) ($row['item_id'] ?? 0); ?>"
    data-item-description="<?= e($row['item_description'] ?? ''); ?>">
    <?php if ($can_view_items) { ?>
    <td class="text-center project-boq-select-cell">
        <input type="checkbox" class="project-boq-select" aria-label="<?= e(_l('project_boq_select_rows')); ?>">
    </td>
    <?php } ?>
    <td class="project-boq-item-label">
        <?= e($row['item_description'] ?? ''); ?>
        <?php if (! empty($row['item_unit'])) { ?>
        <span class="text-muted hide"> <?= e($row['item_unit']); ?></span>
        <?php } ?>
    </td>
    <td class="text-right">
        <?= e($row['planned_qty_display'] ?? app_format_number($planned)); ?>
    </td>
    <td class="text-right">
        <?php if ($can_edit_boq && $can_view_items) { ?>
        <input type="number"
            class="form-control input-sm text-right project-boq-revised-input"
            step="any"
            min="0"
            value="<?= e($revised); ?>"
            data-boq-id="<?= e($id); ?>"
            aria-label="<?= _l('project_boq_revised_qty'); ?>">
        <?php } else { ?>
        <?= e($row['revised_qty_display'] ?? app_format_number($revised)); ?>
        <?php } ?>
    </td>
    <td class="text-right">
        <input type="text"
            class="form-control input-sm text-right"
            readonly
            tabindex="-1"
            value="<?= e($row['purchased_qty_display'] ?? app_format_number($purchased)); ?>">
    </td>
    <td class="text-right">
        <span class="project-boq-remaining-display"><?= e($row['remaining_qty_display'] ?? app_format_number($remaining)); ?></span>
    </td>
    <?php if ($can_edit_boq && $can_view_items) { ?>
    <td>
        <a href="#"
            class="btn btn-default btn-icon project-boq-edit"
            data-boq-id="<?= e($id); ?>"
            data-item-id="<?= (int) ($row['item_id'] ?? 0); ?>"
            data-planned="<?= e($planned); ?>"
            data-revised="<?= e($revised); ?>"
            data-toggle="modal"
            data-target="#project_boq_modal"
            title="<?= _l('edit'); ?>">
            <i class="fa-regular fa-pen-to-square"></i>
        </a>
        <a href="#"
            class="btn btn-danger btn-icon project-boq-delete"
            data-boq-id="<?= e($id); ?>"
            title="<?= _l('delete'); ?>">
            <i class="fa-regular fa-trash-can"></i>
        </a>
    </td>
    <?php } ?>
</tr>
